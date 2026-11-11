<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Support\PermissionCatalog;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\HumanResources\Database\Seeders\PositionSeeder;
use Modules\Shopping\Database\Seeders\ShoppingDatabaseSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {

        Role::factory()->createMany([
            ['name' => 'Administrador'],
            ['name' => 'Contabilidad'],
            ['name' => 'Operaciones'],
        ]);

        // AllModulePermissionsSeeder también entrega todo el catálogo al rol Administrador.
        $this->call([AllModulePermissionsSeeder::class, ServicePointSeeder::class, ProcessSeeder::class, CharacteristicOptionSeeder::class,
            ReferenceCatalogSeeder::class]);

        // Operaciones gestiona Farmacia: todas las acciones de sus módulos.
        Role::where('name', 'Operaciones')->first()?->givePermissionTo(
            collect(['stock-outs', 'pendings', 'inventory-balances'])
                ->flatMap(fn (string $module) => PermissionCatalog::allForModule($module))
                ->all()
        );

        // Usuarios reales del sistema anterior de requisiciones (doc/users.csv).
        $this->call(UserSeeder::class);

        // Catálogo real desde database/DB.xlsx (o factory si el archivo no existe).
        $this->call(ShoppingDatabaseSeeder::class);

        // Proveedores del sistema anterior de requisiciones (vinculación administrativa) y COODESCOR.
        $this->call(ProviderSeeder::class);

        // Cargos del organigrama vigente (Talento Humano → Características → Cargos).
        $this->call(PositionSeeder::class);

        // Cargos que verifican cada tipo de proveedor (Datos de la empresa → Revisión del FO-300-01).
        $this->call(ProviderVerifierSeeder::class);
    }
}
