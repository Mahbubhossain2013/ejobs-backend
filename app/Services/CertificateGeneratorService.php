<?php

namespace App\Services;

use App\Models\Certificate;
use App\Models\CertificateTemplate;
use App\Models\SkillAssessmentAttempt;
use App\Models\SkillEnrollment;
use App\Models\User;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CertificateGeneratorService
{
    public function generateFromEnrollment(
        User $user,
        SkillEnrollment $enrollment,
        ?int $templateId = null
    ): Certificate {
        if ($enrollment->status !== 'completed') {
            throw new \Exception("Course must be completed before generating a certificate.");
        }

        $existing = Certificate::where('user_id', $user->id)
            ->where('enrollment_id', $enrollment->id)
            ->where('type', 'course_completion')
            ->first();

        if ($existing && $existing->file_path && Storage::exists($existing->file_path)) {
            return $existing;
        }

        $template = $this->resolveTemplate($templateId);
        $course = $enrollment->course;

        $certificate = Certificate::create([
            'certificate_number' => Certificate::generateNumber(),
            'user_id' => $user->id,
            'template_id' => $template->id,
            'enrollment_id' => $enrollment->id,
            'type' => 'course_completion',
            'recipient_name' => $user->name,
            'course_title' => $course->title,
            'description' => "This certifies that {$user->name} has successfully completed the course \"{$course->title}\" with a completion rate of {$enrollment->progress}%.",
            'score' => $enrollment->progress,
            'issued_at' => now()->format('F j, Y'),
            'expires_at' => now()->addYears(1)->format('Y-m-d'),
            'metadata' => [
                'course_id' => $course->id,
                'category' => $course->category,
                'difficulty' => $course->difficulty,
                'duration_hours' => $course->duration_hours,
                'instructor' => $course->instructor_name,
            ],
        ]);

        try {
            $filePath = $this->renderPdfCertificate($certificate, $template);
            $certificate->update([
                'file_path' => $filePath,
                'file_format' => 'pdf',
                'generated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Certificate PDF generation failed for {$certificate->certificate_number}: " . $e->getMessage());
        }

        return $certificate->fresh();
    }

    public function generateFromAssessment(
        User $user,
        SkillAssessmentAttempt $attempt,
        ?int $templateId = null
    ): Certificate {
        if ($attempt->status !== 'completed') {
            throw new \Exception("Assessment must be completed before generating a certificate.");
        }

        if (!$attempt->is_passed) {
            throw new \Exception("Assessment must be passed to generate a certificate.");
        }

        $existing = Certificate::where('user_id', $user->id)
            ->where('attempt_id', $attempt->id)
            ->where('type', 'assessment_pass')
            ->first();

        if ($existing && $existing->file_path && Storage::exists($existing->file_path)) {
            return $existing;
        }

        $template = $this->resolveTemplate($templateId);
        $assessment = $attempt->assessment;
        $course = $assessment->course;

        $certificate = Certificate::create([
            'certificate_number' => Certificate::generateNumber(),
            'user_id' => $user->id,
            'template_id' => $template->id,
            'attempt_id' => $attempt->id,
            'type' => 'assessment_pass',
            'recipient_name' => $user->name,
            'course_title' => $assessment->title,
            'description' => "This certifies that {$user->name} has passed the assessment \"{$assessment->title}\" with a score of {$attempt->score}%.",
            'score' => $attempt->score,
            'issued_at' => now()->format('F j, Y'),
            'expires_at' => now()->addYears(1)->format('Y-m-d'),
            'metadata' => [
                'assessment_id' => $assessment->id,
                'course_id' => $course->id,
                'passing_score' => $assessment->passing_score,
                'score_achieved' => $attempt->score,
                'time_spent_seconds' => $attempt->time_spent_seconds,
            ],
        ]);

        try {
            $filePath = $this->renderPdfCertificate($certificate, $template);
            $certificate->update([
                'file_path' => $filePath,
                'file_format' => 'pdf',
                'generated_at' => now(),
            ]);
        } catch (\Exception $e) {
            Log::error("Certificate PDF generation failed for {$certificate->certificate_number}: " . $e->getMessage());
        }

        return $certificate->fresh();
    }

    public function verifyCertificate(string $certificateNumber): array
    {
        $certificate = Certificate::where('certificate_number', $certificateNumber)->first();

        if (!$certificate) {
            return [
                'valid' => false,
                'error' => 'Certificate not found',
            ];
        }

        return [
            'valid' => $certificate->is_verified && !$certificate->isExpired(),
            'certificate_number' => $certificate->certificate_number,
            'recipient_name' => $certificate->recipient_name,
            'course_title' => $certificate->course_title,
            'issued_at' => $certificate->issued_at,
            'expires_at' => $certificate->expires_at,
            'type' => $certificate->type,
            'score' => $certificate->score,
            'is_expired' => $certificate->isExpired(),
            'verification_url' => $certificate->verification_url,
        ];
    }

    public function downloadCertificate(Certificate $certificate): ?string
    {
        if (!$certificate->file_path || !Storage::exists($certificate->file_path)) {
            return null;
        }

        return Storage::disk('public')->path($certificate->file_path);
    }

    public function getUserCertificates(User $user, ?string $type = null): \Illuminate\Database\Eloquent\Builder
    {
        $query = Certificate::where('user_id', $user->id)->orderByDesc('created_at');

        if ($type) {
            $query->where('type', $type);
        }

        return $query;
    }

    protected function resolveTemplate(?int $templateId): CertificateTemplate
    {
        if ($templateId) {
            return CertificateTemplate::active()->findOrFail($templateId);
        }

        return CertificateTemplate::active()->default()->first()
            ?? CertificateTemplate::active()->first()
            ?? $this->createDefaultTemplate();
    }

    protected function createDefaultTemplate(): CertificateTemplate
    {
        return CertificateTemplate::create([
            'name' => 'Default Certificate',
            'slug' => 'default',
            'description' => 'Standard course completion certificate',
            'orientation' => 'landscape',
            'background_color' => '#FFFFFF',
            'primary_color' => '#1E3A5F',
            'accent_color' => '#D4AF37',
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    protected function renderPdfCertificate(Certificate $certificate, CertificateTemplate $template): string
    {
        $data = [
            'certificate' => $certificate,
            'template' => $template,
            'recipient_name' => $certificate->recipient_name,
            'course_title' => $certificate->course_title,
            'description' => $certificate->description,
            'certificate_number' => $certificate->certificate_number,
            'issued_at' => $certificate->issued_at,
            'expires_at' => $certificate->expires_at,
            'score' => $certificate->score,
            'primary_color' => $template->primary_color,
            'accent_color' => $template->accent_color,
            'background_color' => $template->background_color,
        ];

        $html = view('certificates.certificate', $data)->render();

        $pdf = Pdf::loadHtml($html)
            ->setPaper($template->orientation === 'landscape' ? 'letter' : 'portrait', $template->orientation)
            ->setOption('isRemoteEnabled', true)
            ->setOption('isPhpEnabled', true)
            ->setOption('defaultFont', 'sans-serif');

        $fileName = "certificates/{$certificate->certificate_number}.pdf";
        $fullPath = storage_path("app/public/{$fileName}");
        $dir = dirname($fullPath);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $pdf->save($fullPath);

        return $fileName;
    }
}
