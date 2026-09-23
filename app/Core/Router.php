<?php

namespace App\Core;

final class Router
{
    /** @var array{method:string,pattern:string,handler:string,action:string}[] */
    private array $routes = [];

    public function get(string $pattern, string $controller, string $action): void
    {
        $this->routes[] = ['method' => 'GET', 'pattern' => $pattern, 'controller' => $controller, 'action' => $action];
    }

    public function post(string $pattern, string $controller, string $action): void
    {
        $this->routes[] = ['method' => 'POST', 'pattern' => $pattern, 'controller' => $controller, 'action' => $action];
    }

    public function any(string $pattern, string $controller, string $action): void
    {
        $this->routes[] = ['method' => '*', 'pattern' => $pattern, 'controller' => $controller, 'action' => $action];
    }

    public function dispatch(Request $request): void
    {
        $path = $request->path();
        $method = $request->method();

        foreach ($this->routes as $route) {
            if ($route['method'] !== '*' && $route['method'] !== $method) {
                continue;
            }
            if (!preg_match('#^' . $route['pattern'] . '$#', $path, $matches)) {
                continue;
            }

            $params = [];
            if (count($matches) > 1) {
                array_shift($matches);
                $params = array_values($matches);
            }

            $class = 'App\\Controllers\\' . $route['controller'];
            if (!class_exists($class)) {
                throw new \RuntimeException('Controlador no encontrado: ' . $class);
            }
            $controller = new $class();
            $controller->{$route['action']}($request, ...$params);
            return;
        }

        http_response_code(404);
        echo View::render('errors/404', [], null);
    }
}