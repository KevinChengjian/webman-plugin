<?php

namespace Nasus\Webman\Router;

use Nasus\Webman\Annotation\RequestMapping;
use Nasus\Webman\Utils\Helper;

class Route
{
    public static $routers = [];

    /**
     * 注册注解路由
     * @param string|null $app
     * @return void
     */
    public static function register(string $app = null)
    {
        $controllers = self::parser($app);
        foreach ($controllers as $controller => $refs) {
            $methods = $refs['methods'] ?? [];
            foreach ($methods as $method) {
                \Webman\Route::any($method['router'], [$refs['ref']->name, $method['name']])->middleware($method['ref']->middleware);
                self::$routers[sprintf('%s@%s', $refs['ref']->name, $method['name'])] = [
                    'menu' => $refs['router']->name,
                    'title' => $method['ref']->name,
                    'desc' => $method['ref']->desc,
                    'authCode' => $method['authCode'],
                    'authType' => $method['ref']?->authType?->value
                ];
            }
        }
    }

    /**
     * 扫描模块下的控制器
     *
     * @param string $module
     * @return array
     */
    public static function scan(?string $module): array
    {
        $moduleControllerMap = [];
        if (is_null($module) && file_exists(app_path('controller'))) {
            foreach (Helper::scanDir(app_path('controller')) as $file) {
                $controllerName = basename($file, '.php');
                $moduleControllerMap['app'][] = sprintf('%s/%s', 'app/controller', $controllerName);
            }
        }

        foreach (Helper::scanDir(base_path('plugin')) as $app) {
            if ($module && $module != $app) continue;
            $namespace = sprintf('/plugin/%s/app/controller', $app);

            foreach (Helper::scanDir(base_path($namespace)) as $file) {
                $controllerName = basename($file, '.php');
                $moduleControllerMap[$app][] = sprintf('%s/%s', $namespace, $controllerName);
            }
        }
        return $moduleControllerMap;
    }

    /**
     * 解析路由
     * @param $module
     * @return array
     * @throws \ReflectionException
     */
    public static function parser($module)
    {
        $moduleRouterMap = [];
        foreach (self::scan($module) as $app => $controllers) {
            foreach ($controllers as $controller) {
                $controllerRef = new \ReflectionClass(str_replace('/', '\\', $controller));

                $controllerRouter = self::router($controllerRef->getAttributes(), basename($controller));
                if (is_null($controllerRouter)) continue;

                $methods = $controllerRef->getMethods(\ReflectionMethod::IS_PUBLIC);
                $methodRouters = [];

                foreach ($methods as $methodRef) {
                    $methodRouter = self::router($methodRef->getAttributes(), $methodRef->getName());
                    if (is_null($methodRouter)) continue;

                    $fullRouter = sprintf('/%s/%s/%s', Helper::humpToCL($app), trim($controllerRouter->path, '/'), trim($methodRouter->path, '/'));
                    $methodRouters[] = [
                        'ref' => $methodRouter,
                        'name' => $methodRef->getName(),
                        'router' => $fullRouter,
                        'authCode' => str_replace('/', '.', ltrim($fullRouter, '/'))
                    ];
                }

                $moduleRouterMap[$controller] = [
                    'ref' => $controllerRef,
                    'router' => $controllerRouter,
                    'methods' => $methodRouters
                ];
            }
        }
        return $moduleRouterMap;
    }

    /**
     * 获取路由实例
     *
     * @param array $attrs
     * @param string $path
     * @return null|Router
     */
    public static function router(array $attrs = [], string $path = ''): ?RequestMapping
    {
        $routerAnn = null;
        foreach ($attrs as $attr) {
            if ($attr->getName() == RequestMapping::class) {
                $routerAnn = $attr;
                break;
            }
        }
        if (is_null($routerAnn)) return null;
        $router = $routerAnn->newInstance();

        if (!$router->path && $path) {
            $router->path = Helper::humpToCL(str_replace('Controller', '', $path));
        }
        return $router;
    }
}
