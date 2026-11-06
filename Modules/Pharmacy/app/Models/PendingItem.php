<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Models;

use App\Models\ServicePoint;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Pharmacy\App\Enums\PendingItemStatus;
use Modules\Pharmacy\App\Enums\PendingStatus;
use Modules\Pharmacy\Database\Factories\PendingItemFactory;
use Modules\Shopping\App\Models\Product;

/**
 * Ítem (detalle) de un pendiente de farmacia.
 *
 * Guarda un snapshot del producto para conservar el histórico aunque el catálogo cambie.
 * El destino se guarda como código de sede (no FK) para que la importación por archivo
 * use el código conocido por los usuarios.
 *
 * Excepción a la regla de modelos: no lleva SoftDeletes ni Userstamps (no hay auditoría
 * de ítems); eliminar un ítem lo borra definitivamente.
 *
 * @property int $id
 * @property int $pending_id
 * @property int $product_id
 * @property string $product_name
 * @property string $generic_code
 * @property string $generic_name
 * @property string|null $brand
 * @property string $destination_code
 * @property int $quantity
 * @property int|null $send_quantity
 * @property CarbonImmutable|null $date_send
 * @property string|null $doc_number
 * @property int|null $number_day
 * @property string|null $circular_number
 * @property string|null $observations
 * @property PendingItemStatus $status
 * @property CarbonImmutable $created_at
 * @property-read Pending|null $pending
 * @property-read Product|null $product
 * @property-read ServicePoint|null $destination
 */
final class PendingItem extends Model
{
    /** @use HasFactory<PendingItemFactory> */
    use HasFactory;

    protected $table = 'pharmacy_pending_items';

    protected $fillable = [
        'pending_id', 'product_id', 'product_name', 'generic_code', 'generic_name', 'brand',
        'destination_code', 'quantity', 'send_quantity', 'date_send', 'doc_number', 'number_day',
        'circular_number', 'observations', 'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'send_quantity' => 'integer',
            'number_day' => 'integer',
            'date_send' => 'date',
            'status' => PendingItemStatus::class,
        ];
    }

    protected static function newFactory(): PendingItemFactory
    {
        return PendingItemFactory::new();
    }

    /**
     * @return BelongsTo<Pending, $this>
     */
    public function pending(): BelongsTo
    {
        return $this->belongsTo(Pending::class);
    }

    /**
     * Producto del catálogo; incluye eliminados para no perder el histórico.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /**
     * Sede destino resuelta por código (relación sin FK en la base de datos).
     *
     * @return BelongsTo<ServicePoint, $this>
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(ServicePoint::class, 'destination_code', 'code')->withTrashed();
    }

    /** Agotado es un estado definitivo: el ítem ya no se edita. */
    public function isOutOfStock(): bool
    {
        return $this->status === PendingItemStatus::OutOfStock;
    }

    /**
     * Ítems que Oficina Principal puede asignar: Sin revisión, Pendiente o Parcial
     * de pendientes Terminados (los Agotados y Cumplidos no se distribuyen).
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeAssignable(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(fn (PendingItemStatus $status) => $status->value, PendingItemStatus::assignable()))
            ->whereHas('pending', fn (Builder $pending) => $pending->where('status', PendingStatus::Finished));
    }

    /** Mismo criterio de scopeAssignable() para un ítem ya cargado (requiere la relación pending). */
    public function isAssignable(): bool
    {
        return in_array($this->status, PendingItemStatus::assignable(), true)
            && $this->pending?->status === PendingStatus::Finished;
    }
}
