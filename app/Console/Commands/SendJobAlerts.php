<?php

namespace App\Console\Commands;

use App\Models\Job;
use App\Models\JobAlert;
use App\Services\Notification\NotificationService;
use Illuminate\Console\Command;

class SendJobAlerts extends Command
{
    protected $signature = 'job-alerts:send';
    protected $description = 'Match active job alerts against new jobs and send notifications';

    public function handle(NotificationService $notificationService): int
    {
        $now = now();
        $alerts = JobAlert::where('is_active', true)
            ->with('user:id,name,email')
            ->get();

        $sent = 0;

        foreach ($alerts as $alert) {
            if ($alert->frequency === 'daily' && $alert->last_sent_at && $alert->last_sent_at->isToday()) {
                continue;
            }
            if ($alert->frequency === 'weekly' && $alert->last_sent_at && $alert->last_sent_at->diffInDays($now) < 7) {
                continue;
            }

            $jobs = $this->matchJobs($alert);

            if ($jobs->isEmpty()) {
                continue;
            }

            $count = $jobs->count();
            $label = $alert->label ?: 'your alert';
            $jobTitles = $jobs->take(5)->pluck('title')->implode(', ');
            $moreText = $count > 5 ? " and " . ($count - 5) . " more" : "";

            $title = "{$count} new job" . ($count > 1 ? "s" : "") . " match \"{$label}\"";
            $message = "Found {$count} matching job" . ($count > 1 ? "s" : "") . ": {$jobTitles}{$moreText}. View and apply now!";

            $notificationService->sendNotification(
                $alert->user,
                $title,
                $message,
                'job_alert',
                '/jobs'
            );

            $alert->update(['last_sent_at' => $now]);
            $sent++;
        }

        $this->info("Processed {$alerts->count()} alerts, sent {$sent} notifications.");
        return Command::SUCCESS;
    }

    private function matchJobs(JobAlert $alert)
    {
        $query = Job::where('is_active', true)
            ->where('visibility', 'public')
            ->where('created_at', '>=', $alert->last_sent_at ?? now()->subDay());

        if (!empty($alert->keywords)) {
            $query->where(function ($q) use ($alert) {
                $q->where('title', 'like', "%{$alert->keywords}%")
                  ->orWhere('description', 'like', "%{$alert->keywords}%");
            });
        }

        if ($alert->category_id) {
            $query->where('category_id', $alert->category_id);
        }

        if (!empty($alert->job_type)) {
            $query->where('job_type', $alert->job_type);
        }

        if (!empty($alert->location)) {
            $query->where('location', 'like', "%{$alert->location}%");
        }

        if ($alert->salary_min) {
            $query->where('salary_max', '>=', $alert->salary_min);
        }

        if ($alert->salary_max) {
            $query->where('salary_min', '<=', $alert->salary_max);
        }

        if ($alert->is_remote !== null) {
            $query->where('is_remote_project', $alert->is_remote);
        }

        return $query->get();
    }
}
