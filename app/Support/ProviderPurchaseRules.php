<?php

declare(strict_types=1);

namespace App\Support;

use App\Enums\ProviderFormStatus;
use App\Enums\ProviderLinkageStatus;
use App\Enums\ProviderPurchaseStatus;
use App\Models\ProviderLinkage;
use App\Settings\CompanySettings;
use Carbon\CarbonInterface;
use RuntimeException;

/**
 * Regla de compra a proveedores frente al formato FO-300-01 (punto único para las órdenes de compra).
 *
 * - Tope: múltiplo (2) del SMMLV del año de la orden, configurado en Datos de la empresa.
 * - Se compara el total de la orden con IVA y el acumulado del mes calendario del mismo proceso
 *   (órdenes aprobadas y en revisión) más la orden nueva. Lo calcula quien llama, porque las
 *   órdenes viven en el módulo de cada proceso.
 * - Sin formato vigente y por encima del tope: la orden se permite con alerta y la vinculación
 *   queda marcada "FO-300-01 requerido".
 * - Formato vencido (actualización anual) o vinculación no activa: no se permiten órdenes.
 * - Un proveedor con formato vigente compra sin tope.
 */
final class ProviderPurchaseRules
{
    public function __construct(private readonly CompanySettings $settings) {}

    /** Valor desde el cual se exige el FO-300-01 en la fecha indicada. */
    public function threshold(CarbonInterface $date): int
    {
        $wage = $this->settings->minimumWage($date->year)
            ?? throw new RuntimeException("No está configurado el SMMLV de {$date->year} en Datos de la empresa.");

        return $wage * $this->settings->provider_form_wage_multiplier;
    }

    /**
     * Evalúa una orden de compra del proceso de la vinculación.
     *
     * @param  float  $orderTotal  Total de la orden con IVA.
     * @param  float  $monthAccumulated  Total con IVA de las demás órdenes del proveedor en el mes
     *                                   calendario de la orden, en el mismo proceso (aprobadas y en revisión).
     */
    public function evaluate(ProviderLinkage $linkage, float $orderTotal, float $monthAccumulated, CarbonInterface $date): ProviderPurchaseCheck
    {
        $provider = $linkage->loadMissing('provider.openVersion')->provider;

        if ($linkage->status !== ProviderLinkageStatus::Active) {
            return new ProviderPurchaseCheck(ProviderPurchaseStatus::Blocked, "La vinculación del proveedor está {$linkage->status->label()}.");
        }

        $formStatus = $provider->formStatus();

        if ($formStatus === ProviderFormStatus::Expired) {
            return new ProviderPurchaseCheck(ProviderPurchaseStatus::Blocked,
                'El FO-300-01 del proveedor venció el '.$provider->next_update_on?->format('d/m/Y').': no se pueden hacer órdenes hasta que lo actualice.');
        }

        if ($formStatus === ProviderFormStatus::Current) {
            return new ProviderPurchaseCheck(ProviderPurchaseStatus::Allowed);
        }

        $threshold = $this->threshold($date);
        $month = $monthAccumulated + $orderTotal;

        if ($orderTotal <= $threshold && $month <= $threshold) {
            return new ProviderPurchaseCheck(ProviderPurchaseStatus::Allowed);
        }

        $reason = $orderTotal > $threshold
            ? 'Orden de $'.number_format($orderTotal, 0, ',', '.')
            : 'Acumulado de '.$date->translatedFormat('F Y').' de $'.number_format($month, 0, ',', '.');

        return new ProviderPurchaseCheck(ProviderPurchaseStatus::FormRequired,
            "{$reason} supera el tope de $".number_format($threshold, 0, ',', '.').': el proveedor debe diligenciar el FO-300-01.', $reason);
    }

    /** Marca la vinculación como "FO-300-01 requerido" (la primera vez; conserva la fecha original). */
    public function markFormRequired(ProviderLinkage $linkage, string $reason): void
    {
        if ($linkage->form_required_at !== null) {
            return;
        }

        $linkage->update(['form_required_at' => now(), 'form_required_reason' => mb_substr($reason, 0, 255)]);
    }
}
