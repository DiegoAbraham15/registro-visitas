<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CafeteriaResumenControllerTest extends TestCase
{
    use RefreshDatabase;

    private function creaCortesiaActiva(string $piso, string $habitacion, array $atributos = []): void
    {
        DB::table('cafeteria_cortesias')->insert(array_merge([
            'piso' => $piso,
            'habitacion' => $habitacion,
            'activo' => true,
            // CortesiaVigente considera "de hoy" según updated_at; de otro modo
            // toda fila creada aquí se vería como "de un día anterior".
            'created_at' => now(),
            'updated_at' => now(),
        ], $atributos));
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/cafeteria/resumen')->assertRedirect('/login');
    }

    public function test_regular_user_is_forbidden(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get('/cafeteria/resumen')->assertForbidden();
    }

    public function test_user_with_es_admin_cafeteria_can_view_the_summary(): void
    {
        $user = User::factory()->create(['es_admin_cafeteria' => true]);
        $this->actingAs($user);

        $this->get('/cafeteria/resumen')->assertOk();
    }

    public function test_admin_can_also_view_the_summary(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $this->get('/cafeteria/resumen')->assertOk();
    }

    public function test_summary_counts_dishes_and_flags_rooms_missing_a_meal(): void
    {
        $this->creaCortesiaActiva('Piso 1', '101', ['platillo_desayuno' => 'Hot cakes', 'platillo_cena' => 'Sopa']);
        $this->creaCortesiaActiva('Piso 1', '102', ['platillo_desayuno' => 'Hot cakes']);
        $this->creaCortesiaActiva('Piso 2', '201', ['activo' => false, 'platillo_desayuno' => 'Huevo']);

        $user = User::factory()->create(['es_admin_cafeteria' => true]);
        $this->actingAs($user);

        $response = $this->get('/cafeteria/resumen');

        $response->assertViewHas('resumen', function ($resumen) {
            return $resumen['total_habitaciones'] === 2
                && $resumen['sin_cena'] === 1
                && $resumen['desayuno']->get('Hot cakes') === 2;
        });
    }

    public function test_summary_treats_a_previous_days_menu_as_not_defined_for_today(): void
    {
        // Mismo paciente dos días seguidos: nadie tocó la habitación hoy, así
        // que el desayuno/cena de ayer no debe contar como "de hoy".
        $this->creaCortesiaActiva('Piso 1', '101', [
            'platillo_desayuno' => 'Hot cakes',
            'platillo_cena' => 'Sopa',
            'updated_at' => now()->subDay(),
        ]);

        $user = User::factory()->create(['es_admin_cafeteria' => true]);
        $this->actingAs($user);

        $response = $this->get('/cafeteria/resumen');

        $response->assertViewHas('resumen', function ($resumen) {
            return $resumen['sin_desayuno'] === 1
                && $resumen['sin_cena'] === 1
                && $resumen['desayuno']->isEmpty();
        });
    }
}
