{{--
    Cuerpo común del detalle de un proveedor (App\Livewire\Providers\ProviderShow).
    Lo incluyen las páginas de cada módulo; el tipo de proveedor sale de providerScope().
--}}
@use('App\Enums\IdentityDocumentType')
@use('App\Enums\PersonType')
@use('App\Enums\ProviderDocumentType')
@use('App\Enums\ProviderFormStatus')
@use('App\Enums\ProviderVersionStatus')

@php
    $scope = $this->providerScope();
    $linkage = $this->freshLinkage;
    $provider = $linkage->provider;
    $version = $this->version;
    $sensitive = $this->canViewSensitive;
    $editing = $this->canEditDraft;
    $options = $this->characteristicOptions;
    $date = fn ($value) => $value?->format('Y/m/d');
    $money = fn ($value) => $value === null ? null : '$ '.number_format((float) $value, 2, ',', '.');
    $yesNo = fn (bool $value) => $value ? 'Sí' : 'No';
    $otherLinkages = $provider->linkages->reject(fn ($other) => $other->scope === $scope);
    $formStatus = $provider->formStatus();
    $startLabel = $provider->current_version_id !== null ? 'Actualizar FO-300-01' : 'Diligenciar FO-300-01';
    $viewingDefault = $this->viewingDefault;
    $years = $this->years;
@endphp

<div x-data="{ showYears: @js(! $viewingDefault) }">
    {{-- Encabezado: tercero y vinculación con este proceso --}}
    <x-document.header :title="$provider->name" icon="bi-person-vcard"
                       :subtitle="$provider->document_type->value.' '.$provider->identification.' · '.$provider->person_type->label()"
                       :status="'Vinculación '.$linkage->status->label()" :status-class="$linkage->status->badgeClass()"
                       :back="route($scope->module().'.index')">
        <x-slot:actions>
            @if ($version)
                <x-tooltip text="Descargar PDF del formato">
                    <button type="button" wire:click="downloadPdf({{ $version->id }})" class="btn btn-sm btn-outline-secondary" aria-label="Descargar PDF">
                        <i class="bi bi-file-earmark-pdf" aria-hidden="true"></i> PDF
                    </button>
                </x-tooltip>
            @endif
            @can('updateTerms', $linkage)
                <button type="button" wire:click="openTerms" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-cash-stack" aria-hidden="true"></i> Condiciones
                </button>
            @endcan
            @can('updateBasic', $linkage)
                <button type="button" wire:click="$dispatch('open-provider-basic-edit', { linkageId: {{ $linkage->id }} })" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i> Editar datos básicos
                </button>
            @endcan
            @if ($viewingDefault && auth()->user()->can('update', $linkage))
                <button type="button" wire:click="editDraft" class="btn btn-sm btn-primary">
                    <i class="bi bi-pencil-square" aria-hidden="true"></i> Editar borrador
                </button>
            @endif
            @can('correctRejected', $linkage)
                <button type="button" class="btn btn-sm btn-primary"
                        x-on:click="confirmAction({ title: 'Corregir FO-300-01', text: 'El formato rechazado vuelve a Borrador para corregirlo y enviarlo de nuevo. Las vinculaciones rechazadas quedan pendientes.' }).then(() => $wire.correctRejected()).catch(() => {})">
                    <i class="bi bi-pencil" aria-hidden="true"></i> Corregir FO-300-01
                </button>
            @endcan
            @can('startUpdate', $linkage)
                <button type="button" class="btn btn-sm btn-primary"
                        x-on:click="confirmAction({ title: @js($startLabel), text: @js($provider->current_version_id ? 'Se creará una nueva versión en Borrador copiada de la vigente.' : 'Se abrirá el FO-300-01 en Borrador con los datos básicos del proveedor.') }).then(() => $wire.startUpdate()).catch(() => {})">
                    <i class="bi bi-arrow-repeat" aria-hidden="true"></i> {{ $startLabel }}
                </button>
            @endcan
        </x-slot:actions>

        <x-document.section title="Proveedor" icon="bi-person-vcard">
            <x-document.field label="Riesgo">
                @if ($provider->risk_level)<span class="badge {{ $provider->risk_level->badgeClass() }}">{{ $provider->risk_level->label() }}</span>@else - @endif
                @if ($provider->is_pep)<span class="badge bg-dark ms-1">PEP</span>@endif
            </x-document.field>
            <x-document.field label="Correo" :value="$provider->email" class="text-break" />
            <x-document.field label="Teléfono" :value="$provider->phone" />
            <x-document.field label="Ubicación" :value="$provider->municipality ? $provider->address.' · '.$provider->municipality->name.' ('.$provider->municipality->parent?->name.')' : $provider->address" />
            <x-document.field label="Contacto" :value="$provider->contact_name" />
        </x-document.section>

        <x-document.section title="Vinculación {{ mb_strtolower($scope->label()) }}" icon="bi-link-45deg">
            @if ($scope === \App\Enums\ProviderScope::Commercial)
                <x-document.field label="Segmento" :value="$linkage->commercial_segment" />
            @endif
            <x-document.field label="Condición de pago" :value="$linkage->payment_condition" />
            <x-document.field label="Plazo de pago" :value="$linkage->payment_term" />
            <x-document.field label="Aprobada el" :value="$date($linkage->approved_at)" />
        </x-document.section>

        <x-document.section title="FO-300-01" icon="bi-calendar-check">
            <x-document.field label="Estado">
                <span class="badge {{ $formStatus->badgeClass() }}">{{ $formStatus->label() }}</span>
            </x-document.field>
            <x-document.field label="Próxima actualización">
                <span @class(['text-danger fw-semibold' => $provider->updateIsDue()])>{{ $date($provider->next_update_on) ?? '-' }}</span>
                @if ($provider->updateIsDue())<small class="d-block text-danger">Vencida: no se pueden hacer órdenes hasta actualizarlo.</small>@endif
            </x-document.field>
            <x-document.field label="Otros procesos"
                              :value="$otherLinkages->map(fn ($other) => $other->scope->label().' ('.$other->status->label().')')->join(', ') ?: 'Ninguno'" />
            <x-document.field label="Años">
                @if ($years === [])
                    -
                @else
                    <button type="button" class="btn btn-sm btn-outline-primary py-0" x-on:click="showYears = ! showYears" :aria-expanded="showYears">
                        <i class="bi bi-calendar3" aria-hidden="true"></i> Ver años ({{ count($years) }})
                    </button>
                @endif
            </x-document.field>
        </x-document.section>
    </x-document.header>

    {{-- Años del FO-300-01: una caja por versión y las informativas del sistema anterior --}}
    @if ($years !== [])
        <div class="card provider-years-card" x-show="showYears" x-cloak>
            <div class="card-header d-flex flex-wrap align-items-center gap-2">
                <h5 class="mb-0 me-auto"><i class="bi bi-calendar3" aria-hidden="true"></i> Años del FO-300-01</h5>
                <small class="text-body-secondary">Elige un año para consultar su formato.</small>
            </div>
            <div class="card-body">
                <div class="provider-years">
                    @foreach ($years as $box)
                        @if ($box['version'])
                            @php
                                $item = $box['version'];
                            @endphp
                            <button type="button" wire:click="selectVersion({{ $item->id }})" wire:key="year-version-{{ $item->id }}"
                                    @class(['provider-year', 'active' => $item->id === $version?->id])
                                    aria-pressed="{{ $item->id === $version?->id ? 'true' : 'false' }}">
                                <span class="provider-year-number">{{ $box['year'] }}</span>
                                <span class="badge {{ $item->id === $provider->current_version_id ? 'bg-success' : $item->status->badgeClass() }}">
                                    {{ $item->id === $provider->current_version_id ? 'Vigente' : $item->status->label() }}
                                </span>
                                <span class="provider-year-meta">V{{ $item->number }} · {{ $item->request_type->label() }}</span>
                                <span class="provider-year-meta">{{ $box['date'] }}</span>
                            </button>
                        @else
                            <div class="provider-year provider-year-legacy" wire:key="year-legacy-{{ $box['year'] }}"
                                 title="Actualización registrada en el sistema anterior, sin formato digital.">
                                <span class="provider-year-number">{{ $box['year'] }}</span>
                                <span class="badge bg-light text-dark border">Sistema anterior</span>
                                <span class="provider-year-meta">Sin formato digital</span>
                                <span class="provider-year-meta">{{ $box['date'] ?? '-' }}</span>
                            </div>
                        @endif
                    @endforeach
                </div>
            </div>
        </div>
    @endif

    @unless ($viewingDefault)
        <div class="alert alert-info d-flex flex-wrap align-items-center gap-2">
            <i class="bi bi-eye" aria-hidden="true"></i>
            <div class="me-auto">
                Estás consultando el FO-300-01 de <strong>{{ $version?->created_at?->year }}</strong> (versión {{ $version?->number }}), solo lectura.
            </div>
            <button type="button" wire:click="showDefaultVersion" class="btn btn-sm btn-outline-primary">
                <i class="bi bi-arrow-left" aria-hidden="true"></i> Volver al formato actual
            </button>
        </div>
    @endunless

    @if ($linkage->form_required_at && in_array($formStatus, [ProviderFormStatus::None, ProviderFormStatus::InProgress], true))
        <div class="alert alert-warning d-flex gap-2">
            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
            <div>
                <strong>FO-300-01 requerido</strong> desde el {{ $date($linkage->form_required_at) }}: {{ $linkage->form_required_reason }}
                supera el tope de compras sin formato. Diligéncielo para seguir comprando sin restricciones.
            </div>
        </div>
    @endif

    @if ($version === null)
        <div class="alert alert-info d-flex gap-2">
            <i class="bi bi-info-circle" aria-hidden="true"></i>
            <div>
                Proveedor básico, sin FO-300-01: se le compra hasta 2 SMMLV por orden y por mes. Si se supera el tope,
                use "Diligenciar FO-300-01".
                @if ($years !== [])
                    Tiene formatos de años anteriores: consúltelos en "Ver años".
                @endif
            </div>
        </div>
    @else
        {{-- Formulario FO-300-01 de la versión mostrada --}}
        <div class="card">
            <div class="card-header d-flex flex-wrap align-items-center gap-2">
                <h5 class="mb-0 me-auto">
                    <i class="bi bi-file-earmark-text" aria-hidden="true"></i>
                    FO-300-01 · Versión {{ $version->number }} ({{ $version->request_type->label() }})
                </h5>
                <span class="badge {{ $version->status->badgeClass() }}">{{ $version->status->label() }}</span>
                <span class="badge {{ $version->risk_level->badgeClass() }}">Riesgo {{ $version->risk_level->label() }}</span>
            </div>
            <div class="card-body">
                @if ($version->rejected_at)
                    <div @class(['alert d-flex gap-2', 'alert-danger' => $version->status === ProviderVersionStatus::Rejected, 'alert-warning' => $version->status !== ProviderVersionStatus::Rejected])>
                        <i class="bi bi-x-octagon" aria-hidden="true"></i>
                        <div>
                            <strong>{{ $version->status === ProviderVersionStatus::Rejected ? 'Rechazado' : 'Corrección de un rechazo' }}</strong>
                            el {{ $date($version->rejected_at) }}{{ $version->rejecter ? ' por '.$version->rejecter->fullName() : '' }}:
                            {{ $version->rejection_reason }}
                            @if ($version->status === ProviderVersionStatus::Rejected && $viewingDefault)
                                <div class="small mt-1">Para que sea aceptado, quien tenga el permiso de reversar usa "Corregir FO-300-01", se ajusta el Borrador y se envía de nuevo.</div>
                            @endif
                        </div>
                    </div>
                @endif
                <div class="document-sections">
                    <x-document.section title="Identificación" icon="bi-person">
                        <x-document.field label="Documento" :value="$version->document_type->label().' '.$version->identification" />
                        @if ($version->isNatural())
                            <x-document.field label="Expedición" :value="$date($version->document_issued_on)" />
                            <x-document.field label="Nacimiento" :value="$date($version->birth_date)" />
                            <x-document.field label="Lugar de nacimiento" :value="$version->birthMunicipality ? $version->birthMunicipality->name.' ('.$version->birthDepartment?->name.')' : null" />
                            <x-document.field label="Nacionalidad" :value="$version->nationality?->name" />
                            <x-document.field label="País de residencia" :value="$version->country?->name" />
                        @else
                            <x-document.field label="País de constitución" :value="$version->country?->name" />
                            <x-document.field label="Régimen tributario" :value="$version->tax_regime" />
                            <x-document.field label="Tipo de empresa" :value="$version->company_type?->label()" />
                            <x-document.field label="Representante legal"
                                              :value="$version->legal_representative_name ? $version->legal_representative_name.' · '.$version->legal_representative_document_type?->value.' '.$version->legal_representative_identification : null" />
                        @endif
                    </x-document.section>

                    <x-document.section title="Domicilio y actividad" icon="bi-geo-alt">
                        <x-document.field label="Dirección" :value="$version->address" />
                        @if ($version->isNatural())
                            <x-document.field label="Zona" :value="$version->residence_zone?->label()" />
                        @endif
                        <x-document.field label="CIIU" :value="$version->economicActivity?->label()" />
                        <x-document.field label="Actividad" :value="$version->economic_activity_description" />
                    </x-document.section>

                    @if ($version->isNatural())
                        <x-document.section title="Perfil" icon="bi-mortarboard">
                            <x-document.field label="Educación" :value="$version->education_level" />
                            <x-document.field label="Ocupación" :value="trim($version->occupation.' '.$version->occupation_other)" />
                            <x-document.field label="Empresa donde labora" :value="$version->employer_name" />
                            <x-document.field label="Representante (menor)" :value="$version->minor_representative_name" />
                            @if ($sensitive)
                                <x-document.field label="Grupo de protección" :value="trim($version->protection_group.' '.$version->protection_group_other)" />
                            @endif
                        </x-document.section>
                    @endif

                    @if ($sensitive)
                        <x-document.section title="Información financiera" icon="bi-cash-coin">
                            <x-document.field label="Ingresos mensuales" :value="$money($version->monthly_income)" />
                            <x-document.field label="Otros ingresos" :value="$money($version->other_income)" />
                            @unless ($version->isNatural())
                                <x-document.field label="Activos" :value="$money($version->total_assets)" />
                                <x-document.field label="Pasivos" :value="$money($version->total_liabilities)" />
                                <x-document.field label="Patrimonio" :value="$money($version->total_equity)" />
                                <x-document.field label="Insolvencia" :value="$yesNo($version->in_insolvency)" />
                            @endunless
                            <x-document.field label="Moneda extranjera" :value="$version->foreign_currency_operations ? 'Sí: '.$version->foreign_currency_detail : 'No'" />
                        </x-document.section>

                        <x-document.section title="PEP" icon="bi-bank">
                            <x-document.field label="Recursos públicos" :value="$yesNo($version->manages_public_resources)" />
                            <x-document.field label="Es PEP" :value="$version->declares_pep ? 'Sí: '.$version->public_office : 'No'" />
                            @if ($version->declares_pep)
                                <x-document.field label="Cargo" :value="$date($version->pep_starts_on).' a '.($date($version->pep_ends_on) ?? 'vigente')" />
                            @endif
                            <x-document.field label="Relación con PEP" :value="$yesNo($version->has_pep_relationship)" />
                        </x-document.section>
                    @else
                        <x-document.section title="Datos reservados" icon="bi-lock">
                            <x-document.field label="Información financiera y PEP" value="Solo para quien verifica y aprueba (Ley 1581 de 2012)." />
                        </x-document.section>
                    @endif
                </div>

                {{-- Contactos y declaraciones --}}
                <div class="row g-3 mt-1">
                    <div class="col-lg-8">
                        <h6 class="fw-semibold"><i class="bi bi-telephone" aria-hidden="true"></i> Contactos</h6>
                        <div class="table-responsive border rounded">
                            <table class="table table-sm align-middle mb-0">
                                <thead><tr><th>Cargo</th><th>Nombre</th><th>Teléfono</th><th>Correo</th></tr></thead>
                                <tbody>
                                    @forelse ($version->contacts as $contact)
                                        <tr wire:key="contact-{{ $contact->id }}">
                                            <td>{{ $contact->role->label() }}</td><td>{{ $contact->name ?? '-' }}</td>
                                            <td>{{ $contact->phone ?? '-' }}</td><td class="text-break">{{ $contact->email ?? '-' }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="text-center text-body-secondary py-3">Sin contactos registrados</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        @if ($version->dispatch_schedule)
                            <p class="small mt-2 mb-0"><strong>Despacho de pedidos:</strong> {{ $version->dispatch_schedule }}</p>
                        @endif
                    </div>
                    <div class="col-lg-4">
                        <h6 class="fw-semibold"><i class="bi bi-patch-check" aria-hidden="true"></i> Declaraciones</h6>
                        <ul class="list-unstyled small mb-0">
                            @foreach ([
                                'accepts_truthfulness' => 'Veracidad', 'accepts_data_processing' => 'Tratamiento de datos',
                                'accepts_information_received' => 'Información recibida', 'accepts_funds_origin' => 'Origen de fondos',
                                'accepts_risk_bureau' => 'Centrales de riesgo', 'accepts_electronic_means' => 'Mecanismos electrónicos',
                            ] as $field => $label)
                                @continue($field === 'accepts_electronic_means' && ! $version->isNatural())
                                <li>
                                    <i @class(['bi', 'bi-check-circle-fill text-success' => $version->{$field}, 'bi-x-circle text-danger' => ! $version->{$field}]) aria-hidden="true"></i>
                                    {{ $label }}
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>

        {{-- Accionistas (solo persona jurídica) --}}
        @if ($version->person_type === PersonType::Legal)
            <x-document.lines title="Accionistas, propietarios o consorciados (5 % o más)" icon="bi-diagram-2" entry-title="Agregar accionista">
                @if ($editing)
                    <x-slot:entry>
                        <form wire:submit="addShareholder" class="row g-3 align-items-start">
                            <div class="col-lg-2 col-md-4">
                                <x-forms.select name="shareholderForm.document_type" id="shareholder-type" label="Tipo" required
                                                :options="IdentityDocumentType::optionsFor(IdentityDocumentType::forShareholders())" />
                            </div>
                            <div class="col-lg-2 col-md-4"><x-forms.input name="shareholderForm.identification" id="shareholder-id" label="Identificación" required /></div>
                            <div class="col-lg-3 col-md-4"><x-forms.input name="shareholderForm.name" id="shareholder-name" label="Nombre o razón social" required /></div>
                            <div class="col-lg-2 col-md-4"><x-forms.input name="shareholderForm.legal_representative_name" id="shareholder-rep" label="Representante legal" /></div>
                            <div class="col-lg-1 col-md-4"><x-forms.input name="shareholderForm.ownership_percentage" id="shareholder-pct" label="%" type="number" step="0.01" min="5" max="100" required /></div>
                            <div class="col-lg-2 document-entry-actions">
                                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="addShareholder">
                                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Agregar
                                </button>
                            </div>
                        </form>
                    </x-slot:entry>
                @endif

                <div class="table-responsive border rounded document-lines-table">
                    <table class="table align-middle">
                        <thead><tr><th class="col-fit">Documento</th><th>Nombre o razón social</th><th>Representante legal</th><th class="col-fit text-end">%</th><th class="col-fit text-end">Acciones</th></tr></thead>
                        <tbody>
                            @forelse ($version->shareholders as $shareholder)
                                <tr wire:key="shareholder-{{ $shareholder->id }}">
                                    <td class="text-nowrap">{{ $shareholder->document_type->value }} {{ $shareholder->identification }}</td>
                                    <td>{{ $shareholder->name }}</td>
                                    <td>{{ $shareholder->legal_representative_name ?? '-' }}</td>
                                    <td class="text-end">{{ number_format((float) $shareholder->ownership_percentage, 2) }}</td>
                                    <td class="text-end">
                                        @if ($editing)
                                            <x-tooltip text="Quitar accionista">
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-table-icon" aria-label="Quitar accionista"
                                                        x-on:click="confirmAction({ title: 'Quitar accionista', text: '¿Quitar este accionista de la versión?' }).then(() => $wire.removeShareholder({{ $shareholder->id }})).catch(() => {})">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </x-tooltip>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <x-document.empty-row colspan="5" text="Sin accionistas registrados" />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-document.lines>
        @endif

        {{-- Familiares o personas relacionadas con un PEP (dato sensible) --}}
        @if ($sensitive || $editing)
            <x-document.lines title="Grupo familiar o relación con PEP" icon="bi-people" entry-title="Agregar persona relacionada">
                @if ($editing)
                    <x-slot:entry>
                        <form wire:submit="addRelative" class="row g-3 align-items-start">
                            <div class="col-lg-2 col-md-4">
                                <x-forms.select name="relativeForm.document_type" id="relative-type" label="Tipo"
                                                :options="IdentityDocumentType::optionsFor(IdentityDocumentType::forPeople())" placeholder="-" />
                            </div>
                            <div class="col-lg-2 col-md-4"><x-forms.input name="relativeForm.identification" id="relative-id" label="Número" /></div>
                            <div class="col-lg-3 col-md-4"><x-forms.input name="relativeForm.name" id="relative-name" label="Nombres y apellidos" required /></div>
                            <div class="col-lg-2 col-md-4">
                                <x-forms.select name="relativeForm.relationship" id="relative-relationship" label="Parentesco" required
                                                :options="$options['relationship']" placeholder="Seleccione" />
                            </div>
                            <div class="col-lg-3 col-md-8 d-flex align-items-end">
                                <x-forms.checkbox name="relativeForm.is_pep" id="relative-is-pep" label="Es PEP" model-modifier="live" class="mb-2" />
                            </div>
                            @if ($relativeForm->is_pep)
                                <div class="col-lg-4 col-md-6"><x-forms.input name="relativeForm.public_office" id="relative-office" label="Cargo PEP" required /></div>
                                <div class="col-lg-2 col-md-3"><x-forms.input name="relativeForm.pep_starts_on" id="relative-starts" label="Inicio" type="date" /></div>
                                <div class="col-lg-2 col-md-3"><x-forms.input name="relativeForm.pep_ends_on" id="relative-ends" label="Fin" type="date" hint="Vacío si sigue vigente." /></div>
                            @endif
                            <div class="col-lg-2 document-entry-actions">
                                <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="addRelative">
                                    <i class="bi bi-plus-lg" aria-hidden="true"></i> Agregar
                                </button>
                            </div>
                        </form>
                    </x-slot:entry>
                @endif

                <div class="table-responsive border rounded document-lines-table">
                    <table class="table align-middle">
                        <thead><tr><th class="col-fit">Documento</th><th>Nombre</th><th>Parentesco</th><th>PEP</th><th class="col-fit text-end">Acciones</th></tr></thead>
                        <tbody>
                            @forelse ($version->pepRelatives as $relative)
                                <tr wire:key="relative-{{ $relative->id }}">
                                    <td class="text-nowrap">{{ trim($relative->document_type?->value.' '.$relative->identification) ?: '-' }}</td>
                                    <td>{{ $relative->name }}</td>
                                    <td>{{ $relative->relationship }}</td>
                                    <td>{{ $relative->is_pep ? 'Sí · '.$relative->public_office.' ('.($date($relative->pep_ends_on) ?? 'vigente').')' : 'No' }}</td>
                                    <td class="text-end">
                                        @if ($editing)
                                            <x-tooltip text="Quitar persona">
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-table-icon" aria-label="Quitar persona"
                                                        x-on:click="confirmAction({ title: 'Quitar persona', text: '¿Quitar esta persona de la versión?' }).then(() => $wire.removeRelative({{ $relative->id }})).catch(() => {})">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </x-tooltip>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <x-document.empty-row colspan="5" text="Sin personas relacionadas" />
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </x-document.lines>
        @endif

        {{-- Anexos --}}
        <x-document.lines title="Documentos anexos" icon="bi-paperclip" entry-title="Cargar anexo" entry-icon="bi-upload">
            @if ($editing)
                <x-slot:entry>
                    <form wire:submit="addDocument" class="row g-3 align-items-start">
                        <div class="col-lg-4 col-md-6">
                            <x-forms.select name="documentForm.type" id="document-type" label="Tipo de anexo" required
                                            :options="ProviderDocumentType::options()" placeholder="Seleccione" model-modifier="live" />
                        </div>
                        @if (ProviderDocumentType::tryFrom($documentForm->type)?->requiresIssueDate())
                            <div class="col-lg-2 col-md-6"><x-forms.input name="documentForm.issued_on" id="document-issued" label="Expedición" type="date" required /></div>
                        @endif
                        <div class="col-lg-4 col-md-8">
                            <x-forms.field id="document-file" name="documentForm.file" label="Archivo" required hint="PDF, JPG o PNG; máximo 5 MB.">
                                <input id="document-file" type="file" wire:model="documentForm.file" accept=".pdf,.jpg,.jpeg,.png"
                                       @class(['form-control', 'is-invalid' => $errors->has('documentForm.file')])>
                            </x-forms.field>
                        </div>
                        <div class="col-lg-2 document-entry-actions">
                            <button type="submit" class="btn btn-primary" wire:loading.attr="disabled" wire:target="addDocument,documentForm.file">
                                <i class="bi bi-upload" aria-hidden="true"></i> Cargar
                            </button>
                        </div>
                    </form>
                </x-slot:entry>
            @endif

            <div class="table-responsive border rounded document-lines-table">
                <table class="table align-middle">
                    <thead><tr><th>Tipo</th><th>Archivo</th><th class="col-fit">Expedición</th><th class="col-fit">Cargado</th><th class="col-fit text-end">Acciones</th></tr></thead>
                    <tbody>
                        @forelse ($version->documents as $document)
                            <tr wire:key="document-{{ $document->id }}">
                                <td>
                                    {{ $document->type->label() }}
                                    @if ($document->isExpired())<span class="badge bg-danger ms-1">Vencido</span>@endif
                                </td>
                                <td class="text-break">{{ $document->original_name }}</td>
                                <td>{{ $date($document->issued_on) ?? '-' }}</td>
                                <td class="text-nowrap">{{ $date($document->created_at) }} · {{ $document->uploader?->fullName() }}</td>
                                <td class="text-end">
                                    <div class="table-actions-group">
                                        <x-tooltip text="Descargar">
                                            <button type="button" wire:click="downloadDocument({{ $document->id }})" class="btn btn-sm btn-outline-primary btn-table-icon" aria-label="Descargar anexo">
                                                <i class="bi bi-download"></i>
                                            </button>
                                        </x-tooltip>
                                        @if ($editing)
                                            <x-tooltip text="Quitar anexo">
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-table-icon" aria-label="Quitar anexo"
                                                        x-on:click="confirmAction({ title: 'Quitar anexo', text: '¿Quitar este anexo de la versión?' }).then(() => $wire.removeDocument({{ $document->id }})).catch(() => {})">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </x-tooltip>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <x-document.empty-row colspan="5" text="Sin anexos"
                                                  hint="Documento de identificación, cámara de comercio (máx. 3 meses), RUT y políticas de devolución." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-document.lines>

        {{-- Revisión: firmas y acciones según el estado --}}
        <div class="card">
            <div class="card-header"><h5 class="mb-0"><i class="bi bi-pen" aria-hidden="true"></i> Verificación y aprobación</h5></div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-lg-7">
                        <ul class="list-group">
                            <li class="list-group-item">
                                <strong>Enviada a verificación:</strong>
                                {{ $version->submitted_at ? $date($version->submitted_at).' · '.$version->submitter?->fullName() : 'Pendiente' }}
                            </li>
                            <li class="list-group-item">
                                <strong>Verificación:</strong>
                                {{ $version->verified_at ? $date($version->verified_at).' · '.$version->verifier?->fullName() : 'Pendiente' }}
                                @if ($version->verification_observations)<div class="small text-body-secondary">{{ $version->verification_observations }}</div>@endif
                            </li>
                            @if ($version->is_pep)
                                <li class="list-group-item">
                                    <strong>Oficial de cumplimiento:</strong>
                                    {{ $version->compliance_at ? $date($version->compliance_at).' · '.$version->complianceOfficer?->fullName() : 'Pendiente ('.($this->complianceOfficerName ?? 'sin configurar en Datos de la empresa').')' }}
                                    @if ($version->compliance_observations)<div class="small text-body-secondary">{{ $version->compliance_observations }}</div>@endif
                                </li>
                            @endif
                            @foreach ($version->approvals as $approval)
                                <li class="list-group-item" wire:key="approval-{{ $approval->id }}">
                                    <strong>Aprobación {{ mb_strtolower($approval->scope->label()) }}:</strong>
                                    {{ $date($approval->approved_at) }} · {{ $approval->user?->fullName() }}
                                    @if ($approval->observations)<div class="small text-body-secondary">{{ $approval->observations }}</div>@endif
                                </li>
                            @endforeach
                            @foreach ($this->pendingApprovals as $scopeLabel => $processName)
                                <li class="list-group-item text-body-secondary">
                                    <strong>Aprobación {{ mb_strtolower($scopeLabel) }}:</strong> pendiente (director de {{ $processName }})
                                </li>
                            @endforeach
                            @if ($version->status === ProviderVersionStatus::Rejected)
                                <li class="list-group-item list-group-item-danger">
                                    <strong>Rechazada:</strong> {{ $date($version->rejected_at) }} · {{ $version->rejecter?->fullName() }}
                                    <div class="small">{{ $version->rejection_reason }}</div>
                                </li>
                            @endif
                        </ul>
                    </div>

                    <div class="col-lg-5">
                        @php
                            $canAct = $viewingDefault && (auth()->user()->can('submit', $linkage) || auth()->user()->can('verify', $linkage)
                                || auth()->user()->can('certifyCompliance', $linkage) || auth()->user()->can('approve', $linkage)
                                || auth()->user()->can('reject', $linkage) || auth()->user()->can('returnToDraft', $linkage));
                        @endphp
                        @if ($canAct)
                            @if ($version->status !== ProviderVersionStatus::Draft)
                                <x-forms.textarea name="reviewNotes" id="review-notes" label="Observaciones" rows="3"
                                                  hint="Obligatorias al rechazar; opcionales al firmar." />
                            @endif
                            <div class="d-flex flex-wrap gap-2 mt-2">
                                @can('submit', $linkage)
                                    <button type="button" class="btn btn-primary" wire:loading.attr="disabled"
                                            x-on:click="confirmAction({ title: 'Enviar a verificación', text: 'El borrador no podrá editarse mientras esté en revisión.' }).then(() => $wire.submit()).catch(() => {})">
                                        <i class="bi bi-send" aria-hidden="true"></i> Enviar a verificación
                                    </button>
                                @endcan
                                @can('verify', $linkage)
                                    <button type="button" wire:click="verify" wire:loading.attr="disabled" class="btn btn-success">
                                        <i class="bi bi-check2-circle" aria-hidden="true"></i> Verificar
                                    </button>
                                @endcan
                                @can('certifyCompliance', $linkage)
                                    <button type="button" wire:click="certifyCompliance" wire:loading.attr="disabled" class="btn btn-success">
                                        <i class="bi bi-shield-check" aria-hidden="true"></i> Visto bueno de cumplimiento
                                    </button>
                                @endcan
                                @can('approve', $linkage)
                                    <button type="button" wire:click="approve" wire:loading.attr="disabled" class="btn btn-success">
                                        <i class="bi bi-patch-check" aria-hidden="true"></i> Aprobar
                                    </button>
                                @endcan
                                @can('reject', $linkage)
                                    <button type="button" class="btn btn-outline-danger"
                                            x-on:click="confirmAction({ title: 'Rechazar versión', text: 'Las vinculaciones pendientes quedarán rechazadas.' }).then(() => $wire.reject()).catch(() => {})">
                                        <i class="bi bi-x-circle" aria-hidden="true"></i> Rechazar
                                    </button>
                                @endcan
                                @can('returnToDraft', $linkage)
                                    <button type="button" class="btn btn-outline-secondary"
                                            x-on:click="confirmAction({ title: 'Devolver a Borrador', text: 'Se descartarán las firmas de esta versión.' }).then(() => $wire.returnToDraft()).catch(() => {})">
                                        <i class="bi bi-arrow-counterclockwise" aria-hidden="true"></i> Devolver a Borrador
                                    </button>
                                @endcan
                            </div>
                        @else
                            <p class="text-body-secondary small mb-0">
                                @if ($version->status->isOpen())
                                    No tienes acciones pendientes en esta etapa ({{ $version->status->label() }}).
                                @else
                                    La versión {{ $version->number }} está {{ mb_strtolower($version->status->label()) }}.
                                @endif
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endif

    {{-- Condiciones que negocia el proceso --}}
    <x-modal wire-model="showTerms" title="Condiciones del proceso" :subtitle="'Vinculación '.mb_strtolower($scope->label())" icon="bi-cash-stack" size="md">
        <div class="modal-body">
            <div class="row g-3">
                @if ($termsForm->commercial)
                    <div class="col-12">
                        <x-forms.select name="termsForm.commercial_segment" id="terms-segment" label="Segmento comercial"
                                        :options="$options['commercial_segment']" placeholder="Sin segmento" />
                    </div>
                @endif
                <div class="col-md-6">
                    <x-forms.select name="termsForm.payment_condition" id="terms-condition" label="Condición de pago"
                                    :options="$options['payment_condition']" placeholder="Sin definir" />
                </div>
                <div class="col-md-6">
                    <x-forms.select name="termsForm.payment_term" id="terms-term" label="Plazo de pago"
                                    :options="$options['payment_term']" placeholder="Sin definir" />
                </div>
            </div>
        </div>
        <x-slot:footer>
            <x-forms.modal-actions submit="saveTerms" />
        </x-slot:footer>
    </x-modal>

    <livewire:providers.provider-form-modal :scope="$scope->value" />
    @unless ($scope->requiresForm())
        <livewire:providers.provider-basic-modal :scope="$scope->value" />
    @endunless
</div>
