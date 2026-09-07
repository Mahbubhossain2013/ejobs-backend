<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CandidateData;
use App\Models\CandidateEducation;
use App\Models\CandidateExperience;
use App\Models\CandidateCertification;
use App\Models\CandidateReference;
use App\Models\CandidateTraining;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CvProfileController extends Controller
{
    public function getProfile()
    {
        $user = Auth::user();

        $cvData = CandidateData::firstOrCreate(
            ['user_id' => $user->id],
            [
                'personal_info' => [],
                'skills' => [],
                'experience' => [],
                'education' => [],
                'projects' => [],
                'social_links' => [],
                'certifications' => [],
                'languages' => [],
                'awards' => [],
                'hobbies' => [],
            ]
        );

        $personalInfo = $cvData->personal_info ?? [];
        $isEmpty = empty($personalInfo) || empty($personalInfo['full_name'] ?? '');

        if ($isEmpty) {
            $this->syncFromMainProfile($user, $cvData);
        }

        return response()->json(['status' => true, 'data' => $cvData]);
    }

    public function syncFromMainProfile($user, $cvData)
    {
        $profile = $user->profile;

        if (!$profile) {
            return;
        }

        // 1. Personal Info
        $personalInfo = [
            'full_name' => $user->name,
            'title' => $profile->current_position ?? '',
            'current_position' => $profile->current_position ?? '',
            'email' => $user->email,
            'phone' => $profile->phone ?? '',
            'address' => $profile->present_address ?? '',
            'city' => $profile->city ?? $profile->district ?? '',
            'website' => $profile->portfolio_url ?? '',
            'linkedin' => $profile->linkedin_url ?? '',
            'github' => $profile->github_url ?? '',
            'summary' => $profile->career_objective ?? '',
            'nationality' => $profile->nationality ?? '',
            'date_of_birth' => $profile->date_of_birth ?? '',
            'photo_url' => $profile->avatar ?? '',
        ];

        // 2. Skills
        $skills = $profile->skills ?? [];

        // 3. Work Experience (from dedicated table, fallback to JSON)
        $experiences = CandidateExperience::where('user_id', $user->id)
            ->orderBy('start_date', 'desc')
            ->get()
            ->map(fn($e) => [
                'company' => $e->company_name,
                'position' => $e->designation,
                'start_date' => $e->start_date,
                'end_date' => $e->end_date,
                'is_current' => (bool)$e->is_current,
                'description' => $e->responsibilities,
            ])->toArray();

        if (empty($experiences)) {
            $rawExp = $profile->experience ?? [];
            $experiences = array_map(fn($e) => [
                'company' => $e['company_name'] ?? $e['company'] ?? '',
                'position' => $e['designation'] ?? $e['position'] ?? '',
                'start_date' => $e['start_date'] ?? '',
                'end_date' => $e['end_date'] ?? '',
                'is_current' => $e['is_current'] ?? false,
                'description' => $e['responsibilities'] ?? $e['description'] ?? '',
            ], $rawExp);
        }

        // 4. Education (from dedicated table, fallback to JSON)
        $educations = CandidateEducation::where('user_id', $user->id)
            ->orderBy('passing_year', 'desc')
            ->get()
            ->map(fn($e) => [
                'institution' => $e->institute_name,
                'degree' => $e->degree_name ?? $e->level,
                'field_of_study' => $e->group_or_subject ?? '',
                'year' => (string)$e->passing_year,
            ])->toArray();

        if (empty($educations)) {
            $rawEdu = $profile->education ?? [];
            $educations = array_map(fn($e) => [
                'institution' => $e['institute_name'] ?? $e['institution'] ?? '',
                'degree' => $e['degree_name'] ?? $e['level'] ?? $e['degree'] ?? '',
                'field_of_study' => $e['group_or_subject'] ?? $e['field_of_study'] ?? '',
                'year' => (string)($e['passing_year'] ?? $e['year'] ?? ''),
            ], $rawEdu);
        }

        // 5. Certifications
        $certifications = CandidateCertification::where('user_id', $user->id)
            ->get()
            ->map(fn($c) => [
                'name' => $c->name,
                'issuer' => $c->organization ?? '',
                'date' => $c->issue_date ?? '',
            ])->toArray();

        // 5b. References (from dedicated table)
        $references = CandidateReference::where('user_id', $user->id)
            ->get()
            ->map(fn($r) => [
                'name' => $r->name,
                'designation' => $r->designation ?? '',
                'organization' => $r->organization ?? '',
                'phone' => $r->phone ?? '',
                'email' => $r->email ?? '',
            ])->toArray();

        // 5c. Training (from dedicated table)
        $training = CandidateTraining::where('user_id', $user->id)
            ->get()
            ->map(fn($t) => [
                'institute' => $t->institute_name ?? '',
                'title' => $t->title,
                'duration' => $t->duration ?? '',
            ])->toArray();

        // 6. Languages
        $langProf = $profile->language_proficiency ?? [];
        $languages = [];
        foreach ($langProf as $l) {
            $langName = $l['name'] ?? $l['language'] ?? '';
            if ($langName) {
                $proficiency = $l['proficiency'] ?? '';
                if (!$proficiency) {
                    if (!empty($l['read']) && !empty($l['write']) && !empty($l['speak'])) {
                        $proficiency = 'fluent';
                    } elseif (!empty($l['read']) || !empty($l['write']) || !empty($l['speak'])) {
                        $proficiency = 'intermediate';
                    } else {
                        $proficiency = 'basic';
                    }
                }
                $languages[] = [
                    'name' => $langName,
                    'proficiency' => $proficiency,
                ];
            }
        }

        // 7. Projects
        $projects = $profile->projects ?? [];

        // 8. Social Links
        $socialLinks = $profile->social_links ?? [];
        if ($profile->linkedin_url) $socialLinks['linkedin'] = $profile->linkedin_url;
        if ($profile->github_url) $socialLinks['github'] = $profile->github_url;
        if ($profile->facebook_url) $socialLinks['facebook'] = $profile->facebook_url;
        if ($profile->portfolio_url) $socialLinks['portfolio'] = $profile->portfolio_url;

        $cvData->personal_info = $personalInfo;
        $cvData->skills = $skills;
        $cvData->experience = $experiences;
        $cvData->education = $educations;
        $cvData->certifications = $certifications;
        $cvData->languages = $languages;
        $cvData->awards = $profile->awards ?? [];
        $cvData->hobbies = $profile->hobbies ?? [];
        $cvData->projects = $projects;
        $cvData->social_links = $socialLinks;
        $cvData->references = $references;
        $cvData->training = $training;
        $cvData->save();
    }

    public function updateProfile(Request $request)
    {
        $input = $request->all();

        // Normalize field aliases from wizard/editor
        $personal = $input['personal_info'] ?? $input['personal'] ?? [];
        $summary = $input['summary'] ?? $input['resume_objective']['description'] ?? $input['resume_objective'] ?? ($personal['summary'] ?? null);
        if ($summary) {
            $personal['summary'] = is_array($summary) ? ($summary['description'] ?? '') : $summary;
        }

        $experience = $input['experience'] ?? $input['experiences'] ?? $input['work_experience'] ?? [];
        $education = $input['education'] ?? $input['educations'] ?? [];
        $skills = $input['skills'] ?? [];
        $languages = $input['languages'] ?? [];
        $certifications = $input['certifications'] ?? [];
        $projects = $input['projects'] ?? [];
        $hobbies = $input['hobbies'] ?? $input['interests'] ?? [];
        $awards = $input['awards'] ?? $input['achievements'] ?? [];
        $references = $input['references'] ?? [];
        $training = $input['training'] ?? $input['trainings'] ?? [];
        $socialLinks = $input['social_links'] ?? [];

        $dataToSave = [
            'personal_info' => $personal,
            'skills' => $skills,
            'experience' => $experience,
            'education' => $education,
            'projects' => $projects,
            'social_links' => $socialLinks,
            'certifications' => $certifications,
            'languages' => $languages,
            'awards' => $awards,
            'hobbies' => $hobbies,
            'references' => $references,
            'training' => $training,
        ];

        $profile = CandidateData::updateOrCreate(
            ['user_id' => Auth::id()],
            $dataToSave
        );

        return response()->json(['status' => true, 'message' => 'CV Profile Saved!', 'data' => $profile]);
    }

    public function uploadPhoto(Request $request)
    {
        try {
            $request->validate([
                'photo' => 'required|file|max:20480',
            ]);

            $user = Auth::user();
            if (!$user) {
                return response()->json(['status' => false, 'message' => 'Unauthenticated'], 401);
            }

            $cvData = CandidateData::firstOrCreate(['user_id' => $user->id]);

            $photoFile = \App\Services\Media\ImageOptimizerService::convertToWebp($request->file('photo'));
            $path = $photoFile->store('cv-photos', 'public');

            $personalInfo = $cvData->personal_info ?? [];
            $personalInfo['photo_url'] = $path;
            $cvData->personal_info = $personalInfo;
            $cvData->save();

            return response()->json([
                'status' => true,
                'data' => [
                    'photo_url' => $path,
                    'full_url' => asset('storage/' . $path),
                ],
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'status' => false,
                'message' => 'ছবি সাইজ সর্বোচ্চ ২০MB পর্যন্ত অনুমোদিত।',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('Photo upload failed: ' . $e->getMessage());
            return response()->json([
                'status' => false,
                'message' => 'Upload failed. Please try again.',
            ], 500);
        }
    }
}
