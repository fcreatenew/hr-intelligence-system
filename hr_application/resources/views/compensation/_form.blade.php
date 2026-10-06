@php
    $val = fn ($key, $default = '') => old($key, $component->{$key} ?? $default);
@endphp

<div class="grid">
    <div class="field">
        <label for="employee_id">Karyawan</label>
        <select id="employee_id" name="employee_id" required>
            <option value="">— Pilih —</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" @selected((int) $val('employee_id') === $employee->id)>
                    {{ $employee->name }} — {{ $employee->nik }}
                </option>
            @endforeach
        </select>
        @error('employee_id') <div class="error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label for="name">Nama komponen</label>
        <input id="name" name="name" value="{{ $val('name') }}" required maxlength="120">
        @error('name') <div class="error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label for="type">Tipe</label>
        <select id="type" name="type" required>
            @foreach ($types as $type)
                <option value="{{ $type }}" @selected($val('type') === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="amount">Nominal (Rp)</label>
        <input id="amount" name="amount" type="number" min="0" step="1000" value="{{ $val('amount') }}" required>
        @error('amount') <div class="error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label for="effective_date">Berlaku sejak</label>
        <input id="effective_date" name="effective_date" type="date"
               value="{{ optional($component->effective_date)->format('Y-m-d') ?? $val('effective_date') }}">
    </div>
    <div class="field">
        <label for="end_date">Berakhir</label>
        <input id="end_date" name="end_date" type="date"
               value="{{ optional($component->end_date)->format('Y-m-d') ?? $val('end_date') }}">
        @error('end_date') <div class="error">{{ $message }}</div> @enderror
    </div>
</div>

<div class="field">
    <label for="recurring">
        <input type="hidden" name="recurring" value="0">
        <input id="recurring" name="recurring" type="checkbox" value="1" @checked((bool) $val('recurring', true))>
        Berulang tiap bulan
    </label>
</div>

<div class="field">
    <label for="notes">Catatan</label>
    <textarea id="notes" name="notes" rows="2" maxlength="2000">{{ $val('notes') }}</textarea>
</div>
