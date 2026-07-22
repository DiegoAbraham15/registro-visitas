<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_sees_login_form(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertViewIs('auth.login');
    }

    public function test_authenticated_user_visiting_login_is_redirected_to_their_area_dashboard(): void
    {
        $user = User::factory()->create(['area' => 'cafeteria']);
        $this->actingAs($user);

        $response = $this->get('/login');

        $response->assertRedirect('/cafeteria/dashboard');
    }

    public function test_root_redirects_guest_to_login(): void
    {
        $response = $this->get('/');

        $response->assertRedirect('/login');
    }

    public function test_root_redirects_authenticated_user_to_their_area_dashboard(): void
    {
        $user = User::factory()->create(['area' => 'hospital']);
        $this->actingAs($user);

        $response = $this->get('/');

        $response->assertRedirect('/hospital/dashboard');
    }

    public function test_user_can_login_with_valid_credentials_and_is_sent_to_their_area(): void
    {
        $user = User::factory()->create([
            'area' => 'consultorios',
            'password' => 'password-correcto',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password-correcto',
        ]);

        $response->assertRedirect('/consultorios/dashboard');
        $this->assertTrue(Auth::check());
        $this->assertEquals($user->id, Auth::id());
    }

    public function test_login_fails_with_invalid_credentials(): void
    {
        $user = User::factory()->create(['password' => 'password-correcto']);

        $response = $this->from('/login')->post('/login', [
            'email' => $user->email,
            'password' => 'password-incorrecto',
        ]);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors('email');
        $this->assertFalse(Auth::check());
    }

    public function test_login_requires_email_and_password(): void
    {
        $response = $this->from('/login')->post('/login', []);

        $response->assertRedirect('/login');
        $response->assertSessionHasErrors(['email', 'password']);
    }

    public function test_repeated_failed_logins_are_throttled(): void
    {
        $user = User::factory()->create(['password' => 'password-correcto']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', [
                'email' => $user->email,
                'password' => 'incorrecta',
            ]);
        }

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'incorrecta',
        ]);

        $response->assertStatus(429);
    }

    public function test_authenticated_user_can_logout(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertFalse(Auth::check());
    }
}
