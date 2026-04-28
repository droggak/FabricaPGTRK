<?php
// src/Core/Router.php

namespace App\Core;

class Router
{
    private array $routes = [];

    public function get(string $path, array $handler): void
    {
        $this->routes[] = ['GET', $path, $handler];
    }

    public function post(string $path, array $handler): void
    {
        $this->routes[] = ['POST', $path, $handler];
    }

    public function dispatch(string $method, string $uri): void
    {
        // Убираем query string и базовый путь
        $uri = parse_url($uri, PHP_URL_PATH);
        $uri = '/' . trim($uri, '/');
        if ($uri === '/') $uri = '/';

        foreach ($this->routes as [$routeMethod, $pattern, $handler]) {
            if ($routeMethod !== $method) continue;

            // Преобразуем /story/{id} в regex
            $regex = preg_replace('/\{([a-z_]+)\}/', '(?P<$1>[^/]+)', $pattern);
            $regex = '#^' . $regex . '$#';

            if (preg_match($regex, $uri, $matches)) {
                $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
                [$class, $action] = $handler;
                $controller = new $class();
                $controller->$action($params);
                return;
            }
        }

        http_response_code(404);
        require ROOT . '/views/shared/404.php';
    }
}
