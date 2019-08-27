<?php

namespace Juhlinus\Routing\Router;

class Router
{
    public $routes;

    public function __construct(array $routes)
    {
        $this->routes = $routes;
    }

    public function resolve()
    {
        $matched = false;

        foreach ($this->routes as $route) {
            if ($_SERVER['REQUEST_URI'] === '/'
            && strpos($route->name, 'index')) {
                $matched = true;
                break;
            }
            $pattern = preg_replace('/\{[A-Za-z0-9_-]+\}/', '[A-Za-z0-9_-]+', $route->pattern);
            if (preg_match($pattern, $_SERVER['REQUEST_URI']) === 1
                && in_array(strtolower($_SERVER['REQUEST_METHOD']), $route->request_methods)) {
                $matched = true;
                break;
            }
        }

        if (!$matched) {
            if (realpath(getenv('PATH_APP') . $_SERVER['REQUEST_URI'] . 'index.php')) {
                header('Location: ' . $_SERVER['REQUEST_URI'] . 'index.php');
            } else {
                $class = '\Controllers\NotFoundController';
            }
        }

        $param_str = str_replace($route->pattern, '', $_SERVER['REQUEST_URI']);

        $params = explode('/', trim($param_str, '/'));
        $params = array_filter($params);

        foreach ($params as $key => $param) {
            if (strpos($param, '?')) {
                $request_params = explode('&', explode('?', $param)[1]);

                foreach ($request_params as $req_param) {
                    $exploded_params = explode('=', $req_param);

                    foreach ($route->request_methods as $req_method) {                    
                        $_{$req_method}[$exploded_params[0]] = $exploded_params[1];
                        $params[$exploded_params[0]] = $exploded_params[1];
                    }
                }

                $params[$key] = explode('?', $param)[0];
            }
        }

        $match = clone($route);
        $match->params = $params;
        $match->class = $class ?? $match->class;

        return $match;
    }
}