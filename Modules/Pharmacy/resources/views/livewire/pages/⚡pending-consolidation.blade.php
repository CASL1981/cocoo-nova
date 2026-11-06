<?php

use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Session;
use Livewire\Attributes\Title;
use Livewire\Component;
use App\Livewire\Concerns\HasAuthScopedSession;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Pharmacy\App\Exports\PendingConsolidationWorkbook;
use Modules\Pharmacy\App\Models\InventoryBalance;
use Modules\Pharmacy\App\Reports\NewProductConsolidation;
use Modules\Pharmacy\App\Reports\PendingConsolidation;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Consolidado de pendientes (Farmacia → Gestión): cantidad que falta enviar por
 * producto y sede (ítems Pendiente y Parcial de pendientes Terminados) y, al lado,
 * la de los productos nuevos abiertos (Sin asignar y Parcial).
 * Ver y exportar: inventory-balances-read. Cálculos en PendingConsolidation y NewProductConsolidation.
 */
new #[Layout('layouts.app')] #[Title('Consolidado de pendientes')] class extends Component
{
    use HasAuthScopedSession;

    #[Session(key: 'inventory-balances.consolidation.search-{authId}')]
    public string $search = '';

    public function mount(): void
    {
        Gate::authorize('viewAny', InventoryBalance::class);
    }

    /**
     * @return array{columns: array<int|string, string>, rows: list<array{product: string, quantities: array<int|string, int>, total: int}>}
     */
    #[Computed]
    public function report(): array
    {
        return app(PendingConsolidation::class)->build($this->search);
    }

    /**
     * @return array{columns: array<int|string, string>, rows: list<array{product: string, quantities: array<int|string, int>, total: int}>}
     */
    #[Computed]
    public function newProductReport(): array
    {
        return app(NewProductConsolidation::class)->build($this->search);
    }

    /**
     * Totales por sede de un reporte (fila final de cada tabla).
     *
     * @param  array{columns: array<int|string, string>, rows: list<array{product: string, quantities: array<int|string, int>, total: int}>}  $report
     * @return array<int|string, int>
     */
    public function columnTotals(array $report): array
    {
        $totals = array_fill_keys(array_keys($report['columns']), 0);

        foreach ($report['rows'] as $row) {
            foreach ($row['quantities'] as $code => $quantity) {
                $totals[$code] += $quantity;
            }
        }

        return $totals;
    }

    public function export(): BinaryFileResponse
    {
        Gate::authorize('viewAny', InventoryBalance::class);

        return Excel::download(new PendingConsolidationWorkbook($this->report, $this->newProductReport),
            'consolidado-pendientes-'.now()->format('Y-m-d_His').'.xlsx');
    }
};
?>

<div>
    @php
        // Dos tablas con la misma estructura: pendientes y productos nuevos.
        $sections = [
            'pendings' => [
                'title' => 'Pendientes',
                'report' => $this->report,
                'help' => 'Ítems Pendiente y Parcial de pendientes terminados.',
                'empty' => 'No hay ítems pendientes ni parciales en pendientes terminados',
            ],
            'new-products' => [
                'title' => 'Productos nuevos',
                'report' => $this->newProductReport,
                'help' => 'Solicitudes Sin asignar y Parciales de productos nuevos.',
                'empty' => 'No hay productos nuevos por enviar',
            ],
        ];
    @endphp

    <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h4 class="card-title mb-0">Consolidado de pendientes</h4>
            <div class="d-flex gap-2">
                <input type="search" wire:model.live.debounce.300ms="search" class="form-control form-control-sm"
                       placeholder="Buscar producto..." aria-label="Buscar producto">
                <x-tooltip text="Exportar el consolidado a Excel (hojas de pendientes y productos nuevos)">
                    <button type="button" wire:click="export" class="btn btn-sm btn-outline-success text-nowrap" aria-label="Exportar a Excel">
                        <i class="bi bi-file-earmark-excel"></i> Excel
                    </button>
                </x-tooltip>
            </div>
        </div>

        <div class="card-body">
            <p class="small text-muted mb-2">Cantidad que falta enviar (solicitada − enviada) por producto y sede.</p>

            <div class="row g-3">
                @foreach ($sections as $key => $section)
                    @php $report = $section['report']; $totals = $this->columnTotals($report); @endphp
                    <div class="col-xxl-6" wire:key="consolidation-section-{{ $key }}">
                        <h5 class="mb-1">{{ $section['title'] }}</h5>
                        <p class="small text-muted mb-2">{{ $section['help'] }}</p>

                        <div class="table-fit">
                            <div class="table-scroll-10 border rounded" wire:loading.class="opacity-50">
                                <table class="table table-striped align-middle mb-0">
                                    <thead>
                                        <tr>
                                            <th>Producto</th>
                                            @foreach ($report['columns'] as $code => $abbreviation)
                                                <th class="col-fit text-end" title="C.C. {{ $code }}">{{ $abbreviation }}</th>
                                            @endforeach
                                            <th class="col-fit text-end">Totales</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse ($report['rows'] as $row)
                                            <tr wire:key="consolidation-{{ $key }}-{{ md5($row['product']) }}">
                                                <td class="fw-semibold">{{ $row['product'] }}</td>
                                                @foreach ($row['quantities'] as $quantity)
                                                    <td @class(['col-fit text-end', 'text-muted' => $quantity === 0])>{{ number_format($quantity) }}</td>
                                                @endforeach
                                                <td class="col-fit text-end fw-bold">{{ number_format($row['total']) }}</td>
                                            </tr>
                                        @empty
                                            <tr><td colspan="{{ count($report['columns']) + 2 }}" class="text-center text-muted py-4">{{ $section['empty'] }}</td></tr>
                                        @endforelse
                                    </tbody>
                                    @if ($report['rows'] !== [])
                                        <tfoot>
                                            <tr class="fw-bold">
                                                <td>Totales</td>
                                                @foreach ($totals as $total)
                                                    <td class="col-fit text-end">{{ number_format($total) }}</td>
                                                @endforeach
                                                <td class="col-fit text-end">{{ number_format(array_sum($totals)) }}</td>
                                            </tr>
                                        </tfoot>
                                    @endif
                                </table>
                            </div>
                        </div>
                        <div class="small text-muted mt-2">{{ count($report['rows']) }} producto(s)</div>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
