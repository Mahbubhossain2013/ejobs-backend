<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Services\ProfileIntelligenceService;

class AnalyzeProfileIntelligence extends Command
{
    protected $signature = 'intelligence:analyze';
    protected $description = 'Analyze and update candidate and employer profile intelligence trust scores, metrics, and automated badges';

    public function handle(ProfileIntelligenceService $service)
    {
        $this->info("Starting profile intelligence monitoring analysis...");

        // Fetch all users except admins
        $users = User::whereDoesntHave('roles', function($q) {
            $q->where('name', 'admin');
        })->get();

        $count = 0;
        foreach ($users as $user) {
            $service->analyzeUser($user);
            $count++;
        }

        $this->info("Profile intelligence analysis completed for {$count} users.");
    }
}
