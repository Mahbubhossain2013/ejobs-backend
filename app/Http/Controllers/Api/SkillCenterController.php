<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SkillCourse;
use App\Models\SkillEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SkillCenterController extends Controller
{
    /**
     * List all active courses (public)
     */
    public function index(Request $request)
    {
        $query = SkillCourse::where('is_active', true);

        if ($request->has('category')) {
            $query->where('category', $request->category);
        }
        if ($request->has('difficulty')) {
            $query->where('difficulty', $request->difficulty);
        }
        if ($request->has('search')) {
            $query->where('title', 'like', "%{$request->search}%");
        }

        $courses = $query->orderByDesc('enrollment_count')->paginate(12);

        return response()->json(['status' => true, 'data' => $courses]);
    }

    /**
     * Show single course with lessons
     */
    public function show(int $id)
    {
        $course = SkillCourse::with(['lessons', 'assessment' => function ($q) {
            $q->withCount('questions');
        }])->findOrFail($id);

        $enrollment = null;
        $certificate = null;
        if (Auth::check()) {
            $enrollment = SkillEnrollment::where('user_id', Auth::id())
                ->where('course_id', $id)
                ->first();

            if ($enrollment && $enrollment->status === 'completed') {
                $certificate = \App\Models\Certificate::where('user_id', Auth::id())
                    ->where('enrollment_id', $enrollment->id)
                    ->where('type', 'course_completion')
                    ->first();
            }
        }

        return response()->json([
            'status' => true,
            'data' => $course,
            'enrollment' => $enrollment,
            'certificate' => $certificate,
        ]);
    }

    /**
     * Enroll in a course
     */
    public function enroll(int $courseId)
    {
        $user = Auth::user();
        $course = SkillCourse::findOrFail($courseId);

        if (!$course->is_active) {
            return response()->json(['status' => false, 'message' => 'Course is not available'], 400);
        }

        if ($course->max_enrollments && $course->enrollment_count >= $course->max_enrollments) {
            return response()->json(['status' => false, 'message' => 'Course is full'], 400);
        }

        $existing = SkillEnrollment::where('user_id', $user->id)->where('course_id', $courseId)->first();
        if ($existing) {
            return response()->json(['status' => false, 'message' => 'Already enrolled'], 400);
        }

        $enrollment = SkillEnrollment::create([
            'user_id' => $user->id,
            'course_id' => $courseId,
            'status' => 'enrolled',
            'enrolled_at' => now(),
        ]);

        $course->increment('enrollment_count');

        return response()->json([
            'status' => true,
            'message' => 'Enrolled successfully',
            'data' => $enrollment,
        ], 201);
    }

    /**
     * Update course progress
     */
    public function updateProgress(Request $request, int $enrollmentId)
    {
        $user = Auth::user();
        $enrollment = SkillEnrollment::where('user_id', $user->id)->findOrFail($enrollmentId);

        $validated = $request->validate([
            'progress' => 'required|numeric|min:0|max:100',
        ]);

        $wasCompleted = $enrollment->status === 'completed';
        $newProgress = $validated['progress'];

        $enrollment->update([
            'progress' => $newProgress,
            'status' => $newProgress >= 100 ? 'completed' : 'in_progress',
            'completed_at' => $newProgress >= 100 && !$wasCompleted ? now() : $enrollment->completed_at,
        ]);

        $certificate = null;
        if ($newProgress >= 100 && !$wasCompleted && $user->hasFeature('certificate_generation')) {
            try {
                $certService = app(\App\Services\CertificateGeneratorService::class);
                $certificate = $certService->generateFromEnrollment($user, $enrollment);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning("Auto-certificate generation failed on course completion: " . $e->getMessage());
            }
        }

        return response()->json([
            'status' => true,
            'message' => 'Progress updated',
            'data' => $enrollment->fresh(),
            'certificate' => $certificate ? [
                'id' => $certificate->id,
                'certificate_number' => $certificate->certificate_number,
                'download_url' => "/api/certificates/{$certificate->id}/download",
            ] : null,
        ]);
    }

    /**
     * Get my enrollments
     */
    public function myCourses()
    {
        $enrollments = SkillEnrollment::with('course')
            ->where('user_id', Auth::id())
            ->orderByDesc('updated_at')
            ->get();

        return response()->json(['status' => true, 'data' => $enrollments]);
    }

    /**
     * Get all categories (for filter)
     */
    public function categories()
    {
        $categories = SkillCourse::where('is_active', true)
            ->select('category')
            ->distinct()
            ->pluck('category');

        return response()->json(['status' => true, 'data' => $categories]);
    }
}
