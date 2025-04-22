<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'username' => fake()->userName(),
            'role_id' => null, // Establecer a null inicialmente, se asignará después
            'password' => Hash::make('Isw@2025user'), // Contraseña por defecto para todos los usuarios
        ];
    }

    /**
     * Define a state for superadmin users.
     */
    public function superAdmin()
    {
        return $this->state(function (array $attributes) {
            return [
                'password' => Hash::make('Isw@2025admin'),
            ];
        });
    }
}
