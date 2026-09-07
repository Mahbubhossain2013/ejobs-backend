<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class FinancialSettingController extends Controller
{
    /**
     * Public / Authenticated: Get Financial Settings
     */
    public function getSettings()
    {
        $keys = [
            'remote_job_service_charge',
            'escrow_fee_percent',
            'escrow_fee_payer',
            'regular_job_apply_fee',
            'regular_job_apply_fee_enabled',
            'exchange_rate_usd'
        ];

        $settings = Setting::whereIn('key', $keys)->get();
        $data = [
            'remote_job_service_charge' => 5.00,
            'escrow_fee_percent' => 2.00,
            'escrow_fee_payer' => 'candidate',
            'regular_job_apply_fee' => 0.00,
            'regular_job_apply_fee_enabled' => false,
            'exchange_rate_usd' => 115.00
        ];

        foreach ($settings as $setting) {
            if ($setting->key === 'regular_job_apply_fee_enabled') {
                $data[$setting->key] = filter_var($setting->value, FILTER_VALIDATE_BOOLEAN);
            } elseif (in_array($setting->key, ['remote_job_service_charge', 'escrow_fee_percent', 'regular_job_apply_fee', 'exchange_rate_usd'])) {
                $data[$setting->key] = (float) $setting->value;
            } else {
                $data[$setting->key] = $setting->value;
            }
        }

        return response()->json(['status' => true, 'data' => $data]);
    }

    /**
     * ADMIN ONLY: Update Financial Settings
     */
    public function updateSettings(Request $request)
    {
        $user = Auth::user();
        if (!$user || !$user->hasAnyRole(['super_admin', 'admin'])) {
            return response()->json(['status' => false, 'message' => 'Unauthorized'], 403);
        }

        $request->validate([
            'remote_job_service_charge' => 'required|numeric|min:0|max:100',
            'regular_job_apply_fee' => 'required|numeric|min:0',
            'regular_job_apply_fee_enabled' => 'required|boolean',
            'exchange_rate_usd' => 'required|numeric|min:1'
        ]);

        Setting::updateOrCreate(['key' => 'remote_job_service_charge'], ['value' => $request->remote_job_service_charge]);
        Setting::updateOrCreate(['key' => 'regular_job_apply_fee'], ['value' => $request->regular_job_apply_fee]);
        Setting::updateOrCreate(['key' => 'regular_job_apply_fee_enabled'], ['value' => $request->regular_job_apply_fee_enabled ? '1' : '0']);
        Setting::updateOrCreate(['key' => 'exchange_rate_usd'], ['value' => $request->exchange_rate_usd]);

        return response()->json(['status' => true, 'message' => 'Financial configurations updated successfully.']);
    }
}
