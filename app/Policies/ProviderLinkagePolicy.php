<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ProviderLinkageStatus;
use App\Enums\ProviderScope;
use App\Enums\ProviderVersionStatus;
use App\Models\ProviderLinkage;
use App\Models\ProviderVersion;
use App\Models\User;
use App\Support\PermissionCatalog;
use App\Support\ProviderApprovalRules;

/**
 * Autorización de proveedores por proceso (líneas administrative-providers y commercial-providers).
 *
 * Cada vinculación se autoriza con la línea de permisos de su tipo, así un proceso no gestiona
 * los proveedores del otro. La verificación exige el permiso de procesar y, si están configurados,
 * uno de los cargos verificadores. Las firmas de aprobación no dependen de permisos: director del
 * proceso (siempre), oficial de cumplimiento (PEP) y Gerencia si aprueba en el sistema; también
 * necesitan "read" para entrar a la pantalla.
 */
final class ProviderLinkagePolicy
{
    public function __construct(private readonly ProviderApprovalRules $rules) {}

    /** Sin tipo (ej. exportación genérica) basta con leer alguna de las dos líneas. */
    public function viewAny(User $actor, ?ProviderScope $scope = null): bool
    {
        $scopes = $scope ? [$scope] : ProviderScope::cases();

        foreach ($scopes as $candidate) {
            if ($this->allows($actor, $candidate, 'read')) {
                return true;
            }
        }

        return false;
    }

    public function view(User $actor, ProviderLinkage $linkage): bool
    {
        return $this->allows($actor, $linkage->scope, 'read');
    }

    /** Registrar un tercero nuevo o vincular uno existente al proceso. */
    public function create(User $actor, ProviderScope $scope): bool
    {
        return $this->allows($actor, $scope, 'create');
    }

    /** Editar el Borrador abierto del proveedor (datos, contactos, accionistas, familiares y anexos). */
    public function update(User $actor, ProviderLinkage $linkage): bool
    {
        return $this->allows($actor, $linkage->scope, 'update')
            && $this->openVersion($linkage)?->status === ProviderVersionStatus::Draft;
    }

    /**
     * Editar el registro básico: proveedor sin FO-300-01 vigente, en trámite ni rechazado, de un proceso
     * que no exige el formato a todos sus proveedores (Administrativo).
     */
    public function updateBasic(User $actor, ProviderLinkage $linkage): bool
    {
        if (! $this->allows($actor, $linkage->scope, 'update') || $linkage->scope->requiresForm() || $linkage->trashed()) {
            return false;
        }

        // Sin formato vigente ni en trámite. Si el último fue rechazado, los datos se corrigen en el
        // FO-300-01; los formatos de años anteriores (histórico) no impiden la edición básica.
        $provider = $linkage->loadMissing(['provider.openVersion', 'provider.latestVersion'])->provider;

        return $provider->current_version_id === null && $provider->openVersion === null
            && $provider->latestVersion?->status !== ProviderVersionStatus::Rejected;
    }

    /** Condiciones del proceso (segmento, condición y plazo de pago): se negocian en cualquier momento. */
    public function updateTerms(User $actor, ProviderLinkage $linkage): bool
    {
        return $this->allows($actor, $linkage->scope, 'update') && ! $linkage->trashed();
    }

    /** Enviar el Borrador a verificación. */
    public function submit(User $actor, ProviderLinkage $linkage): bool
    {
        return $this->update($actor, $linkage);
    }

    /**
     * Abrir la actualización anual (o el primer FO-300-01 de un proveedor básico) cuando no hay
     * versión en trámite ni otra solicitud del año en curso (regla anual).
     */
    public function startUpdate(User $actor, ProviderLinkage $linkage): bool
    {
        return $this->allows($actor, $linkage->scope, 'create')
            && $this->openVersion($linkage) === null
            && ! $linkage->provider->hasVersionInYear(now()->year);
    }

    /** Reabrir para corregir la última versión, si fue Rechazada (vuelve a Borrador). */
    public function correctRejected(User $actor, ProviderLinkage $linkage): bool
    {
        if (! $this->allows($actor, $linkage->scope, 'reverse') || $this->openVersion($linkage) !== null) {
            return false;
        }

        return $linkage->loadMissing('provider.latestVersion')->provider->latestVersion?->status === ProviderVersionStatus::Rejected;
    }

    /** Firma de verificación y rechazo en verificación. */
    /** Verificar: permiso de procesar y, si están configurados, uno de los cargos verificadores del tipo. */
    public function verify(User $actor, ProviderLinkage $linkage): bool
    {
        return $this->canVerify($actor, $linkage)
            && $this->openVersion($linkage)?->status === ProviderVersionStatus::Verification;
    }

    /** Observación del oficial de cumplimiento a un proveedor no PEP en revisión. */
    public function addComplianceObservation(User $actor, ProviderLinkage $linkage): bool
    {
        $version = $this->openVersion($linkage);

        return $version !== null && ! $version->is_pep
            && in_array($version->status, [ProviderVersionStatus::Verification, ProviderVersionStatus::Approval], true)
            && $this->rules->isComplianceOfficer($actor);
    }

    /** Aprobación de Gerencia en el sistema (PEP con el modo "en el sistema"). */
    public function approveAsManagement(User $actor, ProviderLinkage $linkage): bool
    {
        $version = $this->openVersion($linkage);

        return $version !== null && $this->rules->canApproveAsManagement($version, $actor);
    }

    /** Registrar la firma física de Gerencia: quien tenga el permiso de procesar el tipo. */
    public function registerManagementApproval(User $actor, ProviderLinkage $linkage): bool
    {
        $version = $this->signedVersion($linkage);

        return $version !== null && $this->allows($actor, $linkage->scope, 'process')
            && $this->rules->acceptsPhysicalManagementApproval($version);
    }

    /** Visto bueno del oficial de cumplimiento (proveedor PEP). */
    public function certifyCompliance(User $actor, ProviderLinkage $linkage): bool
    {
        return $this->openVersion($linkage)?->status === ProviderVersionStatus::Compliance
            && $this->rules->isComplianceOfficer($actor);
    }

    /** Firma de aprobación: el usuario aprueba al menos una vinculación pendiente de la versión. */
    public function approve(User $actor, ProviderLinkage $linkage): bool
    {
        $version = $this->reviewVersion($linkage);

        return in_array($version?->status, [ProviderVersionStatus::Approval, ProviderVersionStatus::Approved], true)
            && $this->rules->scopesApprovableBy($version, $actor) !== [];
    }

    /**
     * Rechazar una versión en revisión: quien verifica (process), el oficial de cumplimiento
     * en su etapa o un aprobador en la suya.
     */
    public function reject(User $actor, ProviderLinkage $linkage): bool
    {
        $version = $this->reviewVersion($linkage);

        return match ($version?->status) {
            ProviderVersionStatus::Verification => $this->canVerify($actor, $linkage),
            ProviderVersionStatus::Compliance => $this->rules->isComplianceOfficer($actor),
            ProviderVersionStatus::Approval => $this->rules->scopesApprovableBy($version, $actor) !== [] || $this->rules->canApproveAsManagement($version, $actor),
            ProviderVersionStatus::Approved => $this->rules->scopesApprovableBy($version, $actor) !== [],
            default => false,
        };
    }

    /** Devolver a Borrador una versión en revisión para corregirla. */
    public function returnToDraft(User $actor, ProviderLinkage $linkage): bool
    {
        return $this->allows($actor, $linkage->scope, 'reverse')
            && (bool) $this->openVersion($linkage)?->status->isUnderReview();
    }

    /** Activar o inactivar solo vinculaciones ya aprobadas. */
    public function toggle(User $actor, ProviderLinkage $linkage): bool
    {
        return $this->allows($actor, $linkage->scope, 'toggle') && $linkage->isApproved();
    }

    /** Eliminar solo vinculaciones que nunca se aprobaron (Pendientes o Rechazadas). */
    public function delete(User $actor, ProviderLinkage $linkage): bool
    {
        return $this->allows($actor, $linkage->scope, 'delete')
            && in_array($linkage->status, [ProviderLinkageStatus::Pending, ProviderLinkageStatus::Rejected], true);
    }

    public function restore(User $actor, ProviderLinkage $linkage): bool
    {
        return $this->allows($actor, $linkage->scope, 'reverse');
    }

    /**
     * Datos sensibles (ingresos, información financiera, PEP, grupo de protección; Ley 1581):
     * quien verifica, los aprobadores y el oficial de cumplimiento.
     */
    public function viewSensitive(User $actor, ProviderLinkage $linkage): bool
    {
        if ($this->allows($actor, $linkage->scope, 'process') || $this->rules->isComplianceOfficer($actor)) {
            return true;
        }

        $version = $this->reviewVersion($linkage);

        return $version !== null && in_array($version->status, [ProviderVersionStatus::Approval, ProviderVersionStatus::Approved], true)
            && $this->rules->scopesApprovableBy($version, $actor) !== [];
    }

    private function allows(User $actor, ProviderScope $scope, string $action): bool
    {
        return $actor->hasPermissionTo(PermissionCatalog::permissionName($scope->module(), $action));
    }

    private function canVerify(User $actor, ProviderLinkage $linkage): bool
    {
        return $this->allows($actor, $linkage->scope, 'process') && $this->rules->canVerifyByPosition($actor, $linkage->scope);
    }

    /** Versión en trámite o, si no hay, la vigente (la firma física de Gerencia puede llegar después). */
    private function signedVersion(ProviderLinkage $linkage): ?ProviderVersion
    {
        $provider = $linkage->loadMissing(['provider.openVersion', 'provider.currentVersion'])->provider;

        return $provider->openVersion ?? $provider->currentVersion;
    }

    private function openVersion(ProviderLinkage $linkage): ?ProviderVersion
    {
        return $linkage->loadMissing('provider.openVersion')->provider->openVersion;
    }

    /** Versión sobre la que se firma (en trámite, o la vigente si hay una vinculación esperando). */
    private function reviewVersion(ProviderLinkage $linkage): ?ProviderVersion
    {
        return $linkage->loadMissing(['provider.openVersion', 'provider.currentVersion', 'provider.linkages'])->provider->reviewVersion();
    }
}
