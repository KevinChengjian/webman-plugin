<?php

namespace Nasus\Webman\Request;

/**
 * @property int $page     页码
 * @property int $pageSize 分页条数
 * @property string $sortField 排序字段
 * @property string $sortOrder 排序方式:ascend(升序),descend(降序)
 */
class PagingRequest extends AbstractRequest
{
    /**
     * @var array|string[]
     */
    public static array $rules = [
        'page' => 'required|integer|min:1',
        'pageSize' => 'required|integer|min:1|max:100',
        'sortField' => 'sometimes|nullable|string|max:100',
        'sortOrder' => 'sometimes|nullable|string|in:ascend,descend',
    ];

    /**
     * @var array|string[]
     */
    public static array $message = [
        'page.required' => '分页参数错误',
        'page.integer' => '分页参数错误',
        'page.min' => '分页参数错误',

        'pageSize.required' => '分页参数错误',
        'pageSize.integer' => '分页参数错误',
        'pageSize.min' => '分页参数错误',
        'pageSize.max' => '最大分页数据100条',

        'sortField.string' => '排序字段类型错误',
        'sortField.max' => '排序字段长度错误',

        'sortOrder.string' => '排序方式错误',
        'sortOrder.in' => '排序方式错误',
    ];
}