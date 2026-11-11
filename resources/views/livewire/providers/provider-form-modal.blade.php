@use('App\Support\ProviderDeclarations')
@use('App\Enums\IdentityDocumentType')
@use('App\Enums\PersonType')
@use('App\Enums\ProviderContactRole')
@use('App\Enums\ResidenceZone')
@use('App\Livewire\Forms\ProviderForm')

@php
    $natural = $form->isNatural();
    $options = $this->characteristicOptions;
    $locked = $form->identityLocked;
@endphp

<div>
    <x-modal wire-model="show" :title="$updateLinkageId ? 'Actualizar proveedor aprobado' : ($form->version ? 'Editar proveedor (FO-300-01)' : 'Registrar proveedor (FO-300-01)')"
             :subtitle="$updateLinkageId ? 'Actualización de datos (FO-300-01)' : ($form->version ? 'Versión '.$form->version->number.' · '.$form->version->request_type->label() : 'Vinculación de proveedor')"
             icon="bi-person-vcard" :size="$updateLinkageId ? 'md' : 'xl'">
        @if ($updateLinkageId)
            {{-- Proveedor aprobado: modificarlo exige abrir la actualización (nueva versión en Borrador). --}}
            <div class="modal-body">
                <div class="alert alert-info d-flex gap-2 mb-0">
                    <i class="bi bi-info-circle" aria-hidden="true"></i>
                    <div>
                        El proveedor está aprobado: sus datos vigentes no se modifican. Para cambiarlos se abre una
                        <strong>actualización</strong> (nueva versión en Borrador, copiada de la vigente) que pasa de nuevo
                        por verificación y aprobación. La versión vigente sigue activa mientras tanto.
                    </div>
                </div>
            </div>
            <x-slot:footer>
                <x-forms.modal-actions submit="startUpdate" submit-label="Abrir actualización" submit-icon="bi-arrow-repeat" />
            </x-slot:footer>
        @else
        <div class="modal-body">
            {{-- 1. Identificación --}}
            <x-forms.section title="Información del proveedor" icon="bi-person-vcard"
                             :description="$locked ? 'La identificación de un proveedor aprobado no se modifica.' : null">
                <div class="col-md-3">
                    <x-forms.select name="form.person_type" id="provider-person-type" label="Tipo de persona" required
                                    :options="PersonType::options()" model-modifier="live" :disabled="$locked" />
                </div>
                <div class="col-md-3">
                    <x-forms.select name="form.document_type" id="provider-document-type" label="Tipo de documento" required
                                    :options="IdentityDocumentType::optionsFor($form->personType()->documentTypes())"
                                    model-modifier="live" :disabled="$locked" />
                </div>
                <div class="col-md-3">
                    <x-forms.input name="form.identification" id="provider-identification" label="Número de identificación" required
                                   model-modifier="live.blur" hint="Sin puntos ni dígito de verificación." :disabled="$locked" autocomplete="off" />
                </div>
                <div class="col-md-3">
                    @if ($natural)
                        <x-forms.input name="form.document_issued_on" id="provider-document-issued-on" label="Fecha de expedición" type="date" required />
                    @else
                        <x-forms.select name="form.country_id" id="provider-country" label="País de constitución" required
                                        :options="$this->countries" placeholder="Seleccione" />
                    @endif
                </div>

                {{-- Identificación ya registrada: se vincula el tercero existente en lugar de duplicarlo. --}}
                @if ($this->existingProvider)
                    <div class="col-12">
                        <div class="alert alert-warning d-flex flex-wrap align-items-center gap-2 py-2 mb-0">
                            <i class="bi bi-exclamation-triangle" aria-hidden="true"></i>
                            <span class="me-auto">
                                Ya existe <strong>{{ $this->existingProvider->name }}</strong> con esta identificación
                                ({{ $this->existingProvider->linkages->map(fn ($l) => $l->scope->label().': '.$l->status->label())->join(', ') ?: 'sin vinculaciones' }}).
                            </span>
                            @if (! $this->alreadyLinked)
                                <button type="button" wire:click="linkExisting" class="btn btn-sm btn-warning" wire:loading.attr="disabled" wire:target="linkExisting">
                                    <i class="bi bi-link-45deg" aria-hidden="true"></i> Vincular a mi proceso
                                </button>
                            @endif
                        </div>
                    </div>
                @endif

                <div class="col-md-6">
                    <x-forms.input name="form.name" id="provider-name" :label="$natural ? 'Nombres y apellidos' : 'Razón social'" required />
                </div>
                @if ($natural)
                    <div class="col-md-3">
                        <x-forms.select name="form.country_id" id="provider-country" label="País de residencia" required
                                        :options="$this->countries" placeholder="Seleccione" />
                    </div>
                    @if ($form->requiresNationality())
                        <div class="col-md-3">
                            <x-forms.select name="form.nationality_country_id" id="provider-nationality" label="Nacionalidad" required
                                            :options="$this->countries" placeholder="Seleccione" />
                        </div>
                    @endif
                @else
                    <div class="col-md-3">
                        <x-forms.select name="form.tax_regime" id="provider-tax-regime" label="Régimen tributario" required
                                        :options="$options['tax_regime']" placeholder="Seleccione" />
                    </div>
                    <div class="col-md-3">
                        <x-forms.select name="form.company_type" id="provider-company-type" label="Tipo de empresa" required
                                        :options="$options['company_type']" placeholder="Seleccione" />
                    </div>
                @endif
            </x-forms.section>

            {{-- 2. Ubicación y contacto --}}
            <x-forms.section title="Ubicación y contacto" icon="bi-geo-alt">
                <div class="col-md-4">
                    <x-forms.select name="form.department_id" id="provider-department" :label="$natural ? 'Departamento de domicilio' : 'Departamento'" required
                                    :options="$this->departments" placeholder="Seleccione" model-modifier="live" />
                </div>
                <div class="col-md-4">
                    <x-forms.select name="form.municipality_id" id="provider-municipality" :label="$natural ? 'Municipio de domicilio' : 'Municipio'" required
                                    :options="$this->municipalities" placeholder="Seleccione" />
                </div>
                @if ($natural)
                    <div class="col-md-4">
                        <x-forms.select name="form.residence_zone" id="provider-zone" label="Zona" required :options="ResidenceZone::options()" placeholder="Seleccione" />
                    </div>
                @endif
                <div class="col-md-{{ $natural ? '12' : '4' }}">
                    <x-forms.input name="form.address" id="provider-address" label="Dirección" required />
                </div>
                <div class="col-md-6">
                    <x-forms.input name="form.phone" id="provider-phone" label="Teléfono / celular" required />
                </div>
                <div class="col-md-6">
                    <x-forms.input name="form.email" id="provider-email" label="Correo electrónico" type="email" required />
                </div>
            </x-forms.section>

            {{-- 3. Perfil de la persona natural o datos de la persona jurídica --}}
            @if ($natural)
                <x-forms.section title="Perfil de la persona natural" icon="bi-person">
                    <div class="col-md-4">
                        <x-forms.input name="form.birth_date" id="provider-birth-date" label="Fecha de nacimiento" type="date" required />
                    </div>
                    <div class="col-md-4">
                        <x-forms.select name="form.birth_department_id" id="provider-birth-department" label="Departamento de nacimiento"
                                        :options="$this->departments" placeholder="Seleccione" model-modifier="live" />
                    </div>
                    <div class="col-md-4">
                        <x-forms.select name="form.birth_municipality_id" id="provider-birth-municipality" label="Municipio de nacimiento"
                                        :options="$this->birthMunicipalities" placeholder="Seleccione" />
                    </div>
                    <div class="col-md-4">
                        <x-forms.select name="form.education_level" id="provider-education" label="Nivel de educación" required
                                        :options="$options['education_level']" placeholder="Seleccione" />
                    </div>
                    <div class="col-md-4">
                        <x-forms.select name="form.occupation" id="provider-occupation" label="Ocupación" required
                                        :options="$options['occupation']" placeholder="Seleccione" model-modifier="live" />
                    </div>
                    @if ($form->occupation === \App\Livewire\Forms\ProviderForm::OTHER_OPTION)
                        <div class="col-md-4">
                            <x-forms.input name="form.occupation_other" id="provider-occupation-other" label="¿Cuál ocupación?" required />
                        </div>
                    @endif
                    <div class="col-md-6">
                        <x-forms.select name="form.protection_group" id="provider-protection-group" label="Grupo de protección especial" required
                                        :options="$options['protection_group']" placeholder="Seleccione" model-modifier="live" />
                    </div>
                    @if ($form->protection_group === \App\Livewire\Forms\ProviderForm::OTHER_OPTION)
                        <div class="col-md-6">
                            <x-forms.input name="form.protection_group_other" id="provider-protection-other" label="¿Cuál grupo?" required />
                        </div>
                    @endif
                    <div class="col-md-4">
                        <x-forms.input name="form.employer_name" id="provider-employer" label="Empresa donde labora" hint="Si aplica." />
                    </div>
                    <div class="col-md-4">
                        <x-forms.input name="form.employer_activity" id="provider-employer-activity" label="Actividad de la empresa" />
                    </div>
                    <div class="col-md-4">
                        <x-forms.search-select name="form.employer_economic_activity_id" label="CIIU de la empresa" search="employerActivitySearch"
                                               select="selectEmployerActivity" :results="$this->employerActivityResults" placeholder="Código o actividad" />
                    </div>
                    @if ($form->isMinor())
                        <div class="col-md-8">
                            <x-forms.input name="form.minor_representative_name" id="provider-minor-rep" label="Representante legal o apoderado del menor" required />
                        </div>
                        <div class="col-md-4">
                            <x-forms.input name="form.minor_representative_identification" id="provider-minor-rep-id" label="Identificación del representante" required />
                        </div>
                    @endif
                </x-forms.section>
            @else
                <x-forms.section title="Representante legal" icon="bi-person-badge">
                    <div class="col-md-6">
                        <x-forms.input name="form.legal_representative_name" id="provider-legal-rep" label="Nombre completo" required />
                    </div>
                    <div class="col-md-3">
                        <x-forms.select name="form.legal_representative_document_type" id="provider-legal-rep-type" label="Tipo de documento" required
                                        :options="IdentityDocumentType::optionsFor(IdentityDocumentType::forLegalRepresentative())" placeholder="Seleccione" />
                    </div>
                    <div class="col-md-3">
                        <x-forms.input name="form.legal_representative_identification" id="provider-legal-rep-id" label="Número de identificación" required />
                    </div>
                </x-forms.section>
            @endif

            {{-- 4. Actividad económica --}}
            <x-forms.section title="Actividad económica" icon="bi-briefcase">
                <div class="col-md-6">
                    <x-forms.search-select name="form.economic_activity_id" label="CIIU" required search="activitySearch"
                                           select="selectActivity" :results="$this->activityResults" placeholder="Código o actividad (ej. 4645)" />
                </div>
                <div class="col-md-6">
                    <x-forms.input name="form.economic_activity_description" id="provider-activity-description" label="Descripción de la actividad" />
                </div>
            </x-forms.section>

            {{-- 5. Información financiera --}}
            <x-forms.section title="Información financiera ($)" icon="bi-cash-coin" description="Dato sensible: solo lo ven quienes verifican y aprueban.">
                <div class="col-md-4">
                    <x-forms.money name="form.monthly_income" id="provider-monthly-income" label="Ingresos mensuales (actividad principal)" required />
                </div>
                <div class="col-md-4">
                    <x-forms.money name="form.other_income" id="provider-other-income" label="Otros ingresos" />
                </div>
                <div class="col-md-4">
                    <x-forms.input name="form.other_income_activity" id="provider-other-income-activity" label="¿De qué actividad?" />
                </div>
                @unless ($natural)
                    <div class="col-md-4">
                        <x-forms.money name="form.total_assets" id="provider-assets" label="Total de activos" required />
                    </div>
                    <div class="col-md-4">
                        <x-forms.money name="form.total_liabilities" id="provider-liabilities" label="Total de pasivos" required />
                    </div>
                    <div class="col-md-4">
                        <x-forms.money name="form.total_equity" id="provider-equity" label="Total de patrimonio" required allow-negative />
                    </div>
                    <div class="col-12">
                        <x-forms.checkbox name="form.in_insolvency" id="provider-insolvency" label="Se encuentra en un proceso de ley de insolvencia" />
                    </div>
                @endunless
                <div class="col-md-6">
                    <x-forms.checkbox name="form.foreign_currency_operations" id="provider-foreign-ops" label="Realiza operaciones en moneda extranjera" model-modifier="live" />
                    <x-forms.checkbox name="form.has_foreign_accounts" id="provider-foreign-accounts" label="Posee cuentas en moneda extranjera" class="mt-2" />
                </div>
                @if ($form->foreign_currency_operations)
                    <div class="col-md-6">
                        <x-forms.input name="form.foreign_currency_detail" id="provider-foreign-detail" label="¿Cuáles?" required />
                    </div>
                @endif
            </x-forms.section>

            {{-- 6. PEP --}}
            <x-forms.section title="Personas expuestas políticamente (PEP)" icon="bi-bank"
                             description="Cualquier respuesta afirmativa califica al proveedor como PEP (riesgo alto).">
                <div class="col-md-4">
                    <x-forms.checkbox name="form.manages_public_resources" id="provider-public-resources" label="Administra recursos públicos" model-modifier="live" />
                </div>
                <div class="col-md-4">
                    <x-forms.checkbox name="form.declares_pep" id="provider-declares-pep" label="Es una persona expuesta políticamente" model-modifier="live" />
                </div>
                <div class="col-md-4">
                    <x-forms.checkbox name="form.has_pep_relationship" id="provider-pep-relationship" label="Tiene relación con un PEP" model-modifier="live"
                                      hint="Padres, cónyuge, suegros, hijos, yerno/nuera, abuelos, hermanos, cuñados o nietos de un PEP." />
                </div>
                {{-- Cargo público y fechas: con recursos públicos, PEP o ambas. --}}
                @if ($form->requiresPublicOffice())
                    <div class="col-md-6">
                        <x-forms.input name="form.public_office" id="provider-public-office" label="Cargo público" required />
                    </div>
                    <div class="col-md-3">
                        <x-forms.input name="form.pep_starts_on" id="provider-pep-starts" label="Inicio en el cargo" type="date" required />
                    </div>
                    <div class="col-md-3">
                        <x-forms.input name="form.pep_ends_on" id="provider-pep-ends" label="Fin del cargo" type="date" hint="Vacío si sigue vigente." />
                    </div>
                @endif
                {{-- Persona PEP con quien tiene relación (distinto del grupo familiar PEP, que va en el detalle). --}}
                @if ($form->has_pep_relationship)
                    <div class="col-12"><span class="form-label fw-semibold mb-0">Datos del PEP con quien tiene relación</span></div>
                    <div class="col-md-5">
                        <x-forms.input name="form.pep_relation_name" id="provider-pep-relation-name" label="Nombre del PEP" required />
                    </div>
                    <div class="col-md-3">
                        <x-forms.input name="form.pep_relation_identification" id="provider-pep-relation-id" label="Identificación del PEP" required />
                    </div>
                    <div class="col-md-4">
                        <x-forms.select name="form.pep_relation_relationship" id="provider-pep-relation-relationship" label="Relación" required
                                        :options="$options['relationship']" placeholder="Seleccione" />
                    </div>
                    <div class="col-md-6">
                        <x-forms.input name="form.pep_relation_office" id="provider-pep-relation-office" label="Cargo del PEP" required />
                    </div>
                    <div class="col-md-3">
                        <x-forms.input name="form.pep_relation_starts_on" id="provider-pep-relation-starts" label="Inicio en el cargo PEP" type="date" required />
                    </div>
                    <div class="col-md-3">
                        <x-forms.input name="form.pep_relation_ends_on" id="provider-pep-relation-ends" label="Fin del cargo PEP" type="date" hint="Vacío si sigue vigente." />
                    </div>
                @endif
            </x-forms.section>

            {{-- 7. Contactos --}}
            <x-forms.section title="Contactos" icon="bi-telephone" description="Deje vacío el contacto que no aplica; si lo diligencia, el cargo es obligatorio.">
                @foreach (ProviderContactRole::cases() as $role)
                    <div class="col-md-2 d-flex align-items-end"><span class="form-label fw-semibold mb-2">{{ $role->label() }}</span></div>
                    <div class="col-md-3">
                        <x-forms.input name="form.contacts.{{ $role->value }}.name" id="provider-contact-{{ $role->value }}-name" label="Nombre" />
                    </div>
                    <div class="col-md-3">
                        <x-forms.input name="form.contacts.{{ $role->value }}.job_title" id="provider-contact-{{ $role->value }}-job-title" label="Cargo" required />
                    </div>
                    <div class="col-md-2">
                        <x-forms.input name="form.contacts.{{ $role->value }}.phone" id="provider-contact-{{ $role->value }}-phone" label="Teléfono" />
                    </div>
                    <div class="col-md-2">
                        <x-forms.input name="form.contacts.{{ $role->value }}.email" id="provider-contact-{{ $role->value }}-email" label="Correo" type="email" />
                    </div>
                @endforeach
                <div class="col-12">
                    <x-forms.textarea name="form.dispatch_schedule" id="provider-dispatch" label="Despacho de pedidos" rows="2"
                                      hint="Días y horarios, si hay restricciones en los despachos." />
                </div>
            </x-forms.section>

            {{-- 8. Declaraciones --}}
            <x-forms.section title="Declaraciones y autorizaciones" icon="bi-patch-check"
                             description="Las cuatro primeras son obligatorias para enviar a verificación. Pase el mouse por el ícono para leer el texto completo.">
                @php
                    $declarationIds = [
                        'accepts_truthfulness' => 'provider-truthfulness', 'accepts_data_processing' => 'provider-data-processing',
                        'accepts_information_received' => 'provider-information-received', 'accepts_funds_origin' => 'provider-funds-origin',
                        'accepts_risk_bureau' => 'provider-risk-bureau', 'accepts_electronic_means' => 'provider-electronic-means',
                    ];
                @endphp
                @foreach (ProviderDeclarations::for($form->personType()) as $field => $declaration)
                    <div class="col-md-6" wire:key="declaration-{{ $field }}">
                        <x-forms.checkbox name="form.{{ $field }}" :id="$declarationIds[$field]" :label="$declaration['label']" :info="$declaration['text']" />
                    </div>
                @endforeach
            </x-forms.section>
        </div>
        <x-slot:footer>
            <x-forms.modal-actions />
        </x-slot:footer>
        @endif
    </x-modal>
</div>
