<?php

namespace App\Contracts;

use Illuminate\Database\Eloquent\Model;

/**
 * Definición de la importación masiva de un modelo desde Excel/CSV.
 *
 * Cada CRUD importable implementa esta interfaz una sola vez y la comparten
 * la pantalla (trait ImportsRows) y los seeders, de modo que columnas,
 * validación y reglas de guardado no se dupliquen.
 */
interface RowImporter
{
    /**
     * FQCN del modelo importado (autorización y búsqueda de existentes).
     *
     * @return class-string<Model>
     */
    public function modelClass(): string;

    /**
     * Columnas aceptadas: encabezado del archivo => etiqueta legible.
     * Los encabezados son los de la plantilla; cualquier otra columna se ignora.
     *
     * @return array<string, string>
     */
    public function columns(): array;

    /**
     * Columna que identifica el registro: si ya existe se actualiza, si no se crea.
     * null indica una importación de solo creación: cada fila es un registro nuevo
     * y todas las columnas se validan aunque el archivo no las traiga.
     */
    public function key(): ?string;

    /**
     * Reglas de validación aplicadas a cada fila ya normalizada.
     *
     * @return array<string, mixed>
     */
    public function rules(): array;

    /**
     * Transforma una fila normalizada antes de validarla (ej. código de sede → id).
     *
     * @param  array<string, mixed>  $row
     * @return array<string, mixed>
     */
    public function prepare(array $row): array;

    /**
     * Guarda una fila válida; $existing es el registro con la misma clave, si existe.
     *
     * @param  array<string, mixed>  $data
     */
    public function save(array $data, ?Model $existing): void;
}
