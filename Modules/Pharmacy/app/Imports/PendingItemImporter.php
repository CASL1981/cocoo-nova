<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Imports;

use App\Contracts\RowImporter;
use App\Enums\ServicePointStatus;
use App\Models\ServicePoint;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Validation\DataAwareRule;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Modules\Pharmacy\App\Actions\SavePendingItem;
use Modules\Pharmacy\App\Models\Pending;
use Modules\Pharmacy\App\Models\PendingItem;
use Modules\Shopping\App\Enums\ProductStatus;
use Modules\Shopping\App\Models\Product;

/**
 * Importación de ítems de pendientes desde Excel/CSV.
 *
 * Formato del archivo base (doc/detallepedido.xlsx): item, idpendiente,
 * nombreproducto, marca, centrocosto, cantidad.
 *
 * Dos modos:
 * - Pendiente fijo (pantalla de detalle): idpendiente debe ser el pendiente abierto.
 * - Varios pendientes (listado, solo Oficina Principal): cada fila va al pendiente de
 *   su idpendiente, que debe existir, estar activo y permitir agregar ítems al usuario.
 *
 * - Solo crea: cada fila es un ítem nuevo guardado con SavePendingItem
 *   (snapshot del producto y Agotado automático si su genérico tiene circular activa).
 * - item es el ítem del catálogo (shopping_products.item, producto activo);
 *   nombreproducto es informativo y no se guarda.
 * - Un genérico no se repite en un pendiente para el mismo C.C., ni contra los ítems
 *   ya registrados ni entre filas del archivo.
 */
final class PendingItemImporter implements RowImporter
{
    /** @var array<string, Product|null> Productos activos por ítem, consultados una vez por valor. */
    private array $products = [];

    /** @var array<string, Pending|null> Pendientes por número; null si no existe o no se le pueden agregar ítems. */
    private array $pendings = [];

    /** @var array<int, array<string, true>> Genérico + C.C. ya registrados, por pendiente. */
    private array $registered = [];

    /** @var array<string, true> Pendiente + genérico + C.C. de las filas del archivo ya validadas. */
    private array $seenInFile = [];

    /**
     * @param  Pending|null  $pending  Pendiente abierto; null importa a varios pendientes (Oficina Principal).
     */
    public function __construct(
        private readonly ?Pending $pending,
        private readonly User $actor,
        private readonly SavePendingItem $savePendingItem = new SavePendingItem,
    ) {}

    public function modelClass(): string
    {
        return PendingItem::class;
    }

    public function columns(): array
    {
        return [
            'item' => 'Ítem',
            'idpendiente' => 'N° pendiente',
            'nombreproducto' => 'Nombre del producto',
            'marca' => 'Marca',
            'centrocosto' => 'C.C.',
            'cantidad' => 'Cantidad',
        ];
    }

    public function key(): ?string
    {
        return null;
    }

    public function rules(): array
    {
        // Las reglas se arman una vez por importación: se reinicia el control de repetidos del archivo.
        $this->seenInFile = [];

        return [
            'item' => ['required', 'string', function (string $attribute, mixed $value, Closure $fail): void {
                if ($this->product((string) $value) === null) {
                    $fail('El ítem no existe en el catálogo o no está activo.');
                }
            }],
            'idpendiente' => ['required', function (string $attribute, mixed $value, Closure $fail): void {
                if ($this->pending !== null && (string) $value !== (string) $this->pending->id) {
                    $fail("El N° pendiente debe ser {$this->pending->id} (el pendiente abierto).");
                } elseif ($this->pending === null && $this->targetPending((string) $value) === null) {
                    $fail("El pendiente {$value} no existe, no está activo o no tiene permiso para agregarle ítems.");
                }
            }],
            'nombreproducto' => ['nullable', 'string', 'max:255'],
            'marca' => ['nullable', 'string', 'max:255'],
            'centrocosto' => [
                'required', 'string', 'max:20',
                Rule::exists(ServicePoint::class, 'code')->whereNull('deleted_at')->where('status', ServicePointStatus::Active->value),
                $this->rowRule(fn (array $row, Closure $fail) => $this->checkDestinationScope($row, $fail)),
                $this->rowRule(fn (array $row, Closure $fail) => $this->checkUniqueGeneric($row, $fail)),
            ],
            'cantidad' => ['required', 'integer', 'min:1', 'max:9999999'],
        ];
    }

    public function prepare(array $row): array
    {
        return $row;
    }

    public function save(array $data, ?Model $existing): void
    {
        $product = $this->product((string) $data['item']);
        /** @var Pending $pending Validado en rules(). */
        $pending = $this->pending ?? $this->targetPending((string) $data['idpendiente']);

        $this->savePendingItem->execute([
            'product_id' => $product?->id,
            'brand' => $data['marca'] ?? null,
            'quantity' => (int) $data['cantidad'],
            'destination_code' => $data['centrocosto'],
        ], $pending);
    }

    private function product(string $item): ?Product
    {
        if (! array_key_exists($item, $this->products)) {
            $this->products[$item] = Product::query()
                ->where('item', $item)
                ->where('status', ProductStatus::Active)
                ->first(['id', 'item', 'generic_name', 'generic_code']);
        }

        return $this->products[$item];
    }

    /**
     * Pendiente de la fila: el abierto o, en modo varios pendientes, el del número indicado
     * si el usuario puede agregarle ítems (activo, permiso y sede; PendingItemPolicy::create).
     */
    private function targetPending(string $id): ?Pending
    {
        if ($this->pending !== null) {
            return $this->pending;
        }

        if (! array_key_exists($id, $this->pendings)) {
            $pending = ctype_digit($id) ? Pending::query()->with('destination:id,code')->find((int) $id) : null;

            $this->pendings[$id] = $pending !== null && Gate::forUser($this->actor)->allows('create', [PendingItem::class, $pending])
                ? $pending
                : null;
        }

        return $this->pendings[$id];
    }

    /**
     * Regla con acceso a la fila completa (necesita el ítem y el pendiente de la fila).
     * Laravel entrega la fila con setData() antes de validar cada una.
     *
     * @param  Closure(array<string, mixed>, Closure): void  $check
     */
    private function rowRule(Closure $check): ValidationRule
    {
        return new class($check) implements DataAwareRule, ValidationRule
        {
            /** @var array<string, mixed> */
            private array $data = [];

            public function __construct(private readonly Closure $check) {}

            /** @param  array<string, mixed>  $data */
            public function setData(array $data): static
            {
                $this->data = $data;

                return $this;
            }

            public function validate(string $attribute, mixed $value, Closure $fail): void
            {
                ($this->check)($this->data, $fail);
            }
        };
    }

    /**
     * Oficina Principal registra cualquier sede; el resto, solo el destino del pendiente.
     *
     * @param  array<string, mixed>  $row
     */
    private function checkDestinationScope(array $row, Closure $fail): void
    {
        $pending = $this->targetPending((string) ($row['idpendiente'] ?? ''));

        if ($pending === null || $this->actor->worksAtHeadquarters()) {
            return;
        }

        if ((string) $row['centrocosto'] !== (string) $pending->loadMissing('destination')->destination?->code) {
            $fail('Solo Oficina Principal puede registrar un C.C. distinto al del pendiente.');
        }
    }

    /** @param  array<string, mixed>  $row */
    private function checkUniqueGeneric(array $row, Closure $fail): void
    {
        $product = $this->product((string) ($row['item'] ?? ''));
        $pending = $this->targetPending((string) ($row['idpendiente'] ?? ''));

        if ($product === null || $pending === null) {
            return;
        }

        $key = $this->genericKey($product->generic_name, $product->generic_code, (string) $row['centrocosto']);
        $fileKey = "{$pending->id}|{$key}";

        if (isset($this->registeredGenerics($pending)[$key])) {
            $fail("El producto ya está registrado en el pendiente {$pending->id} para el mismo C.C.");
        } elseif (isset($this->seenInFile[$fileKey])) {
            $fail("El producto está repetido en otra fila del archivo para el pendiente {$pending->id} y el mismo C.C.");
        } else {
            $this->seenInFile[$fileKey] = true;
        }
    }

    /** @return array<string, true> */
    private function registeredGenerics(Pending $pending): array
    {
        return $this->registered[$pending->id] ??= $pending->items()
            ->get(['generic_name', 'generic_code', 'destination_code'])
            ->mapWithKeys(fn (PendingItem $item) => [$this->genericKey($item->generic_name, $item->generic_code, $item->destination_code) => true])
            ->all();
    }

    private function genericKey(?string $name, ?string $code, string $destination): string
    {
        return mb_strtolower("{$name}|{$code}|{$destination}");
    }
}
