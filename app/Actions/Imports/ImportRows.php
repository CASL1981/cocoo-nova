<?php

namespace App\Actions\Imports;

use App\Contracts\RowImporter;
use App\Imports\SheetRowsImport;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Exceptions\NoTypeDetectedException;
use PhpOffice\PhpSpreadsheet\Exception as SpreadsheetException;

/**
 * Importa un archivo Excel/CSV con la definición de un RowImporter.
 *
 * Política "todo o nada": primero se valida el archivo completo y, solo si
 * ninguna fila tiene errores, se guardan todas dentro de una transacción.
 * Los registros existentes (misma clave, incluidos los eliminados) se
 * actualizan; los demás se crean. Sin clave, todas las filas se crean.
 */
final class ImportRows
{
    /** Máximo de mensajes devueltos para no saturar la pantalla. */
    private const MAX_ERRORS = 100;

    /** La fila 1 es el encabezado: el primer registro está en la fila 2. */
    private const FIRST_DATA_ROW = 2;

    /**
     * @param  string|null  $sheet  Nombre de la hoja a leer; null toma la primera.
     */
    public function execute(RowImporter $importer, string|UploadedFile $file, ?string $sheet = null): ImportResult
    {
        try {
            $rows = SheetRowsImport::read($file, $sheet);
        } catch (NoTypeDetectedException|SpreadsheetException) {
            return new ImportResult(errors: ['No se pudo leer el archivo: verifique que sea un Excel (.xlsx) o CSV válido.']);
        }

        if ($rows === []) {
            return new ImportResult(errors: ['El archivo no contiene registros para importar.']);
        }

        $columns = $importer->columns();
        $key = $importer->key();

        // Solo se procesan las columnas aceptadas que trae el archivo: una columna
        // ausente no se toca al actualizar (no se borra su valor).
        $present = array_values(array_intersect(array_keys($columns), array_map('strval', array_keys($rows[0]))));

        if ($key !== null && ! in_array($key, $present, true)) {
            return new ImportResult(errors: ["El archivo no contiene la columna obligatoria \"{$key}\". Descargue la plantilla."]);
        }

        [$valid, $errors] = $this->validate($importer, $rows, $present, $columns, $key);

        if ($errors !== []) {
            return new ImportResult(errors: $errors);
        }

        return $this->persist($importer, $valid, $key);
    }

    /**
     * Normaliza y valida cada fila; devuelve [filas válidas, errores].
     *
     * @param  list<array<string|int, mixed>>  $rows
     * @param  list<string>  $present
     * @param  array<string, string>  $columns
     * @return array{0: list<array<string, mixed>>, 1: list<string>}
     */
    private function validate(RowImporter $importer, array $rows, array $present, array $columns, ?string $key): array
    {
        // Al actualizar, una columna ausente no se toca; al solo crear, todas sus reglas aplican
        // (una columna obligatoria que falte en el archivo se reporta en cada fila).
        $rules = $key === null ? $importer->rules() : array_intersect_key($importer->rules(), array_flip($present));
        $valid = [];
        $errors = [];
        $errorCount = 0;
        $seenKeys = [];

        foreach ($rows as $index => $raw) {
            $line = $index + self::FIRST_DATA_ROW;
            $row = $this->normalize($raw, $present);

            if (array_filter($row, fn ($value) => $value !== null) === []) {
                continue;
            }

            $row = $importer->prepare($row);
            $messages = Validator::make($row, $rules, [], $columns)->errors()->all();

            $keyValue = $key === null ? null : ($row[$key] ?? null);
            if ($keyValue !== null && isset($seenKeys[$keyValue])) {
                $messages[] = "El valor \"{$keyValue}\" de {$columns[$key]} está repetido en la fila {$seenKeys[$keyValue]}.";
            } elseif ($keyValue !== null) {
                $seenKeys[$keyValue] = $line;
            }

            foreach ($messages as $message) {
                if (++$errorCount <= self::MAX_ERRORS) {
                    $errors[] = "Fila {$line}: {$message}";
                }
            }

            if ($messages === []) {
                $valid[] = $row;
            }
        }

        if ($errorCount > self::MAX_ERRORS) {
            $errors[] = '… y '.($errorCount - self::MAX_ERRORS).' error(es) más.';
        }

        return [$valid, $errors];
    }

    /**
     * Recorta textos, convierte "NULL"/vacío en null y los números en texto
     * (Excel entrega códigos como 1 o 19.0 aunque la columna sea de texto).
     *
     * @param  array<string|int, mixed>  $raw
     * @param  list<string>  $present
     * @return array<string, mixed>
     */
    private function normalize(array $raw, array $present): array
    {
        $row = [];

        foreach ($present as $field) {
            $value = $raw[$field] ?? null;

            if (is_string($value)) {
                $value = trim($value);
                $value = ($value === '' || strcasecmp($value, 'NULL') === 0) ? null : $value;
            } elseif (is_float($value) && floor($value) === $value && abs($value) < PHP_INT_MAX) {
                $value = (string) (int) $value;
            } elseif (is_int($value) || is_float($value)) {
                $value = (string) $value;
            }

            $row[$field] = $value;
        }

        return $row;
    }

    /**
     * @param  list<array<string, mixed>>  $rows
     */
    private function persist(RowImporter $importer, array $rows, ?string $key): ImportResult
    {
        $existing = $key === null ? new Collection : $this->existing($importer->modelClass(), $key, array_column($rows, $key));

        return DB::transaction(function () use ($importer, $rows, $key, $existing): ImportResult {
            $created = 0;
            $updated = 0;

            foreach ($rows as $row) {
                $model = $key === null ? null : $existing->get((string) $row[$key]);
                $importer->save($row, $model);
                $model === null ? $created++ : $updated++;
            }

            return new ImportResult($created, $updated);
        });
    }

    /**
     * Registros ya existentes indexados por la clave, cargados en bloques.
     *
     * @param  class-string<Model>  $modelClass
     * @param  array<int, mixed>  $keys
     * @return Collection<string, Model>
     */
    private function existing(string $modelClass, string $key, array $keys): Collection
    {
        // Incluye los eliminados lógicamente: la clave sigue ocupada y se actualizan.
        $softDeletes = in_array(SoftDeletes::class, class_uses_recursive($modelClass), true);
        $found = new Collection;

        foreach (array_chunk($keys, 1000) as $chunk) {
            $query = $modelClass::query();

            if ($softDeletes) {
                $query->withoutGlobalScope(SoftDeletingScope::class);
            }

            foreach ($query->whereIn($key, $chunk)->get() as $model) {
                $found->put((string) $model->getAttribute($key), $model);
            }
        }

        return $found;
    }
}
