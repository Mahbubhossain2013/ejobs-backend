<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\Notification\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

use App\Models\Company;
use App\Models\Verification;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    /**
     * Register a new user and create an associated profile.
     */
    public function register(Request $request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role' => 'required|in:candidate,employer',
        ];

        if ($request->input('role') === 'candidate') {
            $rules['phone'] = 'required|string|max:255';
        }

        if ($request->input('role') === 'employer') {
            $rules['company_name'] = 'required|string|max:255';
            $rules['phone'] = 'required|string|max:255';
            $rules['address'] = 'required|string|max:255';
            $rules['trade_license_number'] = 'nullable|string|max:255';
            $rules['trade_license_document'] = 'nullable|file|mimes:pdf,docx,jpg,jpeg,png,webp|max:20480';
        }

        $request->validate($rules);

        try {
            return DB::transaction(function () use ($request) {
                // Generate a base unique username from their name
                $baseUsername = Str::slug($request->name);
                $username = $baseUsername;
                $counter = 1;

                while (User::where('username', $username)->exists()) {
                    $username = $baseUsername . $counter;
                    $counter++;
                }

                // Create User
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'username' => $username,
                    'password' => Hash::make($request->password),
                ]);

                // Assign Role
                $user->assignRole($request->role);

                if ($request->role === 'employer') {
                    // Upload Trade License Document
                    $licensePath = null;
                    if ($request->hasFile('trade_license_document')) {
                        $licensePath = $request->file('trade_license_document')->store('licenses', 'public');
                    }

                    // Create UserProfile for Employer
                    UserProfile::create([
                        'user_id' => $user->id,
                        'company_name' => $request->company_name,
                        'phone' => $request->phone,
                        'address' => $request->address,
                        'trade_license_number' => $request->trade_license_number,
                        'trade_license_document' => $licensePath,
                    ]);

                    // Create Company for Employer (for direct dashboard access)
                    Company::create([
                        'user_id' => $user->id,
                        'name' => $request->company_name,
                        'slug' => Str::slug($request->company_name) . '-' . Str::random(6),
                        'location' => $request->address,
                        'trade_license_number' => $request->trade_license_number,
                        'trade_license_document' => $licensePath,
                        'is_verified' => false,
                    ]);
                } else {
                    // Create profile for Candidate with phone
                    UserProfile::create([
                        'user_id' => $user->id,
                        'phone' => $request->phone,
                    ]);
                }

                $token = $user->createToken('auth_token')->plainTextToken;

                $brandName = Setting::where('key', 'site_name')->value('value') ?? config('app.name', 'eJobs');

                try {
                    app(NotificationService::class)->sendNotification(
                        $user,
                        'Welcome to ' . $brandName . '!',
                        'Your account has been created successfully. Start exploring opportunities and building your career today.',
                        'general',
                        '/dashboard/verify'
                    );
                } catch (\Throwable $e) {
                    Log::warning("Welcome notification skipped: " . $e->getMessage());
                }

                // Send welcome email
                $dashboardUrl = config('app.frontend_url', config('app.url', 'http://localhost:3000')) . '/dashboard';
                try {
                    Mail::to($user->email)->queue(new \App\Mail\GenericMail('emails.welcome', [
                        'brandName' => $brandName,
                        'userName' => $user->name,
                        'dashboardUrl' => $dashboardUrl,
                    ], "Welcome to {$brandName}!"));
                } catch (\Throwable $e) {
                    Log::error("Welcome email failed for {$user->email}: " . $e->getMessage());
                }

                // Auto-send email OTP for verification
                try {
                    $emailCode = random_int(100000, 999999);
                    $expiresAt = Carbon::now()->addMinutes(10);

                    Verification::updateOrCreate(
                        [
                            'user_id' => $user->id,
                            'verification_type' => 'email',
                        ],
                        [
                            'email' => $user->email,
                            'otp_code' => $emailCode,
                            'otp_expires_at' => $expiresAt,
                            'status' => 'pending',
                            'ip_address' => $request->ip(),
                            'device_fingerprint' => $request->header('User-Agent'),
                        ]
                    );

                    Mail::to($user->email)->queue(new \App\Mail\GenericMail('emails.otp_verification', [
                        'otpCode' => $emailCode,
                        'userName' => $user->name,
                        'brandName' => $brandName,
                    ], "{$brandName} — Email Verification Code"));
                } catch (\Throwable $e) {
                    Log::error("Auto OTP dispatch failed for {$user->email}: " . $e->getMessage());
                }

                return response()->json([
                    'status' => true,
                    'message' => 'User registered successfully',
                    'token' => $token,
                    'user' => $user->load('profile'),
                    'role' => $request->role
                ]);
            });
        } catch (\Exception $e) {
            Log::error('Registration Error: ' . $e->getMessage());
            return response()->json(['status' => false, 'message' => 'Registration failed: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Authenticate user and issue API Token.
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (!Auth::attempt($request->only('email', 'password'))) {
            // Track failed login logs
            $ip = $request->ip();
            $userAgent = $request->userAgent();
            $fingerprint = hash('sha256', $userAgent . $ip);
            
            \App\Services\Security\SecurityAuditService::logEvent(
                null, $ip, $userAgent, $fingerprint, 'failed_login', 
                15, false, "Failed login attempt with email: '{$request->email}'."
            );

            return response()->json([
                'status' => false,
                'message' => 'Invalid login details'
            ], 401);
        }

        $user = User::where('email', $request->email)->firstOrFail();

        // 1. Audit suspension status before login completions
        if ($user->profile && $user->profile->restriction_status === 'suspended') {
            return response()->json(['status' => false, 'message' => 'Your account has been temporarily suspended due to security violations.'], 403);
        }

        // 2. Check if Two-Factor Authentication is active
        if ($user->has2faEnabled()) {
            $tempToken = \Illuminate\Support\Facades\Crypt::encryptString($user->id . '|' . now()->addMinutes(10)->timestamp);
            
            return response()->json([
                'status' => true,
                'requires_2fa' => true,
                'temp_token' => $tempToken,
                'two_factor_method' => $user->two_factor_method ?? 'totp',
            ]);
        }

        // 3. Normal Authentication: Perform device audits & AI consultant controls
        $audit = \App\Services\Security\SecurityAuditService::auditRequest($user, $request);
        if ($audit['status'] === 'locked') {
            return response()->json([
                'status' => false,
                'message' => $audit['message']
            ], 403);
        }

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'status' => true,
            'token' => $token,
            'user' => $user->load('profile'),
            'role' => $user->getRoleNames()->first()
        ]);
    }

    /**
     * Authenticate and authorize a stateless login via TOTP code challenge
     */
    public function verifyTwoFactor(Request $request)
    {
        $request->validate([
            'temp_token' => 'required|string',
            'code' => 'required|string',
        ]);

        try {
            $decrypted = \Illuminate\Support\Facades\Crypt::decryptString($request->temp_token);
            list($userId, $expiry) = explode('|', $decrypted);

            if (now()->timestamp > $expiry) {
                return response()->json(['status' => false, 'message' => 'The login session has expired. Please try logging in again.'], 422);
            }

            $user = User::findOrFail($userId);
            $code = trim($request->code);

            // 1. Verify standard OTP
            $verified = \App\Services\Security\TwoFactorService::verifyCode($user->two_factor_secret, $code);

            // 2. Fallback: Verify recovery codes
            if (!$verified) {
                $verified = \App\Services\Security\TwoFactorService::verifyRecoveryCode($user, $code);
                if ($verified) {
                    \App\Services\Security\SecurityAuditService::logEvent(
                        $user->id, $request->ip(), $request->userAgent(), hash('sha256', $request->userAgent().$request->ip()),
                        'recovery_code_used', 10, false, 'Recovery code successfully used to authorize session.'
                    );
                }
            }

            if ($verified) {
                // Perform device audits & AI consulting checks
                $audit = \App\Services\Security\SecurityAuditService::auditRequest($user, $request);
                if ($audit['status'] === 'locked') {
                    return response()->json([
                        'status' => false,
                        'message' => $audit['message']
                    ], 403);
                }

                $user->update(['last_2fa_verified_at' => now()]);
                $token = $user->createToken('auth_token')->plainTextToken;

                return response()->json([
                    'status' => true,
                    'token' => $token,
                    'user' => $user->load('profile'),
                    'role' => $user->getRoleNames()->first()
                ]);
            }

            // OTP failure
            \App\Services\Security\SecurityAuditService::logEvent(
                $user->id, $request->ip(), $request->userAgent(), hash('sha256', $request->userAgent().$request->ip()),
                'failed_otp', 30, false, "Failed 2FA code verification attempt: '{$code}'."
            );

            return response()->json(['status' => false, 'message' => 'The provided verification code is incorrect.'], 422);

        } catch (\Exception $e) {
            return response()->json(['status' => false, 'message' => 'Invalid temporary login token.'], 422);
        }
    }

    /**
     * Clear current authenticated session API token.
     */
    public function logout(Request $request)
    {
        $token = $request->user()?->currentAccessToken();
        if ($token) {
            $token->delete();
        }
        return response()->json(['status' => true, 'message' => 'Logged out']);
    }

    /**
     * Live-check if a given username string is available or reserved.
     */
    public function checkUsername(Request $request) 
    {
        $username = Str::slug($request->query('username', ''));
        
        if (strlen($username) < 3) {
            return response()->json(['available' => false, 'message' => 'Too short']);
        }

        // Reserved words that users cannot take
        $reserved = ['admin', 'dashboard', 'login', 'register', 'api', 'settings', 'jobs', 'companies'];
        if (in_array($username, $reserved)) {
            return response()->json(['available' => false, 'message' => 'Reserved word']);
        }

        $exists = User::where('username', $username)->exists();

        if (!$exists) {
            return response()->json(['available' => true, 'username' => $username]);
        }

        // Generate Suggestions if taken
        $suggestions = [
            $username . '-dev',
            $username . strtolower(Str::random(2)),
            'its-' . $username,
            'the-' . $username
        ];

        return response()->json([
            'available' => false,
            'username' => $username,
            'suggestions' => $suggestions
        ]);
    }

    /**
     * Check account type by email (for login page warning)
     */
    public function checkAccountType(Request $request)
    {
        $request->validate(['email' => 'required|email']);
        
        $user = User::where('email', $request->email)->first();
        
        if (!$user) {
            return response()->json(['exists' => false]);
        }
        
        $role = $user->hasRole('employer') ? 'employer' : ($user->hasRole('admin') ? 'admin' : 'candidate');
        
        return response()->json([
            'exists' => true,
            'role' => $role,
        ]);
    }

    /**
     * Admin impersonate candidate or employer (SSO Login)
     */
    public function impersonate(Request $request, $userId)
    {
        // 1. Double check permission (Must have role:admin)
        $admin = Auth::user();
        if (!$admin || !$admin->hasAnyRole(['super_admin', 'admin'])) {
            return response()->json(['status' => false, 'message' => 'Unauthorized. Admin role required.'], 403);
        }

        // 2. Fetch the target user
        $targetUser = User::findOrFail($userId);

        // 3. Prevent admin impersonating other admin
        if ($targetUser->hasAnyRole(['super_admin', 'admin'])) {
            return response()->json(['status' => false, 'message' => 'Cannot impersonate another administrator.'], 403);
        }

        // 4. Create token for target user
        $token = $targetUser->createToken('admin_sso_token')->plainTextToken;
        $role = $targetUser->getRoleNames()->first() ?? 'candidate';

        return response()->json([
            'status' => true,
            'token' => $token,
            'role' => $role,
            'user' => $targetUser->load('profile'),
            'message' => "Successfully generated impersonation token for {$targetUser->name}"
        ]);
    }
}