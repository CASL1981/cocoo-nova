<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Models;

use App\Models\ServicePoint;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use Mattiverse\Userstamps\Traits\Userstamps;
use Modules\Pharmacy\App\Enums\NewProductStatus;
use Modules\Pharmacy\Database\Factories\NewProductFactory;
use Modules\Shopping\App\Models\Product;

/**
 * Solicitud de un producto nuevo: nunca ha existido en el inventario y no tiene codificación.
 *
 * Proceso independiente de los pendientes: la farmacia registra nombre, marca (opcional),
 * cantidad y su C.C.; Oficina Principal asocia el ítem cuando Compras lo codifica, asigna
 * unidades manualmente (sin tocar el saldo del ERP) y el número TB.
 * Una vez asociado, el nombre ya no se puede solicitar como producto nuevo.
 *
 * @property int $id
 * @property string $product_name
 * @property string $normalized_name
 * @property string|null $brand
 * @property int $quantity
 * @property string $destination_code
 * @property int $user_id
 * @property int|null $product_id
 * @property int|null $send_quantity
 * @property CarbonImmutable|null $date_send
 * @property string|null $doc_number
 * @property string|null $observations
 * @property NewProductStatus $status
 * @property CarbonImmutable $created_at
 * @property-read Product|null $product
 * @property-read ServicePoint|null $destination
 * @property-read User|null $user
 */
final class NewProduct extends Model
{
    /** @use HasFactory<NewProductFactory> */
    use HasFactory, SoftDeletes, Userstamps;

    protected $table = 'pharmacy_new_products';

    protected $fillable = [
        'product_name', 'brand', 'quantity', 'destination_code', 'user_id', 'product_id',
        'send_quantity', 'date_send', 'doc_number', 'observations', 'status',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'send_quantity' => 'integer',
            'date_send' => 'date',
            'status' => NewProductStatus::class,
        ];
    }

    protected static function newFactory(): NewProductFactory
    {
        return NewProductFactory::new();
    }

    /** El nombre normalizado se mantiene siempre sincronizado con el nombre escrito. */
    protected static function booted(): void
    {
        self::saving(function (self $newProduct): void {
            $newProduct->normalized_name = self::normalizeName($newProduct->product_name);
        });
    }

    /**
     * Forma comparable de un nombre: sin tildes, en mayúsculas y con un solo espacio
     * entre palabras ("Acetaminofén  500mg" → "ACETAMINOFEN 500MG").
     */
    public static function normalizeName(string $name): string
    {
        return Str::of($name)->ascii()->upper()->squish()->toString();
    }

    /**
     * Ítem del catálogo asociado; incluye eliminados para no perder el histórico.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class)->withTrashed();
    }

    /**
     * Farmacia solicitante resuelta por código (relación sin FK en la base de datos).
     *
     * @return BelongsTo<ServicePoint, $this>
     */
    public function destination(): BelongsTo
    {
        return $this->belongsTo(ServicePoint::class, 'destination_code', 'code')->withTrashed();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class)->withTrashed();
    }

    /**
     * Solicitudes Sin asignar o Parciales.
     *
     * @param  Builder<self>  $query
     * @return Builder<self>
     */
    public function scopeOpen(Builder $query): Builder
    {
        return $query->whereIn('status', array_map(fn (NewProductStatus $status) => $status->value, NewProductStatus::open()));
    }

    public function isOpen(): bool
    {
        return in_array($this->status, NewProductStatus::open(), true);
    }

    /** La farmacia solo edita o elimina mientras Oficina Principal no la haya gestionado. */
    public function isEditable(): bool
    {
        return ! $this->trashed() && $this->status === NewProductStatus::Unassigned && $this->product_id === null;
    }
}
