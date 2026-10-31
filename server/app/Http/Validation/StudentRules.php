<?php

namespace App\Http\Validation;

use Illuminate\Validation\Rule;

class StudentRules
{
    /** @return array<string, mixed> */
    public static function create(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'unique:students,Email',
                'max:64',
            ],
            'name' => CommonRules::name(),
            'phone' => CommonRules::phone(),
            'image' => CommonRules::image(true, 500, 250),
            'courses' => [
                'sometimes',
                'array',
            ],
            'courses.*' => [
                'integer',
                'distinct',
                'exists:courses,Course_ID',
            ],
        ];
    }

    /** @return array<string, mixed> */
    public static function update(int $id): array
    {
        return [
            'email' => [
                'required',
                'email',
                Rule::unique('students', 'Email')
                    ->ignore($id, 'Student_ID'),
                'max:64',
                'min:7',
            ],
            'name' => CommonRules::name(),
            'phone' => CommonRules::phone(),
            'image' => CommonRules::image(false, 500, 250),
            'courses' => [
                'sometimes',
                'array',
            ],
            'courses.*' => [
                'integer',
                'distinct',
                'exists:courses,Course_ID',
            ],
        ];
    }
}
