<?php

namespace App\Imports;

use Illuminate\Http\UploadedFile;
use Maatwebsite\Excel\Concerns\Import;
use Maatwebsite\Excel\Concerns\ToArray;
use Maatwebsite\Excel\Concerns\WithCalculatedFormulas;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Lee una hoja completa como arreglo asociativo usando la primera fila como encabezado.
 *
 * El formateador de encabezados por defecto (slug) normaliza "ATC" → "atc".
 * No omite filas vacías para que el índice siga correspondiendo al número
 * de fila del archivo en los mensajes de error.
 */
final class SheetRowsImport implements ToArray, WithCalculatedFormulas, WithHeadingRow
{
    /** @var list<array<string|int, mixed>> */
    public array $rows = [];

    /**
     * @param  array<int, array<string|int, mixed>>  $array
     */
    public function array(array $array): void
    {
        $this->rows = array_values($array);
    }

    /**
     * Lee una sola hoja del archivo (Excel o CSV).
     *
     * @param  string|null  $sheet  Nombre de la hoja; null toma la primera.
     * @return list<array<string|int, mixed>>
     */
    public static function read(string|UploadedFile $file, ?string $sheet = null): array
    {
        // Las rutas absolutas (seeders) se envuelven para no depender de un disco de Storage.
        if (is_string($file)) {
            $file = new UploadedFile($file, basename($file), null, null, true);
        }

        $rows = new self;

        // Sin WithMultipleSheets el paquete aplica la importación a todas las hojas.
        Excel::import(new class($sheet ?? 0, $rows) implements Import, WithMultipleSheets
        {
            public function __construct(private readonly string|int $sheet, private readonly SheetRowsImport $rows) {}

            /**
             * @return array<string|int, SheetRowsImport>
             */
            public function sheets(): array
            {
                return [$this->sheet => $this->rows];
            }
        }, $file);

        return $rows->rows;
    }
}
