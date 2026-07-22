<?php

namespace Tests\Feature;

use App\Models\MenuDia;
use App\Models\MenuSemanaOpciones;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MenuControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/vinculacion/menus')->assertRedirect('/login');
    }

    public function test_user_from_another_area_is_forbidden(): void
    {
        $user = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($user);

        $this->get('/vinculacion/menus')->assertForbidden();
        $this->put('/vinculacion/menus', [])->assertForbidden();
    }

    public function test_user_with_acceso_vinculacion_can_view_the_menu_from_another_area(): void
    {
        $user = User::factory()->create(['area' => 'hospital', 'acceso_vinculacion' => true]);
        $this->actingAs($user);

        $this->get('/vinculacion/menus')->assertOk();
    }

    public function test_vinculacion_user_can_view_the_weekly_menu_with_all_seven_days(): void
    {
        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $response = $this->get('/vinculacion/menus');

        $response->assertOk();
        $response->assertViewIs('menus.edit');
        $response->assertViewHas('dias', function ($dias) {
            return $dias->count() === 7 && $dias->pluck('dia')->all() === MenuDia::DIAS;
        });
        $response->assertViewHas('opciones');
    }

    public function test_admin_can_also_view_the_weekly_menu(): void
    {
        $admin = User::factory()->admin()->create(['area' => 'hospital']);
        $this->actingAs($admin);

        $this->get('/vinculacion/menus')->assertOk();
    }

    public function test_update_saves_each_days_comida_and_the_weekwide_options(): void
    {
        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $response = $this->put('/vinculacion/menus', [
            'dias' => [
                'lunes' => ['comida' => 'Pollo con arroz'],
                'martes' => ['comida' => 'Pescado empapelado'],
            ],
            'desayuno_opciones' => ['Hot cakes', '', 'Huevo con jamón', '  '],
            'cena_opciones' => ['Sopa', 'Sandwich'],
        ]);

        $response->assertRedirect('/vinculacion/menus');

        $lunes = MenuDia::where('dia', 'lunes')->first();
        $martes = MenuDia::where('dia', 'martes')->first();
        $this->assertSame('Pollo con arroz', $lunes->comida);
        $this->assertSame('Pescado empapelado', $martes->comida);

        $opciones = MenuSemanaOpciones::actual();
        $this->assertSame(['Hot cakes', 'Huevo con jamón'], $opciones->desayuno_opciones);
        $this->assertSame(['Sopa', 'Sandwich'], $opciones->cena_opciones);
    }

    public function test_weekwide_options_apply_to_every_day_not_just_one(): void
    {
        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $this->put('/vinculacion/menus', [
            'dias' => ['lunes' => ['comida' => 'Pollo con arroz']],
            'desayuno_opciones' => ['Hot cakes', 'Huevo'],
            'cena_opciones' => ['Sopa'],
        ]);

        $response = $this->get('/vinculacion/dashboard');

        // Las mismas opciones deben verse el día que sea, no solo lunes.
        $response->assertViewHas('opcionesSemana', function ($opciones) {
            return $opciones->desayuno_opciones === ['Hot cakes', 'Huevo']
                && $opciones->cena_opciones === ['Sopa'];
        });
    }

    public function test_update_requires_the_dias_array(): void
    {
        $user = User::factory()->create(['area' => 'vinculacion']);
        $this->actingAs($user);

        $response = $this->from('/vinculacion/menus')->put('/vinculacion/menus', []);

        $response->assertSessionHasErrors('dias');
    }
}
