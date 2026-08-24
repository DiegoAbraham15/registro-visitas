<?php

namespace Tests\Feature;

use App\Models\Bitacora;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/usuarios')->assertRedirect('/login');
    }

    public function test_non_admin_user_cannot_access_user_management(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $this->get('/usuarios')->assertForbidden();
        $this->post('/usuarios', [])->assertForbidden();
        $this->get('/usuarios/1/editar')->assertForbidden();
        $this->put('/usuarios/1', [])->assertForbidden();
        $this->delete('/usuarios/1')->assertForbidden();
    }

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->admin()->create();
        User::factory()->count(3)->create();
        $this->actingAs($admin);

        $response = $this->get('/usuarios');

        $response->assertOk();
        $response->assertViewIs('usuarios.index');
        $response->assertViewHas('usuarios', fn ($usuarios) => $usuarios->count() === 4);
    }

    public function test_usuarios_page_shows_the_activity_log(): void
    {
        $admin = User::factory()->admin()->create();
        Bitacora::registrar('prueba.accion', 'Descripción de prueba');
        $this->actingAs($admin);

        $response = $this->get('/usuarios');

        $response->assertOk();
        $response->assertViewHas('bitacora', fn ($bitacora) => $bitacora->total() === 1);
        $response->assertSee('prueba.accion');
        $response->assertSee('Descripción de prueba');
    }

    public function test_updating_a_user_writes_to_the_activity_log(): void
    {
        $admin = User::factory()->admin()->create();
        $otro = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($admin);

        $this->put("/usuarios/{$otro->id}", [
            'name' => $otro->name,
            'email' => $otro->email,
            'area' => $otro->area,
        ]);

        $this->assertDatabaseHas('bitacora', ['accion' => 'usuario.actualizar']);
    }

    public function test_admin_can_create_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->post('/usuarios', [
            'name' => 'Nuevo Usuario',
            'email' => 'nuevo@example.com',
            'password' => 'password123',
            'area' => 'cafeteria',
        ]);

        $response->assertRedirect('/usuarios');
        $this->assertDatabaseHas('users', [
            'email' => 'nuevo@example.com',
            'area' => 'cafeteria',
            'es_admin' => false,
            'acceso_reportes' => false,
        ]);
    }

    public function test_admin_can_create_a_vinculacion_user(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->post('/usuarios', [
            'name' => 'Encargado de Vinculación',
            'email' => 'vinculacion@example.com',
            'password' => 'password123',
            'area' => 'vinculacion',
        ]);

        $response->assertRedirect('/usuarios');
        $this->assertDatabaseHas('users', [
            'email' => 'vinculacion@example.com',
            'area' => 'vinculacion',
        ]);
    }

    public function test_admin_can_grant_acceso_vinculacion_to_a_user_of_another_area(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->post('/usuarios', [
            'name' => 'Enfermera con Vinculación',
            'email' => 'enfermera@example.com',
            'password' => 'password123',
            'area' => 'hospital',
            'acceso_vinculacion' => '1',
        ]);

        $response->assertRedirect('/usuarios');
        $this->assertDatabaseHas('users', [
            'email' => 'enfermera@example.com',
            'area' => 'hospital',
            'acceso_vinculacion' => true,
        ]);
    }

    public function test_admin_can_grant_es_admin_cafeteria_to_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->post('/usuarios', [
            'name' => 'Encargado de Cafetería',
            'email' => 'cafeteria-admin@example.com',
            'password' => 'password123',
            'area' => 'cafeteria',
            'es_admin_cafeteria' => '1',
        ]);

        $response->assertRedirect('/usuarios');
        $this->assertDatabaseHas('users', [
            'email' => 'cafeteria-admin@example.com',
            'es_admin_cafeteria' => true,
        ]);
    }

    public function test_admin_can_grant_acceso_catalogos_to_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->post('/usuarios', [
            'name' => 'Encargado de Catálogos',
            'email' => 'catalogos@example.com',
            'password' => 'password123',
            'area' => 'hospital',
            'acceso_catalogos' => '1',
        ]);

        $response->assertRedirect('/usuarios');
        $this->assertDatabaseHas('users', [
            'email' => 'catalogos@example.com',
            'acceso_catalogos' => true,
        ]);
    }

    public function test_admin_can_update_a_users_acceso_catalogos(): void
    {
        $admin = User::factory()->admin()->create();
        $otro = User::factory()->create(['area' => 'hospital', 'acceso_catalogos' => false]);
        $this->actingAs($admin);

        $this->put("/usuarios/{$otro->id}", [
            'name' => $otro->name,
            'email' => $otro->email,
            'area' => $otro->area,
            'acceso_catalogos' => '1',
        ]);

        $this->assertTrue($otro->fresh()->acceso_catalogos);
    }

    public function test_admin_can_grant_acceso_medicos_to_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->post('/usuarios', [
            'name' => 'Encargado de Médicos',
            'email' => 'medicos@example.com',
            'password' => 'password123',
            'area' => 'consultorios',
            'acceso_medicos' => '1',
        ]);

        $response->assertRedirect('/usuarios');
        $this->assertDatabaseHas('users', [
            'email' => 'medicos@example.com',
            'acceso_medicos' => true,
            // Confirma que no se activó el permiso del Hospital de rebote.
            'acceso_catalogos' => false,
        ]);
    }

    public function test_admin_can_update_a_users_acceso_medicos(): void
    {
        $admin = User::factory()->admin()->create();
        $otro = User::factory()->create(['area' => 'consultorios', 'acceso_medicos' => false]);
        $this->actingAs($admin);

        $this->put("/usuarios/{$otro->id}", [
            'name' => $otro->name,
            'email' => $otro->email,
            'area' => $otro->area,
            'acceso_medicos' => '1',
        ]);

        $this->assertTrue($otro->fresh()->acceso_medicos);
    }

    public function test_creating_a_user_requires_unique_email(): void
    {
        $admin = User::factory()->admin()->create();
        $existente = User::factory()->create(['email' => 'ocupado@example.com']);
        $this->actingAs($admin);

        $response = $this->from('/usuarios')->post('/usuarios', [
            'name' => 'Nuevo Usuario',
            'email' => 'ocupado@example.com',
            'password' => 'password123',
            'area' => 'hospital',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertSame(1, User::where('email', 'ocupado@example.com')->count());
    }

    public function test_creating_a_user_requires_a_valid_area(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->from('/usuarios')->post('/usuarios', [
            'name' => 'Nuevo Usuario',
            'email' => 'nuevo@example.com',
            'password' => 'password123',
            'area' => 'zona-inexistente',
        ]);

        $response->assertSessionHasErrors('area');
    }

    public function test_admin_can_view_edit_form_for_a_user(): void
    {
        $admin = User::factory()->admin()->create();
        $otro = User::factory()->create();
        $this->actingAs($admin);

        $response = $this->get("/usuarios/{$otro->id}/editar");

        $response->assertOk();
        $response->assertViewIs('usuarios.editar');
    }

    public function test_admin_can_update_a_user_without_changing_password(): void
    {
        $admin = User::factory()->admin()->create();
        $otro = User::factory()->create(['area' => 'hospital', 'password' => 'password-original']);
        $this->actingAs($admin);

        $response = $this->put("/usuarios/{$otro->id}", [
            'name' => 'Nombre Editado',
            'email' => $otro->email,
            'area' => 'consultorios',
        ]);

        $response->assertRedirect('/usuarios');

        $otro->refresh();
        $this->assertSame('Nombre Editado', $otro->name);
        $this->assertSame('consultorios', $otro->area);
        $this->assertTrue(Hash::check('password-original', $otro->password));
    }

    public function test_admin_can_update_a_users_password(): void
    {
        $admin = User::factory()->admin()->create();
        $otro = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($admin);

        $this->put("/usuarios/{$otro->id}", [
            'name' => $otro->name,
            'email' => $otro->email,
            'area' => $otro->area,
            'password' => 'password-nuevo',
        ]);

        $otro->refresh();
        $this->assertTrue(Hash::check('password-nuevo', $otro->password));
    }

    public function test_updating_a_user_keeps_the_email_unique_rule_ignoring_itself(): void
    {
        $admin = User::factory()->admin()->create();
        $otro = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($admin);

        $response = $this->put("/usuarios/{$otro->id}", [
            'name' => $otro->name,
            'email' => $otro->email,
            'area' => $otro->area,
        ]);

        $response->assertSessionDoesntHaveErrors('email');
    }

    public function test_admin_cannot_delete_their_own_account(): void
    {
        $admin = User::factory()->admin()->create();
        $this->actingAs($admin);

        $response = $this->from('/usuarios')->delete("/usuarios/{$admin->id}");

        $response->assertSessionHasErrors('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_delete_another_user(): void
    {
        $admin = User::factory()->admin()->create();
        $otro = User::factory()->create();
        $this->actingAs($admin);

        $response = $this->delete("/usuarios/{$otro->id}");

        $response->assertRedirect('/usuarios');
        $this->assertDatabaseMissing('users', ['id' => $otro->id]);
    }
}
