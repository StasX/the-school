<?php

namespace App\Http\Validation;

use Illuminate\Validation\Rule;

class AdministratorRules
{
    /** @return array<string, mixed> */
    public static function create(): array
    {
        return [
            'email' => [
                'required',
                'email',
                'unique:administrators,Email',
                'max:64',
            ],
            'name' => CommonRules::name(),
            'role' => [
                'required',
                Rule::in(['manager', 'sales']),
            ],
            'phone' => CommonRules::phone(),
            'password' => [
                'required',
                'string',
                'min:8',
                'max:32',
            ],
            'image' => CommonRules::image(true, 500, 250),
        ];
    }

    /** @return array<string, mixed> */
    public static function update(int $id): array
    {
        return [
            'email' => [
                'required',
                'email',
                'max:64',
                Rule::unique('administrators', 'Email')
                    ->ignore($id, 'Administrator_ID'),
            ],
            'name' => CommonRules::name(),
            'role' => [
                'sometimes',
                Rule::in(['manager', 'owner', 'sales']),
            ],
            'phone' => CommonRules::phone(),
            'image' => CommonRules::image(false, 500, 250),
            'password' => [
                'sometimes',
                'nullable',
                'string',
                'min:8',
                'max:32',
            ],
        ];
    }
}
