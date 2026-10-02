<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\HrIntelligencePrediction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeManagementTest extends TestCase
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

    /** @return array<string, mixed> */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'nik' => 'EMP0001',
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'department' => 'IT',
            'position' => 'Backend Engineer',
            'join_date' => '2021-03-15',
            'status' => '1',
            'status_kerja' => 'Tetap',
            'pendidikan' => 'S1',
            'gaji_bulanan' => 9_500_000,
            'jam_lembur_bulanan' => 22,
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/employees')->assertRedirect('/login');
    }

    public function test_login_page_renders(): void
    {
        $this->get('/login')->assertOk()->assertSee('Masuk');
    }

    public function test_user_can_login_and_logout(): void
    {
        $user = User::factory()->create(['role' => 'admin', 'password' => 'rahasia123']);

        $this->post('/login', ['email' => $user->email, 'password' => 'rahasia123'])
            ->assertRedirect(route('employees.index'));

        $this->assertAuthenticatedAs($user);

        $this->post('/logout')->assertRedirect(route('login'));
        $this->assertGuest();
    }

    public function test_login_rejects_wrong_password(): void
    {
        $user = User::factory()->create(['password' => 'benar']);

        $this->post('/login', ['email' => $user->email, 'password' => 'salah'])
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_hr_manager_can_list_employees(): void
    {
        Employee::factory()->count(3)->create();

        $this->actingAs($this->hrManager())
            ->get('/employees')
            ->assertOk()
            ->assertSee('Karyawan');
    }

    public function test_index_searches_by_name_nik_and_email(): void
    {
        Employee::factory()->create(['name' => 'Siti Aminah', 'nik' => 'EMP9001', 'email' => 'siti@example.com']);
        Employee::factory()->create(['name' => 'Andi Wijaya', 'nik' => 'EMP9002', 'email' => 'andi@example.com']);

        $this->actingAs($this->admin())->get('/employees?q=Siti')->assertSee('Siti Aminah')->assertDontSee('Andi Wijaya');
        $this->actingAs($this->admin())->get('/employees?q=EMP9002')->assertSee('Andi Wijaya')->assertDontSee('Siti Aminah');
        $this->actingAs($this->admin())->get('/employees?q=siti@example.com')->assertSee('Siti Aminah');
    }

    public function test_index_filters_by_department_and_status(): void
    {
        Employee::factory()->create(['name' => 'Karyawan IT', 'department' => 'IT']);
        Employee::factory()->create(['name' => 'Karyawan HR', 'department' => 'HR']);
        Employee::factory()->inactive()->create(['name' => 'Mantan Karyawan', 'department' => 'IT']);

        $this->actingAs($this->admin())->get('/employees?department=HR')
            ->assertSee('Karyawan HR')->assertDontSee('Karyawan IT');

        $this->actingAs($this->admin())->get('/employees?status=nonaktif')
            ->assertSee('Mantan Karyawan')->assertDontSee('Karyawan HR');
    }

    public function test_employee_can_be_created(): void
    {
        $this->actingAs($this->admin())
            ->post('/employees', $this->validPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('employees', [
            'nik' => 'EMP0001',
            'name' => 'Budi Santoso',
            'department' => 'IT',
            'status' => 1,
        ]);
    }

    public function test_created_employee_computes_derived_attributes(): void
    {
        $this->actingAs($this->admin())->post('/employees', $this->validPayload([
            'tanggal_lahir' => '1994-05-20',
            'join_date' => '2021-03-15',
        ]));

        $employee = Employee::firstWhere('nik', 'EMP0001');

        $this->assertNotNull($employee->usia);
        $this->assertGreaterThan(20, $employee->usia);
        $this->assertGreaterThan(48, $employee->masa_kerja_bulan);
    }

    public function test_create_rejects_duplicate_nik_and_email(): void
    {
        Employee::factory()->create(['nik' => 'EMP0001', 'email' => 'budi@example.com']);

        $this->actingAs($this->admin())
            ->post('/employees', $this->validPayload())
            ->assertSessionHasErrors(['nik', 'email']);

        $this->assertSame(1, Employee::count());
    }

    public function test_create_validates_out_of_range_values(): void
    {
        $this->actingAs($this->admin())
            ->post('/employees', $this->validPayload([
                'kepuasan_kerja' => 9,
                'periode' => '01-2026',
                'status_kerja' => 'Alien',
            ]))
            ->assertSessionHasErrors(['kepuasan_kerja', 'periode', 'status_kerja']);
    }

    public function test_employee_can_be_updated(): void
    {
        $employee = Employee::factory()->create(['nik' => 'EMP0001', 'name' => 'Nama Lama']);

        $this->actingAs($this->admin())
            ->put("/employees/{$employee->id}", $this->validPayload(['name' => 'Nama Baru', 'email' => $employee->email]))
            ->assertRedirect(route('employees.show', $employee));

        $this->assertSame('Nama Baru', $employee->fresh()->name);
    }

    public function test_update_allows_keeping_own_nik_and_email(): void
    {
        $employee = Employee::factory()->create(['nik' => 'EMP0001', 'email' => 'budi@example.com']);

        $this->actingAs($this->admin())
            ->put("/employees/{$employee->id}", $this->validPayload([
                'nik' => 'EMP0001',
                'email' => 'budi@example.com',
                'name' => 'Tetap Boleh',
            ]))
            ->assertSessionHasNoErrors();

        $this->assertSame('Tetap Boleh', $employee->fresh()->name);
    }

    public function test_blank_ml_fields_stay_null_instead_of_zero(): void
    {
        $this->actingAs($this->admin())->post('/employees', $this->validPayload([
            'kepuasan_kerja' => '',
            'beban_kerja' => '',
        ]));

        $employee = Employee::firstWhere('nik', 'EMP0001');

        $this->assertNull($employee->kepuasan_kerja);
        $this->assertNull($employee->beban_kerja);
    }

    public function test_hr_manager_cannot_delete_employee(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->hrManager())
            ->delete("/employees/{$employee->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('employees', ['id' => $employee->id]);
    }

    public function test_admin_can_delete_employee(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->admin())
            ->delete("/employees/{$employee->id}")
            ->assertRedirect(route('employees.index'));

        $this->assertDatabaseMissing('employees', ['id' => $employee->id]);
    }

    public function test_show_displays_prediction_history_linked_by_nik(): void
    {
        $employee = Employee::factory()->create(['nik' => 'EMP0001']);

        HrIntelligencePrediction::create([
            'use_case' => 'turnover',
            'employee_id' => 'EMP0001',
            'skor' => 0.42,
            'label' => 'Sedang',
            'is_flag' => false,
            'detail' => ['employee_id' => 'EMP0001'],
            'model_version' => 'v2',
            'predicted_at' => now(),
        ]);

        $this->actingAs($this->admin())
            ->get("/employees/{$employee->id}")
            ->assertOk()
            ->assertSee('turnover')
            ->assertSee('v2');
    }
}
