<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;

/**
 * Plantilla vacía de importación: solo la fila de encabezados que espera un RowImporter.
 */
final class ImportTemplateExport implements FromArray, ShouldAutoSize, WithHeadings
{
    /**
     * @param  list<string>  $headings
     */
    public function __construct(private readonly array $headings) {}

    /**
     * @return array<int, array<int, mixed>>
     */
    public function array(): array
    {
        return [];
    }

    /**
     * @return list<string>
     */
    public function headings(): array
    {
        return $this->headings;
    }
}
