<?php

declare(strict_types=1);

namespace App\Enums;

use App\Enums\Concerns\HasLabelOptions;

/**
 * Anexos del formato FO-300-01, más el formulario firmado escaneado.
 */
enum ProviderDocumentType: string
{
    use HasLabelOptions;

    case IdentityDocument = 'identity_document';
    case ChamberOfCommerce = 'chamber_of_commerce';
    case Rut = 'rut';
    case ReturnPolicy = 'return_policy';
    case SignedForm = 'signed_form';
    case Other = 'other';

    /** Vigencia máxima del certificado de existencia y representación legal, en meses. */
    public const CHAMBER_OF_COMMERCE_VALIDITY_MONTHS = 3;

    public function label(): string
    {
        return match ($this) {
            self::IdentityDocument => 'Documento de identificación',
            self::ChamberOfCommerce => 'Certificado de existencia y representación legal',
            self::Rut => 'Registro único tributario (RUT)',
            self::ReturnPolicy => 'Políticas de devolución',
            self::SignedForm => 'Formulario FO-300-01 firmado',
            self::Other => 'Otro',
        };
    }

    /** El documento exige fecha de expedición para controlar su vigencia. */
    public function requiresIssueDate(): bool
    {
        return $this === self::ChamberOfCommerce;
    }
}
