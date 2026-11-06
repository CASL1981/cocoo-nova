<?php

declare(strict_types=1);

namespace Modules\HumanResources\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HumanResources\App\Models\HrCharacteristic;
use Modules\HumanResources\App\Models\HrCharacteristicOption;

/**
 * @extends Factory<HrCharacteristicOption>
 */
final class HrCharacteristicOptionFactory extends Factory
{
    protected $model = HrCharacteristicOption::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'hr_characteristic_id' => HrCharacteristic::factory(),
            'name' => 'Opción '.fake()->unique()->numerify('######'),
            'process_id' => null,
            'parent_id' => null,
            'status' => 'active',
        ];
    }
}
