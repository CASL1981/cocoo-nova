<?php

namespace App\Enums;

/**
 * Clasifica un ServicePoint según su naturaleza dentro de la organización.
 * Permite que el futuro módulo de Farmacia filtre únicamente los puntos
 * de tipo Pharmacy sin acoplarse a nombres o códigos específicos.
 */
enum ServicePointType: string
{
    case Headquarters = 'headquarters';
    case Pharmacy = 'pharmacy';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Headquarters => 'Oficina Principal',
            self::Pharmacy => 'Farmacia',
            self::Other => 'Otro',
        };
    }

    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $case) => [$case->value => $case->label()])->all();
    }
}
