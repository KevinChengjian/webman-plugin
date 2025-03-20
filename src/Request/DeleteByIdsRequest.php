<?php

namespace Nasus\Webman\Request;

/**
 * @property array $ids 数据ID
 */
class DeleteByIdsRequest extends AbstractRequest
{
    /**
     * @var array|string[]
     */
    public static array $rules = [
        'ids' => 'required|array'
    ];

    /**
     * @var array|string[]
     */
    public static array $message = [
        'ids.required' => '请选择要删除的数据',
        'ids.array' => '请选择要删除的数据'
    ];
}