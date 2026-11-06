<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ProviderLinkageStatus;
use App\Enums\ProviderScope;
use App\Enums\ProviderVersionStatus;
use App\Models\Process;
use App\Models\ProviderLinkage;
use App\Models\ProviderVersion;
use App\Models\User;
use App\Settings\CompanySettings;

/**
 * Reglas de quién revisa y aprueba una versión del formato FO-300-01.
 *
 * - Cada vinculación pendiente la aprueba el director del proceso de su tipo (Administrativo
 *   o Comercial) o su reemplazo vigente.
 * - Si el proveedor es PEP, aprueba el director de Gerencia (o su reemplazo) por todas las
 *   vinculaciones, después de la revisión del oficial de cumplimiento.
 */
final class ProviderApprovalRules
{
    public function __construct(private readonly CompanySettings $settings) {}

    /**
     * Tipos de proveedor cuya vinculación participa en la aprobación de la versión:
     * todas las vinculaciones vigentes del proveedor, salvo las rechazadas.
     *
     * @return list<ProviderScope>
     */
    public function requiredScopes(ProviderVersion $version): array
    {
        return array_values(ProviderLinkage::query()
            ->where('provider_id', $version->provider_id)
            ->where('status', '!=', ProviderLinkageStatus::Rejected)
            ->get(['scope'])
            ->map(fn (ProviderLinkage $linkage): ProviderScope => $linkage->scope)
            ->all());
    }

    /**
     * Tipos de proveedor que aún esperan la firma de aprobación.
     *
     * @return list<ProviderScope>
     */
    public function pendingScopes(ProviderVersion $version): array
    {
        // Versión vigente: solo esperan firma los procesos que se vincularon después, en el mismo año.
        if ($version->status === ProviderVersionStatus::Approved) {
            return array_values(ProviderLinkage::query()
                ->where('provider_id', $version->provider_id)
                ->where('status', ProviderLinkageStatus::Pending)
                ->get(['scope'])
                ->map(fn (ProviderLinkage $linkage): ProviderScope => $linkage->scope)
                ->all());
        }

        $approved = $version->approvals()->pluck('scope')->map(fn (ProviderScope $scope) => $scope->value)->all();

        return array_values(array_filter(
            $this->requiredScopes($version),
            fn (ProviderScope $scope) => ! in_array($scope->value, $approved, true),
        ));
    }

    /**
     * Tipos pendientes que el usuario puede aprobar ahora mismo (vacío si no le corresponde).
     *
     * @return list<ProviderScope>
     */
    public function scopesApprovableBy(ProviderVersion $version, User $user): array
    {
        $pending = $this->pendingScopes($version);

        if ($version->is_pep) {
            return $this->isProcessApprover(Process::GENERAL_MANAGEMENT, $user) ? $pending : [];
        }

        return array_values(array_filter(
            $pending,
            fn (ProviderScope $scope) => $this->isProcessApprover($scope->processName(), $user),
        ));
    }

    /** El usuario es el oficial de cumplimiento configurado en Datos de la empresa. */
    public function isComplianceOfficer(User $user): bool
    {
        return $this->settings->compliance_officer_id === $user->id;
    }

    /** Nombre del proceso que aprueba la versión, para mostrarlo en pantalla. */
    public function approverProcessName(ProviderVersion $version, ProviderScope $scope): string
    {
        return $version->is_pep ? Process::GENERAL_MANAGEMENT : $scope->processName();
    }

    private function isProcessApprover(string $processName, User $user): bool
    {
        return (bool) Process::named($processName)?->isApprover($user);
    }
}
