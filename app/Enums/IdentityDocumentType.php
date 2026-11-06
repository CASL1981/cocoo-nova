<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabelOptions;

/**
 * Tipos de documento de identificación usados en el formato FO-300-01 (hoja "Criterios").
 */
enum IdentityDocumentType: string
{
    use HasLabelOptions;

    case CivilRegistry = 'RC';
    case CitizenshipCard = 'CC';
    case IdentityCard = 'TI';
    case ForeignerId = 'CE';
    case Passport = 'PAS';
    case Nit = 'NIT';
    case TemporaryUnion = 'UT';

    public function label(): string
    {
        return match ($this) {
            self::CivilRegistry => 'Registro civil (RC)',
            self::CitizenshipCard => 'Cédula de ciudadanía (CC)',
            self::IdentityCard => 'Tarjeta de identidad (TI)',
            self::ForeignerId => 'Cédula de extranjería (CE)',
            self::Passport => 'Pasaporte (PAS)',
            self::Nit => 'NIT',
            self::TemporaryUnion => 'Unión temporal',
        };
    }

    /**
     * Documentos de personas naturales (proveedor natural y familiares PEP).
     *
     * @return list<self>
     */
    public static function forPeople(): array
    {
        return [self::CivilRegistry, self::CitizenshipCard, self::IdentityCard, self::ForeignerId, self::Passport];
    }

    /**
     * Documentos del representante legal de una persona jurídica.
     *
     * @return list<self>
     */
    public static function forLegalRepresentative(): array
    {
        return [self::CitizenshipCard, self::ForeignerId, self::Passport];
    }

    /**
     * Documentos de accionistas y consorciados: personas naturales o jurídicas.
     *
     * @return list<self>
     */
    public static function forShareholders(): array
    {
        return [...self::forPeople(), self::Nit];
    }
}
