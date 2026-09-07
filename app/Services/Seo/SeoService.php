<?php

namespace App\Services\Seo;

use App\Models\Job;
use App\Models\Company;
use App\Models\UserProfile;
use App\Models\SeoSetting;
use Illuminate\Support\Facades\URL;

class SeoService
{
    /**
     * Compile standard and advanced meta tags for any entity
     */
    public function compileMeta(string $pageKey, ?object $entity = null, string $lang = 'en'): array
    {
        // 1. Load custom SEO setting override from DB if configured
        $seoSetting = SeoSetting::where('page_key', $pageKey)->first();

        $title = $this->translateField($seoSetting?->meta_title, $lang);
        $description = $this->translateField($seoSetting?->meta_description, $lang);
        $ogImage = $seoSetting?->og_image_path ? asset('storage/' . $seoSetting->og_image_path) : null;
        $structuredData = $seoSetting?->structured_data ?? [];

        // 2. Dynamically resolve tags if analyzing models
        if ($entity instanceof Job) {
            $title = $entity->title . ' | ' . ($entity->company?->name ?? 'Job Portal');
            $description = strip_tags(str_replace('&nbsp;', ' ', Str::limit($entity->description, 160)));
            $ogImage = $entity->company?->logo ? asset('storage/' . $entity->company->logo) : $ogImage;
            $structuredData = $this->generateJobPostingSchema($entity);
        } elseif ($entity instanceof Company) {
            $title = $entity->name . ' - Careers and Company Profile';
            $description = strip_tags(Str::limit($entity->description, 160));
            $ogImage = $entity->logo ? asset('storage/' . $entity->logo) : $ogImage;
            $structuredData = $this->generateOrganizationSchema($entity);
        } elseif ($entity instanceof UserProfile) {
            $title = ($entity->user?->name ?? 'Candidate') . ' - Portfolio | ' . ($entity->current_position ?? 'Professional');
            $description = strip_tags(Str::limit($entity->bio ?? 'View my professional portfolio, skills, and qualifications.', 160));
            $ogImage = $entity->avatar ? asset('storage/' . $entity->avatar) : $ogImage;
            $structuredData = $this->generateProfileSchema($entity);
        }

        // Apply default fallbacks if still empty
        $defaultTitle = config('app.name', 'Professional Job Portal');
        $title = $title ?: $defaultTitle;
        $description = $description ?: 'The premier platform for professional corporate and remote freelance developer jobs.';
        $ogImage = $ogImage ?: asset('assets/images/default-og.png');

        return [
            'meta' => [
                'title' => $title,
                'description' => $description,
                'canonical' => URL::current(),
                'opengraph' => [
                    'og:title' => $title,
                    'og:description' => $description,
                    'og:image' => $ogImage,
                    'og:url' => URL::current(),
                    'og:type' => $entity ? 'article' : 'website',
                ],
                'twitter' => [
                    'twitter:card' => 'summary_large_image',
                    'twitter:title' => $title,
                    'twitter:description' => $description,
                    'twitter:image' => $ogImage,
                ]
            ],
            'schema' => $structuredData
        ];
    }

    /**
     * Resolve multilingual translations safely
     */
    protected function translateField($field, string $lang): string
    {
        if (empty($field)) {
            return '';
        }
        
        $arr = is_string($field) ? json_decode($field, true) : $field;
        if (!is_array($arr)) {
            return (string)$field;
        }

        return $arr[$lang] ?? $arr['en'] ?? reset($arr) ?? '';
    }

    /**
     * Generate Schema.org JobPosting structure (aligned with Google Jobs search crawler guidelines)
     */
    public function generateJobPostingSchema(Job $job): array
    {
        $companyName = $job->company?->name ?? 'Confidential Employer';
        
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'JobPosting',
            'title' => $job->title,
            'description' => $job->description,
            'datePosted' => $job->created_at?->toIso8601String() ?? now()->toIso8601String(),
            'validThrough' => $job->deadline?->toIso8601String(),
            'employmentType' => $this->mapEmploymentType($job->job_type),
            'hiringOrganization' => [
                '@type' => 'Organization',
                'name' => $companyName,
                'sameAs' => $job->company?->website,
                'logo' => $job->company?->logo ? asset('storage/' . $job->company->logo) : null
            ],
            'identifier' => [
                '@type' => 'PropertyValue',
                'name' => $companyName,
                'value' => 'JOB-' . $job->id
            ]
        ];

        // Remote/On-site configurations
        if ($job->is_remote_project) {
            $schema['jobLocationType'] = 'TELECOMMUTE';
            $schema['applicantLocationRequirements'] = [
                '@type' => 'Country',
                'name' => $job->country_restriction ?: 'Anywhere'
            ];
        } else {
            $schema['jobLocation'] = [
                '@type' => 'Place',
                'address' => [
                    '@type' => 'PostalAddress',
                    'addressLocality' => $job->location ?: 'Not Specified',
                    'addressCountry' => 'BD'
                ]
            ];
        }

        // Salary definitions
        if ($job->budget > 0) {
            $schema['baseSalary'] = [
                '@type' => 'MonetaryAmount',
                'currency' => 'USD',
                'value' => [
                    '@type' => 'QuantitativeValue',
                    'value' => $job->budget,
                    'unitText' => strtoupper($job->budget_type ?: 'project')
                ]
            ];
        }

        return $schema;
    }

    /**
     * Generate Schema.org Organization structure
     */
    public function generateOrganizationSchema(Company $company): array
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => $company->name,
            'slug' => $company->slug,
            'url' => url('/companies/' . $company->slug),
            'logo' => $company->logo ? asset('storage/' . $company->logo) : null,
            'description' => $company->description,
            'sameAs' => array_filter([$company->website]),
            'address' => [
                '@type' => 'PostalAddress',
                'addressLocality' => $company->location ?: 'Bangladesh',
                'addressCountry' => 'BD'
            ]
        ];
    }

    /**
     * Generate Schema.org ProfilePage structure
     */
    public function generateProfileSchema(UserProfile $profile): array
    {
        $user = $profile->user;
        return [
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'mainEntity' => [
                '@type' => 'Person',
                'name' => $user?->name ?? 'Candidate',
                'jobTitle' => $profile->current_position,
                'worksFor' => [
                    '@type' => 'Organization',
                    'name' => $profile->current_company ?: 'Independent Freelancer'
                ],
                'description' => $profile->bio,
                'image' => $profile->avatar ? asset('storage/' . $profile->avatar) : null,
                'knowsAbout' => $profile->skills ?? []
            ]
        ];
    }

    /**
     * Map platform job types to schema.org acceptable formats
     */
    protected function mapEmploymentType(?string $type): string
    {
        $type = strtolower($type ?? 'full-time');
        
        if (str_contains($type, 'part')) return 'PART_TIME';
        if (str_contains($type, 'contract') || str_contains($type, 'freelance')) return 'CONTRACTOR';
        if (str_contains($type, 'intern')) return 'INTERN';
        
        return 'FULL_TIME';
    }
}
// Clean structural support helper
if (!class_exists('Str')) {
    class Str extends \Illuminate\Support\Str {}
}
