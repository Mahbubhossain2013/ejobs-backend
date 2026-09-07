<?php

namespace App\Services;

use App\Models\SslCertificateConfig;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class SslManagerService
{
    public function storeConfig(User $user, array $data): SslCertificateConfig
    {
        $this->validateCertificatePaths($data);

        $config = SslCertificateConfig::create([
            'user_id' => $user->id,
            'name' => $data['name'],
            'domain' => $data['domain'],
            'cert_type' => $data['cert_type'] ?? 'letsencrypt',
            'format' => $data['format'] ?? 'pem',
            'certificate_path' => $data['certificate_path'],
            'private_key_path' => $data['private_key_path'] ?? null,
            'chain_path' => $data['chain_path'] ?? null,
            'passphrase' => isset($data['passphrase']) ? Crypt::encryptString($data['passphrase']) : null,
            'passphrase_algorithm' => $data['passphrase_algorithm'] ?? 'sha256',
            'issued_at' => $data['issued_at'] ?? null,
            'expires_at' => $data['expires_at'] ?? null,
            'renewal_at' => $data['renewal_at'] ?? null,
            'auto_renew' => $data['auto_renew'] ?? true,
            'is_active' => $data['is_active'] ?? true,
        ]);

        $validation = $config->validateCertificate();
        if (!$validation['is_valid']) {
            Log::warning("SSL certificate stored but validation failed", [
                'config_id' => $config->id,
                'errors' => $validation['errors'],
            ]);
        }

        return $config;
    }

    public function updateConfig(SslCertificateConfig $config, array $data): SslCertificateConfig
    {
        if (isset($data['certificate_path']) || isset($data['private_key_path'])) {
            $mergedData = array_merge([
                'certificate_path' => $config->certificate_path,
                'private_key_path' => $config->private_key_path,
            ], $data);
            $this->validateCertificatePaths($mergedData);
        }

        $updateData = collect($data)->only([
            'name', 'domain', 'cert_type', 'format',
            'certificate_path', 'private_key_path', 'chain_path',
            'passphrase_algorithm', 'issued_at', 'expires_at',
            'renewal_at', 'auto_renew', 'is_active',
        ])->toArray();

        if (isset($data['passphrase'])) {
            $updateData['passphrase'] = Crypt::encryptString($data['passphrase']);
        }

        $config->update($updateData);
        $config->refresh();

        $validation = $config->validateCertificate();
        if (!$validation['is_valid']) {
            Log::warning("SSL certificate updated but validation failed", [
                'config_id' => $config->id,
                'errors' => $validation['errors'],
            ]);
        }

        return $config;
    }

    public function deleteConfig(SslCertificateConfig $config): bool
    {
        return $config->delete();
    }

    public function getConfig(int $id): ?SslCertificateConfig
    {
        return SslCertificateConfig::with('user')->find($id);
    }

    public function getUserConfigs(User $user): \Illuminate\Database\Eloquent\Collection
    {
        return SslCertificateConfig::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->get();
    }

    public function getActiveConfigForDomain(string $domain): ?SslCertificateConfig
    {
        return SslCertificateConfig::active()
            ->forDomain($domain)
            ->orderByDesc('created_at')
            ->first();
    }

    public function validateConfig(SslCertificateConfig $config): array
    {
        return $config->validateCertificate();
    }

    public function getExpiringCertificates(int $days = 30): \Illuminate\Database\Eloquent\Collection
    {
        return SslCertificateConfig::getExpiringCertificates($days);
    }

    public function getCertificateInfo(SslCertificateConfig $config): array
    {
        $result = [
            'config_id' => $config->id,
            'name' => $config->name,
            'domain' => $config->domain,
            'cert_type' => $config->cert_type,
            'format' => $config->format,
            'is_active' => $config->is_active,
            'auto_renew' => $config->auto_renew,
            'issued_at' => $config->issued_at?->toISOString(),
            'expires_at' => $config->expires_at?->toISOString(),
            'renewal_at' => $config->renewal_at?->toISOString(),
            'is_expired' => $config->isExpired(),
            'is_expiring_soon' => $config->isExpiringSoon(),
            'needs_renewal' => $config->needsRenewal(),
            'has_private_key' => !empty($config->private_key_path),
            'has_chain' => !empty($config->chain_path),
            'has_passphrase' => !empty($config->passphrase),
            'validation' => $config->validation_result,
        ];

        return $result;
    }

    public function exportConfig(SslCertificateConfig $config): array
    {
        return [
            'name' => $config->name,
            'domain' => $config->domain,
            'cert_type' => $config->cert_type,
            'format' => $config->format,
            'certificate_path' => $config->certificate_path,
            'private_key_path' => $config->private_key_path,
            'chain_path' => $config->chain_path,
            'passphrase_algorithm' => $config->passphrase_algorithm,
            'issued_at' => $config->issued_at?->toISOString(),
            'expires_at' => $config->expires_at?->toISOString(),
            'renewal_at' => $config->renewal_at?->toISOString(),
            'auto_renew' => $config->auto_renew,
            'is_active' => $config->is_active,
            'metadata' => $config->metadata,
        ];
    }

    protected function validateCertificatePaths(array $data): void
    {
        $certPath = $data['certificate_path'] ?? null;
        $keyPath = $data['private_key_path'] ?? null;
        $chainPath = $data['chain_path'] ?? null;

        if ($certPath && !file_exists($certPath)) {
            $storagePath = storage_path('app/' . $certPath);
            $publicPath = storage_path('app/public/' . $certPath);

            if (!file_exists($storagePath) && !file_exists($publicPath)) {
                throw new \InvalidArgumentException("Certificate file not found: {$certPath}");
            }
        }

        if ($keyPath && !file_exists($keyPath)) {
            $storagePath = storage_path('app/' . $keyPath);
            $publicPath = storage_path('app/public/' . $keyPath);

            if (!file_exists($storagePath) && !file_exists($publicPath)) {
                throw new \InvalidArgumentException("Private key file not found: {$keyPath}");
            }
        }

        if ($chainPath && !file_exists($chainPath)) {
            $storagePath = storage_path('app/' . $chainPath);
            $publicPath = storage_path('app/public/' . $chainPath);

            if (!file_exists($storagePath) && !file_exists($publicPath)) {
                throw new \InvalidArgumentException("Chain file not found: {$chainPath}");
            }
        }

        if ($certPath) {
            $realCertPath = $this->resolvePath($certPath);
            if ($realCertPath && !is_readable($realCertPath)) {
                throw new \InvalidArgumentException("Certificate file is not readable: {$certPath}");
            }
        }

        if ($keyPath) {
            $realKeyPath = $this->resolvePath($keyPath);
            if ($realKeyPath && !is_readable($realKeyPath)) {
                throw new \InvalidArgumentException("Private key file is not readable: {$keyPath}");
            }
        }

        if ($certPath) {
            $realCertPath = $this->resolvePath($certPath);
            if ($realCertPath) {
                $certContent = file_get_contents($realCertPath);
                $certData = @openssl_x509_parse($certContent);
                if (!$certData) {
                    throw new \InvalidArgumentException("Invalid certificate format or content");
                }
            }
        }

        if ($keyPath) {
            $realKeyPath = $this->resolvePath($keyPath);
            if ($realKeyPath) {
                $keyContent = file_get_contents($realKeyPath);
                $passphrase = $data['passphrase'] ?? null;
                $key = @openssl_pkey_get_private($keyContent, $passphrase);
                if (!$key) {
                    throw new \InvalidArgumentException("Private key is invalid or passphrase is incorrect");
                }
            }
        }
    }

    protected function resolvePath(string $path): ?string
    {
        if (str_contains($path, '..') || str_contains($path, "\0")) {
            return null;
        }

        $storagePath = storage_path('app/' . $path);
        $realPath = realpath($storagePath);
        $allowedBase = realpath(storage_path('app'));
        if ($realPath && $allowedBase && str_starts_with($realPath, $allowedBase)) {
            return $realPath;
        }

        $publicPath = storage_path('app/public/' . $path);
        $realPublic = realpath($publicPath);
        $publicBase = realpath(storage_path('app/public'));
        if ($realPublic && $publicBase && str_starts_with($realPublic, $publicBase)) {
            return $realPublic;
        }

        return null;
    }
}
