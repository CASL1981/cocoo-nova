<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Imports;

use App\Contracts\RowImporter;
use App\Models\CharacteristicOption;
use App\Models\ServicePoint;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Modules\Pharmacy\App\Actions\SavePending;
use Modules\Pharmacy\App\Enums\PendingType;
use Modules\Pharmacy\App\Livewire\Forms\PendingForm;
use Modules\Pharmacy\App\Models\Pending;

/**
 * Importación masiva de encabezados de pendientes desde la pantalla de Pendientes.
 *
 * Solo crea: cada fila es un pendiente nuevo, Activo y con el usuario que importa
 * como solicitante. El destino llega como código de sede y aplica la misma regla
 * del formulario (salvo Oficina Principal, solo la sede del usuario).
 */
final class PendingImporter implements RowImporter
{
    /** Columnas cuyo valor debe ser una opción activa de una característica. */
    private const CHARACTERISTIC_COLUMNS = [
        'category' => Pending::CATEGORY_CHARACTERISTIC_CODE,
        'eps' => Pending::EPS_CHARACTERISTIC_CODE,
        'contracting_modality' => Pending::CONTRACTING_MODALITY_CHARACTERISTIC_CODE,
        'invoicing_method' => Pending::INVOICING_METHOD_CHARACTERISTIC_CODE,
    ];

    /** @var array<string, int>|null Ids de sede por código, cargados al guardar la primera fila. */
    private ?array $destinationIds = null;

    public function __construct(
        private readonly User $actor,
        private readonly SavePending $savePending = new SavePending,
    ) {}

    public function modelClass(): string
    {
        return Pending::class;
    }

    public function columns(): array
    {
        return [
            'type' => 'Tipo',
            'category' => 'Categoría',
            'destination' => 'Destino (código de sede)',
            'reason' => 'Motivo',
            'duration' => 'Duración',
            'eps' => 'EPS',
            'contracting_modality' => 'Modalidad de contratación',
            'invoicing_method' => 'Forma de facturación',
            'manager' => 'Autoriza',
            'observations' => 'Observaciones',
        ];
    }

    public function key(): ?string
    {
        return null;
    }

    public function rules(): array
    {
        $rules = PendingForm::fieldRules($this->actor);
        unset($rules['destination_id']);

        // Mismas reglas del formulario, pero el destino se valida por código de sede.
        $rules['destination'] = [
            'required', 'string',
            Rule::exists(ServicePoint::class, 'code')->whereNull('deleted_at'),
            ...($this->actor->worksAtHeadquarters() ? [] : [Rule::in(array_filter([$this->actor->loadMissing('servicePoint')->servicePoint?->code]))]),
        ];

        return $rules;
    }

    /**
     * Acepta el tipo como código (PD), etiqueta (Pedido) o "PD - Pedido", y las
     * opciones de característica sin distinguir mayúsculas.
     */
    public function prepare(array $row): array
    {
        if (is_string($row['type'] ?? null)) {
            $row['type'] = $this->normalizeType($row['type']);
        }

        foreach (self::CHARACTERISTIC_COLUMNS as $column => $code) {
            if (is_string($row[$column] ?? null)) {
                $options = CharacteristicOption::forCharacteristicCode($code)->keys()->all();
                $byLower = array_combine(array_map('mb_strtolower', $options), $options);
                $row[$column] = $byLower[mb_strtolower($row[$column])] ?? $row[$column];
            }
        }

        return $row;
    }

    public function save(array $data, ?Model $existing): void
    {
        $this->destinationIds ??= ServicePoint::query()->pluck('id', 'code')->all();

        $data['destination_id'] = $this->destinationIds[$data['destination']];
        unset($data['destination']);

        $this->savePending->execute($data, $this->actor);
    }

    private function normalizeType(string $value): string
    {
        $value = mb_strtolower($value);

        foreach (PendingType::cases() as $type) {
            $accepted = [mb_strtolower($type->value), mb_strtolower($type->label()), mb_strtolower(PendingType::options()[$type->value])];

            if (in_array($value, $accepted, true)) {
                return $type->value;
            }
        }

        return $value;
    }
}
