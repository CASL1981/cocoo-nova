<?php

declare(strict_types=1);

namespace App\Actions\Providers;

use App\Enums\ProviderContactRole;
use App\Enums\ProviderLinkageStatus;
use App\Enums\ProviderRequestType;
use App\Enums\ProviderScope;
use App\Enums\ProviderVersionStatus;
use App\Models\Provider;
use App\Models\ProviderLinkage;
use App\Models\ProviderVersion;
use App\Models\User;
use App\Support\ProviderApprovalRules;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Punto único del flujo de una versión del formato FO-300-01 y de las vinculaciones.
 *
 * Borrador → En verificación → (Cumplimiento si es PEP) → En aprobación → Aprobada; rechazar
 * o devolver a Borrador desde cualquier revisión. La autorización (permisos, aprobadores y
 * oficial de cumplimiento) la decide ProviderLinkagePolicy antes de llamar a estos métodos;
 * aquí se validan los estados para que dos usuarios no avancen la misma versión a la vez.
 */
final class ProviderWorkflow
{
    public function __construct(private readonly ProviderApprovalRules $rules) {}

    /**
     * Nueva versión en Borrador copiada de la vigente (Actualización) o, si el proveedor nunca se
     * aprobó, de su última versión rechazada (nueva Vinculación), con sus tablas hijas.
     * Un proveedor básico (sin versiones) inicia su primer FO-300-01 con sus datos básicos.
     * Los anexos copian la referencia al archivo (no se duplica en disco).
     */
    public function startUpdate(Provider $provider): ProviderVersion
    {
        return DB::transaction(function () use ($provider): ProviderVersion {
            $provider = Provider::query()->with('municipality')->lockForUpdate()->findOrFail($provider->id);

            if ($provider->openVersion()->exists()) {
                throw ValidationException::withMessages(['version' => 'El proveedor ya tiene una versión en trámite.']);
            }

            // Una sola solicitud por año calendario: una rechazada se corrige, no se reemplaza.
            if ($provider->hasVersionInYear(now()->year)) {
                throw ValidationException::withMessages(['version' => 'El proveedor ya tiene el FO-300-01 de '.now()->year.': solo se diligencia uno por año. Si fue rechazado, use "Corregir FO-300-01".']);
            }

            $children = ['contacts', 'shareholders', 'pepRelatives', 'documents'];
            $isUpdate = $provider->current_version_id !== null;
            $current = $isUpdate
                ? $provider->currentVersion()->with($children)->first()
                : $provider->versions()->with($children)->orderByDesc('number')->first();

            if ($current === null) {
                return $this->firstDraftFromBasicData($provider);
            }

            $draft = $current->replicate([
                'status', 'submitted_by', 'submitted_at', 'verified_by', 'verified_at', 'verification_observations',
                'compliance_by', 'compliance_at', 'compliance_observations', 'rejected_by', 'rejected_at',
                'rejection_reason', 'approved_at',
            ]);
            $draft->fill([
                'number' => (int) $provider->versions()->withTrashed()->max('number') + 1,
                'request_type' => $isUpdate ? ProviderRequestType::Update : ProviderRequestType::Linkage,
                'status' => ProviderVersionStatus::Draft,
            ])->save();

            foreach ($children as $relation) {
                foreach ($current->{$relation} as $child) {
                    $draft->{$relation}()->create($child->replicate(['provider_version_id'])->getAttributes());
                }
            }

            return $draft;
        });
    }

    /**
     * Vincula un proveedor existente a otro proceso. En un proceso que no exige el formato
     * (Administrativo) la vinculación queda activa de inmediato. En los que lo exigen (Comercial),
     * si hay una versión en trámite la nueva vinculación se suma a su aprobación; si el formato de
     * este año ya está aprobado, el director la aprueba sobre la versión vigente; si no, se abre
     * una actualización (o el primer FO-300-01 de un proveedor básico) en Borrador.
     */
    public function link(Provider $provider, ProviderScope $scope): ProviderLinkage
    {
        return DB::transaction(function () use ($provider, $scope): ProviderLinkage {
            $linkage = ProviderLinkage::withTrashed()->where('provider_id', $provider->id)->where('scope', $scope)->first();

            if ($linkage !== null && ! $linkage->trashed() && $linkage->status !== ProviderLinkageStatus::Rejected) {
                throw ValidationException::withMessages(['identification' => "El proveedor ya está vinculado como {$scope->label()}."]);
            }

            $linkage ??= new ProviderLinkage(['provider_id' => $provider->id, 'scope' => $scope]);

            // Un proceso que no exige el formato (Administrativo) vincula de inmediato, sin aprobación.
            if (! $scope->requiresForm()) {
                $linkage->fill(['status' => ProviderLinkageStatus::Active, 'approved_at' => now()]);
                $linkage->trashed() ? $linkage->restore() : $linkage->save();

                return $linkage;
            }

            $linkage->status = ProviderLinkageStatus::Pending;

            // Una vinculación eliminada se restaura en lugar de duplicarla (índice único por proceso).
            $linkage->trashed() ? $linkage->restore() : $linkage->save();

            // Sin versión en trámite se abre un Borrador; si ya hay una, también esperará la firma de este proceso.
            // Si el formato de este año ya existe (regla anual), no se abre otro: la vinculación se aprueba
            // sobre el vigente o espera a que se corrija el rechazado.
            if (! $provider->openVersion()->exists() && ! $provider->hasVersionInYear(now()->year)) {
                $this->startUpdate($provider);
            }

            return $linkage;
        });
    }

    /**
     * Primera versión del FO-300-01 de un proveedor básico, prellenada con sus datos básicos.
     * El riesgo se recalcula al guardar el Borrador (SaveProviderVersion).
     */
    private function firstDraftFromBasicData(Provider $provider): ProviderVersion
    {
        $draft = $provider->versions()->create([
            'number' => (int) $provider->versions()->withTrashed()->max('number') + 1,
            'request_type' => ProviderRequestType::Linkage,
            'status' => ProviderVersionStatus::Draft,
            'person_type' => $provider->person_type,
            'document_type' => $provider->document_type,
            'identification' => $provider->identification,
            'name' => $provider->name,
            'email' => $provider->email,
            'phone' => $provider->phone,
            'address' => $provider->address,
            'municipality_id' => $provider->municipality_id,
            'department_id' => $provider->municipality?->parent_id,
            'is_pep' => false,
            'risk_level' => $provider->person_type->riskLevel(false),
        ]);

        if ($provider->contact_name !== null) {
            $draft->contacts()->create(['role' => ProviderContactRole::SalesRepresentative, 'name' => $provider->contact_name]);
        }

        return $draft;
    }

    /** Envía el Borrador a verificación; exige las declaraciones obligatorias. */
    public function submit(ProviderVersion $version, User $actor): ProviderVersion
    {
        return $this->transition($version, [ProviderVersionStatus::Draft], function (ProviderVersion $version) use ($actor): void {
            $missing = array_filter(ProviderVersion::REQUIRED_DECLARATIONS, fn (string $field) => ! $version->{$field});

            if ($missing !== []) {
                throw ValidationException::withMessages(['version' => 'Para enviar la versión deben aceptarse las declaraciones de veracidad, tratamiento de datos, información recibida y origen de fondos.']);
            }

            $version->fill(['status' => ProviderVersionStatus::Verification, 'submitted_by' => $actor->id, 'submitted_at' => now(),
                'submitted_position' => $actor->positionName()]);
        });
    }

    /** Firma de verificación: un proveedor PEP pasa al oficial de cumplimiento; los demás a aprobación. */
    public function verify(ProviderVersion $version, User $actor, ?string $observations): ProviderVersion
    {
        return $this->transition($version, [ProviderVersionStatus::Verification], function (ProviderVersion $version) use ($actor, $observations): void {
            $version->fill([
                'status' => $version->is_pep ? ProviderVersionStatus::Compliance : ProviderVersionStatus::Approval,
                'verified_by' => $actor->id,
                'verified_at' => now(),
                'verified_position' => $actor->positionName(),
                'verification_observations' => $observations,
            ]);
        });
    }

    /** Visto bueno del oficial de cumplimiento a un proveedor PEP. */
    public function certifyCompliance(ProviderVersion $version, User $actor, ?string $observations): ProviderVersion
    {
        return $this->transition($version, [ProviderVersionStatus::Compliance], function (ProviderVersion $version) use ($actor, $observations): void {
            $version->fill([
                'status' => ProviderVersionStatus::Approval,
                'compliance_by' => $actor->id,
                'compliance_at' => now(),
                'compliance_position' => $actor->positionName(),
                'compliance_observations' => $observations,
            ]);
        });
    }

    /**
     * Observación del oficial de cumplimiento a un proveedor que no es PEP, cuando lo considere
     * necesario. No es una etapa: la versión sigue en el mismo estado.
     */
    public function addComplianceObservation(ProviderVersion $version, User $actor, string $observations): ProviderVersion
    {
        return $this->transition($version, [ProviderVersionStatus::Verification, ProviderVersionStatus::Approval],
            function (ProviderVersion $version) use ($actor, $observations): void {
                if ($version->is_pep) {
                    throw ValidationException::withMessages(['version' => 'Un proveedor PEP se revisa con el visto bueno de cumplimiento.']);
                }

                $version->fill([
                    'compliance_by' => $actor->id,
                    'compliance_at' => now(),
                    'compliance_position' => $actor->positionName(),
                    'compliance_observations' => $observations,
                ]);
            });
    }

    /**
     * Aprobación de Gerencia en el sistema (PEP con el modo "en el sistema"). Si los directores ya
     * aprobaron sus vinculaciones, la versión queda aprobada.
     */
    public function approveAsManagement(ProviderVersion $version, User $actor, ?string $observations): ProviderVersion
    {
        return DB::transaction(function () use ($version, $actor, $observations): ProviderVersion {
            $version = $this->lock($version, [ProviderVersionStatus::Approval]);

            if (! $this->rules->canApproveAsManagement($version, $actor)) {
                throw ValidationException::withMessages(['version' => 'Esta versión no espera tu aprobación como Gerencia.']);
            }

            $version->fill($this->managementSignature($actor, true, today()->toDateString(), $observations))->save();

            if ($this->rules->isFullyApproved($version)) {
                $this->finalize($version, $actor);
            }

            return $version;
        });
    }

    /**
     * Registra la firma física de Gerencia de un proveedor PEP (modo "en físico"). Es informativa:
     * no cambia el estado ni bloquea la aprobación del director.
     */
    public function registerPhysicalManagementApproval(ProviderVersion $version, User $actor, string $signedOn, ?string $observations): ProviderVersion
    {
        return $this->transition($version, [ProviderVersionStatus::Approval, ProviderVersionStatus::Approved],
            function (ProviderVersion $version) use ($actor, $signedOn, $observations): void {
                if (! $this->rules->acceptsPhysicalManagementApproval($version)) {
                    throw ValidationException::withMessages(['version' => 'Esta versión no admite el registro de la firma física de Gerencia.']);
                }

                $version->fill($this->managementSignature($actor, false, $signedOn, $observations));
            });
    }

    /**
     * @return array<string, mixed>
     */
    private function managementSignature(User $actor, bool $inSystem, string $signedOn, ?string $observations): array
    {
        return [
            'management_by' => $actor->id,
            'management_position' => $actor->positionName(),
            'management_in_system' => $inSystem,
            'management_signed_on' => $signedOn,
            'management_registered_at' => now(),
            'management_observations' => $observations,
        ];
    }

    /**
     * Firma de aprobación de los procesos que le corresponden al usuario. Cuando todas las
     * vinculaciones están aprobadas, la versión pasa a ser la vigente y las pendientes se activan.
     *
     * @return list<ProviderScope> Tipos aprobados en esta firma.
     */
    public function approve(ProviderVersion $version, User $actor, ?string $observations): array
    {
        return DB::transaction(function () use ($version, $actor, $observations): array {
            $version = $this->lock($version, [ProviderVersionStatus::Approval, ProviderVersionStatus::Approved]);
            $this->ensureCurrentWhenApproved($version);
            $scopes = $this->rules->scopesApprovableBy($version, $actor);

            if ($scopes === []) {
                throw ValidationException::withMessages(['version' => 'No tienes vinculaciones pendientes por aprobar en esta versión.']);
            }

            foreach ($scopes as $scope) {
                // Sobre la vigente puede existir la firma de una vinculación anterior del mismo proceso.
                $version->approvals()->updateOrCreate(['scope' => $scope], [
                    'user_id' => $actor->id, 'user_position' => $actor->positionName(), 'approved_at' => now(), 'observations' => $observations,
                ]);
            }

            if ($version->status === ProviderVersionStatus::Approved) {
                // Vinculación nueva sobre el formato vigente: se activa sin cambiar la versión ni su vencimiento.
                ProviderLinkage::query()->where('provider_id', $version->provider_id)
                    ->where('status', ProviderLinkageStatus::Pending)
                    ->whereIn('scope', $scopes)
                    ->update(['status' => ProviderLinkageStatus::Active, 'approved_by' => $actor->id, 'approved_at' => now()]);
            } elseif ($this->rules->isFullyApproved($version)) {
                $this->finalize($version, $actor);
            }

            return $scopes;
        });
    }

    /**
     * Rechaza una versión en revisión; las vinculaciones que nunca se aprobaron quedan Rechazadas.
     * Sobre la versión vigente (vinculación nueva del mismo año) solo se rechazan las vinculaciones
     * pendientes: el formato aprobado no cambia.
     */
    public function reject(ProviderVersion $version, User $actor, string $reason): ProviderVersion
    {
        return $this->transition($version, ProviderVersionStatus::cases(), function (ProviderVersion $version) use ($actor, $reason): void {
            if ($version->status === ProviderVersionStatus::Approved) {
                $this->ensureCurrentWhenApproved($version);
                ProviderLinkage::query()->where('provider_id', $version->provider_id)
                    ->where('status', ProviderLinkageStatus::Pending)
                    ->update(['status' => ProviderLinkageStatus::Rejected]);

                return;
            }

            if (! $version->status->isUnderReview()) {
                throw ValidationException::withMessages(['version' => 'Solo se rechaza una versión en revisión.']);
            }

            $version->fill([
                'status' => ProviderVersionStatus::Rejected,
                'rejected_by' => $actor->id,
                'rejected_at' => now(),
                'rejection_reason' => $reason,
            ]);

            ProviderLinkage::query()->where('provider_id', $version->provider_id)
                ->where('status', ProviderLinkageStatus::Pending)
                ->update(['status' => ProviderLinkageStatus::Rejected]);
        });
    }

    /** Devuelve a Borrador una versión en revisión para corregirla; descarta firmas y aprobaciones. */
    public function returnToDraft(ProviderVersion $version): ProviderVersion
    {
        return $this->transition($version, [ProviderVersionStatus::Verification, ProviderVersionStatus::Compliance, ProviderVersionStatus::Approval],
            function (ProviderVersion $version): void {
                $version->approvals()->delete();
                $version->fill(['status' => ProviderVersionStatus::Draft, ...ProviderVersion::REVIEW_RESET]);
            });
    }

    /**
     * Reabre una versión Rechazada para corregirla (regla anual: no se crea otra solicitud ese año).
     * Vuelve a Borrador con el mismo número, se descartan las firmas y las vinculaciones rechazadas
     * vuelven a Pendientes. El motivo del rechazo se conserva para que se vea qué corregir.
     */
    public function correctRejected(ProviderVersion $version): ProviderVersion
    {
        return $this->transition($version, [ProviderVersionStatus::Rejected], function (ProviderVersion $version): void {
            $latest = (int) $version->provider->versions()->max('number');

            if ($version->number !== $latest || $version->provider->openVersion()->exists()) {
                throw ValidationException::withMessages(['version' => 'Solo se corrige la última versión del proveedor y sin otra en trámite.']);
            }

            $version->approvals()->delete();
            $version->fill(['status' => ProviderVersionStatus::Draft, ...ProviderVersion::REVIEW_RESET]);

            ProviderLinkage::query()->where('provider_id', $version->provider_id)
                ->where('status', ProviderLinkageStatus::Rejected)
                ->update(['status' => ProviderLinkageStatus::Pending]);
        });
    }

    /** Sobre una versión aprobada solo se firma si es la vigente del proveedor. */
    private function ensureCurrentWhenApproved(ProviderVersion $version): void
    {
        if ($version->status === ProviderVersionStatus::Approved && $version->provider->current_version_id !== $version->id) {
            throw ValidationException::withMessages(['version' => 'Solo se firman vinculaciones sobre la versión vigente del proveedor.']);
        }
    }

    /** La versión aprobada pasa a ser la vigente: resumen del proveedor y vinculaciones pendientes activas. */
    private function finalize(ProviderVersion $version, User $actor): void
    {
        $approvedAt = now();
        $version->fill(['status' => ProviderVersionStatus::Approved, 'approved_at' => $approvedAt])->save();

        $version->provider->update([
            'current_version_id' => $version->id,
            'person_type' => $version->person_type,
            'document_type' => $version->document_type,
            'identification' => $version->identification,
            'name' => $version->name,
            // Los datos básicos quedan iguales a los del formato aprobado.
            'email' => $version->email,
            'phone' => $version->phone,
            'address' => $version->address,
            'municipality_id' => $version->municipality_id,
            'risk_level' => $version->risk_level,
            'is_pep' => $version->is_pep,
            'next_update_on' => $approvedAt->addMonths(Provider::UPDATE_PERIOD_MONTHS)->toDateString(),
        ]);

        ProviderLinkage::query()->where('provider_id', $version->provider_id)
            ->where('status', ProviderLinkageStatus::Pending)
            ->update(['status' => ProviderLinkageStatus::Active, 'approved_by' => $actor->id, 'approved_at' => $approvedAt]);
    }

    /**
     * Cambia el estado con bloqueo de fila y validación del estado de origen.
     *
     * @param  list<ProviderVersionStatus>  $from
     * @param  callable(ProviderVersion): void  $change
     */
    private function transition(ProviderVersion $version, array $from, callable $change): ProviderVersion
    {
        return DB::transaction(function () use ($version, $from, $change): ProviderVersion {
            $version = $this->lock($version, $from);
            $change($version);
            $version->save();

            return $version;
        });
    }

    /** @param list<ProviderVersionStatus> $from */
    private function lock(ProviderVersion $version, array $from): ProviderVersion
    {
        $locked = ProviderVersion::query()->with('provider')->lockForUpdate()->findOrFail($version->id);

        if (! in_array($locked->status, $from, true)) {
            throw ValidationException::withMessages(['version' => "La versión cambió de estado ({$locked->status->label()}): actualiza la página."]);
        }

        return $locked;
    }
}
