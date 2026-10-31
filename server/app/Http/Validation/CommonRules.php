<?php

namespace App\Http\Validation;

class CommonRules
{
    /** @return list<string> */
    public static function name(): array
    {
        return ['required', 'string', 'max:32', 'min:2'];
    }

    /** @return list<string> */
    public static function phone(): array
    {
        return ['required', 'string', 'max:16', 'regex:/^\+?[0-9](?:[0-9\s\-().]*[0-9])?$/'];
    }

    /** @return list<string> */
    public static function image(bool $required, int $maxSize, int $maxDimension): array
    {
        return [
            ...($required ? ['required'] : ['sometimes', 'nullable']),
            'image',
            'mimes:jpg,jpeg,png,gif',
            "max:$maxSize",
            "dimensions:max_width=$maxDimension,max_height=$maxDimension",
        ];
    }

    /** @return array<string, list<string>> */
    public static function pagination(): array
    {
        return ['page' => ['sometimes', 'integer', 'min:1']];
    }
}
