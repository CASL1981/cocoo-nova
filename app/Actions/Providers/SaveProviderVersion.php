<?php

declare(strict_types=1);

namespace App\Actions\Providers;

use App\Enums\PersonType;
use App\Enums\ProviderContactRole;
use App\Enums\ProviderLinkageStatus;
use App\Enums\ProviderRequestType;
use App\Enums\ProviderScope;
use App\Enums\ProviderVersionStatus;
use App\Models\Provider;
use App\Models\ProviderLinkage;
use App\Models\ProviderVersion;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Punto único de escritura de los datos del formato FO-300-01 (versión en Borrador).
 *
 * Crear: registra el tercero, su primera versión (Vinculación) y la vinculación con el
 * proceso que lo registra. Editar: solo versiones en Borrador. Calcula si es PEP y el nivel
 * de riesgo, limpia los campos que no aplican al tipo de persona y reemplaza los contactos.
 * La identificación y el tipo de persona solo cambian mientras el proveedor nunca se ha aprobado.
 */
final class SaveProviderVersion
{
    /**
     * @param  array<string, mixed>  $data  Campos de ProviderVersion::FORM_FIELDS ya validados.
     * @param  array<string, array{name: ?string, job_title: ?string, phone: ?string, email: ?string}>  $contacts  Por valor de ProviderContactRole.
     */
    public function execute(array $data, array $contacts, ProviderScope $scope, ?ProviderVersion $version = null): ProviderVersion
    {
        return DB::transaction(function () use ($data, $contacts, $scope, $version): ProviderVersion {
            $version = $version === null
                ? $this->createProvider($data, $scope)
                : $this->lockDraft($version);

            $provider = $version->provider;

            // Un proveedor aprobado o ya en uso (básico activo) conserva su identificación.
            $identityLocked = $provider->isIdentityLocked();
            if ($identityLocked) {
                $data = array_merge($data, $provider->only(['person_type', 'document_type', 'identification']));
            }

            $data = $this->clearNotApplicable($data);
            $isPep = self::evaluatePep($data);
            $personType = $data['person_type'] instanceof PersonType ? $data['person_type'] : PersonType::from($data['person_type']);

            $version->fill([...$data, 'is_pep' => $isPep, 'risk_level' => $personType->riskLevel($isPep)])->save();

            if ($personType === PersonType::Natural) {
                $version->shareholders()->delete();
            }

            $this->syncContacts($version, $contacts);

            // Proveedor nuevo registrado con el formato: sus datos básicos salen del Borrador. Uno en
            // uso conserva los suyos hasta que se apruebe la versión (ProviderWorkflow::finalize).
            if (! $identityLocked) {
                $provider->update([
                    'person_type' => $version->person_type,
                    'document_type' => $version->document_type,
                    'identification' => $version->identification,
                    'name' => $version->name,
                    'email' => $version->email,
                    'phone' => $version->phone,
                    'address' => $version->address,
                    'municipality_id' => $version->municipality_id,
                    'is_pep' => $version->is_pep,
                    'risk_level' => $version->risk_level,
                ]);
            }

            return $version;
        });
    }

    /**
     * Regla del formato: es PEP si administra recursos públicos, tiene relación con un PEP o
     * se declara PEP, salvo que su cargo haya terminado hace más de 2 años (Decreto 830 de 2021).
     *
     * @param  array<string, mixed>  $data
     */
    public static function evaluatePep(array $data): bool
    {
        if (! empty($data['manages_public_resources']) || ! empty($data['has_pep_relationship'])) {
            return true;
        }

        if (empty($data['declares_pep'])) {
            return false;
        }

        $endsOn = $data['pep_ends_on'] ?? null;

        if ($endsOn === null || $endsOn === '') {
            return true;
        }

        return CarbonImmutable::parse($endsOn)->addYears(ProviderVersion::PEP_VALIDITY_YEARS)->gte(today());
    }

    /** @param array<string, mixed> $data */
    private function createProvider(array $data, ProviderScope $scope): ProviderVersion
    {
        $provider = Provider::create([
            'person_type' => $data['person_type'],
            'document_type' => $data['document_type'],
            'identification' => $data['identification'],
            'name' => $data['name'],
        ]);

        ProviderLinkage::create([
            'provider_id' => $provider->id,
            'scope' => $scope,
            'status' => ProviderLinkageStatus::Pending,
        ]);

        $version = new ProviderVersion([
            'provider_id' => $provider->id,
            'number' => 1,
            'request_type' => ProviderRequestType::Linkage,
            'status' => ProviderVersionStatus::Draft,
        ]);

        return $version->setRelation('provider', $provider);
    }

    /** Bloquea la versión y confirma que siga en Borrador (otra persona pudo enviarla). */
    private function lockDraft(ProviderVersion $version): ProviderVersion
    {
        $locked = ProviderVersion::query()->with('provider')->lockForUpdate()->findOrFail($version->id);

        if (! $locked->isDraft()) {
            throw ValidationException::withMessages(['name' => 'La versión ya no está en Borrador: no se puede editar.']);
        }

        return $locked;
    }

    /**
     * Limpia los campos del otro tipo de persona y los datos PEP que no aplican.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function clearNotApplicable(array $data): array
    {
        $personType = $data['person_type'] instanceof PersonType ? $data['person_type'] : PersonType::from($data['person_type']);
        $cleared = $personType === PersonType::Natural ? ProviderVersion::LEGAL_FIELDS : ProviderVersion::NATURAL_FIELDS;

        foreach ($cleared as $field) {
            $data[$field] = in_array($field, ['accepts_electronic_means', 'in_insolvency'], true) ? false : null;
        }

        if (empty($data['declares_pep']) && empty($data['manages_public_resources'])) {
            $data['public_office'] = $data['pep_starts_on'] = $data['pep_ends_on'] = null;
        }

        if (empty($data['foreign_currency_operations'])) {
            $data['foreign_currency_detail'] = null;
        }

        if (empty($data['has_pep_relationship'])) {
            foreach (ProviderVersion::PEP_RELATION_FIELDS as $field) {
                $data[$field] = null;
            }
        }

        return $data;
    }

    /**
     * Reemplaza los contactos por rol fijo del formato; un rol sin nombre, teléfono ni correo no se
     * guarda (el cargo solo no basta).
     *
     * @param  array<string, array{name: ?string, job_title: ?string, phone: ?string, email: ?string}>  $contacts
     */
    private function syncContacts(ProviderVersion $version, array $contacts): void
    {
        $version->contacts()->delete();

        foreach (ProviderContactRole::cases() as $role) {
            $contact = array_filter($contacts[$role->value] ?? [], fn ($value) => $value !== null && $value !== '');

            if (array_diff_key($contact, ['job_title' => true]) !== []) {
                $version->contacts()->create(['role' => $role, ...$contact]);
            }
        }
    }
}
