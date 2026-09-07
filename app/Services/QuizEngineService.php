<?php

namespace App\Services;

use App\Models\SkillAssessment;
use App\Models\SkillAssessmentQuestion;
use App\Models\SkillAssessmentAttempt;
use App\Models\SkillAssessmentAnswer;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class QuizEngineService
{
    public function startAttempt(User $user, SkillAssessment $assessment): SkillAssessmentAttempt
    {
        $previousAttempts = $assessment->attempts()
            ->where('user_id', $user->id)
            ->whereIn('status', ['completed', 'timed_out'])
            ->count();

        if ($previousAttempts >= $assessment->max_attempts) {
            throw new \Exception("Maximum attempts ({$assessment->max_attempts}) reached for this assessment.");
        }

        $activeAttempt = $assessment->attempts()
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->first();

        if ($activeAttempt) {
            if ($activeAttempt->isTimedOut()) {
                $activeAttempt->update([
                    'status' => 'timed_out',
                    'completed_at' => now(),
                ]);
            } else {
                return $activeAttempt;
            }
        }

        $questions = $this->getQuestionsForAttempt($assessment);

        $attempt = SkillAssessmentAttempt::create([
            'user_id' => $user->id,
            'assessment_id' => $assessment->id,
            'status' => 'in_progress',
            'started_at' => now(),
            'total_points' => $questions->sum('points'),
        ]);

        return $attempt->load('assessment');
    }

    public function submitAnswer(SkillAssessmentAttempt $attempt, int $questionId, ?string $answer): SkillAssessmentAnswer
    {
        if ($attempt->status !== 'in_progress') {
            throw new \Exception("This attempt is no longer active.");
        }

        if ($attempt->isTimedOut()) {
            $attempt->update([
                'status' => 'timed_out',
                'completed_at' => now(),
            ]);
            throw new \Exception("Time limit exceeded. Your attempt has been finalized.");
        }

        $question = SkillAssessmentQuestion::findOrFail($questionId);

        if ($question->assessment_id !== $attempt->assessment_id) {
            throw new \Exception("Question does not belong to this assessment.");
        }

        $existingAnswer = SkillAssessmentAnswer::where('attempt_id', $attempt->id)
            ->where('question_id', $questionId)
            ->first();

        $isCorrect = $this->checkAnswer($question, $answer);
        $pointsEarned = $isCorrect ? $question->points : 0;

        if ($existingAnswer) {
            $existingAnswer->update([
                'user_answer' => $answer,
                'is_correct' => $isCorrect,
                'points_earned' => $pointsEarned,
            ]);
            return $existingAnswer->fresh();
        }

        return SkillAssessmentAnswer::create([
            'attempt_id' => $attempt->id,
            'question_id' => $questionId,
            'user_answer' => $answer,
            'is_correct' => $isCorrect,
            'points_earned' => $pointsEarned,
        ]);
    }

    public function submitAllAnswers(SkillAssessmentAttempt $attempt, array $answers): SkillAssessmentAttempt
    {
        if ($attempt->status !== 'in_progress') {
            throw new \Exception("This attempt is no longer active.");
        }

        DB::beginTransaction();

        try {
            foreach ($answers as $questionId => $answer) {
                $this->submitAnswer($attempt, $questionId, $answer);
            }

            $finalized = $this->finalizeAttempt($attempt);

            DB::commit();

            return $finalized;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    public function finalizeAttempt(SkillAssessmentAttempt $attempt): SkillAssessmentAttempt
    {
        $totalPoints = $attempt->assessment->questions()->sum('points');
        $earnedPoints = $attempt->answers()->sum('points_earned');

        $score = $totalPoints > 0 ? round(($earnedPoints / $totalPoints) * 100) : 0;
        $isPassed = $score >= $attempt->assessment->passing_score;

        $attempt->update([
            'status' => 'completed',
            'score' => $score,
            'total_points' => $totalPoints,
            'is_passed' => $isPassed,
            'completed_at' => now(),
            'time_spent_seconds' => $attempt->started_at->diffInSeconds(now()),
        ]);

        Log::info("Assessment attempt {$attempt->id} completed", [
            'user_id' => $attempt->user_id,
            'assessment_id' => $attempt->assessment_id,
            'score' => $score,
            'is_passed' => $isPassed,
        ]);

        return $attempt->fresh(['assessment', 'answers.question']);
    }

    public function getAttemptResult(SkillAssessmentAttempt $attempt): array
    {
        $attempt->load(['assessment', 'answers.question', 'user']);

        $answers = $attempt->answers->map(function ($answer) {
            return [
                'id' => $answer->id,
                'question_id' => $answer->question_id,
                'question' => $answer->question->question,
                'type' => $answer->question->type,
                'options' => $answer->question->options,
                'user_answer' => $answer->user_answer,
                'correct_answer' => $answer->question->correct_answer,
                'is_correct' => $answer->is_correct,
                'points_earned' => $answer->points_earned,
                'total_points' => $answer->question->points,
            ];
        });

        return [
            'attempt_id' => $attempt->id,
            'status' => $attempt->status,
            'score' => $attempt->score,
            'total_points' => $attempt->total_points,
            'is_passed' => $attempt->is_passed,
            'passing_score' => $attempt->assessment->passing_score,
            'time_spent_seconds' => $attempt->time_spent_seconds,
            'started_at' => $attempt->started_at->toISOString(),
            'completed_at' => $attempt->completed_at?->toISOString(),
            'remaining_time_seconds' => $attempt->remaining_time_seconds,
            'answers' => $answers,
        ];
    }

    public function getUserAttemptHistory(User $user, SkillAssessment $assessment): \Illuminate\Database\Eloquent\Collection
    {
        return $assessment->attempts()
            ->where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();
    }

    public function canAttempt(User $user, SkillAssessment $assessment): array
    {
        $attemptCount = $assessment->attempts()
            ->where('user_id', $user->id)
            ->whereIn('status', ['completed', 'timed_out'])
            ->count();

        $activeAttempt = $assessment->attempts()
            ->where('user_id', $user->id)
            ->where('status', 'in_progress')
            ->first();

        $bestAttempt = $assessment->attempts()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->orderByDesc('score')
            ->first();

        return [
            'can_attempt' => $attemptCount < $assessment->max_attempts || $activeAttempt,
            'attempts_used' => $attemptCount,
            'max_attempts' => $assessment->max_attempts,
            'has_active_attempt' => (bool) $activeAttempt,
            'best_score' => $bestAttempt?->score,
            'is_passed' => $bestAttempt?->is_passed ?? false,
            'passing_score' => $assessment->passing_score,
        ];
    }

    protected function getQuestionsForAttempt(SkillAssessment $assessment)
    {
        $questions = $assessment->questions();

        if ($assessment->shuffle_questions) {
            $questions->inRandomOrder();
        } else {
            $questions->orderBy('order');
        }

        if ($assessment->questions_to_show && $assessment->questions_to_show > 0) {
            $questions->limit($assessment->questions_to_show);
        }

        return $questions->get();
    }

    protected function checkAnswer(SkillAssessmentQuestion $question, ?string $answer): bool
    {
        if ($answer === null) {
            return false;
        }

        $normalizedAnswer = strtolower(trim($answer));
        $correctAnswer = strtolower(trim($question->correct_answer));

        return match ($question->type) {
            'multiple_choice' => $normalizedAnswer === $correctAnswer,
            'true_false' => in_array($normalizedAnswer, ['true', 'false']) && $normalizedAnswer === $correctAnswer,
            'short_answer' => levenshtein($normalizedAnswer, $correctAnswer) <= 2 || str_contains($correctAnswer, $normalizedAnswer),
            default => false,
        };
    }
}
