<?php

namespace App\Services\Security;

use App\Models\User;
use Illuminate\Support\Str;

class TwoFactorService
{
    private static $chars = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';

    /**
     * Generate a secure random 16-character Base32 secret string
     */
    public static function generateSecretKey(): string
    {
        $secret = '';
        for ($i = 0; $i < 16; $i++) {
            $secret .= self::$chars[random_int(0, 31)];
        }
        return $secret;
    }

    /**
     * Decodes a Base32 string into binary data
     */
    public static function base32Decode(string $base32): string
    {
        $base32 = strtoupper($base32);
        $base32 = str_replace('=', '', $base32);
        $len = strlen($base32);
        $n = 0;
        $j = 0;
        $binary = '';
        
        for ($i = 0; $i < $len; $i++) {
            $pos = strpos(self::$chars, $base32[$i]);
            if ($pos === false) {
                continue;
            }
            $n = $n << 5;
            $n = $n + $pos;
            $j = $j + 5;
            if ($j >= 8) {
                $j = $j - 8;
                $binary .= chr(($n & (0xFF << $j)) >> $j);
            }
        }
        return $binary;
    }

    /**
     * Build the standard otpauth:// TOTP URI for authenticator apps
     */
    public static function getOtpauthUri(string $name, string $email, string $secret): string
    {
        $issuer = urlencode($name);
        $label = urlencode($name . ':' . $email);
        return "otpauth://totp/{$label}?secret={$secret}&issuer={$issuer}&algorithm=SHA1&digits=6&period=30";
    }

    /**
     * Get the TOTP QR Code URL using standard otpauth:// format
     * Google Charts API is leveraged to draw a high-fidelity image dynamically
     */
    public static function getQrCodeUrl(string $name, string $email, string $secret): string
    {
        $otpauthUrl = self::getOtpauthUri($name, $email, $secret);

        return "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($otpauthUrl);
    }

    /**
     * Verify a 6-digit TOTP code against a secret key using RFC 6238 hmac windowing
     */
    public static function verifyCode(string $secret, string $code, int $window = 1): bool
    {
        if (strlen($code) !== 6 || !is_numeric($code)) {
            return false;
        }

        try {
            $secretBinary = self::base32Decode($secret);
        } catch (\Exception $e) {
            return false;
        }

        // 30-second steps
        $currentTimeStep = floor(time() / 30);

        // Scan the verification window to protect against network delay
        for ($i = -$window; $i <= $window; $i++) {
            $timeStep = $currentTimeStep + $i;
            
            // Pack time into 8-byte binary string
            $timeStepBinary = pack('N*', 0) . pack('N*', $timeStep);
            
            // Generate HMAC-SHA1
            $hmac = hash_hmac('sha1', $timeStepBinary, $secretBinary, true);
            
            // Dynamic truncation offset
            $offset = ord($hmac[19]) & 0xf;
            
            // Extract a 4-byte integer
            $hash = (
                (ord($hmac[$offset + 0]) & 0x7f) << 24 |
                (ord($hmac[$offset + 1]) & 0xff) << 16 |
                (ord($hmac[$offset + 2]) & 0xff) << 8 |
                (ord($hmac[$offset + 3]) & 0xff)
            ) % 1000000;
            
            // Zero-pad hash to 6 digits
            $calculatedCode = str_pad($hash, 6, '0', STR_PAD_LEFT);
            
            if (hash_equals($calculatedCode, $code)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Verify a recovery code against the user's secure hashed codes pool
     */
    public static function verifyRecoveryCode(User $user, string $code): bool
    {
        if (empty($user->two_factor_recovery_codes)) {
            return false;
        }

        $codes = json_decode($user->two_factor_recovery_codes, true);
        if (!is_array($codes)) {
            // Check decrypted json value
            $decrypted = json_decode(decrypt($user->two_factor_recovery_codes), true);
            $codes = is_array($decrypted) ? $decrypted : [];
        }

        foreach ($codes as $index => $hashedCode) {
            if (password_verify($code, $hashedCode)) {
                // Delete spent recovery code to prevent re-use (one-time check policy)
                unset($codes[$index]);
                $user->update([
                    'two_factor_recovery_codes' => json_encode(array_values($codes))
                ]);
                return true;
            }
        }

        return false;
    }
}
