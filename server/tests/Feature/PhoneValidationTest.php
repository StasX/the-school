<?php

namespace Tests\Feature;

use App\Http\Validation\CommonRules;
use Illuminate\Support\Facades\Validator;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PhoneValidationTest extends TestCase
{
    #[DataProvider('phoneNumbers')]
    public function test_phone_validation(mixed $phone, bool $valid): void
    {
        $validator = Validator::make(['phone' => $phone], ['phone' => CommonRules::phone()]);

        $this->assertSame($valid, $validator->passes());
    }

    public static function phoneNumbers(): array
    {
        return [
            'one digit' => ['1', false],
            'six digits' => ['123456', false],
            'formatting does not count as digits' => ['+1 (23)-456', false],
            'seven digits' => ['1234567', true],
            'sixteen digits' => ['1234567890123456', true],
            'seventeen digits' => ['12345678901234567', false],
            'formatted phone' => ['+972(50)123-4567', true],
            'formatting exceeds column length' => ['+972 (50) 123-4567', false],
            'letters' => ['123456a', false],
            'misplaced plus' => ['123+4567', false],
            'empty' => ['', false],
            'null' => [null, false],
            'numeric value' => [1234567, false],
        ];
    }
}
