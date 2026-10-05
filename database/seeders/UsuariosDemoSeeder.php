<?php

namespace Database\Seeders;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Datos inventados para desarrollo. Contraseña de todos: Demo12345
 */
class UsuariosDemoSeeder extends Seeder
{
    public function run(): void
    {
        $usuarios = [
            [Rol::Administrador, 'Marcela', 'Rojas Vaca', '5000001', 'admin@nexo.test', 'admin@nexo.edu.test'],
            [Rol::Docente, 'Carlos', 'Mendoza Peña', '5000002', 'carlos.mendoza@nexo.test', 'cmendoza@nexo.edu.test'],
            [Rol::Docente, 'Lucía', 'Salvatierra Ortiz', '5000003', 'lucia.salvatierra@nexo.test', 'lsalvatierra@nexo.edu.test'],
            [Rol::Estudiante, 'Andrés', 'Flores Suárez', '5000004', 'andres.flores@nexo.test', 'aflores@nexo.edu.test'],
            [Rol::Estudiante, 'Daniela', 'Choque Mamani', '5000005', 'daniela.choque@nexo.test', 'dchoque@nexo.edu.test'],
            [Rol::Estudiante, 'Mateo', 'Justiniano Ribera', '5000006', 'mateo.justiniano@nexo.test', 'mjustiniano@nexo.edu.test'],
        ];

        foreach ($usuarios as [$rol, $nombres, $apellidos, $ci, $email, $institucional]) {
            User::firstOrCreate(
                ['email' => $email],
                [
                    'rol' => $rol,
                    'nombres' => $nombres,
                    'apellidos' => $apellidos,
                    'documento_identidad' => $ci,
                    'correo_institucional' => $institucional,
                    'telefono' => '70000'.substr($ci, -3),
                    'password' => 'Demo12345',
                    'estado' => 'activo',
                ]
            );
        }
    }
}
