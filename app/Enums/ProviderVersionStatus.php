<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabelOptions;

/**
 * Estado de una versión (vinculación o actualización) del formato FO-300-01.
 *
 * Borrador → En verificación → (Cumplimiento si es PEP) → En aprobación → Aprobada.
 * Cualquier revisión puede rechazarla, y "Devolver" la regresa a Borrador.
 */
enum ProviderVersionStatus: string
{
    use HasLabelOptions;

    case Draft = 'draft';
    case Verification = 'verification';
    case Compliance = 'compliance';
    case Approval = 'approval';
    case Approved = 'approved';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Borrador',
            self::Verification => 'En verificación',
            self::Compliance => 'Cumplimiento',
            self::Approval => 'En aprobación',
            self::Approved => 'Aprobada',
            self::Rejected => 'Rechazada',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'bg-secondary',
            self::Verification => 'bg-info',
            self::Compliance => 'bg-warning text-dark',
            self::Approval => 'bg-primary',
            self::Approved => 'bg-success',
            self::Rejected => 'bg-danger',
        };
    }

    /** La versión sigue en trámite; solo puede haber una abierta por proveedor. */
    public function isOpen(): bool
    {
        return ! in_array($this, [self::Approved, self::Rejected], true);
    }

    /** Estados de revisión, en los que se puede rechazar o devolver a Borrador. */
    public function isUnderReview(): bool
    {
        return in_array($this, [self::Verification, self::Compliance, self::Approval], true);
    }

    /**
     * Estados abiertos, para consultas.
     *
     * @return list<self>
     */
    public static function open(): array
    {
        return [self::Draft, self::Verification, self::Compliance, self::Approval];
    }
}
