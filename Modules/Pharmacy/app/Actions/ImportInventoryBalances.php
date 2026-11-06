<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Actions;

use App\Actions\Imports\ImportResult;
use App\Imports\SheetRowsImport;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Exceptions\NoTypeDetectedException;
use Modules\Pharmacy\App\Models\InventoryBalance;
use Modules\Shopping\App\Models\Product;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;

/**
 * Carga el inventario disponible de Oficina Principal desde el archivo del ERP (ispl).
 *
 * - Solo se leen ProductId (ítem), ProductName y Disponible (no BalanceQuantity, que
 *   incluye lo separado). Un ítem llega en varias filas (lotes y localizaciones):
 *   el disponible se suma por ítem y los ítems con total 0 se descartan.
 * - Los ítems se homologan con el catálogo por shopping_products.item; los que no
 *   existen se guardan sin producto y no se cruzan con los pendientes.
 * - Todo o nada: si alguna fila es inválida no se carga nada. La carga reemplaza por
 *   completo la anterior, con lo que lo asignado (assigned_quantity) vuelve a cero.
 */
final class ImportInventoryBalances
{
    /** Columnas del ERP, tal como quedan tras el formateador de encabezados (slug). */
    private const ITEM_COLUMN = 'productid';

    private const NAME_COLUMN = 'productname';

    private const AVAILABLE_COLUMN = 'disponible';

    private const MAX_ERRORS = 100;

    private const FIRST_DATA_ROW = 2;

    /** Devuelve en created la cantidad de ítems cargados. */
    public function execute(string|UploadedFile $file, CarbonImmutable $cutOffDate, User $actor): ImportResult
    {
        try {
            $rows = SheetRowsImport::read($file);
        } catch (NoTypeDetectedException|SpreadsheetException) {
            return new ImportResult(errors: ['No se pudo leer el archivo: verifique que sea un Excel (.xlsx) o CSV válido.']);
        }

        if ($rows === []) {
            return new ImportResult(errors: ['El archivo no contiene registros para importar.']);
        }

        $missing = array_diff([self::ITEM_COLUMN, self::AVAILABLE_COLUMN], array_map('strval', array_keys($rows[0])));
        if ($missing !== []) {
            return new ImportResult(errors: ['El archivo no contiene las columnas obligatorias ProductId y Disponible del ERP.']);
        }

        [$totals, $names, $errors] = $this->aggregate($rows);

        if ($errors !== []) {
            return new ImportResult(errors: $errors);
        }

        $totals = array_filter($totals, fn (int $quantity): bool => $quantity > 0);

        if ($totals === []) {
            return new ImportResult(errors: ['El archivo no contiene ítems con cantidad disponible.']);
        }

        $this->replace($totals, $names, $cutOffDate, $actor);

        return new ImportResult(created: count($totals));
    }

    /**
     * Valida cada fila y suma el disponible por ítem.
     *
     * @param  list<array<string|int, mixed>>  $rows
     * @return array{0: array<int, int>, 1: array<int, string|null>, 2: list<string>}
     */
    private function aggregate(array $rows): array
    {
        $totals = [];
        $names = [];
        $errors = [];
        $errorCount = 0;

        foreach ($rows as $index => $row) {
            $line = $index + self::FIRST_DATA_ROW;
            $item = $this->integerValue($row[self::ITEM_COLUMN] ?? null);
            $available = $this->integerValue($row[self::AVAILABLE_COLUMN] ?? null);

            // Filas vacías (p. ej. el final de la hoja) se ignoran.
            if (($row[self::ITEM_COLUMN] ?? null) === null && ($row[self::AVAILABLE_COLUMN] ?? null) === null) {
                continue;
            }

            $messages = [];
            if ($item === null || $item <= 0) {
                $messages[] = 'el ProductId debe ser un número entero.';
            }
            if ($available === null || $available < 0) {
                $messages[] = 'el Disponible debe ser un número entero mayor o igual a cero.';
            }

            foreach ($messages as $message) {
                if (++$errorCount <= self::MAX_ERRORS) {
                    $errors[] = "Fila {$line}: {$message}";
                }
            }

            if ($messages === []) {
                $key = (int) $item;
                $totals[$key] = ($totals[$key] ?? 0) + (int) $available;
                $name = $row[self::NAME_COLUMN] ?? null;
                $names[$key] ??= is_string($name) ? mb_substr(trim($name), 0, 255) : null;
            }
        }

        if ($errorCount > self::MAX_ERRORS) {
            $errors[] = '… y '.($errorCount - self::MAX_ERRORS).' error(es) más.';
        }

        return [$totals, $names, $errors];
    }

    /** Entero desde la celda (Excel entrega 10 o 10.0); null si no es un entero. */
    private function integerValue(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value) && floor($value) === $value) {
            return (int) $value;
        }

        if (is_string($value) && preg_match('/^\s*-?\d+(\.0+)?\s*$/', $value) === 1) {
            return (int) $value;
        }

        return null;
    }

    /**
     * Reemplaza la carga vigente por la nueva dentro de una transacción.
     *
     * @param  array<int, int>  $totals
     * @param  array<int, string|null>  $names
     */
    private function replace(array $totals, array $names, CarbonImmutable $cutOffDate, User $actor): void
    {
        /** @var Collection<string, Product> $products */
        $products = new Collection;
        foreach (array_chunk(array_keys($totals), 1000) as $chunk) {
            $found = Product::query()->whereIn('item', array_map('strval', $chunk))->get(['id', 'item', 'generic_code']);
            $products = $products->replace($found->keyBy(fn (Product $product): string => (string) $product->item));
        }

        $now = now();
        $records = [];
        foreach ($totals as $item => $quantity) {
            $product = $products->get((string) $item);
            $records[] = [
                'item' => (string) $item,
                'product_name' => $names[$item] ?? null,
                'product_id' => $product?->id,
                'generic_code' => $product?->generic_code,
                'available_quantity' => $quantity,
                'assigned_quantity' => 0,
                'cut_off_date' => $cutOffDate->toDateString(),
                'created_at' => $now,
                'updated_at' => $now,
                'created_by' => $actor->id,
            ];
        }

        DB::transaction(function () use ($records): void {
            InventoryBalance::query()->delete();

            foreach (array_chunk($records, 500) as $chunk) {
                InventoryBalance::query()->insert($chunk);
            }
        });
    }
}
