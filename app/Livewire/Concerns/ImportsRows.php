<?php

namespace App\Livewire\Concerns;

use App\Actions\Imports\ImportRows;
use App\Contracts\RowImporter;
use App\Exports\ImportTemplateExport;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Livewire\WithFileUploads;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

/**
 * Importación masiva desde Excel/CSV para un listado (modal <x-table.import>).
 *
 * El componente define qué se importa con importer() y cómo refrescar el
 * listado con afterImport(). Requiere el permiso {modulo}-import (habilidad
 * "import" de la Policy). Requiere además el trait Notifies.
 */
trait ImportsRows
{
    use WithFileUploads;

    /** Visibilidad del modal de importación. */
    public bool $showImport = false;

    /** Archivo cargado temporalmente por Livewire. */
    public ?TemporaryUploadedFile $importFile = null;

    /** @var list<string> Errores por fila de la última importación fallida. */
    public array $importErrors = [];

    abstract protected function importer(): RowImporter;

    /** Refresca los datos del listado tras una importación exitosa. */
    abstract protected function afterImport(): void;

    public function openImport(): void
    {
        $this->authorizeImport();

        $this->resetImportState();
        $this->showImport = true;
    }

    public function closeImport(): void
    {
        $this->showImport = false;
        $this->resetImportState();
    }

    public function import(): void
    {
        $this->authorizeImport();
        $importer = $this->importer();

        $this->importErrors = [];
        $this->validate(
            ['importFile' => ['required', 'file', 'mimes:xlsx,csv,txt', 'max:10240']],
            [
                'importFile.required' => 'Seleccione un archivo.',
                'importFile.mimes' => 'El archivo debe ser Excel (.xlsx) o CSV.',
                'importFile.max' => 'El archivo no debe superar 10 MB.',
            ],
        );

        /** @var TemporaryUploadedFile $file */
        $file = $this->importFile;
        $result = app(ImportRows::class)->execute($importer, $file);

        if ($result->failed()) {
            $this->importErrors = $result->errors;
            $this->toastError('No se importó ningún registro: corrija los errores del archivo.');

            return;
        }

        $this->closeImport();
        $this->afterImport();
        $this->toastSuccess("Importación completada: {$result->created} creado(s), {$result->updated} actualizado(s).");
    }

    public function downloadImportTemplate(): BinaryFileResponse
    {
        $this->authorizeImport();
        $importer = $this->importer();

        $name = Str::kebab(class_basename($importer->modelClass()));

        return Excel::download(new ImportTemplateExport(array_keys($importer->columns())), "plantilla-{$name}.xlsx");
    }

    /**
     * Habilidad "import" de la Policy del modelo importado. Se sobrescribe cuando la
     * importación depende de otro registro (ej. ítems del pendiente abierto).
     */
    protected function authorizeImport(): void
    {
        Gate::authorize('import', $this->importer()->modelClass());
    }

    private function resetImportState(): void
    {
        $this->reset('importFile', 'importErrors');
        $this->resetValidation('importFile');
    }
}
