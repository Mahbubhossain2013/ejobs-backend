<?php

namespace App\Jobs;

use App\Models\BulkMessageRecipient;
use App\Models\Company;
use App\Models\Setting;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendBulkApplicationEmail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $timeout = 60;

    public function __construct(
        public int $recipientId,
        public string $template,
        public ?string $subject,
        public ?string $message,
        public ?string $interviewDate,
        public ?string $interviewLocation
    ) {}

    public function handle(): void
    {
        $recipient = BulkMessageRecipient::with(['user.profile', 'application.job'])->find($this->recipientId);
        if (!$recipient) {
            return;
        }

        $batch = $recipient->batch;

        if ($recipient->status === 'sent') {
            return;
        }

        $candidate = $recipient->user;
        if (!$candidate || empty($candidate->email)) {
            $recipient->update(['status' => 'skipped', 'error' => 'No email address']);
            $batch?->refreshCounts();
            return;
        }

        $job = $recipient->application?->job;
        $companyName = $job?->company->name ?? Company::where('user_id', $recipient->batch?->employer_id)->value('name') ?? '';
        $brandName = Setting::where('key', 'site_name')->value('value') ?? config('app.name', 'eJobs');

        $jobTitle = $job?->title ?? 'your job application';
        $statusLabel = $recipient->batch?->status_filter ?? 'updated';
        $dashboardUrl = config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/dashboard/applied-jobs';

        try {
            \Illuminate\Support\Facades\Mail::send('emails.bulk_message', [
                'brandName' => $brandName,
                'candidateName' => $candidate->name,
                'jobTitle' => $jobTitle,
                'companyName' => $companyName,
                'status' => $statusLabel,
                'template' => $this->template,
                'subject' => $this->subject,
                'body' => $this->message,
                'interviewDate' => $this->interviewDate,
                'interviewLocation' => $this->interviewLocation,
                'dashboardUrl' => $dashboardUrl,
            ], function ($mail) use ($candidate, $companyName) {
                $mail->to($candidate->email)
                     ->subject($this->subject ?: "Update from {$companyName}");
            });

            $recipient->update([
                'status' => 'sent',
                'error' => null,
                'attempts' => $recipient->attempts + 1,
                'sent_at' => now(),
            ]);
            $batch?->refreshCounts();
        } catch (\Throwable $e) {
            $recipient->update([
                'status' => 'failed',
                'error' => substr($e->getMessage(), 0, 500),
                'attempts' => $recipient->attempts + 1,
            ]);
            $batch?->refreshCounts();
            throw $e;
        }
    }
}