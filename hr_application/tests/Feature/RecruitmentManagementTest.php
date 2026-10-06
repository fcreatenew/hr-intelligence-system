<?php

namespace Tests\Feature;

use App\Models\Candidate;
use App\Models\Employee;
use App\Models\JobOpening;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecruitmentManagementTest extends TestCase
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
    private function openingPayload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Backend Engineer',
            'department' => 'IT',
            'employment_type' => 'tetap',
            'location' => 'Jakarta',
            'quota' => 2,
            'status' => JobOpening::STATUS_OPEN,
            'opened_at' => '2026-10-01',
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('job-openings.index'))->assertRedirect(route('login'));
    }

    public function test_hr_can_create_opening(): void
    {
        $this->actingAs($this->hrManager())
            ->post(route('job-openings.store'), $this->openingPayload())
            ->assertRedirect();

        $this->assertDatabaseHas('job_openings', [
            'title' => 'Backend Engineer',
            'department' => 'IT',
            'status' => JobOpening::STATUS_OPEN,
        ]);
    }

    public function test_opening_requires_valid_status_and_employment_type(): void
    {
        $this->actingAs($this->admin())
            ->post(route('job-openings.store'), $this->openingPayload([
                'status' => 'ngawur',
                'employment_type' => 'alien',
            ]))
            ->assertSessionHasErrors(['status', 'employment_type']);
    }

    public function test_closed_at_cannot_precede_opened_at(): void
    {
        $this->actingAs($this->admin())
            ->post(route('job-openings.store'), $this->openingPayload([
                'opened_at' => '2026-10-10',
                'closed_at' => '2026-10-01',
            ]))
            ->assertSessionHasErrors('closed_at');
    }

    public function test_hr_can_add_candidate_to_opening(): void
    {
        $opening = JobOpening::factory()->create();

        $this->actingAs($this->hrManager())
            ->post(route('job-openings.candidates.store', $opening), [
                'name' => 'Budi Santoso',
                'email' => 'budi@example.test',
                'stage' => Candidate::STAGE_SCREENING,
                'expected_salary' => 12000000,
            ])
            ->assertRedirect(route('job-openings.show', $opening));

        $this->assertDatabaseHas('candidates', [
            'job_opening_id' => $opening->id,
            'email' => 'budi@example.test',
            'stage' => Candidate::STAGE_SCREENING,
        ]);
    }

    public function test_hiring_candidate_creates_employee(): void
    {
        $opening = JobOpening::factory()->create([
            'department' => 'IT',
            'title' => 'Backend Engineer',
            'employment_type' => 'kontrak',
        ]);
        $candidate = Candidate::factory()->create([
            'job_opening_id' => $opening->id,
            'name' => 'Siti Aminah',
            'email' => 'siti@example.test',
            'expected_salary' => 9000000,
        ]);

        $this->actingAs($this->hrManager())
            ->post(route('candidates.hire', $candidate))
            ->assertRedirect();

        $this->assertSame(Candidate::STAGE_HIRED, $candidate->refresh()->stage);

        $employee = Employee::query()->where('email', 'siti@example.test')->first();
        $this->assertNotNull($employee);
        $this->assertSame('IT', $employee->department);
        $this->assertSame('Backend Engineer', $employee->position);
        $this->assertSame('Kontrak', $employee->status_kerja);
        $this->assertSame(9000000, (int) $employee->gaji_bulanan);
    }

    public function test_hiring_twice_is_idempotent(): void
    {
        $candidate = Candidate::factory()->hired()->create();

        $this->actingAs($this->admin())
            ->post(route('candidates.hire', $candidate))
            ->assertSessionHas('status', 'Kandidat sudah diterima sebelumnya.');

        $this->assertSame(0, Employee::query()->where('email', $candidate->email)->count());
    }

    public function test_only_admin_can_delete_opening(): void
    {
        $opening = JobOpening::factory()->create();

        $this->actingAs($this->hrManager())
            ->delete(route('job-openings.destroy', $opening))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->delete(route('job-openings.destroy', $opening))
            ->assertRedirect(route('job-openings.index'));

        $this->assertDatabaseMissing('job_openings', ['id' => $opening->id]);
    }

    public function test_index_lists_openings(): void
    {
        JobOpening::factory()->create(['title' => 'Data Analyst']);

        $this->actingAs($this->admin())
            ->get(route('job-openings.index'))
            ->assertOk()
            ->assertSee('Data Analyst');
    }
}
