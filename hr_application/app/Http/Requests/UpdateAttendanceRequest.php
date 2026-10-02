<?php

namespace App\Http\Requests;

class UpdateAttendanceRequest extends StoreAttendanceRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('attendance')) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return parent::rules();
    }
}
