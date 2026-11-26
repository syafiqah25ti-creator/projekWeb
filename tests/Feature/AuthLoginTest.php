<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_invalid_credentials_returns_validation_error_instead_of_500(): void
    {
        $response = $this->from(route('login'))->post(route('auth.login'), [
            'email'    => 'tidakada@example.com',
            'password' => 'salah123',
        ]);

        $response->assertRedirect(route('login'));
        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_login_with_valid_credentials_authenticates_and_redirects_to_dashboard(): void
    {
        $user = User::create([
            'name'     => 'Tester',
            'email'    => 'tester@example.com',
            'password' => Hash::make('secret123'),
        ]);

        $response = $this->post(route('auth.login'), [
            'email'    => 'tester@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }
}
