<?php

namespace Database\Factories;

use App\Enums\Rol;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    protected static ?string $password;

    public function definition(): array
    {
        return [
            'rol' => Rol::Estudiante,
            'nombres' => fake()->firstName(),
            'apellidos' => fake()->lastName().' '.fake()->lastName(),
            'documento_identidad' => (string) fake()->unique()->numberBetween(4000000, 9999999),
            'email' => fake()->unique()->safeEmail(),
            'telefono' => '7'.fake()->numberBetween(1000000, 9999999),
            'password' => static::$password ??= Hash::make('Demo12345'),
            'estado' => 'activo',
            'remember_token' => Str::random(10),
        ];
    }
}
