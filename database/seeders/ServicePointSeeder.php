<?php

namespace Database\Seeders;

use App\Enums\ServicePointStatus;
use App\Enums\ServicePointType;
use App\Models\ServicePoint;
use Illuminate\Database\Seeder;

/**
 * Siembra el catálogo real de sedes/servicios de la organización.
 * Los códigos y nombres vienen directo del negocio; no se inventan aquí.
 */

class ServicePointSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $points = [
            ['code' => '1',    'name' => 'OFICINA PRINCIPAL',      'type' => ServicePointType::Headquarters],
            ['code' => '1000', 'name' => 'FARMACIA PUEBLOS NUEVO', 'type' => ServicePointType::Pharmacy],
            ['code' => '1100', 'name' => 'FARMACIA VALENCIA',      'type' => ServicePointType::Pharmacy],
            ['code' => '1300', 'name' => 'FARMACIA MONTELIBANO',   'type' => ServicePointType::Pharmacy],
            ['code' => '1600', 'name' => 'FARMACIA SAN ANTERO',    'type' => ServicePointType::Pharmacy],
            ['code' => '2200', 'name' => 'FARMACIA SAN CARLOS',    'type' => ServicePointType::Pharmacy],
        ];

        foreach ($points as $point) {
            ServicePoint::firstOrCreate(
                ['code' => $point['code']],
                [...$point, 'status' => ServicePointStatus::Active]
            );
        }
    }
}
