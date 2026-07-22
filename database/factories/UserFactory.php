<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
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
            'name' => fake()->name(),
            'username' => fake()->unique()->userName(),
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'idperfil' => null,
            'idunidad' => null,
            'idgrupo' => null,
            'bloqueado' => false,
            'intentos_fallidos' => 0,
            'password_temporal' => false,
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indica que el usuario está bloqueado por el administrador.
     */
    public function bloqueado(): static
    {
        return $this->state(fn (array $attributes) => [
            'bloqueado' => true,
        ]);
    }

    /**
     * Indica que el usuario debe cambiar su contraseña temporal.
     */
    public function conPasswordTemporal(): static
    {
        return $this->state(fn (array $attributes) => [
            'password_temporal' => true,
        ]);
    }
}
