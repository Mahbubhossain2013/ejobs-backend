<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Promotion;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProcessPromotionBilling extends Command
{
    protected $signature = 'promotions:bill';
    protected $description = 'Process hourly billing for active promotions';

    public function handle()
    {
        \App\Services\Ad\CampaignBillingService::processActivePromotionsBilling();
        $this->info("Promotion daily-locked billing processed successfully.");
    }
}