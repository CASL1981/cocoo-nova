<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabelOptions;

/**
 * Cómo aprueba la Gerencia a los proveedores PEP (Datos de la empresa).
 *
 * En físico, la firma de Gerencia se registra en el sistema como información y no bloquea la
 * aprobación del director del proceso. En el sistema, la versión no queda aprobada sin ella.
 */
enum ManagementApprovalMode: string
{
    use HasLabelOptions;

    case Physical = 'physical';
    case System = 'system';

    public function label(): string
    {
        return match ($this) {
            self::Physical => 'En físico (se registra en el sistema)',
            self::System => 'En el sistema',
        };
    }
}
