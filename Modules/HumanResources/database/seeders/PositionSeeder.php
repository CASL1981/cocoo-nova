<?php

declare(strict_types=1);

namespace Modules\HumanResources\Database\Seeders;

use App\Enums\Status;
use App\Models\Process;
use Database\Seeders\ProcessSeeder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Modules\HumanResources\App\Models\HrCharacteristic;
use Modules\HumanResources\App\Models\HrCharacteristicOption;
use RuntimeException;

/**
 * Carga la característica "Cargos" con los 37 cargos del organigrama vigente
 * (doc/ORGANIGRAMA.xlsx, hoja "VERSION 6", diciembre de 2023), con su proceso y su jefe inmediato.
 *
 * No incluye los órganos de gobierno ni externos (Asamblea, Consejo, Junta de Vigilancia, Revisor
 * Fiscal, comités y asesores). Es idempotente: crea lo que falte y no modifica lo ya editado.
 */
final class PositionSeeder extends Seeder
{
    /** Cargo => [proceso, jefe inmediato], en orden jerárquico (el jefe va antes que sus subordinados). */
    public const POSITIONS = [
        'Gerente' => ['Gerencia', null],
        'Asistente de Gerencia' => ['Gerencia', 'Gerente'],
        'Coordinador Sistema de Gestión' => ['Gerencia', 'Gerente'],
        'Comunicador' => ['Gerencia', 'Coordinador Sistema de Gestión'],
        'Auditor' => ['Gerencia', 'Coordinador Sistema de Gestión'],

        'Director Comercial y de Mercadeo' => ['Comercial', 'Gerente'],
        'Asistente Comercial' => ['Comercial', 'Director Comercial y de Mercadeo'],
        'Representante de Ventas' => ['Comercial', 'Director Comercial y de Mercadeo'],
        'Auxiliar de Ventas' => ['Comercial', 'Director Comercial y de Mercadeo'],
        'Coordinador de Compras' => ['Comercial', 'Director Comercial y de Mercadeo'],
        'Auxiliar de Compras' => ['Comercial', 'Coordinador de Compras'],

        'Director de Logística y Operaciones' => ['Logística y Operaciones', 'Gerente'],
        'Director Técnico' => ['Logística y Operaciones', 'Director de Logística y Operaciones'],
        'Químico Farmacéutico' => ['Logística y Operaciones', 'Director de Logística y Operaciones'],
        'Analista de Servicios Farmacéuticos' => ['Logística y Operaciones', 'Director de Logística y Operaciones'],
        'Supernumerario' => ['Logística y Operaciones', 'Director de Logística y Operaciones'],
        'Coordinador CEDIS' => ['Logística y Operaciones', 'Director de Logística y Operaciones'],
        'Auxiliar Logístico CEDIS' => ['Logística y Operaciones', 'Coordinador CEDIS'],
        'Conductor' => ['Logística y Operaciones', 'Coordinador CEDIS'],
        'Director Técnico Servicio Farmacéutico' => ['Logística y Operaciones', 'Director de Logística y Operaciones'],
        'Auxiliar Servicio Farmacéutico' => ['Logística y Operaciones', 'Director Técnico Servicio Farmacéutico'],
        'Auxiliar Logístico Farmacias' => ['Logística y Operaciones', 'Director de Logística y Operaciones'],

        'Director Financiero' => ['Financiero', 'Gerente'],
        'Coordinador Contable' => ['Financiero', 'Director Financiero'],
        'Auxiliar Contable' => ['Financiero', 'Coordinador Contable'],
        'Tesorero' => ['Financiero', 'Director Financiero'],

        'Director Administrativo' => ['Administrativo', 'Gerente'],
        'Coordinador de Talento Humano' => ['Administrativo', 'Director Administrativo'],
        'Coordinador TIC' => ['Administrativo', 'Director Administrativo'],
        'Analista de Datos' => ['Administrativo', 'Coordinador TIC'],
        'Auxiliar de Sistemas' => ['Administrativo', 'Coordinador TIC'],
        'Auxiliar de Archivo' => ['Administrativo', 'Director Administrativo'],
        'Mensajero' => ['Administrativo', 'Director Administrativo'],
        'Auxiliar Administrativo' => ['Administrativo', 'Director Administrativo'],
        'Secretaria' => ['Administrativo', 'Director Administrativo'],
        'Auxiliar de Servicios Generales' => ['Administrativo', 'Director Administrativo'],
        'Analista de Contratación' => ['Administrativo', 'Director Administrativo'],
    ];

    public function run(): void
    {
        $this->call(ProcessSeeder::class);

        DB::transaction(function (): void {
            $characteristic = HrCharacteristic::withTrashed()->firstOrCreate(
                ['code' => HrCharacteristic::POSITIONS_CODE],
                ['name' => 'Cargos', 'status' => Status::Active],
            );

            $processes = Process::query()->pluck('id', 'name');
            $ids = [];

            foreach (self::POSITIONS as $name => [$process, $parent]) {
                $option = HrCharacteristicOption::withTrashed()->firstOrCreate(
                    ['hr_characteristic_id' => $characteristic->id, 'name' => $name],
                    [
                        'process_id' => $processes[$process] ?? throw new RuntimeException("Falta el proceso {$process}."),
                        'parent_id' => $parent === null ? null : $ids[$parent],
                        'status' => Status::Active,
                    ],
                );

                $ids[$name] = $option->id;
            }
        });
    }
}
