<?php

declare(strict_types=1);

namespace App\Actions\Providers;

use App\Enums\ProviderLinkageStatus;
use App\Enums\ProviderScope;
use App\Models\Provider;
use App\Models\ProviderLinkage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Punto único de escritura del proveedor básico (sin FO-300-01).
 *
 * Registrar: crea el tercero y su vinculación activa con el proceso, sin aprobación (compras
 * menores a 2 SMMLV). Editar: actualiza los datos básicos y las condiciones de pago del proceso;
 * la identificación no cambia si el proveedor ya está en uso o tiene formato aprobado.
 * Solo aplica a procesos que no exigen el formato a todos sus proveedores (Administrativo).
 */
final class SaveProviderBasic
{
    /**
     * @param  array<string, mixed>  $data  Campos básicos del proveedor ya validados.
     * @param  array{payment_condition: ?string, payment_term: ?string}  $terms
     */
    public function execute(array $data, array $terms, ProviderScope $scope, ?ProviderLinkage $linkage = null): ProviderLinkage
    {
        if ($scope->requiresForm()) {
            throw ValidationException::withMessages(['name' => "Los proveedores {$scope->label()}s diligencian siempre el FO-300-01."]);
        }

        return DB::transaction(function () use ($data, $terms, $scope, $linkage): ProviderLinkage {
            if ($linkage === null) {
                $provider = Provider::create($data);

                return ProviderLinkage::create([
                    ...$terms,
                    'provider_id' => $provider->id,
                    'scope' => $scope,
                    'status' => ProviderLinkageStatus::Active,
                    'approved_at' => now(),
                ]);
            }

            $provider = Provider::query()->lockForUpdate()->findOrFail($linkage->provider_id);

            // Con formato aprobado los datos se actualizan por el FO-300-01, no por el registro básico.
            if ($provider->current_version_id !== null) {
                throw ValidationException::withMessages(['name' => 'El proveedor tiene FO-300-01: sus datos se actualizan con una nueva versión del formato.']);
            }

            if ($provider->isIdentityLocked()) {
                $data = array_merge($data, $provider->only(['person_type', 'document_type', 'identification']));
            }

            $provider->update($data);
            $linkage->update($terms);

            return $linkage;
        });
    }
}
