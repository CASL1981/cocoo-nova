<?php

namespace Modules\Shopping\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Shopping\App\Models\Product;

class ProductSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Product::factory()->count(50)->create();
    }
}
