@php
    $val = fn ($key, $default = '') => old($key, $goal->{$key} ?? $default);
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
        <label for="period">Periode</label>
        <input id="period" name="period" value="{{ $val('period') }}" required maxlength="10" placeholder="2025-Q1">
        @error('period') <div class="error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label for="weight">Bobot (%)</label>
        <input id="weight" name="weight" type="number" min="0" max="100" value="{{ $val('weight', 0) }}">
    </div>
    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status" required>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected($val('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
</div>

<div class="field">
    <label for="title">Sasaran</label>
    <input id="title" name="title" value="{{ $val('title') }}" required maxlength="150">
    @error('title') <div class="error">{{ $message }}</div> @enderror
</div>

<div class="grid">
    <div class="field">
        <label for="target">Target</label>
        <input id="target" name="target" type="number" min="0" step="0.01" value="{{ $val('target') }}">
    </div>
    <div class="field">
        <label for="achievement">Capaian</label>
        <input id="achievement" name="achievement" type="number" min="0" step="0.01" value="{{ $val('achievement') }}">
    </div>
</div>

<div class="field">
    <label for="description">Deskripsi</label>
    <textarea id="description" name="description" rows="2" maxlength="2000">{{ $val('description') }}</textarea>
</div>
