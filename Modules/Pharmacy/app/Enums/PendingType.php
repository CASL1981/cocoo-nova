<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Enums;

/**
 * Tipo de solicitud pendiente de farmacia.
 * Los valores son los códigos cortos usados históricamente por la organización.
 */
enum PendingType: string
{
    case Order = 'PD';
    case Requisition = 'RQ';
    case InternalMessage = 'MI';

    public function label(): string
    {
        return match ($this) {
            self::Order => 'Pedido',
            self::Requisition => 'Requisición',
            self::InternalMessage => 'Mensaje interno',
        };
    }

    /**
     * Opciones para <x-forms.select>: código => "código - etiqueta".
     *
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => "{$c->value} - {$c->label()}"])->all();
    }
}
