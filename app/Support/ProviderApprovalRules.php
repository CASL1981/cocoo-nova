<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ManagementApprovalMode;
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
 * - Verifica quien tenga el permiso de procesar del tipo y, si Datos de la empresa define cargos
 *   verificadores para ese tipo, además uno de esos cargos.
 * - Cada vinculación la aprueba siempre el director del proceso de su tipo (Administrativo o
 *   Comercial) o su reemplazo vigente, también en los proveedores PEP.
 * - Un proveedor PEP pasa antes por el oficial de cumplimiento. La Gerencia lo aprueba en físico
 *   (registro informativo, no bloquea) o en el sistema (requisito para quedar aprobada), según
 *   Datos de la empresa.
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
        return array_values(array_filter(
            $this->pendingScopes($version),
            fn (ProviderScope $scope) => $this->isProcessApprover($scope->processName(), $user),
        ));
    }

    /** El usuario es el oficial de cumplimiento configurado en Datos de la empresa. */
    public function isComplianceOfficer(User $user): bool
    {
        return $this->settings->compliance_officer_id === $user->id;
    }

    /** Nombre del proceso que aprueba la vinculación, para mostrarlo en pantalla. */
    public function approverProcessName(ProviderVersion $version, ProviderScope $scope): string
    {
        return $scope->processName();
    }

    /**
     * Quienes pueden firmar hoy por el proceso (director y reemplazos vigentes). Vacío si el
     * proceso no existe, está inactivo o no tiene director ni reemplazo: la firma queda bloqueada.
     *
     * @return list<string>
     */
    public function approverNames(string $processName): array
    {
        return Process::named($processName)?->approverNames() ?? [];
    }

    /**
     * El usuario puede verificar proveedores de ese tipo según su cargo (el permiso se revisa aparte).
     * Sin cargos configurados para el tipo no hay restricción por cargo.
     */
    public function canVerifyByPosition(User $user, ProviderScope $scope): bool
    {
        $positions = $this->settings->verifierPositions($scope);

        return $positions === [] || in_array((int) $user->position_id, $positions, true);
    }

    /** La versión necesita la aprobación de Gerencia en el sistema (PEP con el modo "en el sistema"). */
    public function requiresManagementInSystem(ProviderVersion $version): bool
    {
        return $version->is_pep && $this->settings->managementApprovalMode() === ManagementApprovalMode::System;
    }

    /** El usuario aprueba como Gerencia en el sistema (director de Gerencia o su reemplazo). */
    public function canApproveAsManagement(ProviderVersion $version, User $user): bool
    {
        return $this->requiresManagementInSystem($version)
            && $version->status === ProviderVersionStatus::Approval
            && ! $version->hasManagementApproval()
            && $this->isProcessApprover(Process::GENERAL_MANAGEMENT, $user);
    }

    /** La firma física de Gerencia se registra (informativa) en un PEP con el modo "en físico". */
    public function acceptsPhysicalManagementApproval(ProviderVersion $version): bool
    {
        return $version->is_pep
            && $this->settings->managementApprovalMode() === ManagementApprovalMode::Physical
            && in_array($version->status, [ProviderVersionStatus::Approval, ProviderVersionStatus::Approved], true)
            && ! $version->hasManagementApproval();
    }

    /** Todas las vinculaciones aprobadas y, si se exige, la aprobación de Gerencia en el sistema. */
    public function isFullyApproved(ProviderVersion $version): bool
    {
        return $this->pendingScopes($version) === []
            && (! $this->requiresManagementInSystem($version) || $version->hasManagementApproval());
    }

    private function isProcessApprover(string $processName, User $user): bool
    {
        return (bool) Process::named($processName)?->isApprover($user);
    }
}
