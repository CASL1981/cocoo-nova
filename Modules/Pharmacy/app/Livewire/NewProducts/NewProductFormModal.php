<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Livewire\NewProducts;

use App\Livewire\Concerns\ManagesModal;
use App\Livewire\Concerns\Notifies;
use App\Models\ServicePoint;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Computed;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Pharmacy\App\Actions\SaveNewProduct;
use Modules\Pharmacy\App\Livewire\Forms\NewProductForm;
use Modules\Pharmacy\App\Models\NewProduct;
use Modules\Shopping\App\Enums\ProductStatus;
use Modules\Shopping\App\Models\Product;

/**
 * Modal de creación y edición de solicitudes de productos nuevos.
 *
 * Mientras se escribe el nombre muestra coincidencias del catálogo y de productos nuevos
 * ya asociados (primera barrera contra duplicados); el bloqueo definitivo está en SaveNewProduct.
 */
final class NewProductFormModal extends Component
{
    use ManagesModal, Notifies;

    /** Máximo de coincidencias sugeridas por grupo. */
    private const SUGGESTIONS_LIMIT = 8;

    public NewProductForm $form;

    #[On('open-new-product-create')]
    public function openCreate(): void
    {
        Gate::authorize('create', NewProduct::class);
        $this->openModal();

        // Por defecto la farmacia es la sede del solicitante.
        $this->form->destination_code = (string) $this->actor()->loadMissing('servicePoint')->servicePoint?->code;
    }

    #[On('open-new-product-edit')]
    public function openEdit(int $newProductId): void
    {
        $newProduct = NewProduct::findOrFail($newProductId);
        Gate::authorize('update', $newProduct);

        // El modal se abre (y resetea) antes de poblar el formulario (lección #3).
        $this->openModal();
        $this->form->setNewProduct($newProduct);
    }

    protected function resetModalState(): void
    {
        $this->form->reset();
        $this->resetErrorBag();
    }

    /**
     * Farmacias no eliminadas como "código - nombre".
     * Solo Oficina Principal ve todas; el resto únicamente su propia sede.
     *
     * @return array<string, string>
     */
    #[Computed]
    public function destinationOptions(): array
    {
        $actor = $this->actor();

        return ServicePoint::query()
            ->unless($actor->worksAtHeadquarters(), fn (Builder $query) => $query->whereKey($actor->service_point_id))
            ->orderBy('code')->get(['code', 'name'])
            ->mapWithKeys(fn (ServicePoint $point) => [$point->code => "{$point->code} - {$point->name}"])
            ->all();
    }

    /**
     * Coincidencias del nombre escrito (desde 3 caracteres).
     * catalog: productos activos ya codificados; linked: productos nuevos ya asociados a un ítem.
     *
     * @return array{catalog: array<int, string>, linked: array<int, string>}
     */
    #[Computed]
    public function suggestions(): array
    {
        $term = NewProduct::normalizeName($this->form->product_name);

        if (mb_strlen($term) < 3) {
            return ['catalog' => [], 'linked' => []];
        }

        $catalog = Product::query()->where('status', ProductStatus::Active)
            ->where(fn (Builder $query) => $query->where('description', 'like', "%{$term}%")->orWhere('generic_name', 'like', "%{$term}%"))
            ->orderBy('description')->limit(self::SUGGESTIONS_LIMIT)->get(['item', 'description'])
            ->map(fn (Product $product) => "{$product->item} - {$product->description}")
            ->values()->all();

        $linked = NewProduct::query()->with('product:id,item')->whereNotNull('product_id')
            ->where('normalized_name', 'like', "%{$term}%")
            ->orderBy('product_name')->limit(self::SUGGESTIONS_LIMIT)->get(['id', 'product_name', 'product_id'])
            ->map(fn (NewProduct $newProduct) => "{$newProduct->product_name} → ítem {$newProduct->product?->item}")
            ->unique()->values()->all();

        return ['catalog' => $catalog, 'linked' => $linked];
    }

    public function save(SaveNewProduct $action): void
    {
        $newProduct = $this->form->newProduct;
        $newProduct ? Gate::authorize('update', $newProduct) : Gate::authorize('create', NewProduct::class);

        $payload = $this->form->payload();

        try {
            $action->execute($payload, $this->actor(), $newProduct);
        } catch (ValidationException $exception) {
            // Los errores de negocio se muestran en el campo correspondiente del formulario.
            foreach ($exception->errors() as $field => $messages) {
                $this->addError("form.{$field}", $messages[0]);
            }

            return;
        } catch (QueryException $e) {
            Log::error('new-products.save_failed', ['id' => $newProduct?->getKey(), 'error' => $e->getMessage()]);
            $this->toastError('No se pudo guardar la solicitud. Inténtalo de nuevo.');

            return;
        }

        $this->dispatch('new-product-saved');
        $this->toastSuccess($newProduct ? 'Solicitud actualizada' : 'Solicitud de producto nuevo registrada');
        $this->closeModal();
    }

    public function render(): View
    {
        return view('pharmacy::livewire.new-products.new-product-form-modal');
    }

    private function actor(): User
    {
        /** @var User */
        return Auth::user();
    }
}
