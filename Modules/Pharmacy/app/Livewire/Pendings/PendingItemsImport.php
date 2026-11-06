<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Livewire\Pendings;

use App\Contracts\RowImporter;
use App\Livewire\Concerns\ImportsRows;
use App\Livewire\Concerns\Notifies;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Modules\Pharmacy\App\Imports\PendingItemImporter;
use Modules\Pharmacy\App\Models\Pending;

/**
 * Botón + modal del listado de pendientes para importar ítems a varios pendientes
 * activos desde un solo archivo (columna idpendiente por fila).
 *
 * Exclusivo de Oficina Principal: exige pendings-import y que el usuario trabaje en
 * la sede de Oficina Principal; cada pendiente del archivo se valida además con
 * PendingItemPolicy::create (activo y permiso de editar).
 */
final class PendingItemsImport extends Component
{
    use ImportsRows, Notifies;

    /** El botón solo se muestra a usuarios de Oficina Principal. */
    public function worksAtHeadquarters(): bool
    {
        return $this->actor()->worksAtHeadquarters();
    }

    protected function importer(): RowImporter
    {
        return new PendingItemImporter(null, $this->actor());
    }

    protected function authorizeImport(): void
    {
        Gate::authorize('import', Pending::class);
        abort_unless($this->worksAtHeadquarters(), 403, 'Solo Oficina Principal importa ítems a varios pendientes.');
    }

    /** Los ítems no se muestran en el listado de encabezados: basta con notificar. */
    protected function afterImport(): void {}

    private function actor(): User
    {
        /** @var User $user */
        $user = Auth::user();

        return $user;
    }

    public function render(): View
    {
        return view('pharmacy::livewire.pendings.pending-items-import');
    }
}
