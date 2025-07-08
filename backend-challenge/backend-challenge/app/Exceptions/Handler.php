<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Log;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            // Custom logging for specific exceptions
            if ($e instanceof QueryException) {
                Log::error('Database Query Exception', [
                    'message' => $e->getMessage(),
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        });
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param Request $request
     * @param Throwable $e
     * @return Response
     */
    public function render($request, Throwable $e): Response
    {
        // Handle API requests differently
        if ($request->expectsJson() || $request->is('api/*')) {
            return $this->handleApiException($request, $e);
        }

        return parent::render($request, $e);
    }

    /**
     * Handle exceptions for API requests
     *
     * @param Request $request
     * @param Throwable $e
     * @return Response
     */
    protected function handleApiException(Request $request, Throwable $e): Response
    {
        $response = [
            'success' => false,
            'message' => 'An error occurred',
            'data' => null
        ];

        $statusCode = 500;

        // Handle specific exception types
        if ($e instanceof ValidationException) {
            $response['message'] = 'Validation failed';
            $response['errors'] = $e->errors();
            $statusCode = 422;
        } elseif ($e instanceof ModelNotFoundException) {
            $response['message'] = 'Resource not found';
            $statusCode = 404;
        } elseif ($e instanceof NotFoundHttpException) {
            $response['message'] = 'Endpoint not found';
            $statusCode = 404;
        } elseif ($e instanceof HttpException) {
            $response['message'] = $e->getMessage() ?: 'HTTP error occurred';
            $statusCode = $e->getStatusCode();
        } elseif ($e instanceof QueryException) {
            $response['message'] = 'Database error occurred';
            
            // Don't expose database details in production
            if (config('app.debug')) {
                $response['debug'] = [
                    'sql' => $e->getSql(),
                    'bindings' => $e->getBindings(),
                    'message' => $e->getMessage()
                ];
            }
            
            $statusCode = 500;
        } elseif ($e instanceof \InvalidArgumentException) {
            $response['message'] = 'Invalid argument: ' . $e->getMessage();
            $statusCode = 400;
        } else {
            // Generic error handling
            $response['message'] = config('app.debug') 
                ? $e->getMessage() 
                : 'Internal server error';
            
            if (config('app.debug')) {
                $response['debug'] = [
                    'exception' => get_class($e),
                    'file' => $e->getFile(),
                    'line' => $e->getLine(),
                    'trace' => $e->getTraceAsString()
                ];
            }
        }

        // Log the error for monitoring
        if ($statusCode >= 500) {
            Log::error('API Exception', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'exception' => get_class($e),
                'message' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }

        return response()->json($response, $statusCode);
    }

    /**
     * Get the default context variables for logging.
     *
     * @return array<string, mixed>
     */
    protected function context(): array
    {
        return array_merge(parent::context(), [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
        ]);
    }
}
