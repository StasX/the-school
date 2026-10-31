<?php

namespace App\Http\Validation;

class CourseRules
{
    /** @return array<string, mixed> */
    public static function create(): array
    {
        return [
            'name' => CommonRules::name(),
            'description' => ['required', 'string', 'max:500', 'min:8'],
            'image' => CommonRules::image(true, 1024, 350),
        ];
    }

    /** @return array<string, mixed> */
    public static function update(int $id): array
    {
        return [
            'name' => CommonRules::name(),
            'description' => ['required', 'string', 'max:500', 'min:8'],
            'image' => CommonRules::image(false, 1024, 350),
        ];
    }
}
