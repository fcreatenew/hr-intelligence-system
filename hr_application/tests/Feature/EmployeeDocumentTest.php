<?php

namespace Tests\Feature;

use App\Models\Employee;
use App\Models\EmployeeDocument;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class EmployeeDocumentTest extends TestCase
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
        $this->get(route('employee-documents.index'))->assertRedirect(route('login'));
    }

    public function test_hr_can_create_document(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->hrManager())
            ->post(route('employee-documents.store'), [
                'employee_id' => $employee->id,
                'title' => 'Kontrak Kerja 2026',
                'category' => 'kontrak',
                'issued_at' => '2026-01-01',
                'expires_at' => '2028-01-01',
                'status' => EmployeeDocument::STATUS_ACTIVE,
            ])
            ->assertRedirect(route('employee-documents.index'));

        $this->assertDatabaseHas('employee_documents', [
            'employee_id' => $employee->id,
            'title' => 'Kontrak Kerja 2026',
            'category' => 'kontrak',
        ]);
    }

    public function test_document_requires_valid_category(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('employee-documents.store'), [
                'employee_id' => $employee->id,
                'title' => 'Dokumen',
                'category' => 'ngawur',
                'status' => EmployeeDocument::STATUS_ACTIVE,
            ])
            ->assertSessionHasErrors('category');
    }

    public function test_expiry_cannot_precede_issue_date(): void
    {
        $employee = Employee::factory()->create();

        $this->actingAs($this->admin())
            ->post(route('employee-documents.store'), [
                'employee_id' => $employee->id,
                'title' => 'Sertifikat',
                'category' => 'sertifikat',
                'issued_at' => '2026-10-10',
                'expires_at' => '2026-10-01',
                'status' => EmployeeDocument::STATUS_ACTIVE,
            ])
            ->assertSessionHasErrors('expires_at');
    }

    public function test_expires_soon_detection(): void
    {
        $soon = EmployeeDocument::factory()->expiringSoon()->create();
        $far = EmployeeDocument::factory()->create(['expires_at' => now()->addYear()]);

        $this->assertTrue($soon->expiresSoon());
        $this->assertFalse($far->expiresSoon());
    }

    public function test_only_admin_can_delete_document(): void
    {
        $document = EmployeeDocument::factory()->create();

        $this->actingAs($this->hrManager())
            ->delete(route('employee-documents.destroy', $document))
            ->assertForbidden();

        $this->actingAs($this->admin())
            ->delete(route('employee-documents.destroy', $document))
            ->assertRedirect(route('employee-documents.index'));

        $this->assertDatabaseMissing('employee_documents', ['id' => $document->id]);
    }
}
