{{--
    Cuerpo común del listado de proveedores de un proceso (App\Livewire\Providers\ProviderIndex).
    Lo incluyen las páginas de cada módulo; el tipo de proveedor sale de providerScope().
--}}
@php
    $scope = $this->providerScope();
    $pageIds = $this->linkages->pluck('id')->all();
@endphp

<div x-data>
    <div class="card">
        <div class="card-header"><h4 class="card-title mb-0">Proveedores {{ mb_strtolower($scope->label()) }}s</h4></div>

        <div class="card-body">
            <p class="small text-muted mb-2">
                @if ($scope->requiresForm())
                    Todo proveedor {{ mb_strtolower($scope->label()) }} diligencia el formato FO-300-01 y lo actualiza cada año.
                @else
                    Proveedores con compras hasta 2 SMMLV se registran con datos básicos; si una orden, o lo comprado en el mes,
                    supera el tope, deben diligenciar el FO-300-01 y actualizarlo cada año.
                @endif
                Si el proveedor ya está registrado en otro proceso, use Agregar y digite su identificación para vincularlo.
            </p>

            <x-data-table :paginator="$this->linkages" :per-page-options="$this->perPageOptions()"
                          :columns="$this->columns()" :hidden-columns="$this->activeHiddenColumns()">
                <x-slot:toolbar-right>
                    <x-table.actions :module="$scope->module()" :model-class="\App\Models\ProviderLinkage::class" :selected="$selected"
                                     :single-active="$this->singleEditableSelected()" :selected-count="$this->selectedCount()"
                                     create-event="{{ $scope->requiresForm() ? 'open-provider-create' : 'open-provider-basic-create' }}" edit-event="open-provider-edit" edit-param="linkageId">
                        <x-tooltip text="Ver detalle">
                            <button type="button" wire:click="viewSelected" @disabled(count($selected) !== 1)
                                    class="btn btn-sm btn-outline-secondary btn-table-icon" aria-label="Ver detalle">
                                <i class="bi bi-eye"></i>
                            </button>
                        </x-tooltip>
                    </x-table.actions>
                </x-slot:toolbar-right>

                <x-slot:before-table>
                    <x-table.selection-banner :page-ids="$pageIds" :selected="$selected" :all-matching="$selectAllMatching"
                                              :matching-count="$this->matchingSelectionCount($pageIds)" />
                </x-slot:before-table>

                <x-slot:head>
                    <x-table.head :columns="$this->columns()" :sort-by="$sortBy" :sort-direction="$sortDirection"
                                  :page-ids="$pageIds" :selected="$selected" />
                </x-slot:head>

                <x-slot:filters>
                    <x-table.filters :columns="$this->columns()" />
                </x-slot:filters>

                @forelse ($this->linkages as $linkage)
                    @php
                        $provider = $linkage->provider;
                        $open = $provider->openVersion;
                    @endphp
                    <tr wire:key="provider-linkage-{{ $linkage->id }}" class="{{ $this->rowStatusClass($linkage) }}">
                        <x-table.row-checkbox :id="$linkage->id" :selected="$selected" />
                        <td>{{ $provider->identification }} @if ($linkage->trashed())<span class="badge bg-danger ms-1">Elim</span>@endif</td>
                        <td>
                            <a href="{{ route($this->showRoute(), $linkage) }}" wire:navigate class="fw-semibold">{{ $provider->name }}</a>
                        </td>
                        <td>{{ $provider->person_type === \App\Enums\PersonType::Natural ? 'Natural' : 'Jurídica' }}</td>
                        <td>
                            @if ($provider->risk_level)
                                <span class="badge {{ $provider->risk_level->badgeClass() }}">{{ $provider->risk_level->label() }}</span>
                            @endif
                            @if ($provider->is_pep)<span class="badge bg-dark ms-1">PEP</span>@endif
                        </td>
                        <td><span class="badge {{ $linkage->status->badgeClass() }}">{{ $linkage->status->label() }}</span></td>
                        <td class="text-nowrap">
                            @php $formStatus = $provider->formStatus(); @endphp
                            <span class="badge {{ $formStatus->badgeClass() }}">{{ $formStatus->label() }}</span>
                            @if ($open && $formStatus !== \App\Enums\ProviderFormStatus::InProgress)
                                <span class="badge {{ $open->status->badgeClass() }} ms-1">V{{ $open->number }} · {{ $open->status->label() }}</span>
                            @endif
                            @if ($linkage->form_required_at && in_array($formStatus, [\App\Enums\ProviderFormStatus::None, \App\Enums\ProviderFormStatus::InProgress], true))
                                <span class="badge bg-warning text-dark ms-1" title="{{ $linkage->form_required_reason }}">Requerido</span>
                            @endif
                        </td>
                        <td>{{ $linkage->payment_condition ?? '-' }}</td>
                        <td @class(['text-danger fw-semibold' => $provider->updateIsDue()])>
                            {{ $provider->next_update_on?->format('Y/m/d') ?? '-' }}
                            @if ($provider->updateIsDue())<i class="bi bi-exclamation-circle ms-1" title="Actualización vencida"></i>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="text-center text-muted py-4">Sin resultados</td></tr>
                @endforelse
            </x-data-table>
        </div>
    </div>

    <livewire:providers.provider-form-modal :scope="$scope->value" />
    @unless ($scope->requiresForm())
        <livewire:providers.provider-basic-modal :scope="$scope->value" />
    @endunless
</div>
