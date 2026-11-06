<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Livewire\Forms;

use App\Livewire\Concerns\SanitizesPayload;
use App\Models\CharacteristicOption;
use App\Models\ServicePoint;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\In;
use Livewire\Form;
use Modules\Pharmacy\App\Enums\PendingType;
use Modules\Pharmacy\App\Models\Pending;

/**
 * Formulario del encabezado de pendientes.
 * Solicitante y estado no se editan aquí: los asigna el servidor.
 */
final class PendingForm extends Form
{
    use SanitizesPayload;

    // Pendiente en edición; null cuando se crea uno nuevo.
    public ?Pending $pending = null;

    public string $type = '';

    public string $category = '';

    public string $destination_id = '';

    public ?string $reason = null;

    public ?string $duration = null;

    public ?string $observations = null;

    public ?string $eps = null;

    public ?string $contracting_modality = null;

    public ?string $invoicing_method = null;

    public ?string $manager = null;

    /**
     * Carga los datos de un pendiente existente en el formulario.
     */
    public function setPending(Pending $pending): void
    {
        $this->pending = $pending;
        $this->fill($pending->only([
            'category', 'reason', 'duration', 'observations', 'eps',
            'contracting_modality', 'invoicing_method', 'manager',
        ]));

        $this->type = $pending->type->value;
        $this->destination_id = (string) $pending->destination_id;
    }

    /** @return array<string, array<int, mixed>> */
    protected function rules(): array
    {
        /** @var User $user */
        $user = Auth::user();

        return self::fieldRules($user, $this->pending);
    }

    /**
     * Reglas de los campos, compartidas con la importación masiva (PendingImporter).
     *
     * @param  Pending|null  $pending  Pendiente en edición: acepta sus valores ya guardados.
     * @return array<string, array<int, mixed>>
     */
    public static function fieldRules(User $actor, ?Pending $pending = null): array
    {
        return [
            'type' => ['required', Rule::enum(PendingType::class)],
            'category' => ['required', 'string', self::characteristicRule(Pending::CATEGORY_CHARACTERISTIC_CODE, $pending?->category)],
            // Solo sedes no eliminadas y, salvo Oficina Principal, únicamente la sede del usuario.
            'destination_id' => [
                'required', 'integer',
                Rule::exists(ServicePoint::class, 'id')->whereNull('deleted_at'),
                ...self::destinationScopeRule($actor),
            ],
            'reason' => ['nullable', 'string', 'max:255'],
            'duration' => ['nullable', 'string', 'max:150'],
            'observations' => ['nullable', 'string', 'max:2000'],
            'eps' => ['nullable', 'string', self::characteristicRule(Pending::EPS_CHARACTERISTIC_CODE, $pending?->eps)],
            'contracting_modality' => ['nullable', 'string', self::characteristicRule(Pending::CONTRACTING_MODALITY_CHARACTERISTIC_CODE, $pending?->contracting_modality)],
            'invoicing_method' => ['nullable', 'string', self::characteristicRule(Pending::INVOICING_METHOD_CHARACTERISTIC_CODE, $pending?->invoicing_method)],
            'manager' => ['nullable', 'string', 'max:150'],
        ];
    }

    /** @return array<string, string> */
    protected function validationAttributes(): array
    {
        return [
            'type' => 'tipo',
            'category' => 'categoría',
            'destination_id' => 'destino',
            'reason' => 'motivo',
            'duration' => 'duración',
            'observations' => 'observaciones',
            'eps' => 'EPS',
            'contracting_modality' => 'modalidad de contratación',
            'invoicing_method' => 'forma de facturación',
            'manager' => 'autoriza',
        ];
    }

    /**
     * Datos validados y limpios listos para persistir.
     *
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->sanitize($this->validate());
    }

    /**
     * Oficina Principal puede elegir cualquier destino; el resto de usuarios solo su propia sede.
     *
     * @return array<int, In>
     */
    private static function destinationScopeRule(User $actor): array
    {
        return $actor->worksAtHeadquarters() ? [] : [Rule::in(array_filter([$actor->service_point_id]))];
    }

    /**
     * El valor debe ser una opción activa de la característica indicada.
     * Al editar también se acepta el valor ya guardado ($current), aunque su opción se haya
     * inactivado después, para no bloquear la edición de registros históricos.
     */
    private static function characteristicRule(string $code, ?string $current): In
    {
        $allowed = CharacteristicOption::forCharacteristicCode($code)->keys()->all();

        return Rule::in($current === null ? $allowed : [...$allowed, $current]);
    }
}
