<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

class SslCertificateConfig extends Model
{
    protected $fillable = [
        'user_id', 'name', 'domain', 'cert_type', 'format',
        'certificate_path', 'private_key_path', 'chain_path',
        'passphrase', 'passphrase_algorithm', 'issued_at',
        'expires_at', 'renewal_at', 'auto_renew', 'is_active',
        'validation_result', 'metadata',
    ];

    protected $hidden = [
        'passphrase',
    ];

    protected $casts = [
        'issued_at' => 'datetime',
        'expires_at' => 'datetime',
        'renewal_at' => 'datetime',
        'auto_renew' => 'boolean',
        'is_active' => 'boolean',
        'validation_result' => 'array',
        'metadata' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForDomain($query, string $domain)
    {
        return $query->where('domain', $domain);
    }

    public function scopeExpiringSoon($query, int $days = 30)
    {
        return $query->where('expires_at', '<=', now()->addDays($days))
            ->where('expires_at', '>', now());
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isExpiringSoon(int $days = 30): bool
    {
        return $this->expires_at &&
            $this->expires_at->isFuture() &&
            $this->expires_at->lte(now()->addDays($days));
    }

    public function needsRenewal(): bool
    {
        return $this->auto_renew && $this->isExpiringSoon(30);
    }

    public function setPassphrase(string $passphrase): void
    {
        $this->update([
            'passphrase' => Crypt::encryptString($passphrase),
        ]);
    }

    public function getPassphrase(): ?string
    {
        if (!$this->passphrase) {
            return null;
        }

        try {
            return Crypt::decryptString($this->passphrase);
        } catch (\Exception $e) {
            Log::error("Failed to decrypt SSL passphrase for config {$this->id}: " . $e->getMessage());
            return null;
        }
    }

    public function validateCertificate(): array
    {
        $result = [
            'is_valid' => false,
            'errors' => [],
            'warnings' => [],
            'details' => [],
        ];

        if (!file_exists($this->certificate_path)) {
            $result['errors'][] = "Certificate file not found: {$this->certificate_path}";
            return $result;
        }

        $certContent = @file_get_contents($this->certificate_path);
        if ($certContent === false) {
            $result['errors'][] = "Unable to read certificate file";
            return $result;
        }

        $certData = @openssl_x509_parse($certContent);
        if (!$certData) {
            $result['errors'][] = "Invalid certificate format or content";
            return $result;
        }

        $result['details'] = [
            'subject' => $certData['subject']['CN'] ?? 'Unknown',
            'issuer' => $certData['issuer']['CN'] ?? 'Unknown',
            'serial_number' => $certData['serialNumberHex'] ?? 'Unknown',
            'valid_from' => date('Y-m-d H:i:s', $certData['validFrom_time_t']),
            'valid_to' => date('Y-m-d H:i:s', $certData['validTo_time_t']),
            'signature_type' => $certData['signatureTypeSN'] ?? 'Unknown',
            'key_size' => $certData['key_size'] ?? 'Unknown',
        ];

        if (isset($certData['validFrom_time_t']) && $certData['validFrom_time_t'] > time()) {
            $result['errors'][] = "Certificate is not yet valid";
        }

        if (isset($certData['validTo_time_t']) && $certData['validTo_time_t'] < time()) {
            $result['errors'][] = "Certificate has expired";
        }

        if (isset($certData['validTo_time_t']) && $certData['validTo_time_t'] < strtotime('+30 days')) {
            $result['warnings'][] = "Certificate expires within 30 days";
        }

        if ($this->private_key_path && !file_exists($this->private_key_path)) {
            $result['warnings'][] = "Private key file not found: {$this->private_key_path}";
        }

        if ($this->chain_path && !file_exists($this->chain_path)) {
            $result['warnings'][] = "Chain file not found: {$this->chain_path}";
        }

        if ($this->private_key_path && file_exists($this->private_key_path)) {
            $passphrase = $this->getPassphrase();
            $key = @openssl_pkey_get_private(file_get_contents($this->private_key_path), $passphrase);
            if (!$key) {
                $result['errors'][] = "Private key is invalid or passphrase is incorrect";
            }
        }

        $result['is_valid'] = empty($result['errors']);

        $this->update(['validation_result' => $result]);

        return $result;
    }

    public static function getExpiringCertificates(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return static::active()
            ->where('expires_at', '<=', now()->addDays($days))
            ->where('expires_at', '>', now())
            ->get();
    }
}
