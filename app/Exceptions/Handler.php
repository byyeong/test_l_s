<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpFoundation\Exception\SuspiciousOperationException;
use Illuminate\Database\QueryException;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {
        if ($exception instanceof \Illuminate\Database\Eloquent\ModelNotFoundException) {
            return response()->json([
                'errorCode' => config('message.exception.IVD_REQ'),
                'message' => 'resource you are trying to request does not exist.',
            ], 410);
        } else if ($exception instanceof MethodNotAllowedHttpException) {
            return response()->json([
                'errorCode' => config('message.exception.IVD_REQ'),
                'message' => 'mothod not allow',
            ], 403);
        } else if ($exception instanceof NotFoundHttpException) {
            return response()->json([
                'errorCode' => config('message.exception.IVD_REQ'),
                'message' => 'not found',
            ], 410);
        } else if ($exception instanceof QueryException) {
            return response()->json([
                'errorCode' => config('message.exception.IVD_REQ'),
                'message' => 'database error or query error',
            ], 410);
        } else if ($exception instanceof SuspiciousOperationException) {
            return response()->json([
                'errorCode' => config('message.exception.IVD_REQ'),
                'message' => 'suspicious operation error',
            ], 406);
        }

        return parent::render($request, $exception);
    }

    protected function unauthenticated($request, AuthenticationException $exception)
    {
        //     if ($request->expectsJson()) {
        //         return response()->json([
        //             'errorCode' => config('message.exception.IVD_CRD'),
        //             'message' => 'Unauthenticated.',
        //         ], 401);
        //     }

        //     return redirect()->guest(route('login'));
        return response()->json([
            'errorCode' => config('message.exception.IVD_CRD'),
            'message' => 'Unauthenticated.',
        ], 401);
    }
}
