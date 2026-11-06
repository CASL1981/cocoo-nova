<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithEvents;
use Maatwebsite\Excel\Concerns\WithTitle;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * Excel del consolidado de pendientes con el formato del informe de referencia
 * (doc/consolidado_pendientes.xlsx): título "CANTIDAD" combinado sobre las sedes,
 * encabezados por abreviatura de sede y columna TOTALES.
 *
 * Excepción a "nunca una clase Export por modelo": es un reporte con diseño propio
 * (celdas combinadas y colores) que GenericExport no cubre.
 */
final class PendingConsolidationExport implements FromArray, ShouldAutoSize, WithEvents, WithTitle
{
    private const HEADER_FILL = '227ACB';

    private const SERVICE_POINT_FILL = '92D050';

    /**
     * @param  array{columns: array<int|string, string>, rows: list<array{product: string, quantities: array<int|string, int>, total: int}>}  $report
     * @param  string  $title  Nombre de la hoja (el libro del consolidado agrega la hoja de productos nuevos).
     */
    public function __construct(private readonly array $report, private readonly string $title = 'INFORME') {}

    /** @return list<list<int|string|null>> */
    public function array(): array
    {
        $columns = $this->report['columns'];

        $data = [
            [null, 'CANTIDAD', ...array_fill(0, count($columns), null)],
            ['PRODUCTO', ...array_values($columns), 'TOTALES'],
        ];

        foreach ($this->report['rows'] as $row) {
            $data[] = [$row['product'], ...array_values($row['quantities']), $row['total']];
        }

        return $data;
    }

    public function title(): string
    {
        return $this->title;
    }

    /** @return array<class-string, callable> */
    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event): void {
                $sheet = $event->sheet->getDelegate();
                $count = count($this->report['columns']);
                $lastColumn = Coordinate::stringFromColumnIndex($count + 2);
                $lastServicePoint = Coordinate::stringFromColumnIndex($count + 1);

                if ($count > 0) {
                    $sheet->mergeCells("B1:{$lastServicePoint}1");
                    $sheet->getStyle("B2:{$lastServicePoint}2")->getFill()
                        ->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::SERVICE_POINT_FILL);
                }

                foreach (["A1:{$lastColumn}1", 'A2', "{$lastColumn}2"] as $range) {
                    $style = $sheet->getStyle($range);
                    $style->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setRGB(self::HEADER_FILL);
                    $style->getFont()->getColor()->setRGB('FFFFFF');
                }

                $sheet->getStyle("A1:{$lastColumn}2")->getFont()->setBold(true);
                $sheet->getStyle("A1:{$lastColumn}2")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A3:A'.$sheet->getHighestRow())->getFont()->setBold(true);
                $sheet->getStyle($lastColumn.'3:'.$lastColumn.$sheet->getHighestRow())->getFont()->setBold(true);
                $sheet->freezePane('B3');
            },
        ];
    }
}
