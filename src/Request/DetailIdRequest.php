<?php

namespace Nasus\Webman\Request;

/**
 * @property int $id 数据ID
 */
class DetailIdRequest extends AbstractRequest
{
    /**
     * @var array|string[]
     */
    public static array $rules = [
        'id' => 'required'
    ];

    /**
     * @var array|string[]
     */
    public static array $message = [
        'id.required' => '请选择要访问的数据'
    ];
}