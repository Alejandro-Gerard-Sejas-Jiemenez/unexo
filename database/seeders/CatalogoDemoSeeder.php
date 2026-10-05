<?php

namespace Database\Seeders;

use App\Enums\EstadoComercial;
use App\Models\Modulo;
use App\Models\Programa;
use Illuminate\Database\Seeder;

/**
 * Datos inventados (prefijo DEMO-) para desarrollo y presentación.
 */
class CatalogoDemoSeeder extends Seeder
{
    public function run(): void
    {
        $modulos = [];
        $datosModulos = [
            ['DEMO-M01', 'Fundamentos de Excel', 20, EstadoComercial::Abierto],
            ['DEMO-M02', 'Excel Intermedio', 24, EstadoComercial::Abierto],
            ['DEMO-M03', 'Análisis de datos con Power BI', 30, EstadoComercial::Abierto],
            ['DEMO-M04', 'Comunicación efectiva', 16, EstadoComercial::Abierto],
            ['DEMO-M05', 'Gestión del tiempo', 12, EstadoComercial::Borrador],
        ];

        foreach ($datosModulos as [$codigo, $nombre, $horas, $estado]) {
            $modulos[$codigo] = Modulo::firstOrCreate(['codigo' => $codigo], [
                'nombre' => $nombre,
                'duracion_horas' => $horas,
                'estado_comercial' => $estado,
            ]);
        }

        $analisis = Programa::firstOrCreate(['codigo' => 'DEMO-P01'], [
            'nombre' => 'Diplomado en Análisis de Datos',
            'version' => '1.0',
            'descripcion' => 'Programa demo: de Excel a Power BI.',
            'estado_comercial' => EstadoComercial::Abierto,
        ]);

        $habilidades = Programa::firstOrCreate(['codigo' => 'DEMO-P02'], [
            'nombre' => 'Habilidades Profesionales',
            'version' => '1.0',
            'descripcion' => 'Programa demo en borrador.',
            'estado_comercial' => EstadoComercial::Borrador,
        ]);

        if ($analisis->modulos()->count() === 0) {
            $analisis->modulos()->attach($modulos['DEMO-M01']->id, ['orden' => 1]);
            $analisis->modulos()->attach($modulos['DEMO-M02']->id, ['orden' => 2]);
            $analisis->modulos()->attach($modulos['DEMO-M03']->id, ['orden' => 3]);
            // DEMO-M04 queda compartido por los dos programas
            $analisis->modulos()->attach($modulos['DEMO-M04']->id, ['orden' => 4]);
        }

        if ($habilidades->modulos()->count() === 0) {
            $habilidades->modulos()->attach($modulos['DEMO-M04']->id, ['orden' => 1]);
            $habilidades->modulos()->attach($modulos['DEMO-M05']->id, ['orden' => 2]);
        }
    }
}
