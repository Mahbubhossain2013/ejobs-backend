<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;

class ProfileStrengthService
{
    public static function calculate(User $user): int
    {
        $profile = $user->profile;
        $weights = self::getWeights();

        $checks = [
            'basic_info' => $profile && !empty($profile->personal_info) && (
                !empty($profile->personal_info['phone'] ?? '') && !empty($profile->personal_info['city'] ?? '')
            ),
            'resume' => $profile && !empty($profile->personal_info['summary']),
            'skills' => $profile && count($profile->skills ?? []) >= 3,
            'experience' => $profile && count($profile->experiences ?? []) > 0,
            'education' => $profile && count($profile->educations ?? []) > 0,
            'certifications' => $profile && count($profile->certifications ?? []) > 0,
            'avatar' => $user->avatar && $user->avatar !== '',
            'portfolio' => $profile && count($profile->projects ?? []) > 0,
            'social_links' => $profile && (
                !empty($profile->linkedin ?? '') || !empty($profile->github ?? '') ||
                !empty($profile->twitter ?? '') || !empty($profile->website_url ?? '')
            ),
            'bio' => $profile && !empty($profile->personal_info['summary'] ?? '') && strlen($profile->personal_info['summary'] ?? '') > 50,
        ];

        $score = 0;
        foreach ($checks as $field => $passed) {
            if ($passed) {
                $score += $weights["weight_{$field}"] ?? 0;
            }
        }

        return min(100, max(0, $score));
    }

    public static function getWeights(): array
    {
        $keys = [
            'weight_basic_info' => 10, 'weight_resume' => 15, 'weight_skills' => 10,
            'weight_experience' => 15, 'weight_education' => 10, 'weight_certifications' => 10,
            'weight_avatar' => 10, 'weight_portfolio' => 10, 'weight_social_links' => 5,
            'weight_bio' => 5,
        ];
        $settings = Setting::whereIn('key', array_keys($keys))->pluck('value', 'key')->toArray();
        foreach ($keys as $key => $default) {
            $settings[$key] = isset($settings[$key]) ? (int)$settings[$key] : $default;
        }
        return $settings;
    }

    public static function getMinRequiredStrength(): int
    {
        return (int)(Setting::where('key', 'min_required_strength')->value('value') ?? 45);
    }

    public static function isRestrictionEnabled(): bool
    {
        return Setting::where('key', 'restrict_low_strength_apply')->value('value') === '1';
    }
}
