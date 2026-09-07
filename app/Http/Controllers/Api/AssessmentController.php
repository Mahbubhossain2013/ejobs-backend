<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SkillAssessment;
use App\Models\SkillAssessmentAttempt;
use App\Models\SkillAssessmentQuestion;
use App\Models\SkillEnrollment;
use App\Services\QuizEngineService;
use App\Services\CertificateGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AssessmentController extends Controller
{
    public function __construct(
        protected QuizEngineService $quizEngine,
        protected CertificateGeneratorService $certService
    ) {}

    public function index(Request $request)
    {
        $query = SkillAssessment::where('is_active', true)->with('course');

        if ($request->has('course_id')) {
            $query->where('course_id', $request->course_id);
        }

        $assessments = $query->orderByDesc('created_at')->paginate(12);

        return response()->json([
            'status' => true,
            'data' => $assessments,
        ]);
    }

    public function show(int $id)
    {
        $assessment = SkillAssessment::with(['course', 'questions' => function ($q) {
            $q->orderBy('order');
        }])->findOrFail($id);

        $user = Auth::user();
        $canAttempt = null;
        $attemptHistory = null;

        if ($user) {
            $canAttempt = $this->quizEngine->canAttempt($user, $assessment);
            $attemptHistory = $this->quizEngine->getUserAttemptHistory($user, $assessment)
                ->map(fn($a) => [
                    'id' => $a->id,
                    'status' => $a->status,
                    'score' => $a->score,
                    'is_passed' => $a->is_passed,
                    'created_at' => $a->created_at->toISOString(),
                ]);
        }

        $questionsForDisplay = $assessment->questions->map(fn($q) => [
            'id' => $q->id,
            'question' => $q->question,
            'type' => $q->type,
            'options' => $q->options,
            'points' => $q->points,
            'order' => $q->order,
        ]);

        return response()->json([
            'status' => true,
            'data' => [
                'id' => $assessment->id,
                'title' => $assessment->title,
                'description' => $assessment->description,
                'passing_score' => $assessment->passing_score,
                'time_limit_minutes' => $assessment->time_limit_minutes,
                'max_attempts' => $assessment->max_attempts,
                'course' => $assessment->course,
                'questions' => $questionsForDisplay,
                'question_count' => $assessment->question_count,
            ],
            'can_attempt' => $canAttempt,
            'attempt_history' => $attemptHistory,
        ]);
    }

    public function start(Request $request, int $assessmentId)
    {
        $user = Auth::user();
        $assessment = SkillAssessment::findOrFail($assessmentId);

        if (!$assessment->is_active) {
            return response()->json([
                'status' => false,
                'message' => 'Assessment is not available',
            ], 400);
        }

        try {
            $attempt = $this->quizEngine->startAttempt($user, $assessment);

            return response()->json([
                'status' => true,
                'message' => 'Assessment started',
                'data' => [
                    'attempt_id' => $attempt->id,
                    'assessment_id' => $assessment->id,
                    'time_limit_minutes' => $assessment->time_limit_minutes,
                    'total_questions' => $attempt->total_points,
                    'started_at' => $attempt->started_at->toISOString(),
                    'remaining_time_seconds' => $attempt->remaining_time_seconds,
                ],
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function submitAnswer(Request $request, int $attemptId)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'question_id' => 'required|integer|exists:skill_assessment_questions,id',
            'answer' => 'nullable|string|max:1000',
        ]);

        $attempt = SkillAssessmentAttempt::where('user_id', $user->id)->findOrFail($attemptId);

        try {
            $answer = $this->quizEngine->submitAnswer(
                $attempt,
                $validated['question_id'],
                $validated['answer']
            );

            return response()->json([
                'status' => true,
                'data' => [
                    'answer_id' => $answer->id,
                    'is_correct' => $answer->is_correct,
                    'points_earned' => $answer->points_earned,
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function submitAll(Request $request, int $attemptId)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'answers' => 'required|array',
            'answers.*' => 'nullable|string|max:1000',
        ]);

        $attempt = SkillAssessmentAttempt::where('user_id', $user->id)->findOrFail($attemptId);

        try {
            $attempt = $this->quizEngine->submitAllAnswers($attempt, $validated['answers']);

            $response = [
                'status' => true,
                'message' => 'Assessment completed',
                'data' => $this->quizEngine->getAttemptResult($attempt),
            ];

            if ($attempt->is_passed && $user->hasFeature('certificate_generation')) {
                try {
                    $certificate = $this->certService->generateFromAssessment($user, $attempt);
                    $response['certificate'] = [
                        'id' => $certificate->id,
                        'certificate_number' => $certificate->certificate_number,
                        'download_url' => "/api/certificates/{$certificate->id}/download",
                    ];
                } catch (\Exception $e) {
                    Log::warning("Auto-certificate generation failed: " . $e->getMessage());
                    $response['certificate_notice'] = 'Certificate generation is temporarily unavailable. You can download it later from your certificates.';
                }
            }

            return response()->json($response);
        } catch (\Exception $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    public function getAttemptResult(Request $request, int $attemptId)
    {
        $user = Auth::user();
        $attempt = SkillAssessmentAttempt::where('user_id', $user->id)->findOrFail($attemptId);

        if ($attempt->status === 'in_progress') {
            return response()->json([
                'status' => false,
                'message' => 'Attempt is still in progress',
            ], 400);
        }

        return response()->json([
            'status' => true,
            'data' => $this->quizEngine->getAttemptResult($attempt),
        ]);
    }

    public function myAttempts(Request $request, int $assessmentId)
    {
        $user = Auth::user();
        $assessment = SkillAssessment::findOrFail($assessmentId);

        $attempts = $this->quizEngine->getUserAttemptHistory($user, $assessment);

        return response()->json([
            'status' => true,
            'data' => $attempts->map(fn($a) => [
                'id' => $a->id,
                'status' => $a->status,
                'score' => $a->score,
                'total_points' => $a->total_points,
                'is_passed' => $a->is_passed,
                'time_spent_seconds' => $a->time_spent_seconds,
                'started_at' => $a->started_at->toISOString(),
                'completed_at' => $a->completed_at?->toISOString(),
            ]),
        ]);
    }

    public function storeQuestion(Request $request, int $assessmentId)
    {
        $validated = $request->validate([
            'question' => 'required|string|max:2000',
            'type' => 'required|in:multiple_choice,true_false,short_answer',
            'options' => 'nullable|array|min:2',
            'options.*' => 'required|string',
            'correct_answer' => 'required|string|max:20480',
            'points' => 'nullable|integer|min:1|max:100',
            'order' => 'nullable|integer|min:0',
        ]);

        $assessment = SkillAssessment::findOrFail($assessmentId);

        $question = SkillAssessmentQuestion::create([
            'assessment_id' => $assessment->id,
            'question' => $validated['question'],
            'type' => $validated['type'],
            'options' => $validated['options'] ?? null,
            'correct_answer' => $validated['correct_answer'],
            'points' => $validated['points'] ?? 1,
            'order' => $validated['order'] ?? 0,
        ]);

        return response()->json([
            'status' => true,
            'message' => 'Question added',
            'data' => $question,
        ], 201);
    }

    public function updateQuestion(Request $request, int $questionId)
    {
        $validated = $request->validate([
            'question' => 'sometimes|string|max:2000',
            'type' => 'sometimes|in:multiple_choice,true_false,short_answer',
            'options' => 'nullable|array',
            'options.*' => 'required|string',
            'correct_answer' => 'sometimes|string|max:20480',
            'points' => 'sometimes|integer|min:1|max:100',
            'order' => 'sometimes|integer|min:0',
        ]);

        $question = SkillAssessmentQuestion::findOrFail($questionId);
        $question->update($validated);

        return response()->json([
            'status' => true,
            'message' => 'Question updated',
            'data' => $question,
        ]);
    }

    public function deleteQuestion(int $questionId)
    {
        $question = SkillAssessmentQuestion::findOrFail($questionId);
        $question->delete();

        return response()->json([
            'status' => true,
            'message' => 'Question deleted',
        ]);
    }
}
