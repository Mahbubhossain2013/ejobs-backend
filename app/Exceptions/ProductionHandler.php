<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class ProductionHandler extends ExceptionHandler
{
    /**
     * The names of the custom exception properties that are not reported.
     */
    protected $dontReport = [
        //
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Add request context to all exceptions
            if (request()->hasSession()) {
                Log::withContext([
                    'user_id' => auth()->id(),
                    'ip'      => request()->ip(),
                    'url'     => request()->fullUrl(),
                    'method'  => request()->method(),
                ]);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     */
    public function render($request, Throwable $e): JsonResponse
    {
        // API requests get JSON responses
        if ($request->is('api/*') || $request->expectsJson()) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle API exceptions with safe error messages.
     */
    private function handleApiException(Request $request, Throwable $e): JsonResponse
    {
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation failed',
                'errors'  => $e->errors(),
            ], 422);
        }

        if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
            return response()->json([
                'status'  => false,
                'message' => 'Resource not found',
            ], 404);
        }

        if ($e instanceof \Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException) {
            return response()->json([
                'status'  => false,
                'message' => 'Access denied',
            ], 403);
        }

        if ($e instanceof \Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException) {
            $retryAfter = $e->getHeaders()['Retry-After'] ?? 60;
            return response()->json([
                'status'     => false,
                'message'    => 'Too many requests. Please try again later.',
                'retry_after' => $retryAfter,
            ], 429)->header('Retry-After', $retryAfter);
        }

        // Log the full exception server-side
        Log::error('API Exception: ' . $e->getMessage(), [
            'exception' => get_class($e),
            'trace'     => $e->getTraceAsString(),
            'user_id'   => auth()->id(),
            'url'       => $request->fullUrl(),
        ]);

        // Return generic message in production
        $statusCode = method_exists($e, 'getStatusCode') ? $e->getStatusCode() : 500;

        return response()->json([
            'status'  => false,
            'message' => 'An unexpected error occurred. Please try again later.',
        ], $statusCode);
    }
}
