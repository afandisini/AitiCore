<?php

declare(strict_types=1);

namespace System\Http;

use Closure;
use System\Foundation\Application;
use System\Middleware\MiddlewarePipeline;
use System\Routing\Route;

class Kernel
{
    /**
     * @var array<int, class-string>
     */
    private array $globalMiddleware = [];

    public function __construct(private Application $app)
    {
    }

    public function handle(Request $request): Response
    {
        $headOnly = $request->method() === 'HEAD';
        $route = $this->app->router()->match($request);

        if ($route === null) {
            $response = $this->notFoundResponse();
            return $headOnly ? $response->withoutBody() : $response;
        }

        $middlewares = array_merge(
            $this->globalMiddleware,
            $route->group() !== null ? $this->app->middlewareGroup($route->group()) : [],
            $route->middleware()
        );

        $pipeline = new MiddlewarePipeline();
        $result = $pipeline->process(
            $request,
            $middlewares,
            function (Request $request) use ($route): mixed {
                return $this->dispatchToRoute($request, $route);
            }
        );

        $response = $this->normalizeResponse($result);

        return $headOnly ? $response->withoutBody() : $response;
    }

    private function dispatchToRoute(Request $request, Route $route): mixed
    {
        $action = $route->action();
        $params = $this->app->router()->currentParameters();

        if (is_callable($action)) {
            return $action($request, ...array_values($params));
        }

        if (is_array($action) && count($action) === 2) {
            [$class, $method] = $action;
            $controller = $this->app->make($class);
            return $controller->{$method}($request, ...array_values($params));
        }

        return Response::html('Invalid route handler', 500);
    }

    private function normalizeResponse(mixed $result): Response
    {
        if ($result instanceof Response) {
            return $result;
        }

        if (is_array($result)) {
            return Response::json($result);
        }

        return Response::html((string) $result);
    }

    private function notFoundResponse(): Response
    {
        try {
            return Response::html($this->app->view()->render('errors/404'), 404);
        } catch (\RuntimeException) {
            return Response::html('Not Found', 404);
        }
    }
}
