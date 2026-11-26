<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RbacMenuSeeder::class,
            UsuariosDemoSeeder::class,
            CatalogoDemoSeeder::class,
            PlanificacionDemoSeeder::class,
        ]);
    }
}
