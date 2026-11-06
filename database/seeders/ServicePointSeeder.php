<?php

namespace Database\Seeders;

use App\Enums\ServicePointStatus;
use App\Enums\ServicePointType;
use App\Models\ServicePoint;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;

/**
 * Siembra el catálogo real de sedes/servicios de la organización.
 * Los códigos y nombres vienen directo del negocio; no se inventan aquí.
 */
class ServicePointSeeder extends Seeder
{
    /**
     * Catálogo real de sedes; público para que otros seeders usen solo estas sedes.
     */
    public const POINTS = [
        ['code' => '1',    'name' => 'OFICINA PRINCIPAL',      'abbreviation' => 'OF. PRINCIPAL', 'type' => ServicePointType::Headquarters],
        ['code' => '1000', 'name' => 'FARMACIA PUEBLOS NUEVO', 'abbreviation' => 'PUEBLO N.',     'type' => ServicePointType::Pharmacy],
        ['code' => '1100', 'name' => 'FARMACIA VALENCIA',      'abbreviation' => 'VALENCIA',      'type' => ServicePointType::Pharmacy],
        ['code' => '1300', 'name' => 'FARMACIA MONTELIBANO',   'abbreviation' => 'MONTELIBANO.',  'type' => ServicePointType::Pharmacy],
        ['code' => '1600', 'name' => 'FARMACIA SAN ANTERO',    'abbreviation' => 'SAN ANTERO',    'type' => ServicePointType::Pharmacy],
        ['code' => '2200', 'name' => 'FARMACIA SAN CARLOS',    'abbreviation' => 'SAN CARLOS',    'type' => ServicePointType::Pharmacy],
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (self::POINTS as $point) {
            ServicePoint::firstOrCreate(
                ['code' => $point['code']],
                [...$point, 'status' => ServicePointStatus::Active]
            );
        }
    }

    /**
     * Sedes reales ya sembradas, para reciclarlas en factories y no crear sedes ficticias.
     *
     * @return Collection<int, ServicePoint>
     */
    public static function seeded(): Collection
    {
        return ServicePoint::whereIn('code', array_column(self::POINTS, 'code'))->get();
    }
}
