{{--
    PDF del formato FO-300-01 (vinculación y actualización de proveedores) de una versión.
    Variables: $version (ProviderVersion con relaciones), $company (CompanySettings) y
    $sensitive (bool: incluye información financiera, PEP y grupo de protección).
--}}
@php
    $date = fn ($value) => $value?->format('d/m/Y') ?? '';
    $money = fn ($value) => $value === null ? '' : '$ '.number_format((float) $value, 2, ',', '.');
    $yesNo = fn (bool $value) => $value ? 'Sí' : 'No';
    $natural = $version->isNatural();
    $reserved = 'Reservado';
@endphp
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>FO-300-01 {{ $version->identification }} v{{ $version->number }}</title>
    <style>
        @page { margin: 1.2cm 1.2cm; }
        body { font-family: DejaVu Sans, sans-serif; font-size: 8.5pt; color: #222; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        th, td { border: 1px solid #888; padding: 3px 4px; vertical-align: top; }
        th { background: #e8edf3; text-align: left; font-weight: bold; }
        .title td { border: none; }
        .section { background: #1f4e79; color: #fff; font-weight: bold; padding: 3px 4px; margin-top: 6px; }
        .label { width: 22%; background: #f4f6f9; font-weight: bold; }
        .subsection { font-weight: bold; margin: 4px 0 2px; }
        .muted { color: #666; }
        .small { font-size: 7.5pt; }
    </style>
</head>
<body>
    <table class="title">
        <tr>
            <td style="width: 70%;">
                <strong style="font-size: 11pt;">{{ $company->legal_name }}</strong><br>
                <span class="muted">NIT {{ $company->nit }}</span><br>
                <strong>FO-300-01 · Vinculación y actualización de {{ $natural ? 'persona natural' : 'persona jurídica' }} proveedor</strong>
            </td>
            <td style="text-align: right;">
                Versión {{ $version->number }}<br>
                {{ $version->request_type->label() }}<br>
                Estado: {{ $version->status->label() }}<br>
                <span class="small muted">Generado {{ now()->format('d/m/Y H:i') }}</span>
            </td>
        </tr>
    </table>

    <div class="section">1. Información del proveedor</div>
    <table>
        <tr><td class="label">{{ $natural ? 'Nombres y apellidos' : 'Razón social' }}</td><td colspan="3">{{ $version->name }}</td></tr>
        <tr>
            <td class="label">Tipo de identificación</td><td>{{ $version->document_type->label() }}</td>
            <td class="label">Número</td><td>{{ $version->identification }}</td>
        </tr>
        @if ($natural)
            <tr>
                <td class="label">Fecha de expedición</td><td>{{ $date($version->document_issued_on) }}</td>
                <td class="label">Nacionalidad</td><td>{{ $version->nationality?->name }}</td>
            </tr>
            <tr>
                <td class="label">Fecha de nacimiento</td><td>{{ $date($version->birth_date) }}</td>
                <td class="label">Lugar de nacimiento</td><td>{{ trim(($version->birthMunicipality?->name ?? '').' '.($version->birthDepartment?->name ?? '')) }}</td>
            </tr>
            <tr>
                <td class="label">Nivel de educación</td><td>{{ $version->education_level }}</td>
                <td class="label">País de residencia</td><td>{{ $version->country?->name }}</td>
            </tr>
            <tr>
                <td class="label">Ocupación</td><td>{{ trim($version->occupation.' '.$version->occupation_other) }}</td>
                <td class="label">Grupo de protección</td><td>{{ $sensitive ? trim($version->protection_group.' '.$version->protection_group_other) : $reserved }}</td>
            </tr>
            <tr>
                <td class="label">Empresa donde labora</td><td>{{ $version->employer_name }}</td>
                <td class="label">CIIU de la empresa</td><td>{{ $version->employerEconomicActivity?->label() }}</td>
            </tr>
            @if ($version->minor_representative_name)
                <tr>
                    <td class="label">Representante del menor</td><td>{{ $version->minor_representative_name }}</td>
                    <td class="label">Identificación</td><td>{{ $version->minor_representative_identification }}</td>
                </tr>
            @endif
        @else
            <tr>
                <td class="label">País de constitución</td><td>{{ $version->country?->name }}</td>
                <td class="label">Tipo de empresa</td><td>{{ $version->company_type }}</td>
            </tr>
            <tr>
                <td class="label">Régimen tributario</td><td>{{ $version->tax_regime }}</td>
                <td class="label">Representante legal</td><td>{{ $version->legal_representative_name }}</td>
            </tr>
            <tr>
                <td class="label">Tipo de identificación del RL</td><td>{{ $version->legal_representative_document_type?->label() }}</td>
                <td class="label">Número del RL</td><td>{{ $version->legal_representative_identification }}</td>
            </tr>
        @endif
        <tr>
            <td class="label">Dirección</td><td>{{ $version->address }}{{ $natural && $version->residence_zone ? ' ('.$version->residence_zone->label().')' : '' }}</td>
            <td class="label">Departamento / municipio</td><td>{{ $version->department?->name }} / {{ $version->municipality?->name }}</td>
        </tr>
        <tr>
            <td class="label">Correo electrónico</td><td>{{ $version->email }}</td>
            <td class="label">Teléfono / celular</td><td>{{ $version->phone }}</td>
        </tr>
        <tr>
            <td class="label">CIIU</td><td>{{ $version->economicActivity?->label() }}</td>
            <td class="label">Actividad económica</td><td>{{ $version->economic_activity_description }}</td>
        </tr>
    </table>

    @if (! $natural)
        <table>
            <tr><th colspan="5">Propietarios, consorciados o accionistas con participación de 5 % o más</th></tr>
            <tr><th>Documento</th><th>Nombre / razón social</th><th>Representante legal</th><th>Id. RL</th><th>%</th></tr>
            @forelse ($version->shareholders as $shareholder)
                <tr>
                    <td>{{ $shareholder->document_type->value }} {{ $shareholder->identification }}</td><td>{{ $shareholder->name }}</td>
                    <td>{{ $shareholder->legal_representative_name }}</td><td>{{ $shareholder->legal_representative_identification }}</td>
                    <td>{{ number_format((float) $shareholder->ownership_percentage, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Sin registros</td></tr>
            @endforelse
        </table>
    @endif

    <div class="section">2. Información financiera</div>
    @if ($sensitive)
        <table>
            <tr>
                <td class="label">Ingresos mensuales</td><td>{{ $money($version->monthly_income) }}</td>
                <td class="label">Otros ingresos</td><td>{{ $money($version->other_income) }} {{ $version->other_income_activity }}</td>
            </tr>
            @unless ($natural)
                <tr>
                    <td class="label">Total de activos</td><td>{{ $money($version->total_assets) }}</td>
                    <td class="label">Total de pasivos</td><td>{{ $money($version->total_liabilities) }}</td>
                </tr>
                <tr>
                    <td class="label">Total de patrimonio</td><td>{{ $money($version->total_equity) }}</td>
                    <td class="label">Proceso de insolvencia</td><td>{{ $yesNo($version->in_insolvency) }}</td>
                </tr>
            @endunless
            <tr>
                <td class="label">Operaciones en moneda extranjera</td><td>{{ $yesNo($version->foreign_currency_operations) }} {{ $version->foreign_currency_detail }}</td>
                <td class="label">Cuentas en moneda extranjera</td><td>{{ $yesNo($version->has_foreign_accounts) }}</td>
            </tr>
        </table>
    @else
        <p class="muted">{{ $reserved }} (Ley 1581 de 2012).</p>
    @endif

    <div class="section">3. Control de personas expuestas políticamente (PEP)</div>
    @if ($sensitive)
        <table>
            <tr>
                <td class="label">¿Administra recursos públicos?</td><td>{{ $yesNo($version->manages_public_resources) }}</td>
                <td class="label">¿Es PEP?</td><td>{{ $yesNo($version->declares_pep) }}</td>
            </tr>
            <tr>
                <td class="label">Cargo público</td><td>{{ $version->public_office }}</td>
                <td class="label">Inicio / fin del cargo</td><td>{{ $date($version->pep_starts_on) }} {{ $version->hasPublicOffice() ? '- '.($date($version->pep_ends_on) ?: 'Vigente') : '' }}</td>
            </tr>
            <tr>
                <td class="label">¿Tiene relación con PEP?</td><td colspan="3">{{ $yesNo($version->has_pep_relationship) }}</td>
            </tr>
        </table>
        {{-- Como el formato: grupo básico familiar PEP y, aparte, la persona PEP con quien tiene relación. --}}
        <p class="subsection">Grupo básico familiar como PEP (padres, esposo/esposa, suegros, hijos, yerno/nuera, abuelos, hermanos, cuñados y nietos)</p>
        <table>
            <tr><th>Tipo de documento</th><th>Número de documento</th><th>Nombres y apellidos</th><th>Parentesco</th></tr>
            @forelse ($version->pepRelatives as $relative)
                <tr>
                    <td>{{ $relative->document_type?->label() }}</td><td>{{ $relative->identification }}</td>
                    <td>{{ $relative->name }}</td><td>{{ $relative->relationship }}</td>
                </tr>
            @empty
                <tr><td colspan="4" class="muted">Sin registros</td></tr>
            @endforelse
        </table>
        @if ($version->has_pep_relationship)
            <p class="subsection">Relación con PEP</p>
            <table>
                <tr>
                    <td class="label">Nombre del PEP</td><td>{{ $version->pep_relation_name }}</td>
                    <td class="label">Identificación del PEP</td><td>{{ $version->pep_relation_identification }}</td>
                </tr>
                <tr>
                    <td class="label">Cargo del PEP</td><td>{{ $version->pep_relation_office }}</td>
                    <td class="label">Relación</td><td>{{ $version->pep_relation_relationship }}</td>
                </tr>
                <tr>
                    <td class="label">Inicio en el cargo PEP</td><td>{{ $date($version->pep_relation_starts_on) }}</td>
                    <td class="label">Fin del cargo PEP</td><td>{{ $date($version->pep_relation_ends_on) ?: 'Vigente' }}</td>
                </tr>
            </table>
        @endif
    @else
        <p class="muted">{{ $reserved }} (Ley 1581 de 2012).</p>
    @endif

    <div class="section">4. Contactos</div>
    <table>
        <tr><th>Contacto</th><th>Nombre del funcionario</th><th>Cargo</th><th>Teléfono / celular</th><th>Correo electrónico</th></tr>
        @foreach (\App\Enums\ProviderContactRole::cases() as $role)
            @php $contact = $version->contacts->firstWhere('role', $role); @endphp
            <tr><td>{{ $role->label() }}</td><td>{{ $contact?->name ?? 'NO APLICA' }}</td><td>{{ $contact?->job_title }}</td><td>{{ $contact?->phone }}</td><td>{{ $contact?->email }}</td></tr>
        @endforeach
        <tr><td class="label">Despacho de pedidos</td><td colspan="4">{{ $version->dispatch_schedule }}</td></tr>
    </table>

    <div class="section">5. Declaraciones y autorizaciones</div>
    {{-- Texto completo de cada declaración, como en el formato físico. --}}
    <table>
        @foreach (\App\Support\ProviderDeclarations::for($version->person_type) as $field => $declaration)
            <tr>
                <td class="label">{{ $yesNo($version->{$field}) }}</td>
                <td><strong>{{ $declaration['label'] }}.</strong> {{ $declaration['text'] }}</td>
            </tr>
        @endforeach
    </table>

    <div class="section">Documentos anexos</div>
    <table>
        @forelse ($version->documents as $document)
            <tr><td>{{ $document->type }}</td><td>{{ $document->original_name }}</td><td>{{ $date($document->issued_on) }}</td></tr>
        @empty
            <tr><td class="muted">Sin anexos</td></tr>
        @endforelse
    </table>

    <div class="section">Espacio reservado para uso exclusivo de {{ $company->legal_name }}</div>
    <table>
        <tr>
            <td class="label">Nivel de riesgo</td><td>{{ $version->risk_level->label() }}{{ $version->is_pep ? ' (PEP)' : '' }}</td>
            <td class="label">Tipo de proveedor</td><td>{{ $version->provider->linkages->map(fn ($linkage) => $linkage->scope->label())->join(', ') }}</td>
        </tr>
    </table>
    @php
        // Firma: nombre, cargo (guardado al firmar) y fecha.
        $sign = fn ($user, $position, $at) => collect([$user?->fullName(), $position, $date($at)])->filter()->map(fn ($value) => e($value))->join('<br>');
    @endphp
    <table>
        <tr>
            <th>Verificación de la información</th>
            <th>Oficial de cumplimiento</th>
            <th>Aprobación del director del proceso</th>
            <th>Gerencia (proveedor PEP)</th>
        </tr>
        <tr>
            <td>
                {!! $sign($version->verifier, $version->verified_position, $version->verified_at) !!}<br>
                <span class="small">{{ $version->verification_observations }}</span>
            </td>
            <td>
                @if ($version->compliance_at)
                    {!! $sign($version->complianceOfficer, $version->compliance_position, $version->compliance_at) !!}<br>
                    <span class="small">{{ $version->compliance_observations }}</span>
                @else
                    <span class="muted">{{ $version->is_pep ? 'Pendiente' : 'Sin observaciones' }}</span>
                @endif
            </td>
            <td>
                @foreach ($version->approvals as $approval)
                    <strong>{{ $approval->scope->label() }}:</strong><br>
                    {!! $sign($approval->user, $approval->user_position, $approval->approved_at) !!}<br>
                    @if ($approval->observations)<span class="small">{{ $approval->observations }}</span><br>@endif
                @endforeach
            </td>
            <td>
                @if (! $version->is_pep)
                    <span class="muted">No aplica</span>
                @elseif ($version->hasManagementApproval())
                    {{ $version->management_in_system ? 'Aprobado en el sistema' : 'Firma física registrada' }}<br>
                    {!! $sign($version->managementSigner, $version->management_position, $version->management_signed_on) !!}<br>
                    <span class="small">{{ $version->management_observations }}</span>
                @else
                    <span class="muted">Firma:</span><br><br><br>
                @endif
            </td>
        </tr>
    </table>
    <p class="small muted">
        La Dirección Comercial aprueba la vinculación de proveedores comerciales. La Dirección Administrativa aprueba la vinculación
        de proveedores administrativos. Si el proveedor es considerado PEP (Persona políticamente expuesta) será la Gerencia quien
        apruebe la vinculación previa verificación del oficial de cumplimiento.
    </p>
</body>
</html>
