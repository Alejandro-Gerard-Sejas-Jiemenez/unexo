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
            ['DEMO-M01', 'Fundamentos de Excel', 20, EstadoComercial::Abierto, 'Introducción a hojas de cálculo', 2, 'Examen'],
            ['DEMO-M02', 'Excel Intermedio', 24, EstadoComercial::Abierto, 'Manejo avanzado y macros', 3, 'Proyecto Final'],
            ['DEMO-M03', 'Análisis de datos con Power BI', 30, EstadoComercial::Abierto, 'Inteligencia de negocios', 4, 'Proyecto Final'],
            ['DEMO-M04', 'Comunicación efectiva', 16, EstadoComercial::Abierto, 'Habilidades blandas', 2, 'Asistencia'],
            ['DEMO-M05', 'Gestión del tiempo', 12, EstadoComercial::Borrador, 'Optimización personal', 1, 'Asistencia'],
        ];

        foreach ($datosModulos as [$codigo, $nombre, $horas, $estado, $descripcion, $creditos, $evaluacion]) {
            $modulos[$codigo] = Modulo::firstOrCreate(['codigo' => $codigo], [
                'nombre' => $nombre,
                'duracion_horas' => $horas,
                'estado_comercial' => $estado,
                'descripcion' => $descripcion,
                'creditos' => $creditos,
                'tipo_evaluacion' => $evaluacion,
                'pdf_temario_url' => 'https://ejemplo.com/temario.pdf',
            ]);
        }

        $analisis = Programa::firstOrCreate(['codigo' => 'DEMO-P01'], [
            'nombre' => 'Diplomado en Análisis de Datos',
            'version' => '1.0',
            'descripcion' => 'Programa demo: de Excel a Power BI.',
            'estado_comercial' => EstadoComercial::Abierto,
            'precio' => 1500.50,
            'nivel_academico' => 'Diplomado',
            'imagen_portada_url' => 'https://ejemplo.com/portada1.jpg',
            'requisitos_ingreso' => 'Manejo básico de Windows.',
            'perfil_egreso' => 'Especialista capaz de procesar y visualizar grandes volúmenes de datos.',
            'certificado_otorga' => 'Especialista en Data Analytics',
        ]);

        $habilidades = Programa::firstOrCreate(['codigo' => 'DEMO-P02'], [
            'nombre' => 'Habilidades Profesionales',
            'version' => '1.0',
            'descripcion' => 'Programa demo en borrador.',
            'estado_comercial' => EstadoComercial::Borrador,
            'precio' => 500.00,
            'nivel_academico' => 'Curso',
            'imagen_portada_url' => 'https://ejemplo.com/portada2.jpg',
            'requisitos_ingreso' => 'Ninguno.',
            'perfil_egreso' => 'Profesional con liderazgo.',
            'certificado_otorga' => 'Certificado en Liderazgo',
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
