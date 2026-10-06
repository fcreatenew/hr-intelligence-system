<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\Training;
use App\Models\TrainingParticipant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TrainingManagementTest extends TestCase
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

    private function employee(array $overrides = []): Employee
    {
        return Employee::factory()->create($overrides);
    }

    /** @return array<string, mixed> */
    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Dasar Keselamatan Kerja',
            'category' => 'keselamatan',
            'provider' => 'Lembaga K3',
            'mode' => 'offline',
            'start_date' => '2026-10-12',
            'end_date' => '2026-10-14',
            'duration_hours' => 16,
            'cost' => 1500000,
            'quota' => 10,
            'status' => Training::STATUS_RUNNING,
            'description' => 'Pelatihan wajib.',
        ], $overrides);
    }

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get(route('trainings.index'))->assertRedirect(route('login'));
    }

    public function test_hr_can_create_training(): void
    {
        $this->actingAs($this->hrManager())
            ->post(route('trainings.store'), $this->payload())
            ->assertRedirect();

        $this->assertDatabaseHas('trainings', [
            'title' => 'Dasar Keselamatan Kerja',
            'category' => 'keselamatan',
            'status' => Training::STATUS_RUNNING,
        ]);
    }

    public function test_training_requires_valid_category_and_mode(): void
    {
        $this->actingAs($this->admin())
            ->post(route('trainings.store'), $this->payload(['category' => 'ngawur', 'mode' => 'telepati']))
            ->assertSessionHasErrors(['category', 'mode']);
    }

    public function test_end_date_cannot_precede_start_date(): void
    {
        $this->actingAs($this->admin())
            ->post(route('trainings.store'), $this->payload([
                'start_date' => '2026-10-14',
                'end_date' => '2026-10-12',
            ]))
            ->assertSessionHasErrors('end_date');
    }

    public function test_only_admin_can_delete_training(): void
    {
        $training = Training::factory()->create();

        $this->actingAs($this->hrManager())
            ->delete(route('trainings.destroy', $training))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->delete(route('trainings.destroy', $training))
            ->assertRedirect(route('trainings.index'));

        $this->assertDatabaseMissing('trainings', ['id' => $training->id]);
    }

    public function test_participants_can_be_registered(): void
    {
        $training = Training::factory()->create(['quota' => 5]);
        $a = $this->employee();
        $b = $this->employee();

        $this->actingAs($this->admin())
            ->post(route('trainings.participants.store', $training), [
                'employee_ids' => [$a->id, $b->id],
            ])
            ->assertRedirect(route('trainings.show', $training));

        $this->assertSame(2, $training->participants()->count());
        $this->assertSame(2, $training->participants()->where('status', TrainingParticipant::STATUS_REGISTERED)->count());
    }

    public function test_duplicate_registration_is_skipped(): void
    {
        $training = Training::factory()->create(['quota' => 5]);
        $employee = $this->employee();

        TrainingParticipant::factory()->create([
            'training_id' => $training->id,
            'employee_id' => $employee->id,
        ]);

        $this->actingAs($this->admin())
            ->post(route('trainings.participants.store', $training), [
                'employee_ids' => [$employee->id],
            ])
            ->assertSessionHas('status', 'Semua karyawan yang dipilih sudah terdaftar.');

        $this->assertSame(1, $training->participants()->count());
    }

    public function test_quota_is_enforced(): void
    {
        $training = Training::factory()->create(['quota' => 1]);
        $terdaftar = $this->employee();
        $baru = $this->employee();

        TrainingParticipant::factory()->create([
            'training_id' => $training->id,
            'employee_id' => $terdaftar->id,
        ]);

        $this->actingAs($this->admin())
            ->post(route('trainings.participants.store', $training), [
                'employee_ids' => [$baru->id],
            ])
            ->assertSessionHasErrors('employee_ids');

        $this->assertSame(1, $training->participants()->count());
    }

    public function test_completing_participant_syncs_ml_feature(): void
    {
        $employee = $this->employee(['jumlah_training_setahun' => null]);
        $training = Training::factory()->create([
            'status' => Training::STATUS_RUNNING,
            'start_date' => now()->startOfYear()->addMonth()->toDateString(),
        ]);
        $participant = TrainingParticipant::factory()->create([
            'training_id' => $training->id,
            'employee_id' => $employee->id,
        ]);

        $this->actingAs($this->admin())
            ->put(route('participants.update', $participant), [
                'status' => TrainingParticipant::STATUS_COMPLETED,
                'score' => 90,
                'certified' => true,
                'certificate_number' => 'CERT-001',
            ])
            ->assertRedirect(route('trainings.show', $training));

        $participant->refresh();
        $this->assertSame(TrainingParticipant::STATUS_COMPLETED, $participant->status);
        $this->assertNotNull($participant->completed_at);
        $this->assertTrue($participant->certified);

        $this->assertSame(1, $employee->refresh()->jumlah_training_setahun);
    }

    public function test_uncertified_participant_clears_certificate_number(): void
    {
        $employee = $this->employee();
        $training = Training::factory()->create();
        $participant = TrainingParticipant::factory()->create([
            'training_id' => $training->id,
            'employee_id' => $employee->id,
            'certified' => true,
            'certificate_number' => 'CERT-9',
        ]);

        $this->actingAs($this->admin())
            ->put(route('participants.update', $participant), [
                'status' => TrainingParticipant::STATUS_COMPLETED,
                'certified' => false,
                'certificate_number' => 'CERT-9',
            ])
            ->assertRedirect();

        $this->assertNull($participant->refresh()->certificate_number);
    }

    public function test_registered_participants_are_not_counted_as_training_feature(): void
    {
        $employee = $this->employee(['jumlah_training_setahun' => null]);
        $training = Training::factory()->create(['status' => Training::STATUS_RUNNING]);
        TrainingParticipant::factory()->create([
            'training_id' => $training->id,
            'employee_id' => $employee->id,
            'status' => TrainingParticipant::STATUS_REGISTERED,
        ]);

        $jumlah = app(\App\Services\TrainingService::class)->syncEmployeeTrainingCount($employee);

        $this->assertSame(0, $jumlah);
        $this->assertSame(0, $employee->refresh()->jumlah_training_setahun);
    }

    public function test_removing_participant_resyncs_feature(): void
    {
        $employee = $this->employee(['jumlah_training_setahun' => null]);
        $training = Training::factory()->create();
        $participant = TrainingParticipant::factory()->completed()->create([
            'training_id' => $training->id,
            'employee_id' => $employee->id,
        ]);

        $this->actingAs($this->admin())
            ->put(route('participants.update', $participant), [
                'status' => TrainingParticipant::STATUS_COMPLETED,
                'certified' => false,
            ]);

        $this->assertSame(1, $employee->refresh()->jumlah_training_setahun);

        $this->actingAs($this->admin())
            ->delete(route('participants.destroy', $participant))
            ->assertRedirect(route('trainings.show', $training));

        $this->assertSame(0, $employee->refresh()->jumlah_training_setahun);
    }

    public function test_index_and_show_pages_render(): void
    {
        $training = Training::factory()->create();

        $this->actingAs($this->admin())
            ->get(route('trainings.index'))
            ->assertOk()
            ->assertSee($training->title);

        $this->actingAs($this->admin())
            ->get(route('trainings.show', $training))
            ->assertOk()
            ->assertSee('Peserta');
    }
}
