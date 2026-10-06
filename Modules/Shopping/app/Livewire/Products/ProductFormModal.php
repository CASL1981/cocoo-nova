<?php

namespace Modules\Shopping\App\Livewire\Products;

use App\Livewire\Concerns\ManagesModal;
use App\Livewire\Concerns\Notifies;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Livewire\Attributes\On;
use Livewire\Component;
use Modules\Shopping\App\Actions\SaveProduct;
use Modules\Shopping\App\Livewire\Forms\ProductForm;
use Modules\Shopping\App\Models\Product;

class ProductFormModal extends Component
{
    use ManagesModal, Notifies;

    public ProductForm $form;

    #[On('open-product-create')]
    public function openCreate(): void
    {
        Gate::authorize('create', Product::class);
        $this->openModal();
    }

    #[On('open-product-edit')]
    public function openEdit(int $productId): void
    {
        $product = Product::findOrFail($productId);
        Gate::authorize('update', $product);

        $this->openModal();
        $this->form->setProduct($product);
    }

    protected function resetModalState(): void
    {
        $this->form->reset();
        $this->resetErrorBag();
    }

    public function save(SaveProduct $saveProduct): void
    {
        $product = $this->form->product;
        $product ? Gate::authorize('update', $product) : Gate::authorize('create', Product::class);

        try {
            $saveProduct->execute($this->form->payload(), $product);
        } catch (QueryException $e) {
            Log::error('products.save_failed', ['id' => $product?->getKey(), 'error' => $e->getMessage()]);
            $this->toastError('No se pudo guardar el producto. Inténtalo de nuevo.');
            return;
        }

        $this->dispatch('product-saved');
        $this->toastSuccess($product ? 'Producto actualizado' : 'Producto creado exitosamente');
        $this->closeModal();
    }

    public function render()
    {
        return view('shopping::livewire.products.product-form-modal');
    }
}
