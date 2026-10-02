<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function hrManager(): User
    {
        return User::factory()->create(['role' => 'hr-manager']);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/users')->assertRedirect('/login');
    }

    public function test_hr_manager_cannot_manage_users(): void
    {
        $this->actingAs($this->hrManager())->get('/users')->assertForbidden();
        $this->actingAs($this->hrManager())->get('/users/create')->assertForbidden();
    }

    public function test_admin_sees_user_list(): void
    {
        User::factory()->create(['name' => 'Siti HR', 'role' => 'hr-manager']);

        $this->actingAs($this->admin())
            ->get('/users')
            ->assertOk()
            ->assertSee('Siti HR')
            ->assertSee('HR Manager');
    }

    public function test_admin_can_create_user(): void
    {
        $this->actingAs($this->admin())
            ->post('/users', [
                'name' => 'Budi',
                'email' => 'budi@example.com',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
                'role' => 'hr-manager',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseHas('users', ['email' => 'budi@example.com', 'role' => 'hr-manager']);
    }

    public function test_create_rejects_short_password_and_duplicate_email(): void
    {
        User::factory()->create(['email' => 'ada@example.com']);

        $this->actingAs($this->admin())
            ->post('/users', [
                'name' => 'X',
                'email' => 'ada@example.com',
                'password' => 'pendek',
                'password_confirmation' => 'pendek',
                'role' => 'admin',
            ])
            ->assertSessionHasErrors(['email', 'password']);
    }

    public function test_create_rejects_unknown_role(): void
    {
        $this->actingAs($this->admin())
            ->post('/users', [
                'name' => 'X',
                'email' => 'x@example.com',
                'password' => 'rahasia123',
                'password_confirmation' => 'rahasia123',
                'role' => 'superuser',
            ])
            ->assertSessionHasErrors('role');
    }

    public function test_admin_can_update_without_changing_password(): void
    {
        $target = User::factory()->create(['role' => 'hr-manager']);
        $originalHash = $target->password;

        $this->actingAs($this->admin())
            ->put("/users/{$target->id}", [
                'name' => 'Nama Baru',
                'email' => $target->email,
                'role' => 'admin',
                'password' => '',
            ])
            ->assertRedirect(route('users.index'));

        $target->refresh();
        $this->assertSame('Nama Baru', $target->name);
        $this->assertSame('admin', $target->role);
        $this->assertSame($originalHash, $target->password);
    }

    public function test_admin_can_change_password(): void
    {
        $target = User::factory()->create(['role' => 'hr-manager']);
        $originalHash = $target->password;

        $this->actingAs($this->admin())
            ->put("/users/{$target->id}", [
                'name' => $target->name,
                'email' => $target->email,
                'role' => 'hr-manager',
                'password' => 'sandi-baru-123',
                'password_confirmation' => 'sandi-baru-123',
            ])
            ->assertRedirect(route('users.index'));

        $this->assertNotSame($originalHash, $target->refresh()->password);
    }

    public function test_admin_cannot_delete_self(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)
            ->delete("/users/{$admin->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_admin_can_delete_other_user(): void
    {
        $target = User::factory()->create(['role' => 'hr-manager']);

        $this->actingAs($this->admin())
            ->delete("/users/{$target->id}")
            ->assertRedirect(route('users.index'));

        $this->assertDatabaseMissing('users', ['id' => $target->id]);
    }
}
