<?php

namespace Database\Seeders;

use App\Enums\Certificado;
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
            ['DEMO-M01', 'Fundamentos de Excel', 20, EstadoComercial::Abierto, 'Introducción a hojas de cálculo', 'DEMO: conocimientos básicos de Windows'],
            ['DEMO-M02', 'Excel Intermedio', 24, EstadoComercial::Abierto, 'Manejo avanzado y macros', 'DEMO: conocimientos básicos de Excel'],
            ['DEMO-M03', 'Análisis de datos con Power BI', 30, EstadoComercial::Abierto, 'Inteligencia de negocios', null],
            ['DEMO-M04', 'Comunicación efectiva', 16, EstadoComercial::Abierto, 'Habilidades blandas', null],
            ['DEMO-M05', 'Gestión del tiempo', 12, EstadoComercial::Borrador, 'Optimización personal', null],
        ];

        foreach ($datosModulos as [$codigo, $nombre, $horas, $estado, $descripcion, $prerrequisito]) {
            $modulos[$codigo] = Modulo::firstOrCreate(['codigo' => $codigo], [
                'nombre' => $nombre,
                'duracion_horas' => $horas,
                'estado_comercial' => $estado,
                'descripcion' => $descripcion,
                'prerrequisito' => $prerrequisito,
                'pdf_temario_url' => 'https://ejemplo.com/temario.pdf',
            ]);
        }

        $analisis = Programa::firstOrCreate(['codigo' => 'DEMO-P01'], [
            'nombre' => 'Diplomado en Análisis de Datos',
            'version' => '1.0',
            'descripcion' => 'Programa demo: de Excel a Power BI.',
            'estado_comercial' => EstadoComercial::Abierto,
            'precio' => 1500.50,
            'imagen_portada_url' => 'https://ejemplo.com/portada1.jpg',
            'requisitos_ingreso' => 'Manejo básico de Windows.',
            'certificado_otorga' => Certificado::Certificado,
        ]);

        $habilidades = Programa::firstOrCreate(['codigo' => 'DEMO-P02'], [
            'nombre' => 'Habilidades Profesionales',
            'version' => '1.0',
            'descripcion' => 'Programa demo en borrador.',
            'estado_comercial' => EstadoComercial::Borrador,
            'precio' => 500.00,
            'imagen_portada_url' => 'https://ejemplo.com/portada2.jpg',
            'requisitos_ingreso' => 'Ninguno.',
            'certificado_otorga' => Certificado::SinCertificado,
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
