<?php

declare(strict_types=1);

namespace Modules\Pharmacy\App\Imports;

use App\Contracts\RowImporter;
use App\Enums\Status;
use App\Models\CharacteristicOption;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\Rule;
use Modules\Pharmacy\App\Actions\SaveStockOut;
use Modules\Pharmacy\App\Livewire\Forms\StockOutForm;
use Modules\Pharmacy\App\Models\StockOut;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use Throwable;

/**
 * Importación masiva de agotados desde la pantalla de Agotados.
 *
 * Clave: código genérico. Un genérico tiene un solo agotado: si ya existe
 * (aunque esté eliminado lógicamente) se actualiza con la nueva circular;
 * si no, se crea.
 */
final class StockOutImporter implements RowImporter
{
    public function __construct(private readonly SaveStockOut $saveStockOut = new SaveStockOut) {}

    public function modelClass(): string
    {
        return StockOut::class;
    }

    public function columns(): array
    {
        return [
            'generic_code' => 'Código genérico',
            'generic_name' => 'Nombre genérico',
            'product_name' => 'Producto',
            'brand' => 'Marca',
            'classification' => 'Clasificación',
            'circular_number' => 'Número de circular',
            'circular_date' => 'Fecha de circular',
            'status' => 'Estado',
        ];
    }

    public function key(): string
    {
        return 'generic_code';
    }

    public function rules(): array
    {
        // El estado es opcional: vacío crea el agotado activo y no cambia uno existente.
        return [...StockOutForm::fieldRules(), 'status' => ['nullable', Rule::enum(Status::class)]];
    }

    /**
     * Normaliza estado (valor o etiqueta), clasificación (sin distinguir mayúsculas)
     * y fecha de circular (fecha de Excel, d/m/Y o Y-m-d) antes de validar.
     */
    public function prepare(array $row): array
    {
        if (is_string($row['status'] ?? null)) {
            $byLabel = array_change_key_case(array_flip(Status::options()));
            $row['status'] = $byLabel[mb_strtolower($row['status'])] ?? mb_strtolower($row['status']);
        }

        if (is_string($row['classification'] ?? null)) {
            $options = CharacteristicOption::forCharacteristicCode(StockOut::CLASSIFICATION_CHARACTERISTIC_CODE)->keys()->all();
            $byLower = array_combine(array_map('mb_strtolower', $options), $options);
            $row['classification'] = $byLower[mb_strtolower($row['classification'])] ?? $row['classification'];
        }

        if (is_string($row['circular_date'] ?? null)) {
            $row['circular_date'] = $this->normalizeDate($row['circular_date']);
        }

        return $row;
    }

    public function save(array $data, ?Model $existing): void
    {
        if (($data['status'] ?? null) === null) {
            unset($data['status']);
        }

        $this->saveStockOut->execute($data, $existing instanceof StockOut ? $existing : null);
    }

    /**
     * Convierte la fecha a Y-m-d. Excel entrega las celdas de fecha como número de serie
     * (ya convertido a texto por ImportRows); en texto se acepta d/m/Y (formato local).
     * Si no se reconoce, se devuelve tal cual para que la validación la rechace.
     */
    private function normalizeDate(string $value): string
    {
        try {
            if (ctype_digit($value)) {
                return CarbonImmutable::instance(ExcelDate::excelToDateTimeObject((int) $value))->format('Y-m-d');
            }

            if (preg_match('#^\d{1,2}/\d{1,2}/\d{4}$#', $value) === 1) {
                return CarbonImmutable::createFromFormat('!d/m/Y', $value)?->format('Y-m-d') ?? $value;
            }
        } catch (Throwable) {
            return $value;
        }

        return $value;
    }
}
