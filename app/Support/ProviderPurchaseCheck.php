<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ProviderPurchaseStatus;

/**
 * Resultado de ProviderPurchaseRules::evaluate(): estado, mensaje para el usuario y motivo
 * que se guarda al marcar el proveedor como "FO-300-01 requerido".
 */
final readonly class ProviderPurchaseCheck
{
    public function __construct(
        public ProviderPurchaseStatus $status,
        public ?string $message = null,
        public ?string $reason = null,
    ) {}
}
