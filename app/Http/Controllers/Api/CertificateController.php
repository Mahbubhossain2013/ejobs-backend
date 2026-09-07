<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\SkillEnrollment;
use App\Services\CertificateGeneratorService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CertificateController extends Controller
{
    public function __construct(
        protected CertificateGeneratorService $certService
    ) {}

    public function myCertificates(Request $request)
    {
        $user = Auth::user();
        $type = $request->get('type');

        $certificates = $this->certService->getUserCertificates($user, $type)
            ->with('template')
            ->paginate(12);

        return response()->json([
            'status' => true,
            'data' => $certificates,
        ]);
    }

    public function generate(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'enrollment_id' => 'required|integer|exists:skill_enrollments,id',
            'template_id' => 'nullable|integer|exists:certificate_templates,id',
        ]);

        $enrollment = SkillEnrollment::where('user_id', $user->id)->findOrFail($validated['enrollment_id']);

        if (!$user->hasFeature('certificate_generation')) {
            return response()->json([
                'status' => false,
                'message' => 'Certificate generation requires a subscription.',
                'feature_key' => 'certificate_generation',
            ], 403);
        }

        try {
            $certificate = $this->certService->generateFromEnrollment(
                $user,
                $enrollment,
                $validated['template_id'] ?? null
            );

            return response()->json([
                'status' => true,
                'message' => 'Certificate generated successfully',
                'data' => [
                    'id' => $certificate->id,
                    'certificate_number' => $certificate->certificate_number,
                    'recipient_name' => $certificate->recipient_name,
                    'course_title' => $certificate->course_title,
                    'issued_at' => $certificate->issued_at,
                    'expires_at' => $certificate->expires_at,
                    'type' => $certificate->type,
                    'score' => $certificate->score,
                    'download_url' => "/api/certificates/{$certificate->id}/download",
                ],
            ], 201);
        } catch (\Exception $e) {
            Log::error("Certificate generation failed", [
                'user_id' => $user->id,
                'enrollment_id' => $validated['enrollment_id'],
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to generate certificate: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function download(int $id)
    {
        $user = Auth::user();
        $certificate = Certificate::where('user_id', $user->id)->findOrFail($id);

        $filePath = $this->certService->downloadCertificate($certificate);
        if (!$filePath) {
            return response()->json([
                'status' => false,
                'message' => 'Certificate file not found',
            ], 404);
        }

        return response()->download($filePath, "{$certificate->certificate_number}.pdf", [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$certificate->certificate_number}.pdf\"",
        ]);
    }

    public function downloadByNumber(string $certificateNumber)
    {
        $certificate = Certificate::where('certificate_number', $certificateNumber)->firstOrFail();

        $filePath = $this->certService->downloadCertificate($certificate);
        if (!$filePath) {
            return response()->json([
                'status' => false,
                'message' => 'Certificate file not found',
            ], 404);
        }

        return response()->download($filePath, "{$certificate->certificate_number}.pdf", [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => "inline; filename=\"{$certificate->certificate_number}.pdf\"",
        ]);
    }

    public function verify(string $certificateNumber)
    {
        $result = $this->certService->verifyCertificate($certificateNumber);

        return response()->json([
            'status' => true,
            'data' => $result,
        ]);
    }

    public function templates()
    {
        $templates = CertificateTemplate::active()->get();

        return response()->json([
            'status' => true,
            'data' => $templates,
        ]);
    }

    public function show(int $id)
    {
        $user = Auth::user();
        $certificate = Certificate::where('user_id', $user->id)
            ->with('template')
            ->findOrFail($id);

        return response()->json([
            'status' => true,
            'data' => $certificate,
        ]);
    }

    public function destroy(int $id)
    {
        $user = Auth::user();
        $certificate = Certificate::where('user_id', $user->id)->findOrFail($id);

        if ($certificate->file_path && Storage::exists($certificate->file_path)) {
            Storage::delete($certificate->file_path);
        }

        $certificate->delete();

        return response()->json([
            'status' => true,
            'message' => 'Certificate deleted',
        ]);
    }
}
