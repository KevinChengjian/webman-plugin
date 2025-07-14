<?php

namespace Nasus\Webman\Annotation;

use Attribute;
use Nasus\Webman\Enums\AuthTypeInterface;
use support\annotation\Middleware;

#[Attribute(Attribute::TARGET_CLASS | Attribute::TARGET_METHOD)]
class RequestMapping
{
    /**
     * router path
     * @var string
     */
    public string $path;

    /**
     * http methods
     * @var string|array
     */
    public string|array $methods;

    /**
     * router name
     * @var string
     */
    public string $name;

    /**
     * router description
     * @var string
     */
    public string $desc;

    /**
     * auth type
     * @var AuthTypeInterface|null
     */
    public ?AuthTypeInterface $authType;

    /**
     * 中间件
     * @var array
     */
    public array $middleware = [];

    /**
     * @param string $name router name
     * @param string $path router path
     * @param array|string $methods http method
     * @param string $desc router description
     */
    public function __construct(string $name = '', string $path = '', array|string $methods = ['get', 'post'], $middleware = [], string $desc = '', AuthTypeInterface|null $authType = null)
    {
        $this->path = $path;
        $this->methods = $methods;
        $this->name = $name;
        $this->desc = $desc;
        $this->middleware = $middleware;
        $this->authType = $authType;
    }
}