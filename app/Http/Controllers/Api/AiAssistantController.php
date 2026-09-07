<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\Ai\AiManagerService;
use App\Models\SupportTicket;
use App\Models\AdminTask;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class AiAssistantController extends Controller
{
    public function chat(Request $request)
    {
        $request->validate([
            'message' => 'required|string|max:5000',
            'lang' => 'nullable|string|in:en,bn',
        ]);

        $userInput = $request->message;
        $lang = $request->input('lang', 'en');
        $isBn = $lang === 'bn';
        $normalizedInput = strtolower($userInput);
        
        // Get the authenticated user via sanctum
        $user = auth('sanctum')->user();

        // Guard candidate user check for AI Career Assistant feature
        if ($user && ($user->role === 'candidate' || $user->hasRole('candidate'))) {
            if (!$user->hasFeature('ai_career_tools')) {
                return response()->json([
                    'status' => false,
                    'message' => 'AI Career Assistant is only available on Premium and Pro plans. Please upgrade your plan to unlock AI tools!'
                ], 403);
            }
        }

        // 1. Check for Ticketing Trigger (Human / Support)
        if (Str::contains($normalizedInput, ['human', 'support', 'help'])) {
            try {
                $ticket = DB::transaction(function () use ($user) {
                    return SupportTicket::create([
                        'user_id' => $user ? $user->id : null,
                        'subject' => "Help request from AI Chat" . ($user ? " (User: {$user->name})" : " (Guest)"),
                    ]);
                });

                $msg = $user 
                    ? "I've created a support ticket (#{$ticket->id}) for you. Our team will contact you at {$user->email}."
                    : "I've opened a guest support ticket (#{$ticket->id}). Please provide your email address so a human can reach out.";

                return response()->json([
                    'status' => true,
                    'response' => $msg,
                    'action_status' => 'ticket_created'
                ]);

            } catch (\Exception $e) {
                Log::error('AI Chat Ticket Creation Failed: ' . $e->getMessage());
                return response()->json(['response' => "Sorry, I couldn't create a ticket automatically. Please try again."], 500);
            }
        }

        try {
            // 2. Get AI Provider Configuration
            $aiProvider = AiManagerService::getProvider('support');
            $instruction = $aiProvider->getConfig()->system_instruction ?? 'You are a helpful assistant.';

            // Load website knowledgebase
            $kbPath = app_path('Services/Ai/knowledgebase.json');
            $kbContent = '';
            if (file_exists($kbPath)) {
                $kbContent = file_get_contents($kbPath);
            }

            // 3. Build Prompt & Get Response
            $shortReplyDirective = $isBn
                ? 'প্রতিটি উত্তর ক্ষুদ্র ও দ্রুত রাখতে দাও। SMS বা চ্যাটের মতো সংক্ষিপ্ত উত্তর দিন, সাধারণত 2-4 লাইনের মধ্যে রাখুন। অপ্রাসঙ্গিক তথ্য এড়িয়ে চলুন।'
                : 'Keep every reply short and concise like an SMS or chat message. Aim for 1-3 sentences unless the user explicitly asks for detail. Avoid unnecessary filler.';

            $noThinking = $isBn
                ? 'OUTPUT শুধু চূড়ান্ত উত্তর দাও। কখনো chain-of-thought, reasoning, analysis, draft, constraint checklist, confidence score, বা internal thinking output কোরো না। Bullet-point reasoning লিখো না। সরাসরি উত্তর দাও।'
                : 'Output ONLY the final answer. NEVER output chain-of-thought, reasoning, analysis, drafts, constraint checklists, confidence scores, self-correction, or internal thinking. No bullet-point reasoning before the answer. Just give the clean answer directly.';

            // Build context injection for the user message (no structural labels)
            $contextParts = [];
            if ($kbContent) {
                $contextParts[] = "Knowledge Base context: {$kbContent}";
            }
            $contextBlock = $contextParts ? implode("\n\n", $contextParts) . "\n\n" : '';

            // System message: instruction + no-thinking + short reply directive (sent as system role)
            $systemMessage = "{$instruction}\n{$noThinking}\n{$shortReplyDirective}";

            // User message: context + actual question (no labels like "User Question:")
            $userMessage = $contextBlock . $userInput;

            $aiResponse = $aiProvider->generateResponse($userMessage, [], $systemMessage);

            $normalizedResponse = strtolower($aiResponse);

            // 4. Handle AI failure or "I don't know" response (Escalation to Admin)
            if (!$aiResponse || Str::contains($normalizedResponse, ["i don't know", "i am not sure", "cannot answer"])) {
                
                DB::transaction(function () use ($user, $userInput) {
                    AdminTask::create([
                        'title' => "Unresolved Query from " . ($user ? $user->name : "Guest"),
                        'description' => "Asked: {$userInput}",
                        'user_id' => $user ? $user->id : null
                    ]);
                });

                return response()->json([
                    'status' => true,
                    'response' => "I'm not quite sure about that. I've alerted our admin team to review your question and help you out!"
                ]);
            }

            return response()->json([
                'status' => true,
                'response' => $aiResponse
            ]);

        } catch (\Exception $e) {
            Log::error('AI Assistant Error: ' . $e->getMessage());
            return response()->json(['response' => "Technical error occurred. Please try again later."], 500);
        }
    }
}