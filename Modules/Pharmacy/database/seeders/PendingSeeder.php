<?php

namespace Modules\Pharmacy\Database\Seeders;

use App\Models\CharacteristicOption;
use App\Models\User;
use Database\Seeders\CharacteristicOptionSeeder;
use Database\Seeders\ServicePointSeeder;
use Illuminate\Database\Seeder;
use Modules\Pharmacy\App\Models\Pending;

/**
 * Pendientes de prueba para desarrollo.
 *
 * Usa solo las sedes reales de ServicePointSeeder, los usuarios existentes y las
 * opciones activas de las características, para que los registros sembrados
 * pasen la validación del formulario al editarlos.
 */
class PendingSeeder extends Seeder
{
    public function run(): void
    {
        // Ambos seeders son idempotentes: garantizan sedes y opciones aunque se ejecute solo este seeder.
        $this->call([ServicePointSeeder::class, CharacteristicOptionSeeder::class]);

        $options = fn (string $code) => CharacteristicOption::forCharacteristicCode($code)->values();

        $categories = $options(Pending::CATEGORY_CHARACTERISTIC_CODE);
        $eps = $options(Pending::EPS_CHARACTERISTIC_CODE);
        $modalities = $options(Pending::CONTRACTING_MODALITY_CHARACTERISTIC_CODE);
        $invoicingMethods = $options(Pending::INVOICING_METHOD_CHARACTERISTIC_CODE);

        Pending::factory()
            ->count(30)
            ->recycle(ServicePointSeeder::seeded())
            ->recycle(User::all())
            ->state(fn () => [
                'category' => $categories->random(),
                'eps' => $eps->random(),
                'contracting_modality' => $modalities->random(),
                'invoicing_method' => $invoicingMethods->random(),
            ])
            ->create();
    }
}
