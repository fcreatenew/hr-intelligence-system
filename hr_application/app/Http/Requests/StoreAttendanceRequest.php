<?php

namespace App\Http\Requests;

use App\Models\Attendance;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', Attendance::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $employeeId = $this->input('employee_id');
        $date = $this->input('date');

        return [
            'employee_id' => ['required', 'exists:employees,id'],
            'date' => [
                'required',
                'date',
                // Satu karyawan hanya boleh punya satu catatan per tanggal.
                Rule::unique('attendances', 'date')
                    ->where('employee_id', $employeeId)
                    ->ignore($this->route('attendance')),
            ],
            'status' => ['required', Rule::in(Attendance::statuses())],
            'check_in' => ['nullable', 'date_format:H:i'],
            'check_out' => ['nullable', 'date_format:H:i', 'after:check_in'],
            'late_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'overtime_minutes' => ['nullable', 'integer', 'min:0', 'max:1440'],
            'note' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'date.unique' => 'Sudah ada catatan absensi untuk karyawan ini pada tanggal tersebut.',
            'check_out.after' => 'Jam keluar harus setelah jam masuk.',
        ];
    }
}
