<?php

namespace Tests\Feature;

use App\Http\Controllers\AuthController;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthRoutesTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_route_resolves_the_existing_controller_and_renders_the_login_form(): void
    {
        $this->assertTrue(class_exists(AuthController::class));
        $this->assertTrue(method_exists(AuthController::class, 'showLogin'));
        $this->assertTrue(method_exists(AuthController::class, 'login'));

        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Selamat Datang Kembali');
    }

    public function test_valid_credentials_authenticate_and_redirect_to_the_dashboard(): void
    {
        $user = User::create([
            'name' => 'Login User',
            'email' => 'login-user@example.test',
            'password' => 'correct-password',
        ]);

        RateLimiter::clear('login-user@example.test|127.0.0.1');

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'correct-password',
        ])
            ->assertRedirect(route('dashboard'))
            ->assertSessionHasNoErrors();

        $this->assertAuthenticatedAs($user);
    }

    public function test_invalid_credentials_are_rejected_without_authenticating(): void
    {
        $user = User::create([
            'name' => 'Login User',
            'email' => 'login-user@example.test',
            'password' => 'correct-password',
        ]);

        RateLimiter::clear('login-user@example.test|127.0.0.1');

        $this->post(route('login'), [
            'email' => $user->email,
            'password' => 'incorrect-password',
        ])
            ->assertRedirect()
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_authenticated_user_can_log_out_and_return_to_the_login_page(): void
    {
        $user = User::create([
            'name' => 'Login User',
            'email' => 'login-user@example.test',
            'password' => 'correct-password',
        ]);

        $this->actingAs($user)
            ->post(route('logout'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }
}
