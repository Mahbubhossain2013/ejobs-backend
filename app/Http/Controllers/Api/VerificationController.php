<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Verification;
use App\Models\Company;
use App\Models\Setting;
use App\Models\User;
use App\Models\Invoice;
use App\Services\Security\AiVerificationService;
use App\Services\Billing\InvoiceService;
use App\Services\Notification\NotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VerificationController extends Controller
{
    /**
     * Get all current verification statuses for the authenticated user
     */
    public function status(Request $request)
    {
        try {
            $user = $request->user()->load(['profile', 'company']);
            
            $verifications = collect();
            try {
                $verifications = Verification::where('user_id', $user->id)
                    ->orderBy('created_at', 'desc')
                    ->get();
            } catch (\Throwable $e) {
                Log::warning('Verification query failed: ' . $e->getMessage());
            }

            $nid = $verifications->where('verification_type', 'nid')->first();
            $phone = $verifications->where('verification_type', 'phone')->first();
            $email = $verifications->where('verification_type', 'email')->first();
            $employer = $verifications->where('verification_type', 'employer')->first();

            // Self-repair badges (wrapped in try-catch to avoid breaking the endpoint)
            try {
                $badgeChanged = false;
                if ($nid && $nid->status === 'approved' && !$user->hasBadge('nid_verified')) {
                    $user->assignBadge('nid_verified', 'system');
                    $badgeChanged = true;
                }
                if ($phone && $phone->status === 'approved' && !$user->hasBadge('phone_verified')) {
                    $user->assignBadge('phone_verified', 'system');
                    $badgeChanged = true;
                }
                if ($email && $email->status === 'approved' && !$user->hasBadge('email_verified')) {
                    $user->assignBadge('email_verified', 'system');
                    $badgeChanged = true;
                }
                if ($employer && $employer->status === 'approved' && !$user->hasBadge('employer_verified')) {
                    $user->assignBadge('employer_verified', 'system');
                    $badgeChanged = true;
                }
                if ($user->hasBadge('phone_verified') && $user->hasBadge('nid_verified') && !$user->hasBadge('verified')) {
                    $user->assignBadge('verified', 'system');
                    $badgeChanged = true;
                }
                if ($badgeChanged) {
                    $user->unsetRelation('badges');
                }
            } catch (\Throwable $e) {
                Log::warning('Badge sync failed: ' . $e->getMessage());
            }

            $getBadge = fn($key) => false;
            try {
                $getBadge = fn($key) => $user->hasBadge($key);
            } catch (\Throwable $e) {}

            $candidateFee = (float)(Setting::where('key', 'verification_fee_candidate')->value('value') ?? '20');
            $employerFee = (float)(Setting::where('key', 'verification_fee_employer')->value('value') ?? '50');

            return response()->json([
                'status' => true,
                'summary' => [
                    'nid_verified' => $getBadge('nid_verified'),
                    'phone_verified' => $getBadge('phone_verified'),
                    'email_verified' => $getBadge('email_verified'),
                    'employer_verified' => $getBadge('employer_verified'),
                    'is_fully_verified' => $getBadge('verified'),
                ],
                'details' => [
                    'nid' => $nid,
                    'phone' => $phone,
                    'email' => $email,
                    'employer' => $employer,
                ],
                'fees' => [
                    'candidate' => $candidateFee,
                    'employer' => $employerFee,
                ]
            ]);
        } catch (\Throwable $e) {
            Log::error('Verification Status Load Error: ' . $e->getMessage());
            return response()->json([
                'status' => true,
                'summary' => [
                    'nid_verified' => false,
                    'phone_verified' => false,
                    'email_verified' => false,
                    'employer_verified' => false,
                    'is_fully_verified' => false,
                ],
                'details' => ['nid' => null, 'phone' => null, 'email' => null, 'employer' => null],
                'fees' => ['candidate' => 20, 'employer' => 50],
            ]);
        }
    }

    /**
     * Submit NID details and documents for automated AI analysis
     */
    public function submitNid(Request $request)
    {
        // Check if verification is globally enabled
        $globalEnabled = Setting::where('key', 'verification_enabled')->value('value') ?? '1';
        if ($globalEnabled !== '1' && $globalEnabled !== 'true') {
            return response()->json(['status' => false, 'message' => 'Verification system is temporarily offline.'], 400);
        }

        $user = $request->user();

        // 1. Validation Rules based on Admin settings
        $requireFront = Setting::where('key', 'nid_require_front')->value('value') ?? '1';
        $requireBack = Setting::where('key', 'nid_require_back')->value('value') ?? '1';

        $rules = [
            'nid_number' => 'required|string|min:10|max:17',
            'dob' => 'required|date',
        ];

        if ($requireFront === '1' || $requireFront === 'true') {
            $rules['document_front'] = 'required|image|mimes:jpeg,png,jpg,webp|max:20480';
        }
        if ($requireBack === '1' || $requireBack === 'true') {
            $rules['document_back'] = 'required|image|mimes:jpeg,png,jpg,webp|max:20480';
        } else {
            $rules['document_back'] = 'nullable|image|mimes:jpeg,png,jpg,webp|max:20480';
        }

        $request->validate($rules);

        // Prevent resubmission if already verified
        if ($user->hasBadge('nid_verified')) {
            return response()->json(['status' => false, 'message' => 'Your NID identity is already verified.'], 400);
        }

        // Ensure Mobile OTP Verification is completed before processing NID
        if (!$user->hasBadge('phone_verified')) {
            return response()->json(['status' => false, 'message' => 'Mobile OTP verification must be completed before NID verification.'], 400);
        }

        // Prevent resubmission if pending review
        $pending = Verification::where('user_id', $user->id)
            ->where('verification_type', 'nid')
            ->where('status', 'pending')
            ->first();

        if ($pending) {
            return response()->json(['status' => false, 'message' => 'You already have a pending NID submission under review.'], 400);
        }

        // 2. Billing: Candidate verification fee deduction
        $feeAmount = (float)(Setting::where('key', 'verification_fee_candidate')->value('value') ?? '20');
        
        if (!$user->wallet || $user->wallet->balance < $feeAmount) {
            return response()->json([
                'status' => false,
                'insufficient_balance' => true,
                'message' => "Insufficient balance. Identity verification costs {$feeAmount} BDT. Please add funds to your wallet."
            ], 402);
        }

        return DB::transaction(function () use ($request, $user, $feeAmount) {
            try {
                $file = $request->file('trade_license_document');
                $ext = strtolower($file->getClientOriginalExtension());
                if (in_array($ext, ['jpg','jpeg','png','gif','webp'])) {
                    $file = \App\Services\Media\ImageOptimizerService::convertToWebp($file);
                }
                $path = $file->store('secure_verifications', 'public');

                $user->refresh();
                if (!$user->wallet || $user->wallet->balance < $feeAmount) {
                    Storage::disk('public')->delete($path);
                    return response()->json([
                        'status' => false,
                        'message' => 'Insufficient balance. Please add funds and try again.',
                    ], 402);
                }

                $invoiceService = app(InvoiceService::class);
                $invoice = $invoiceService->createInvoice([
                    'user_id' => $user->id,
                    'type' => 'manual',
                    'currency_code' => 'BDT',
                    'subtotal' => $feeAmount,
                    'total_amount' => $feeAmount,
                    'amount_due' => $feeAmount,
                    'billing_name' => $user->name,
                    'billing_email' => $user->email,
                    'notes' => 'Employer Business Verification Fee (Company Verification)',
                ], [[
                    'description' => 'Company verification trade license review fee',
                    'quantity' => 1,
                    'unit_price' => $feeAmount,
                ]]);

                $invoice->applyWalletPayment();

                $company = Company::where('user_id', $user->id)->first();
                if (!$company) {
                    $company = Company::create([
                        'user_id' => $user->id,
                        'name' => $request->company_name,
                    ]);
                }

                $company->update([
                    'trade_license_number' => $request->trade_license_number,
                    'trade_license_document' => $path,
                ]);

                $verification = Verification::create([
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'verification_type' => 'employer',
                    'document_type' => 'trade_license',
                    'document_path' => $path,
                    'notes' => 'Company: ' . $request->company_name . ', Trade License: ' . $request->trade_license_number . ', Contact: ' . $request->company_phone . ', Email: ' . $request->business_email,
                    'status' => 'pending',
                    'ip_address' => $request->ip(),
                    'device_fingerprint' => $request->header('User-Agent'),
                ]);

                app(NotificationService::class)->sendNotification(
                    $user,
                    'Company Verification Submitted',
                    'Your trade license has been uploaded successfully. Admin review is under way.',
                    'warning',
                    '/employer/dashboard'
                );

                return response()->json([
                    'status' => true,
                    'message' => 'Company credentials and trade license submitted successfully for admin review.',
                    'verification' => $verification
                ]);
            } catch (\Throwable $e) {
                Log::error('Employer verification submit failed: ' . $e->getMessage() . $e->getTraceAsString());
                return response()->json([
                    'status' => false,
                    'message' => config('app.debug') ? $e->getMessage() : 'Submission failed. Please try again.',
                ], 500);
            }
        });
    }

    /**
     * Send numeric 6-digit OTP verification code to user phone
     */
    public function requestPhoneOtp(Request $request)
    {
        $request->validate([
            'phone' => 'required|string|regex:/^\+?[0-9]{10,15}$/',
        ]);

        $user = $request->user();
        $phone = $request->phone;

        // Generate 6-digit random code
        $code = random_int(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(5);

        // Store OTP entry in verification attempts
        Verification::updateOrCreate(
            [
                'user_id' => $user->id,
                'verification_type' => 'phone',
            ],
            [
                'phone' => $phone,
                'otp_code' => $code,
                'otp_expires_at' => $expiresAt,
                'status' => 'pending',
                'ip_address' => $request->ip(),
                'device_fingerprint' => $request->header('User-Agent'),
            ]
        );

        // Send SMS via SmsService
        $smsService = resolve(\App\Services\Notification\SmsService::class);
        $brandName = Setting::where('key', 'site_name')->value('value') ?? 'JobBazar';
        $smsMessage = "Your {$brandName} OTP is {$code}";
        $smsService->sendSms($phone, $smsMessage);

        // Keep local log for verification trace
        Log::info("OTP dispatch triggered. SMS: '{$smsMessage}' sent to {$phone}");

        return response()->json([
            'status' => true,
            'message' => '6-digit SMS OTP verification code sent successfully.',
        ]);
    }

    /**
     * Validate numeric 6-digit OTP phone code and award phone badge
     */
    public function confirmPhoneOtp(Request $request)
    {
        $request->validate([
            'otp_code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        $verification = Verification::where('user_id', $user->id)
            ->where('verification_type', 'phone')
            ->where('status', 'pending')
            ->first();

        if (!$verification) {
            return response()->json(['status' => false, 'message' => 'No active phone OTP verification found.'], 400);
        }

        if (Carbon::now()->gt($verification->otp_expires_at)) {
            return response()->json(['status' => false, 'message' => 'OTP has expired. Please request a new code.'], 400);
        }

        if (!hash_equals((string) $verification->otp_code, (string) $request->otp_code)) {
            return response()->json(['status' => false, 'message' => 'Incorrect 6-digit code. Please check and retry.'], 400);
        }

        // OTP verified successfully
        $verification->update([
            'status' => 'approved',
            'verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        // Update user phone in profile
        if ($user->profile) {
            $user->profile->update(['phone' => $verification->phone]);
        }

        $user->assignBadge('phone_verified', 'system');

        // Check if NID is also verified to award full Identity badge
        if ($user->hasBadge('nid_verified')) {
            $user->assignBadge('verified', 'system');
        }

        $siteName = Setting::where('key', 'site_name')->value('value') ?? config('app.name', 'eJobs');

        app(NotificationService::class)->sendNotification(
            $user, 
            '✅ Phone Verified successfully!', 
            'Your mobile number has been verified. Welcome to a secure ' . $siteName . ' network!', 
            'success', 
            '/dashboard'
        );

        return response()->json([
            'status' => true,
            'message' => 'Phone verified successfully and Phone badge awarded!'
        ]);
    }

    /**
     * Send numeric 6-digit OTP verification code to user email
     */
    public function requestEmailOtp(Request $request)
    {
        $user = $request->user();
        $email = $user->email;

        $code = random_int(100000, 999999);
        $expiresAt = Carbon::now()->addMinutes(10);

        Verification::updateOrCreate(
            [
                'user_id' => $user->id,
                'verification_type' => 'email',
            ],
            [
                'email' => $email,
                'otp_code' => $code,
                'otp_expires_at' => $expiresAt,
                'status' => 'pending',
                'ip_address' => $request->ip(),
                'device_fingerprint' => $request->header('User-Agent'),
            ]
        );

        // Send actual email via configured SMTP mailer
        $brandName = Setting::where('key', 'site_name')->value('value') ?? config('app.name', 'JobBazar');
        try {
            \Illuminate\Support\Facades\Mail::send(
                'emails.otp_verification',
                [
                    'otpCode' => $code,
                    'userName' => $user->name,
                    'brandName' => $brandName,
                ],
                function ($message) use ($email, $brandName) {
                    $message->to($email)
                            ->subject("{$brandName} — Email Verification Code");
                }
            );
            Log::info("Email OTP dispatched to {$email} via SMTP.");
        } catch (\Throwable $e) {
            Log::error("Email OTP dispatch failed to {$email}: " . $e->getMessage());
        }

        return response()->json([
            'status' => true,
            'message' => 'Email verification code dispatched successfully.',
        ]);
    }

    /**
     * Confirm email OTP verification and award verified badge
     */
    public function confirmEmailOtp(Request $request)
    {
        $request->validate([
            'otp_code' => 'required|string|size:6',
        ]);

        $user = $request->user();

        $verification = Verification::where('user_id', $user->id)
            ->where('verification_type', 'email')
            ->where('status', 'pending')
            ->first();

        if (!$verification) {
            return response()->json(['status' => false, 'message' => 'No active email verification request found.'], 400);
        }

        if (Carbon::now()->gt($verification->otp_expires_at)) {
            return response()->json(['status' => false, 'message' => 'Code expired. Please trigger a new email code.'], 400);
        }

        if (!hash_equals((string) $verification->otp_code, (string) $request->otp_code)) {
            return response()->json(['status' => false, 'message' => 'Incorrect code. Please check and retry.'], 400);
        }

        $verification->update([
            'status' => 'approved',
            'verified_at' => now(),
            'otp_code' => null,
            'otp_expires_at' => null,
        ]);

        // Mark native Laravel email verified field
        $user->forceFill(['email_verified_at' => now()])->save();

        $user->assignBadge('email_verified', 'system');

        app(NotificationService::class)->sendNotification(
            $user, 
            '📧 Email Verified!', 
            'Your primary email account is verified successfully.', 
            'success', 
            '/dashboard'
        );

        return response()->json([
            'status' => true,
            'message' => 'Email address verified and badge awarded successfully!'
        ]);
    }

    /**
     * Submit business credentials and trade license for employer verification
     */
    public function submitEmployer(Request $request)
    {
        $request->validate([
            'company_name' => 'required|string|max:100',
            'trade_license_number' => 'required|string|min:5|max:30',
            'company_phone' => 'required|string',
            'business_email' => 'required|email',
            'trade_license_document' => 'required|file|mimes:pdf,jpg,jpeg,png,webp|max:20480',
        ]);

        $user = $request->user();

        // Must be employer role to proceed
        if (!$user->hasRole('employer')) {
            return response()->json(['status' => false, 'message' => 'Only employers can apply for company verification.'], 403);
        }

        // Prevent double verification
        if ($user->hasBadge('employer_verified')) {
            return response()->json(['status' => false, 'message' => 'Your company details are already verified.'], 400);
        }

        $pending = Verification::where('user_id', $user->id)
            ->where('verification_type', 'employer')
            ->where('status', 'pending')
            ->first();

        if ($pending) {
            return response()->json(['status' => false, 'message' => 'Company verification review is already in progress.'], 400);
        }

        $feeAmount = (float)(Setting::where('key', 'verification_fee_employer')->value('value') ?? '50');

        if (!$user->wallet || $user->wallet->balance < $feeAmount) {
            return response()->json([
                'status' => false,
                'insufficient_balance' => true,
                'message' => "Insufficient balance. Company verification costs {$feeAmount} BDT. Please add funds."
            ], 402);
        }

        return DB::transaction(function () use ($request, $user, $feeAmount) {
            try {
                $licenseFile = $request->file('trade_license_document');
                $licenseExt = strtolower($licenseFile->getClientOriginalExtension());
                if (in_array($licenseExt, ['jpg','jpeg','png','gif','webp'])) {
                    $licenseFile = \App\Services\Media\ImageOptimizerService::convertToWebp($licenseFile);
                }
                $filePath = $licenseFile->store('secure_verifications', 'public');

                $user->refresh();
                if (!$user->wallet || $user->wallet->balance < $feeAmount) {
                    Storage::disk('public')->delete($filePath);
                    return response()->json(['status' => false, 'message' => 'Insufficient balance. Please add funds and try again.'], 402);
                }

                $invoiceService = app(InvoiceService::class);
                $invoice = $invoiceService->createInvoice([
                    'user_id' => $user->id,
                    'type' => 'manual',
                    'currency_code' => 'BDT',
                    'subtotal' => $feeAmount,
                    'total_amount' => $feeAmount,
                    'amount_due' => $feeAmount,
                    'billing_name' => $user->name,
                    'billing_email' => $user->email,
                    'notes' => "Employer Business Verification Fee (Company Verification)",
                ], [
                    [
                        'description' => "Company verification trade license review fee",
                        'quantity' => 1,
                        'unit_price' => $feeAmount,
                    ]
                ]);

                $invoiceService->applyWalletPayment($invoice);

                $company = Company::where('user_id', $user->id)->first();
                if (!$company) {
                    $company = Company::create([
                        'user_id' => $user->id,
                        'name' => $request->company_name,
                    ]);
                }

                $company->update([
                    'trade_license_number' => $request->trade_license_number,
                    'trade_license_document' => $filePath,
                ]);

                $verification = Verification::create([
                    'user_id' => $user->id,
                    'company_id' => $company->id,
                    'verification_type' => 'employer',
                    'document_type' => 'trade_license',
                    'document_path' => $filePath,
                    'notes' => "Company: {$request->company_name}, Trade License: {$request->trade_license_number}, Contact: {$request->company_phone}, Email: {$request->business_email}",
                    'status' => 'pending',
                    'ip_address' => $request->ip(),
                    'device_fingerprint' => $request->header('User-Agent'),
                ]);

                app(NotificationService::class)->sendNotification(
                    $user,
                    'Company Verification Submitted',
                    'Your trade license has been uploaded successfully. Admin review is under way.',
                    'warning',
                    '/employer/dashboard'
                );

                return response()->json([
                    'status' => true,
                    'message' => 'Company credentials and trade license submitted successfully for admin review.',
                    'verification' => $verification
                ]);
            } catch (\Throwable $e) {
                Log::error('Employer verification submit failed: ' . $e->getMessage() . "\n" . $e->getTraceAsString());
                return response()->json([
                    'status' => false,
                    'message' => config('app.debug') ? $e->getMessage() : 'Submission failed. Please try again.',
                ], 500);
            }
        });
    }
}
