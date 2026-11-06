<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Reports;

use App\Models\ServicePoint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Pharmacy\App\Models\NewProduct;

/**
 * Consolidado de productos nuevos: cantidad que falta enviar por producto y farmacia.
 *
 * - Solo solicitudes abiertas (Sin asignar y Parcial).
 * - Cantidad = solicitada − enviada.
 * - Filas: nombre normalizado + marca (solicitudes del mismo nombre y marca de distintas
 *   farmacias se suman); se muestra el menor de los nombres escritos.
 * - Columnas: solo las farmacias con cantidades, por abreviatura y ordenadas por código.
 *
 * Misma estructura que PendingConsolidation: la comparten la pantalla y la exportación.
 */
final class NewProductConsolidation
{
    /**
     * @return array{columns: array<int|string, string>, rows: list<array{product: string, quantities: array<int|string, int>, total: int}>}
     */
    public function build(string $search = ''): array
    {
        $missing = 'quantity - COALESCE(send_quantity, 0)';
        $search = trim($search);

        $cells = NewProduct::query()->open()
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('product_name', 'like', "%{$search}%")->orWhere('brand', 'like', "%{$search}%")))
            ->groupBy('normalized_name', 'brand', 'destination_code')
            ->havingRaw("SUM({$missing}) > 0")
            ->orderBy('normalized_name')
            ->orderBy('brand')
            ->get(['normalized_name', 'brand', 'destination_code', DB::raw('MIN(product_name) as display_name'), DB::raw("SUM({$missing}) as missing")]);

        $codes = $cells->pluck('destination_code')->unique()->all();
        $columns = ServicePoint::withTrashed()->whereIn('code', $codes)->get(['code', 'abbreviation'])
            ->sortBy(fn (ServicePoint $point) => [mb_strlen($point->code), $point->code])
            ->mapWithKeys(fn (ServicePoint $point) => [$point->code => $point->abbreviation ?: $point->code])
            ->all();

        // Códigos de farmacia sin sede registrada: se muestran por su código.
        foreach (array_diff($codes, array_keys($columns)) as $code) {
            $columns[(string) $code] = (string) $code;
        }

        $rows = [];

        foreach ($cells->groupBy(fn (NewProduct $cell) => "{$cell->normalized_name}|{$cell->brand}") as $group) {
            /** @var NewProduct $first */
            $first = $group->first();
            $quantities = array_fill_keys(array_keys($columns), 0);

            foreach ($group as $cell) {
                $quantities[(string) $cell->destination_code] = (int) $cell->getAttribute('missing');
            }

            // Nombre estable del grupo: el menor de los escritos por las farmacias.
            $name = (string) $group->min(fn (NewProduct $cell) => (string) $cell->getAttribute('display_name'));

            $rows[] = [
                'product' => $first->brand ? "{$name} · {$first->brand}" : $name,
                'quantities' => $quantities,
                'total' => array_sum($quantities),
            ];
        }

        return ['columns' => $columns, 'rows' => $rows];
    }
}
