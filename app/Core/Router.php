<?php declare(strict_types=1);

namespace App\Core;

/**
 * Class Router
 *
 * Dinamik URL Yönlendirici (HTTP Router).
 * Regex parametreli rotalar (`/urun/{slug}`), REST fiilleri (GET, POST),
 * ve middleware zinciri çalıştırmayı destekler.
 *
 * @package App\Core
 */
class Router
{
    /** @var array<int, array{method:string, pattern:string, regex:string, handler:mixed, middleware:array}> */
    private array $routes = [];

    public function add(string $method, string $pattern, $handler, array $middleware = []): void
    {
        $regex = preg_replace('#\{([a-zA-Z_][a-zA-Z0-9_]*)\}#', '(?P<$1>[^/]+)', $pattern);
        $regex = '#^' . rtrim($regex, '/') . '/?$#';
        $this->routes[] = [
            'method'     => strtoupper($method),
            'pattern'    => $pattern,
            'regex'      => $regex,
            'handler'    => $handler,
            'middleware' => $middleware,
        ];
    }

    public function get(string $pattern, $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
    }

    public function post(string $pattern, $handler, array $middleware = []): void
    {
        $this->add('POST', $pattern, $handler, $middleware);
    }

    public function any(string $pattern, $handler, array $middleware = []): void
    {
        $this->add('GET', $pattern, $handler, $middleware);
        $this->add('POST', $pattern, $handler, $middleware);
    }

    public function dispatch(Request $request): Response
    {
        $path = $request->path();
        $method = $request->method();
        $allowed = [];
        foreach ($this->routes as $route) {
            if (preg_match($route['regex'], $path, $matches)) {
                if ($route['method'] !== $method) {
                    $allowed[] = $route['method'];
                    continue;
                }
                $params = [];
                foreach ($matches as $key => $value) {
                    if (!is_int($key)) {
                        $params[$key] = $value;
                    }
                }
                return $this->run($request, $route['middleware'], $route['handler'], $params);
            }
        }
        if (!empty($allowed)) {
            return Response::html('<h1>405 - İzin Verilmeyen Metot</h1>', 405);
        }
        return Response::html('<h1>404 - Sayfa Bulunamadı</h1>', 404);
    }

    private function run(Request $request, array $middleware, $handler, array $params): Response
    {
        $pipeline = array_reverse($middleware);
        $core = function (Request $req) use ($handler, $params): Response {
            return $this->invoke($handler, $req, $params);
        };
        $next = $core;
        foreach ($pipeline as $entry) {
            if (is_array($entry)) {
                $middlewareClass = $entry[0];
                $args = $entry[1] ?? [];
            } else {
                $middlewareClass = $entry;
                $args = [];
            }
            $next = function (Request $req) use ($middlewareClass, $args, $next): Response {
                $instance = new $middlewareClass(...$args);
                $result = $instance->handle($req, $next);
                if ($result instanceof Response) {
                    return $result;
                }
                return $next($req);
            };
        }
        return $next($request);
    }

    private function invoke($handler, Request $request, array $params): Response
    {
        if (is_array($handler) && count($handler) === 2) {
            [$class, $method] = $handler;
            $controller = new $class();
            $result = $controller->{$method}($request, ...array_values($params));
        } elseif (is_callable($handler)) {
            $result = $handler($request, ...array_values($params));
        } else {
            throw new \RuntimeException('Geçersiz rota işleyicisi.');
        }
        if ($result instanceof Response) {
            return $result;
        }
        return Response::html((string) $result);
    }
}
