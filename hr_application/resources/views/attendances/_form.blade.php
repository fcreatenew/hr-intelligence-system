@php
    $val = fn (string $key, $default = '') => old($key, $attendance->{$key} ?? $default);
@endphp

<div class="grid">
    <div class="field">
        <label for="employee_id">Karyawan</label>
        <select id="employee_id" name="employee_id" required>
            <option value="">— pilih —</option>
            @foreach ($employees as $employee)
                <option value="{{ $employee->id }}" @selected((string) $val('employee_id') === (string) $employee->id)>
                    {{ $employee->name }} ({{ $employee->nik }})
                </option>
            @endforeach
        </select>
        @error('employee_id') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="field">
        <label for="date">Tanggal</label>
        <input id="date" name="date" type="date"
               value="{{ old('date', optional($attendance->date)->format('Y-m-d')) }}" required>
        @error('date') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status" required>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected($val('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
        @error('status') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="field">
        <label for="check_in">Jam masuk</label>
        <input id="check_in" name="check_in" type="time" value="{{ $val('check_in') }}">
        @error('check_in') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="field">
        <label for="check_out">Jam keluar</label>
        <input id="check_out" name="check_out" type="time" value="{{ $val('check_out') }}">
        @error('check_out') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="field">
        <label for="late_minutes">Terlambat (menit)</label>
        <input id="late_minutes" name="late_minutes" type="number" min="0" max="1440" value="{{ $val('late_minutes', 0) }}">
        @error('late_minutes') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="field">
        <label for="overtime_minutes">Lembur (menit)</label>
        <input id="overtime_minutes" name="overtime_minutes" type="number" min="0" max="1440" value="{{ $val('overtime_minutes', 0) }}">
        @error('overtime_minutes') <div class="error">{{ $message }}</div> @enderror
    </div>

    <div class="field">
        <label for="note">Catatan</label>
        <input id="note" name="note" value="{{ $val('note') }}" maxlength="255">
    </div>
</div>

<p class="hint">
    Status <strong>izin</strong>, <strong>sakit</strong>, dan <strong>alpha</strong> dihitung
    sebagai hari tidak hadir dan otomatis menjadi fitur model absensi &amp; kinerja.
    Status <strong>cuti</strong> tidak dihitung karena sudah direncanakan.
</p>
