<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class MiddlewareRoleTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role, string $email): User
    {
        return User::create([
            'name'     => $role . ' User',
            'email'    => $email,
            'password' => Hash::make('password123'),
            'role'     => $role,
        ]);
    }

    public function test_guest_is_redirected_to_login_by_checkislogin_middleware(): void
    {
        foreach (['/dashboard', '/user', '/pelanggan'] as $uri) {
            $response = $this->get($uri);
            $response->assertRedirect(route('login'));
            $response->assertSessionHasErrors();
        }
    }

    public function test_super_admin_can_access_dashboard_user_and_pelanggan(): void
    {
        $admin = $this->makeUser('Super Admin', 'superadmin@example.com');
        $this->actingAs($admin);

        $this->get(route('dashboard'))->assertStatus(200);
        $this->get(route('user.index'))->assertStatus(200)->assertSee('Super Admin');
        $this->get(route('pelanggan.index'))->assertStatus(200);
    }

    public function test_mitra_can_access_pelanggan_but_gets_403_on_user(): void
    {
        $mitra = $this->makeUser('mitra', 'mitra@example.com');
        $this->actingAs($mitra);

        $this->get(route('dashboard'))->assertStatus(200);
        $this->get(route('pelanggan.index'))->assertStatus(200);
        $this->get(route('user.index'))->assertStatus(403);
    }

    public function test_pelanggan_role_gets_403_on_user_and_pelanggan(): void
    {
        $pelanggan = $this->makeUser('Pelanggan', 'pelanggan@example.com');
        $this->actingAs($pelanggan);

        $this->get(route('dashboard'))->assertStatus(200);
        $this->get(route('user.index'))->assertStatus(403);
        $this->get(route('pelanggan.index'))->assertStatus(403);
    }

    public function test_super_admin_can_create_and_update_user_with_role(): void
    {
        $admin = $this->makeUser('Super Admin', 'admincrud@example.com');
        $this->actingAs($admin);

        $createResponse = $this->post(route('user.store'), [
            'name'                  => 'Budi Mitra',
            'email'                 => 'budi.mitra@example.com',
            'role'                  => 'mitra',
            'password'              => 'secret1234',
            'password_confirmation' => 'secret1234',
        ]);
        $createResponse->assertRedirect(route('user.index'));

        $created = User::where('email', 'budi.mitra@example.com')->first();
        $this->assertNotNull($created);
        $this->assertSame('mitra', $created->role);

        $updateResponse = $this->put(route('user.update', $created->id), [
            'name'  => 'Budi Updated',
            'email' => 'budi.mitra@example.com',
            'role'  => 'Super Admin',
        ]);
        $updateResponse->assertRedirect(route('user.index'));
        $this->assertSame('Super Admin', $created->fresh()->role);
    }
}
