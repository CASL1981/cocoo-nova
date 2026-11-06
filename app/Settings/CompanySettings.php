<?php

declare(strict_types=1);

namespace App\Settings;

use Spatie\LaravelSettings\Settings;

/**
 * Datos de la empresa usados por formatos, PDF y correos (ej. orden de compra FO-400-28).
 *
 * Se editan en Configuración → Empresa; nunca se escriben fijos en plantillas.
 * Se obtienen por inyección o con app(CompanySettings::class).
 */
final class CompanySettings extends Settings
{
    public string $legal_name;

    // NIT tal como debe aparecer en los documentos (puede incluir el dígito de verificación).
    public string $nit;

    public ?string $address;

    public ?string $city;

    public ?string $phone;

    public ?string $email;

    public ?string $website;

    // Ruta del logo en el disco public; null si no se ha cargado.
    public ?string $logo_path;

    // Usuario oficial de cumplimiento: revisa a los proveedores PEP antes de que Gerencia los apruebe.
    public ?int $compliance_officer_id;

    /**
     * Salario mínimo mensual legal vigente por año (ej. ['2026' => 1750905]).
     *
     * @var array<int|string, int>
     */
    public array $minimum_wages;

    // Múltiplo del SMMLV desde el cual una orden (o el acumulado mensual) exige el FO-300-01.
    public int $provider_form_wage_multiplier;

    public static function group(): string
    {
        return 'company';
    }

    /** SMMLV del año indicado, o null si aún no está configurado. */
    public function minimumWage(int $year): ?int
    {
        return $this->minimum_wages[$year] ?? null;
    }

    /** URL pública del logo, o null si no hay logo cargado. */
    public function logoUrl(): ?string
    {
        return $this->logo_path ? asset('storage/'.$this->logo_path) : null;
    }
}
