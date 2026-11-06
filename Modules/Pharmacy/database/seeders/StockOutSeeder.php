<?php

namespace Modules\Pharmacy\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Pharmacy\App\Models\StockOut;

class StockOutSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        StockOut::factory()
            ->count(50)
            ->create();
    }
}
