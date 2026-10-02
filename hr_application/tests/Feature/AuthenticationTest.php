<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_forgot_password_page_renders(): void
    {
        $this->get(route('password.request'))
            ->assertOk()
            ->assertSee('Lupa kata sandi');
    }

    public function test_reset_link_is_sent_for_existing_email(): void
    {
        Notification::fake();
        $user = User::factory()->create(['email' => 'hr@example.com']);

        $this->post(route('password.email'), ['email' => 'hr@example.com'])
            ->assertRedirect();

        Notification::assertSentTo($user, ResetPassword::class);
    }

    public function test_reset_link_request_hides_whether_email_exists(): void
    {
        Notification::fake();

        $this->post(route('password.email'), ['email' => 'tidak-ada@example.com'])
            ->assertRedirect()
            ->assertSessionHas('status');

        Notification::assertNothingSent();
    }

    public function test_password_can_be_reset_with_valid_token(): void
    {
        $user = User::factory()->create([
            'email' => 'hr@example.com',
            'password' => Hash::make('password-lama'),
        ]);

        $token = app('auth.password.broker')->createToken($user);

        $this->post(route('password.update'), [
            'token' => $token,
            'email' => 'hr@example.com',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertRedirect(route('login'));

        $this->assertTrue(Hash::check('password-baru-123', $user->fresh()->password));
    }

    public function test_reset_with_invalid_token_fails(): void
    {
        $user = User::factory()->create(['email' => 'hr@example.com']);

        $this->post(route('password.update'), [
            'token' => 'token-palsu',
            'email' => 'hr@example.com',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])->assertSessionHasErrors('email');

        $this->assertTrue(Hash::check('password', $user->fresh()->password));
    }

    public function test_user_can_update_own_profile_with_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Nama Baru',
                'email' => 'nama-baru@example.com',
                'current_password' => 'password',
            ])
            ->assertRedirect(route('profile.edit'));

        $user->refresh();
        $this->assertSame('Nama Baru', $user->name);
        $this->assertSame('nama-baru@example.com', $user->email);
    }

    public function test_profile_update_requires_correct_current_password(): void
    {
        $user = User::factory()->create(['password' => Hash::make('password')]);

        $this->actingAs($user)
            ->put(route('profile.update'), [
                'name' => 'Nama Baru',
                'email' => $user->email,
                'current_password' => 'salah',
            ])
            ->assertSessionHasErrors('current_password');

        $this->assertNotSame('Nama Baru', $user->fresh()->name);
    }

    public function test_admin_can_send_reset_link_to_a_user(): void
    {
        Notification::fake();
        $admin = User::factory()->create(['role' => 'admin']);
        $target = User::factory()->create();

        $this->actingAs($admin)
            ->post(route('users.send-reset', $target))
            ->assertRedirect();

        Notification::assertSentTo($target, ResetPassword::class);
    }

    public function test_hr_manager_cannot_send_reset_link_to_a_user(): void
    {
        Notification::fake();
        $manager = User::factory()->create(['role' => 'hr-manager']);
        $target = User::factory()->create();

        $this->actingAs($manager)
            ->post(route('users.send-reset', $target))
            ->assertForbidden();

        Notification::assertNothingSent();
    }
}
