<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RateLimitingTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_is_throttled_after_repeated_failures(): void
    {
        $user = User::factory()->create(['password' => 'benar']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'salah'])
                ->assertRedirect();
        }

        $this->post('/login', ['email' => $user->email, 'password' => 'salah'])
            ->assertTooManyRequests();
    }

    public function test_login_throttle_is_scoped_per_email(): void
    {
        $user = User::factory()->create(['password' => 'benar']);

        for ($i = 0; $i < 5; $i++) {
            $this->post('/login', ['email' => $user->email, 'password' => 'salah'])
                ->assertRedirect();
        }

        // Email lain dari IP yang sama tidak boleh ikut terblokir.
        $this->post('/login', ['email' => 'orang-lain@example.com', 'password' => 'salah'])
            ->assertRedirect();
    }

    public function test_admin_intelligence_endpoint_is_throttled(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        for ($i = 0; $i < 30; $i++) {
            $this->actingAs($admin)
                ->getJson('/admin/hr-intelligence/predictions')
                ->assertOk();
        }

        $this->actingAs($admin)
            ->getJson('/admin/hr-intelligence/predictions')
            ->assertTooManyRequests();
    }
}
