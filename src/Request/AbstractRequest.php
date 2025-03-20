<?php

namespace Nasus\Webman\Request;

use Illuminate\Contracts\Validation\Validator;
use Nasus\Webman\Exception\ValidateException;

/**
 * @method all()
 * @method input(string $name, $default = null)
 * @method only(string[] $array)
 * @method except(array $keys)
 * @method get($name = null, $default = null)
 * @method post($name = null, $default = null)
 * @method header($name = null, $default = null)
 */
abstract class AbstractRequest
{
    /**
     * @param array $rules
     * @param array $message
     * @throws ValidateException
     */
    public function __construct(array $rules = [], array $message = [])
    {
        $this->validate($rules, $message);
    }

    /**
     * @param array $rules
     * @param array $message
     * @return void
     * @throws ValidateException
     */
    public function validate(array $rules = [], array $message = [])
    {
        $rules = empty($rules) ? static::$rules : $rules;

        $message = empty($message) ? static::$message : $message;

        $validator = validator(request()->all(), $rules, $message);

        if ($validator->fails()) {
            $this->failedValidation($validator);
        }
    }

    /**
     * @param Validator $validator
     * @return mixed
     */
    protected function failedValidation(Validator $validator): mixed
    {
        throw new ValidateException($validator->errors()->first());
    }

    /**
     * @param array $keys
     * @return array
     */
    public function withForm(array $keys = []): array
    {
        $keys = empty($keys) ? array_keys(static::$rules) : $keys;
        return \request()->only($keys);
    }

    /**
     * @param string $name
     * @param array $arguments
     * @return mixed
     */
    public function __call(string $name, array $arguments)
    {
        return call_user_func_array([\request(), $name], $arguments);
    }

    /**
     * @param $key
     * @return mixed|null
     */
    public function __get($key)
    {
        return \request()->input($key);
    }

    /**
     * @param $key
     * @return bool
     */
    public function __isset($key): bool
    {
        $value = $this->$key;
        return !empty($value);
    }
}