<?php

namespace Database\Factories;

use App\Models\Tractivo;
use Illuminate\Database\Eloquent\Factories\Factory;

class TractivoFactory extends Factory
{
    protected $model = Tractivo::class;

    public function definition(): array
    {
        return [
            'codigo' => 'T'.fake()->unique()->numberBetween(1000, 9999),
            'descripcion' => 'Tracto '.strtoupper(fake()->randomLetter()).fake()->numberBetween(100, 999),
            'placa' => strtoupper(fake()->randomLetter().fake()->randomLetter().fake()->randomLetter()).fake()->numberBetween(1000, 9999),
            'id_tipo_vehiculo' => null,
            'marca' => fake()->randomElement(['Kenworth', 'Freightliner', 'International', 'Peterbilt', 'Volvo']),
            'modelo' => fake()->randomElement(['T680', 'Cascadia', 'LT625', '579', 'VNL 860']),
            'anno' => fake()->numberBetween(2010, 2025),
            'estado' => fake()->randomElement(['activo', 'inactivo', 'taller']),
        ];
    }
}
