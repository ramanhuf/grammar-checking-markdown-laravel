<?php

use App\Http\Middleware\AcceptJsonMiddleware;
use App\Traits\HttpResponses;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        apiPrefix: 'api',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->append(AcceptJsonMiddleware::class);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        $httpResponses = new class
        {
            use HttpResponses;
        };

        // 400
        $exceptions->renderable(function (InvalidArgumentException $e, $request) use ($httpResponses) {
            return $httpResponses->failureResponse(
                msg: $e->getMessage(),
                statusCode: Response::HTTP_BAD_REQUEST
            );
        });

        // 404
        $exceptions->renderable(function (NotFoundHttpException $e, $request) use ($httpResponses) {
            return $httpResponses->failureResponse(
                msg: 'Model/Route not found.',
                statusCode: Response::HTTP_NOT_FOUND
            );
        });

        // 405
        $exceptions->renderable(function (MethodNotAllowedHttpException $e, $request) use ($httpResponses) {
            return $httpResponses->failureResponse(
                msg: $e->getMessage(),
                statusCode: Response::HTTP_METHOD_NOT_ALLOWED
            );
        });

        // 422
        $exceptions->renderable(function (ValidationException $e, $request) use ($httpResponses) {
            return $httpResponses->failureResponse(
                msg: $e->getMessage(),
                statusCode: Response::HTTP_UNPROCESSABLE_ENTITY
            );
        });

        // 500
        $exceptions->renderable(function (QueryException $e, $request) use ($httpResponses) {
            return $httpResponses->failureResponse(
                msg: $e->getMessage(),
                statusCode: Response::HTTP_INTERNAL_SERVER_ERROR
            );
        });
        $exceptions->renderable(function (Exception $e, $request) use ($httpResponses) {
            return $httpResponses->failureResponse(
                msg: $e->getMessage(),
                statusCode: $e->getCode() ?? Response::HTTP_INTERNAL_SERVER_ERROR
            );
        });
    })->create();
