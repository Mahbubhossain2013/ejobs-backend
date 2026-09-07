<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;
use Filament\Models\Contracts\FilamentUser;
use Filament\Panel;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use App\Traits\HasSubscriptions;

class User extends Authenticatable implements FilamentUser
{
    use HasApiTokens, HasFactory, Notifiable, HasSubscriptions;
    use HasRoles {
        hasPermissionTo as protected traitHasPermissionTo;
    }

    protected $with = ['profile'];

    protected $appends = ['avatar'];

    protected $fillable = [
        'name',
        'username', // Added
        'email',
        'google_id',
        'facebook_id',
        'avatar',
        'provider',
        'password',
        'currency_preference',
        'two_factor_confirmed_at',
        'two_factor_enabled',
        'last_2fa_verified_at',
        'trusted_devices',
        'blocked_users',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'two_factor_secret',
        'two_factor_recovery_codes',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'two_factor_secret' => 'encrypted',
            'two_factor_recovery_codes' => 'encrypted',
            'two_factor_confirmed_at' => 'datetime',
            'two_factor_enabled' => 'boolean',
            'last_2fa_verified_at' => 'datetime',
            'trusted_devices' => 'array',
            'blocked_users' => 'array',
        ];
    }

    /**
     * Check if user has Two-Factor Authentication enabled and confirmed
     */
    public function has2faEnabled(): bool
    {
        return $this->two_factor_enabled && $this->two_factor_confirmed_at !== null;
    }

    /**
     * Generate a new set of 8 random recovery codes
     */
    public function generateRecoveryCodes(): array
    {
        $codes = [];
        for ($i = 0; $i < 8; $i++) {
            $codes[] = Str::random(10) . '-' . Str::random(10);
        }

        // Save recovery codes as hashed strings for high security
        $hashedCodes = array_map(fn($code) => password_hash($code, PASSWORD_BCRYPT), $codes);
        $this->update([
            'two_factor_recovery_codes' => json_encode($hashedCodes)
        ]);

        return $codes;
    }

    /**
     * Reserved usernames that cannot be registered
     */
    public static function reservedUsernames(): array
    {
        return [
            'admin',
            'employer',
            'login',
            'register',
            'jobs',
            'api',
            'dashboard',
            'settings',
            'categories',
            'profile',
            'search',
            'company',
            'onipay'
        ];
    }

    public function profile(): HasOne
    {
        return $this->hasOne(UserProfile::class, 'user_id');
    }

    public function getAvatarAttribute(): ?string
    {
        $avatar = $this->getRawOriginal('avatar');
        if (!empty($avatar)) return $avatar;
        if ($this->relationLoaded('profile') && !empty($this->profile?->avatar)) return $this->profile->avatar;
        if ($this->relationLoaded('company') && !empty($this->company?->logo)) return $this->company->logo;
        return null;
    }
    public function company(): HasOne
    {
        return $this->hasOne(Company::class, 'user_id');
    }
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }
    public function withdrawals(): HasMany
    {
        return $this->hasMany(Withdrawal::class);
    }
    public function deposits(): HasMany
    {
        return $this->hasMany(Deposit::class);
    }

    public function badges(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        return $this->belongsToMany(Badge::class, 'badge_user')
            ->withPivot('assigned_by', 'expires_at', 'is_visible', 'earned_at')
            ->withTimestamps();
    }

    public function activeBadges()
    {
        try {
            return $this->badges()
                ->where('is_active', true)
                ->where(function ($query) {
                    $query->whereNull('expires_at')
                        ->orWhere('expires_at', '>', now());
                })
                ->wherePivot('is_visible', true)
                ->orderBy('priority', 'desc')
                ->get()
                ->map(function ($badge) {
                    return [
                        'id' => $badge->id,
                        'name' => $badge->name,
                        'badge_key' => $badge->badge_key,
                        'description' => $badge->description,
                        'color' => $badge->color,
                        'icon' => $badge->icon,
                        'icon_type' => $badge->icon_type ?? null,
                        'icon_url' => ($badge->icon_path ?? null) ? asset('storage/' . $badge->icon_path) : null,
                        'priority' => $badge->priority ?? 0,
                        'assigned_by' => $badge->pivot->assigned_by ?? null,
                        'earned_at' => $badge->pivot->earned_at ? (\Illuminate\Support\Carbon::parse($badge->pivot->earned_at)->toIso8601String()) : ($badge->pivot->created_at ? $badge->pivot->created_at->toIso8601String() : null),
                        'expires_at' => $badge->pivot->expires_at ? $badge->pivot->expires_at->toIso8601String() : null,
                        'is_visible' => (bool)$badge->pivot->is_visible,
                    ];
                });
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('activeBadges failed: ' . $e->getMessage());
            return collect();
        }
    }

    public function hasBadge($key)
    {
        return $this->badges()
            ->where('badge_key', $key)
            ->where('is_active', true)
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>', now());
            })
            ->exists();
    }

    public function verifications(): \Illuminate\Database\Eloquent\Relations\HasMany
    {
        return $this->hasMany(Verification::class);
    }

    /**
     * Auto-generate username on creation if not provided
     */
    protected static function boot()
    {
        parent::boot();
        static::creating(function ($user) {
            if (!$user->username) {
                $base = Str::slug($user->name);
                $username = $base;
                $i = 1;
                while (self::where('username', $username)->exists() || in_array($username, self::reservedUsernames())) {
                    $username = $base . $i++;
                }
                $user->username = $username;
            }
        });
    }

    public function savedJobs()
    {
        return $this->belongsToMany(Job::class, 'saved_jobs');
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class)->latest();
    }

    public function billingProfile(): HasOne
    {
        return $this->hasOne(BillingProfile::class);
    }

    public function supportTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'user_id')->latest();
    }

    public function assignedTickets(): HasMany
    {
        return $this->hasMany(SupportTicket::class, 'assigned_admin_id')->latest();
    }

    public function followedCompanies(): HasMany
    {
        return $this->hasMany(CompanyFollow::class);
    }

    public function companyReviews(): HasMany
    {
        return $this->hasMany(CompanyReview::class)->latest();
    }

    public function trustScore(): HasOne
    {
        return $this->hasOne(UserTrustScore::class);
    }

    public function moderationLogs(): HasMany
    {
        return $this->hasMany(AiModerationLog::class);
    }

    public function followers(): HasMany
    {
        return $this->hasMany(UserFollow::class, 'following_id');
    }

    public function following(): HasMany
    {
        return $this->hasMany(UserFollow::class, 'follower_id');
    }

    public function canAccessPanel(Panel $panel): bool
    {
        if ($this->id === 1 || in_array($this->email, ['admin@ejobs.bd', 'super-admin@ejobs.bd', 'just.mahub02@gmail.com', 'just.mahub01@gmail.com', 'admin@jobportal.com'])) {
            return true;
        }
        return in_array($this->admin_role, ['super_admin', 'admin', 'manager', 'viewer'])
            || $this->hasAnyRole(['admin', 'super_admin', 'super-admin']);
    }

    public function hasPermissionTo($permission, $guardName = null): bool
    {
        if (in_array($this->admin_role, ['super_admin', 'admin'])) {
            return true;
        }

        try {
            return $this->traitHasPermissionTo($permission, $guardName);
        } catch (\Throwable $e) {
            return false;
        }
    }
}
