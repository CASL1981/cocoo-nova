<?php

namespace Modules\Shopping\Database\Seeders;

use Illuminate\Database\Seeder;

class ShoppingDatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call([
            ProductSeeder::class,
        ]);
    }
}
