<?php

namespace Tests\Feature;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class AttendanceImportTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->create(['role' => 'admin']);
    }

    private function csv(array $rows): UploadedFile
    {
        $content = "nik,date,in,out,status\n";

        foreach ($rows as $row) {
            $content .= implode(',', $row)."\n";
        }

        return UploadedFile::fake()->createWithContent('absensi.csv', $content);
    }

    public function test_guest_cannot_import(): void
    {
        $this->post(route('attendances.import'))->assertRedirect(route('login'));
    }

    public function test_import_creates_records_and_computes_late_and_overtime(): void
    {
        $employee = Employee::factory()->create(['nik' => 'EMP9001']);

        $this->actingAs($this->admin())
            ->post(route('attendances.import'), [
                'file' => $this->csv([
                    ['EMP9001', '2026-05-04', '09:20', '17:45', 'hadir'],
                ]),
            ])
            ->assertRedirect(route('attendances.index'));

        $attendance = Attendance::where('employee_id', $employee->id)->firstOrFail();
        $this->assertSame('2026-05-04', $attendance->date->toDateString());
        $this->assertSame(Attendance::STATUS_HADIR, $attendance->status);
        $this->assertSame('09:20', $attendance->check_in);
        $this->assertSame('17:45', $attendance->check_out);
        $this->assertSame(20, $attendance->late_minutes, '09:20 lewat 20 menit dari jam masuk 09:00');
        $this->assertSame(45, $attendance->overtime_minutes, '17:45 lewat 45 menit dari jam keluar 17:00');
    }

    public function test_import_is_idempotent_for_same_employee_and_date(): void
    {
        Employee::factory()->create(['nik' => 'EMP9002']);

        $payload = ['file' => $this->csv([['EMP9002', '2026-05-05', '08:55', '17:00', 'hadir']])];
        $this->actingAs($this->admin())->post(route('attendances.import'), $payload);
        $this->actingAs($this->admin())->post(route('attendances.import'), $payload);

        $this->assertSame(1, Attendance::count(), 'impor ulang harus memperbarui, bukan menggandakan');
    }

    public function test_import_skips_unknown_nik_and_reports_it(): void
    {
        $this->actingAs($this->admin())
            ->post(route('attendances.import'), [
                'file' => $this->csv([['EMP-TIDAK-ADA', '2026-05-04', '09:00', '17:00', 'hadir']]),
            ])
            ->assertSessionHas('import_skipped');

        $this->assertSame(0, Attendance::count());
    }

    public function test_import_derives_status_from_missing_check_in(): void
    {
        $employee = Employee::factory()->create(['nik' => 'EMP9003']);

        $this->actingAs($this->admin())
            ->post(route('attendances.import'), [
                'file' => $this->csv([['EMP9003', '2026-05-06', '', '', '']]),
            ]);

        $this->assertSame(Attendance::STATUS_ALPHA, Attendance::where('employee_id', $employee->id)->firstOrFail()->status);
    }

    public function test_import_template_downloads_csv(): void
    {
        $response = $this->actingAs($this->admin())
            ->get(route('attendances.import-template'))
            ->assertOk();

        $this->assertStringContainsString('text/csv', (string) $response->headers->get('content-type'));
        $this->assertStringContainsString('nik,date,in,out,status', $response->streamedContent());
    }
}
