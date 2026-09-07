<?php

namespace App\Services\Security;

use App\Models\User;
use App\Models\UserProfile;
use App\Models\Resume;

class ProfileIntelligenceService
{
    /**
     * Define default weights for each profile category
     */
    public static function getWeights(): array
    {
        return [
            'basic_info' => 10,
            'resume' => 15,
            'skills' => 10,
            'experience' => 15,
            'education' => 10,
            'certifications' => 5,
            'photo' => 5,
            'portfolio' => 10,
            'social_links' => 5,
            'bio' => 5,
            'verification' => 10,
        ];
    }

    /**
     * Compute real-time Candidate Profile Strength and dynamic warnings/suggestions
     */
    public static function calculateStrength(User $user): array
    {
        $profile = $user->profile;
        if (!$profile) {
            return [
                'score' => 0,
                'status' => 'Beginner',
                'suggestions' => ['Configure your candidate profile overview to begin.'],
                'breakdown' => []
            ];
        }

        $weights = self::getWeights();
        $breakdown = [];
        $suggestions = [];
        $totalScore = 0;

        // 1. Basic Info Completed (10%)
        $hasBasic = !empty($user->name) && !empty($user->email) && !empty($profile->phone);
        $breakdown['basic_info'] = $hasBasic ? $weights['basic_info'] : 0;
        if (!$hasBasic) {
            $suggestions[] = "Provide your contact number and basic details (+{$weights['basic_info']}% strength)";
        }

        // 2. Resume Uploaded (15%)
        $hasResume = !empty($profile->resume_path) || Resume::where('user_id', $user->id)->exists();
        $breakdown['resume'] = $hasResume ? $weights['resume'] : 0;
        if (!$hasResume) {
            $suggestions[] = "Upload your professional resume PDF (+{$weights['resume']}% strength)";
        }

        // 3. Skills Added (10%)
        $hasSkills = is_array($profile->skills) && count($profile->skills) > 0;
        $breakdown['skills'] = $hasSkills ? $weights['skills'] : 0;
        if (!$hasSkills) {
            $suggestions[] = "Add your core technical skills and expertise (+{$weights['skills']}% strength)";
        } elseif (count($profile->skills) < 5) {
            $suggestions[] = "Add at least 5 tech stack tags for optimized matching";
        }

        // 4. Experience Added (15%)
        $hasExp = is_array($profile->experience) && count($profile->experience) > 0;
        $breakdown['experience'] = $hasExp ? $weights['experience'] : 0;
        if (!$hasExp) {
            $suggestions[] = "Add your professional employment history (+{$weights['experience']}% strength)";
        }

        // 5. Education Added (10%)
        $hasEdu = is_array($profile->education) && count($profile->education) > 0;
        $breakdown['education'] = $hasEdu ? $weights['education'] : 0;
        if (!$hasEdu) {
            $suggestions[] = "Add your academic degree and credentials (+{$weights['education']}% strength)";
        }

        // 6. Certifications Added (5%)
        $hasCert = is_array($profile->interests) && count($profile->interests) > 0; // Using interests/extras as certification snapshot
        $breakdown['certifications'] = $hasCert ? $weights['certifications'] : 0;
        if (!$hasCert) {
            $suggestions[] = "Add industry certifications to capture recruiter attention (+{$weights['certifications']}% strength)";
        }

        // 7. Profile Photo (5%)
        $hasPhoto = !empty($profile->avatar);
        $breakdown['photo'] = $hasPhoto ? $weights['photo'] : 0;
        if (!$hasPhoto) {
            $suggestions[] = "Upload a professional headshot photo (+{$weights['photo']}% strength)";
        }

        // 8. Portfolio/Projects (10%)
        $hasProj = is_array($profile->projects) && count($profile->projects) > 0;
        $breakdown['portfolio'] = $hasProj ? $weights['portfolio'] : 0;
        if (!$hasProj) {
            $suggestions[] = "Add active project links and case studies (+{$weights['portfolio']}% strength)";
        }

        // 9. Social Links (5%)
        $hasSocials = is_array($profile->notification_settings) && isset($profile->notification_settings['linkedin']); // using notification_settings as metadata fields if needed, or check bio links
        $hasSocials = $hasSocials || str_contains($profile->bio ?? '', 'http');
        $breakdown['social_links'] = $hasSocials ? $weights['social_links'] : 0;
        if (!$hasSocials) {
            $suggestions[] = "Link your GitHub or LinkedIn URLs (+{$weights['social_links']}% strength)";
        }

        // 10. Bio/About (5%)
        $hasBio = !empty($profile->bio) && strlen($profile->bio) > 30;
        $breakdown['bio'] = $hasBio ? $weights['bio'] : 0;
        if (!$hasBio) {
            $suggestions[] = "Write a comprehensive professional bio overview (+{$weights['bio']}% strength)";
        }

        // 11. Trust Verification Badge (10%)
        $isVer = (bool)($profile->is_verified || $user->hasBadge('verified') || $user->hasBadge('nid_verified'));
        $breakdown['verification'] = $isVer ? $weights['verification'] : 0;
        if (!$isVer) {
            $suggestions[] = "Verify your account NID / legal documents (+{$weights['verification']}% strength)";
        }

        // Calculate sum
        foreach ($breakdown as $key => $val) {
            $totalScore += $val;
        }

        // Determine Status Tier
        if ($totalScore >= 90) {
            $status = 'Elite';
        } elseif ($totalScore >= 70) {
            $status = 'Excellent';
        } elseif ($totalScore >= 50) {
            $status = 'Good';
        } elseif ($totalScore >= 30) {
            $status = 'Growing';
        } else {
            $status = 'Beginner';
        }

        // Auto update profile strength column to keep DB clean
        $profile->update([
            'profile_completion_percentage' => $totalScore,
            'profile_strength_breakdown' => $breakdown,
            'reputation_status' => $status
        ]);

        return [
            'score' => $totalScore,
            'status' => $status,
            'suggestions' => $suggestions,
            'breakdown' => $breakdown
        ];
    }
}
