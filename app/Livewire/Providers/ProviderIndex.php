<?php

declare(strict_types=1);

namespace App\Livewire\Providers;

use App\Enums\PersonType;
use App\Enums\ProviderLinkageStatus;
use App\Enums\ProviderScope;
use App\Enums\ProviderVersionStatus;
use App\Enums\RiskLevel;
use App\Livewire\Concerns\ExportsRows;
use App\Livewire\Concerns\HasAuthScopedSession;
use App\Livewire\Concerns\HasBulkRowActions;
use App\Livewire\Concerns\HasColumnFilters;
use App\Livewire\Concerns\HasColumnVisibility;
use App\Livewire\Concerns\HasSortableColumns;
use App\Livewire\Concerns\Notifies;
use App\Models\ProviderLinkage;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Attributes\Session;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Listado de proveedores de un proceso: sus vinculaciones con los terceros.
 *
 * Lo extienden las páginas SFC de cada módulo (Administrative y Shopping), que solo fijan el
 * tipo en scope() y su título e incluyen la vista común livewire.providers.provider-index-body.
 * Filtros, orden y columnas se guardan por usuario y por tipo de proveedor.
 */
abstract class ProviderIndex extends Component
{
    use ExportsRows, HasAuthScopedSession, HasBulkRowActions, HasColumnFilters,
        HasColumnVisibility, HasSortableColumns, Notifies, WithPagination;

    // Tipo de proveedor como texto, para las claves de sesión (se fija en boot()).
    public string $scopeKey = '';

    #[Session(key: 'providers.index.filterIdentification-{scopeKey}-{authId}')]
    public string $filterIdentification = '';

    #[Session(key: 'providers.index.filterName-{scopeKey}-{authId}')]
    public string $filterName = '';

    #[Session(key: 'providers.index.filterPersonType-{scopeKey}-{authId}')]
    public string $filterPersonType = '';

    #[Session(key: 'providers.index.filterRisk-{scopeKey}-{authId}')]
    public string $filterRisk = '';

    #[Session(key: 'providers.index.filterStatus-{scopeKey}-{authId}')]
    public string $filterStatus = '';

    #[Session(key: 'providers.index.perPage-{scopeKey}-{authId}')]
    public int $perPage = 10;

    /** @var list<string> */
    #[Session(key: 'providers.index.hiddenColumns-{scopeKey}-{authId}')]
    public array $hiddenColumns = [];

    #[Session(key: 'providers.index.sortBy-{scopeKey}-{authId}')]
    public string $sortBy = 'providers.name';

    #[Session(key: 'providers.index.sortDirection-{scopeKey}-{authId}')]
    public string $sortDirection = 'asc';

    /** Tipo de proveedor que gestiona la pantalla. */
    abstract protected function scope(): ProviderScope;

    public function boot(): void
    {
        $this->scopeKey = $this->scope()->value;
    }

    public function mount(): void
    {
        Gate::authorize('viewAny', [ProviderLinkage::class, $this->scope()]);
    }

    protected function rowModelClass(): string
    {
        return ProviderLinkage::class;
    }

    protected function computedRowsProperty(): string
    {
        return 'linkages';
    }

    /**
     * Vinculaciones del proceso con los datos del tercero (incluidas las eliminadas).
     * Se une con providers para filtrar y ordenar por sus columnas.
     *
     * @return Builder<ProviderLinkage>
     */
    protected function baseQuery(): Builder
    {
        $query = ProviderLinkage::query()->withTrashed()
            ->join('providers', 'providers.id', '=', 'provider_linkages.provider_id')
            ->select('provider_linkages.*')
            ->where('provider_linkages.scope', $this->scope())
            ->with(['provider.openVersion']);

        return $this->applyColumnFilters($query);
    }

    /** @return LengthAwarePaginator<int, ProviderLinkage> */
    #[Computed]
    public function linkages(): LengthAwarePaginator
    {
        return $this->applySort($this->baseQuery())->paginate($this->resolvedPerPage());
    }

    #[On('provider-saved')]
    public function refreshLinkages(): void
    {
        unset($this->linkages);
    }

    /**
     * Edición: una sola vinculación seleccionada de un proveedor básico, con un Borrador abierto o,
     * si está aprobado y sin versión en trámite, que el usuario pueda abrir su actualización.
     */
    public function singleEditableSelected(): bool
    {
        if (count($this->selected) !== 1) {
            return false;
        }

        $linkage = ProviderLinkage::with('provider.openVersion')->find($this->selectedIds()[0]);

        return $linkage !== null
            && (Gate::allows('updateBasic', $linkage) || Gate::allows('update', $linkage) || Gate::allows('startUpdate', $linkage));
    }

    /** Abre el detalle de la única vinculación seleccionada. */
    public function viewSelected(): void
    {
        if ($this->selectAllMatching || count($this->selected) !== 1) {
            return;
        }

        $linkage = ProviderLinkage::query()->where('scope', $this->scope())->findOrFail($this->selectedIds()[0]);
        Gate::authorize('view', $linkage);

        $this->redirectRoute($this->showRoute(), ['linkage' => $linkage->id], navigate: true);
    }

    public function deleteSelected(): void
    {
        $this->runBulkAction('delete', fn (ProviderLinkage $row) => $row->delete(), 'eliminado(s)',
            'solo se eliminan vinculaciones Pendientes o Rechazadas (las aprobadas se inactivan).');
    }

    public function toggleSelected(): void
    {
        $this->runBulkAction('toggle', fn (ProviderLinkage $row) => $row->toggleStatus(), 'actualizado(s)',
            'solo se activan o inactivan vinculaciones aprobadas.');
    }

    /** Inactiva y rechazada se distinguen en amarillo; eliminada en rojo. */
    public function rowStatusClass(mixed $row): string
    {
        if (! $row instanceof ProviderLinkage) {
            return '';
        }

        if ($row->trashed()) {
            return 'table-danger';
        }

        return in_array($row->status, [ProviderLinkageStatus::Inactive, ProviderLinkageStatus::Rejected], true) ? 'table-warning' : '';
    }

    /** Nombre de la ruta del detalle en el módulo del proceso. */
    public function showRoute(): string
    {
        return "{$this->scope()->module()}.show";
    }

    protected function exportFileName(): string
    {
        return 'proveedores-'.$this->scope()->value;
    }

    /** @return list<string> */
    protected function exportHeadings(): array
    {
        return [
            'Tipo de persona', 'Tipo de documento', 'Identificación', 'Nombre o razón social', 'Riesgo', 'PEP',
            'Vinculación', 'Segmento', 'Condición de pago', 'Plazo de pago', 'FO-300-01',
            'Próxima actualización', 'Aprobada', 'Eliminada',
        ];
    }

    /** @return list<mixed> */
    protected function exportRow(Model $row): array
    {
        if (! $row instanceof ProviderLinkage) {
            return [];
        }

        $provider = $row->provider;
        $open = $provider->openVersion;

        return [
            $provider->person_type->label(), $provider->document_type->value, $provider->identification, $provider->name,
            $provider->risk_level?->label(), $provider->is_pep ? 'Sí' : 'No', $row->status->label(), $row->commercial_segment,
            $row->payment_condition, $row->payment_term,
            $provider->formStatus()->label().($open ? " (V{$open->number} · {$open->status->label()})" : ''),
            $provider->next_update_on?->format('Y-m-d'), $row->approved_at?->format('Y-m-d'), $row->trashed() ? 'Sí' : 'No',
        ];
    }

    /** @return list<string> */
    protected function sortableColumns(): array
    {
        return array_keys($this->columns());
    }

    /**
     * La columna FO-300-01 se ordena por la situación del formato (ProviderFormStatus, que se
     * calcula): Sin FO-300-01, En trámite, Vigente y Vencido; a igual situación, por nombre.
     *
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query
     * @return Builder<TModel>
     */
    protected function applySort(Builder $query): Builder
    {
        if ($this->sortBy !== 'open_version') {
            return $query->orderBy(in_array($this->sortBy, $this->sortableColumns(), true) ? $this->sortBy : 'providers.name',
                $this->sortDirection === 'desc' ? 'desc' : 'asc');
        }

        $direction = $this->sortDirection === 'desc' ? 'desc' : 'asc';
        $open = array_map(fn (ProviderVersionStatus $status): string => $status->value, ProviderVersionStatus::open());
        $placeholders = implode(', ', array_fill(0, count($open), '?'));

        return $query->orderByRaw(
            "CASE
                WHEN providers.current_version_id IS NULL THEN
                    CASE WHEN EXISTS (SELECT 1 FROM provider_versions pv WHERE pv.provider_id = providers.id
                        AND pv.deleted_at IS NULL AND pv.status IN ({$placeholders})) THEN 1 ELSE 0 END
                WHEN providers.next_update_on IS NOT NULL AND providers.next_update_on <= ? THEN 3
                ELSE 2
            END {$direction}",
            [...$open, today()->toDateString()],
        )->orderBy('providers.name');
    }

    /**
     * Fuente única de verdad: encabezado, filtro y orden de cada columna.
     *
     * @return array<string, array<string, mixed>>
     */
    protected function columns(): array
    {
        return [
            'providers.identification' => ['label' => 'Identificación', 'filter' => 'filterIdentification'],
            'providers.name' => ['label' => 'Nombre o razón social', 'width' => 'xl', 'filter' => 'filterName', 'hideable' => false],
            'providers.person_type' => ['label' => 'Persona', 'filter' => 'filterPersonType', 'type' => 'select', 'options' => PersonType::options()],
            'providers.risk_level' => ['label' => 'Riesgo', 'filter' => 'filterRisk', 'type' => 'select', 'options' => RiskLevel::options()],
            'provider_linkages.status' => ['label' => 'Vinculación', 'filter' => 'filterStatus', 'type' => 'select', 'options' => ProviderLinkageStatus::options()],
            'open_version' => ['label' => 'FO-300-01', 'filter' => null],
            'provider_linkages.payment_condition' => ['label' => 'Condición de pago', 'width' => 'sm', 'filter' => null],
            'providers.next_update_on' => ['label' => 'Próx. actualización', 'filter' => null],
        ];
    }

    /** Tipo de proveedor para la vista común. */
    public function providerScope(): ProviderScope
    {
        return $this->scope();
    }
}
