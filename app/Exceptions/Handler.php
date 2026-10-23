<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
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
            //
        });

        $this->renderable(function (Throwable $e, $request) {
            if ($request->is('api/*') || $request->expectsJson()) {
                $status = 500;
                if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                    $status = 401;
                } elseif ($e instanceof \Illuminate\Validation\ValidationException) {
                    $status = 422;
                } elseif (method_exists($e, 'getStatusCode')) {
                    $status = $e->getStatusCode();
                } elseif (isset($e->status)) {
                    $status = $e->status;
                }

                $response = [
                    'success' => false,
                    'message' => $e->getMessage() ?: 'Server Error',
                ];

                if (method_exists($e, 'errors')) {
                    $response['errors'] = $e->errors();
                }

                return response()->json($response, $status);
            }
        });
    }
}
