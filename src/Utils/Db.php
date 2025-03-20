<?php

namespace Nasus\Webman\Utils;


class Db extends \support\Db
{
    /**
     * Dynamically pass methods to the default connection.
     *
     * @param string $method
     * @param array $parameters
     * @return mixed
     */
    public static function __callStatic($method, $parameters)
    {
        return static::connection('plugin.nasus.webman.cli')->$method(...$parameters);
    }
}