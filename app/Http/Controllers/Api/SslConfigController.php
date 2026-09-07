<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\SslCertificateConfig;
use App\Services\SslManagerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class SslConfigController extends Controller
{
    public function __construct(
        protected SslManagerService $sslService
    ) {}

    public function index(Request $request)
    {
        $user = Auth::user();
        $configs = $this->sslService->getUserConfigs($user);

        return response()->json([
            'status' => true,
            'data' => $configs->map(fn($c) => $this->sslService->getCertificateInfo($c)),
        ]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'domain' => 'required|string|max:255',
            'cert_type' => 'required|in:letsencrypt,self_signed,commercial,wildcard',
            'format' => 'required|in:pem,pfx,p12,der',
            'certificate_path' => 'required|string|max:20480',
            'private_key_path' => 'nullable|string|max:20480',
            'chain_path' => 'nullable|string|max:20480',
            'passphrase' => 'nullable|string|max:255',
            'passphrase_algorithm' => 'nullable|in:sha256,sha512,md5',
            'issued_at' => 'nullable|date',
            'expires_at' => 'nullable|date|after:issued_at',
            'renewal_at' => 'nullable|date',
            'auto_renew' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ]);

        try {
            $config = $this->sslService->storeConfig($user, $validated);

            return response()->json([
                'status' => true,
                'message' => 'SSL certificate configuration saved',
                'data' => $this->sslService->getCertificateInfo($config),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error("SSL config creation failed", [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to save SSL configuration',
            ], 500);
        }
    }

    public function show(int $id)
    {
        $user = Auth::user();
        $config = $this->sslService->getConfig($id);

        if (!$config || $config->user_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Configuration not found',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data' => $this->sslService->getCertificateInfo($config),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $user = Auth::user();
        $config = $this->sslService->getConfig($id);

        if (!$config || $config->user_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Configuration not found',
            ], 404);
        }

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'domain' => 'sometimes|string|max:255',
            'cert_type' => 'sometimes|in:letsencrypt,self_signed,commercial,wildcard',
            'format' => 'sometimes|in:pem,pfx,p12,der',
            'certificate_path' => 'sometimes|string|max:20480',
            'private_key_path' => 'nullable|string|max:20480',
            'chain_path' => 'nullable|string|max:20480',
            'passphrase' => 'nullable|string|max:255',
            'passphrase_algorithm' => 'nullable|in:sha256,sha512,md5',
            'issued_at' => 'nullable|date',
            'expires_at' => 'nullable|date',
            'renewal_at' => 'nullable|date',
            'auto_renew' => 'nullable|boolean',
            'is_active' => 'nullable|boolean',
            'metadata' => 'nullable|array',
        ]);

        try {
            $config = $this->sslService->updateConfig($config, $validated);

            return response()->json([
                'status' => true,
                'message' => 'SSL configuration updated',
                'data' => $this->sslService->getCertificateInfo($config),
            ]);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'status' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            Log::error("SSL config update failed", [
                'config_id' => $id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'status' => false,
                'message' => 'Failed to update SSL configuration',
            ], 500);
        }
    }

    public function destroy(int $id)
    {
        $user = Auth::user();
        $config = $this->sslService->getConfig($id);

        if (!$config || $config->user_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Configuration not found',
            ], 404);
        }

        $this->sslService->deleteConfig($config);

        return response()->json([
            'status' => true,
            'message' => 'SSL configuration deleted',
        ]);
    }

    public function validate(int $id)
    {
        $user = Auth::user();
        $config = $this->sslService->getConfig($id);

        if (!$config || $config->user_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Configuration not found',
            ], 404);
        }

        $result = $this->sslService->validateConfig($config);

        return response()->json([
            'status' => true,
            'data' => $result,
        ]);
    }

    public function expiring(Request $request)
    {
        $days = $request->get('days', 30);

        $certificates = $this->sslService->getExpiringCertificates($days);

        return response()->json([
            'status' => true,
            'data' => $certificates->map(fn($c) => $this->sslService->getCertificateInfo($c)),
        ]);
    }

    public function export(int $id)
    {
        $user = Auth::user();
        $config = $this->sslService->getConfig($id);

        if (!$config || $config->user_id !== $user->id) {
            return response()->json([
                'status' => false,
                'message' => 'Configuration not found',
            ], 404);
        }

        $exportData = $this->sslService->exportConfig($config);

        return response()->json([
            'status' => true,
            'data' => $exportData,
        ]);
    }
}
