<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Reports;

use App\Models\ServicePoint;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Pharmacy\App\Enums\PendingItemStatus;
use Modules\Pharmacy\App\Enums\PendingStatus;
use Modules\Pharmacy\App\Models\PendingItem;

/**
 * Consolidado de pendientes: cantidad que falta enviar por producto (genérico) y sede.
 *
 * - Solo ítems Pendiente y Parcial de pendientes Terminados.
 * - Cantidad = solicitada − enviada (en un Pendiente sin envíos es la solicitada).
 * - Filas: nombre genérico (si un nombre tiene varios códigos se agrega "· Cód. X").
 * - Columnas: solo las sedes con cantidades, encabezadas por su abreviatura y
 *   ordenadas por código; más la columna de totales por producto.
 *
 * La comparten la pantalla y la exportación a Excel.
 */
final class PendingConsolidation
{
    /**
     * @return array{columns: array<int|string, string>, rows: list<array{product: string, quantities: array<int|string, int>, total: int}>}
     */
    public function build(string $search = ''): array
    {
        $missing = 'quantity - COALESCE(send_quantity, 0)';
        $search = trim($search);

        $cells = PendingItem::query()
            ->whereIn('status', [PendingItemStatus::Pending, PendingItemStatus::Partial])
            ->whereHas('pending', fn (Builder $pending) => $pending->where('status', PendingStatus::Finished))
            ->when($search !== '', fn (Builder $q) => $q->where(fn (Builder $w) => $w
                ->where('generic_name', 'like', "%{$search}%")->orWhere('generic_code', 'like', "%{$search}%")))
            ->groupBy('generic_name', 'generic_code', 'destination_code')
            ->havingRaw("SUM({$missing}) > 0")
            ->orderBy('generic_name')
            ->orderBy('generic_code')
            ->get(['generic_name', 'generic_code', 'destination_code', DB::raw("SUM({$missing}) as missing")]);

        $codes = $cells->pluck('destination_code')->unique()->all();
        $columns = ServicePoint::withTrashed()->whereIn('code', $codes)->get(['code', 'abbreviation'])
            ->sortBy(fn (ServicePoint $point) => [mb_strlen($point->code), $point->code])
            ->mapWithKeys(fn (ServicePoint $point) => [$point->code => $point->abbreviation ?: $point->code])
            ->all();

        // Códigos de destino sin sede registrada: se muestran por su código.
        foreach (array_diff($codes, array_keys($columns)) as $code) {
            $columns[(string) $code] = (string) $code;
        }

        $repeatedNames = $cells->unique(fn (PendingItem $cell) => "{$cell->generic_name}|{$cell->generic_code}")->countBy('generic_name');
        $rows = [];

        foreach ($cells->groupBy(fn (PendingItem $cell) => "{$cell->generic_name}|{$cell->generic_code}") as $group) {
            /** @var PendingItem $first */
            $first = $group->first();
            $quantities = array_fill_keys(array_keys($columns), 0);

            foreach ($group as $cell) {
                $quantities[(string) $cell->destination_code] = (int) $cell->getAttribute('missing');
            }

            $rows[] = [
                'product' => ($repeatedNames[$first->generic_name] ?? 1) > 1
                    ? "{$first->generic_name} · Cód. {$first->generic_code}"
                    : $first->generic_name,
                'quantities' => $quantities,
                'total' => array_sum($quantities),
            ];
        }

        return ['columns' => $columns, 'rows' => $rows];
    }
}
