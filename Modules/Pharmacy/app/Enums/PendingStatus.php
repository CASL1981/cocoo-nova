<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Enums;

/**
 * Ciclo de vida del encabezado de un pendiente.
 *
 * Activo: farmacia arma la solicitud y puede editarla.
 * Terminado: la solicitud fue enviada; farmacia ya no la edita y oficina principal gestiona sus ítems.
 */
enum PendingStatus: string
{
    case Active = 'active';
    case Finished = 'finished';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'Activo',
            self::Finished => 'Terminado',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Active => 'bg-primary',
            self::Finished => 'bg-success',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return collect(self::cases())->mapWithKeys(fn (self $c) => [$c->value => $c->label()])->all();
    }
}
