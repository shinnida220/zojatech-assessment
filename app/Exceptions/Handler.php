<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array<int, class-string<Throwable>>
     */
    protected $dontReport = [
        //

    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
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
     *
     * @return void
     */
    public function register()
    {
        $this->reportable(function (Throwable $e) {
            return false;
        });
    }

    public function render($request, Throwable $exception)
    {
        return $this->handleException($request, $exception);
    }

    private function handleException($request, Throwable $exception)
    {
        $exception = $this->prepareException($exception);

        if ($exception instanceof \Illuminate\Http\Exceptions\HttpResponseException) {
            $exception = $exception->getResponse();
        } else if ($exception instanceof \Illuminate\Auth\AuthenticationException) {
            $exception = $this->unauthenticated($request, $exception);
        } else if ($exception instanceof \Illuminate\Validation\ValidationException) {
            $exception = $this->convertValidationExceptionToResponse($exception, $request);
        }

        return $this->handleCustomResponse($exception);
    }

    private function handleCustomResponse($exception)
    {
        if (method_exists($exception, 'getStatusCode')) {
            $statusCode = $exception->getStatusCode();
        } else {
            $statusCode = 500;
        }

        $response = ['status' => false];

        switch ($statusCode) {
            case 401:
                $response['message'] = 'Unauthorized. Please login to proceed';
                break;
            case 403:
                $response['message'] = $exception->getMessage() ?: 'Request Forbidden';
                break;
            case 404:
                $response['message'] = 'Request Not Found';
                break;
            case 405:
                $response['message'] = 'Method Not Allowed';
                break;
            case 422:
                $response['message'] = $exception->original['message'];
                $response['errors'] = $exception->original['errors'];
                break;
            default:
                $response['message'] = ($statusCode == 500) ? 'An unexpected error has occured. Please try again later' : $exception->getMessage();
                break;
        }

        if (config('app.debug')) {
            $response['exception'] = get_class($exception);
            if (method_exists($exception, 'getTrace')) {
                $response['trace'] = $exception->getTrace();
            }
            // $response['code'] = $exception->getCode();
        }

        $response['code'] = $statusCode;
        $response['status'] = false;

        return response()->json($response, $statusCode);
    }
}