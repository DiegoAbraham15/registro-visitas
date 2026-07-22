<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\Concerns\CreaVisitas;
use Tests\TestCase;

class AreaDashboardAccessTest extends TestCase
{
    use CreaVisitas;
    use RefreshDatabase;

    public static function areasProvider(): array
    {
        return [
            'hospital' => ['/hospital/dashboard', 'hospital'],
            'consultorios' => ['/consultorios/dashboard', 'consultorios'],
            'cafeteria' => ['/cafeteria/dashboard', 'cafeteria'],
        ];
    }

    #[DataProvider('areasProvider')]
    public function test_guest_is_redirected_to_login(string $ruta, string $area): void
    {
        $response = $this->get($ruta);

        $response->assertRedirect('/login');
    }

    #[DataProvider('areasProvider')]
    public function test_user_of_matching_area_can_access_their_dashboard(string $ruta, string $area): void
    {
        $user = User::factory()->create(['area' => $area]);
        $this->actingAs($user);

        $response = $this->get($ruta);

        $response->assertOk();
        $response->assertViewIs('dashboard_area');
    }

    #[DataProvider('areasProvider')]
    public function test_admin_can_access_any_area_dashboard(string $ruta, string $area): void
    {
        $admin = User::factory()->admin()->create(['area' => 'hospital']);
        $this->actingAs($admin);

        $response = $this->get($ruta);

        $response->assertOk();
    }

    public function test_user_from_one_area_cannot_access_another_areas_dashboard(): void
    {
        $user = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->get('/consultorios/dashboard');

        $response->assertForbidden();
    }

    public function test_cafeteria_user_cannot_access_hospital_dashboard(): void
    {
        $user = User::factory()->create(['area' => 'cafeteria']);
        $this->actingAs($user);

        $response = $this->get('/hospital/dashboard');

        $response->assertForbidden();
    }

    public function test_dashboard_helper_route_redirects_to_own_area(): void
    {
        $user = User::factory()->create(['area' => 'cafeteria']);
        $this->actingAs($user);

        $response = $this->get('/dashboard_area');

        $response->assertRedirect('/cafeteria/dashboard');
    }

    public function test_dashboard_hides_finalizadas_by_default_and_shows_them_with_estado_todas(): void
    {
        $this->creaVisitaFamiliar(['id_edificio' => 1, 'estado' => 'activa'], ['nombre' => 'Ana Activa']);
        $this->creaVisitaFamiliar(['id_edificio' => 1, 'estado' => 'finalizada'], ['nombre' => 'Bea Finalizada']);

        $user = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($user);

        $this->get('/hospital/dashboard')
            ->assertViewHas('visitas', fn ($visitas) => $visitas->pluck('nombre_visitante')->all() === ['Ana Activa'])
            ->assertViewHas('soloActivas', true);

        $this->get('/hospital/dashboard?estado=todas')
            ->assertViewHas('visitas', fn ($visitas) => $visitas->count() === 2)
            ->assertViewHas('soloActivas', false);
    }
}
