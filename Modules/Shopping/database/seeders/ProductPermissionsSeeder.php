<?php

namespace Modules\Shopping\Database\Seeders;

use App\Actions\Permissions\SeedModulePermissions;
use Illuminate\Database\Seeder;

class ProductPermissionsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        (new SeedModulePermissions())->execute('products');
    }
}
