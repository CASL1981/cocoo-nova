<?php

namespace Modules\Shopping\Database\Seeders;

use App\Actions\Imports\ImportRows;
use Illuminate\Database\Seeder;
use Modules\Shopping\App\Imports\ProductImporter;
use Modules\Shopping\App\Models\Product;
use RuntimeException;

/**
 * Siembra el catálogo con los productos reales de database/DB.xlsx (hoja
 * shopping_products), con las mismas columnas y reglas que la importación
 * de la pantalla. Sin el archivo, o en pruebas, usa la factory.
 */
class ProductSeeder extends Seeder
{
    private const SOURCE_FILE = 'DB.xlsx';

    private const SOURCE_SHEET = 'shopping_products';

    public function run(ImportRows $importRows): void
    {
        $path = database_path(self::SOURCE_FILE);

        if (app()->environment('testing') || ! is_file($path)) {
            Product::factory()->count(50)->create();

            return;
        }

        $result = $importRows->execute(new ProductImporter, $path, self::SOURCE_SHEET);

        if ($result->failed()) {
            throw new RuntimeException("No se sembraron productos, el archivo tiene errores:\n".implode("\n", $result->errors));
        }

        $this->command->info("Productos importados desde {$path}: {$result->created} creado(s), {$result->updated} actualizado(s).");
    }
}
