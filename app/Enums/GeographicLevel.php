<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabelOptions;

/**
 * Nivel de una ubicación geográfica. La jerarquía es fija: país → departamento → ciudad.
 */
enum GeographicLevel: string
{
    use HasLabelOptions;

    case Country = 'country';
    case Department = 'department';
    case City = 'city';

    public function label(): string
    {
        return match ($this) {
            self::Country => 'País',
            self::Department => 'Departamento',
            self::City => 'Ciudad',
        };
    }

    /** Nombre con artículo indefinido para los mensajes (ej. "una ciudad"). */
    public function withArticle(): string
    {
        return match ($this) {
            self::Country => 'un país',
            self::Department => 'un departamento',
            self::City => 'una ciudad',
        };
    }

    public function badgeClass(): string
    {
        return match ($this) {
            self::Country => 'bg-primary',
            self::Department => 'bg-info',
            self::City => 'bg-secondary',
        };
    }

    /** Nivel al que debe pertenecer: null para un país (no tiene padre). */
    public function parentLevel(): ?self
    {
        return match ($this) {
            self::Country => null,
            self::Department => self::Country,
            self::City => self::Department,
        };
    }

    /** Nivel de sus hijos: null para una ciudad (último nivel). */
    public function childLevel(): ?self
    {
        return match ($this) {
            self::Country => self::Department,
            self::Department => self::City,
            self::City => null,
        };
    }
}
