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
            'email' => fake()->unique()->safeEmail(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
            // 'area' es obligatoria (enum sin default, ver create_users_table);
            // es_admin/acceso_reportes ya tienen default en su propia migración.
            'area' => fake()->randomElement(['hospital', 'consultorios', 'cafeteria', 'vinculacion']),
        ];
    }

    /**
     * Usuario administrador (ve todas las áreas, gestiona usuarios).
     */
    public function admin(): static
    {
        return $this->state(fn (array $attributes) => [
            'es_admin' => true,
        ]);
    }
}
