<?php

declare(strict_types=1);

namespace Modules\HumanResources\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\HumanResources\App\Models\HrCharacteristic;

/**
 * @extends Factory<HrCharacteristic>
 */
final class HrCharacteristicFactory extends Factory
{
    protected $model = HrCharacteristic::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'name' => 'Lista '.fake()->unique()->numerify('######'),
            'code' => 'list_'.fake()->unique()->numerify('######'),
            'status' => 'active',
        ];
    }

    /** Característica de cargos del organigrama. */
    public function positions(): self
    {
        return $this->state(['name' => 'Cargos', 'code' => HrCharacteristic::POSITIONS_CODE]);
    }
}
