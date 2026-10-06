@php
    $val = fn ($key, $default = '') => old($key, $document->{$key} ?? $default);
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
        <label for="title">Judul dokumen</label>
        <input id="title" name="title" value="{{ $val('title') }}" required maxlength="150">
        @error('title') <div class="error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label for="category">Kategori</label>
        <select id="category" name="category" required>
            @foreach ($categories as $category)
                <option value="{{ $category }}" @selected($val('category') === $category)>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="status">Status</label>
        <select id="status" name="status" required>
            @foreach ($statuses as $status)
                <option value="{{ $status }}" @selected($val('status') === $status)>{{ ucfirst($status) }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="issued_at">Tanggal terbit</label>
        <input id="issued_at" name="issued_at" type="date"
               value="{{ optional($document->issued_at)->format('Y-m-d') ?? $val('issued_at') }}">
    </div>
    <div class="field">
        <label for="expires_at">Berlaku sampai</label>
        <input id="expires_at" name="expires_at" type="date"
               value="{{ optional($document->expires_at)->format('Y-m-d') ?? $val('expires_at') }}">
        @error('expires_at') <div class="error">{{ $message }}</div> @enderror
    </div>
</div>

<div class="field">
    <label for="file_path">Tautan/berkas (opsional)</label>
    <input id="file_path" name="file_path" value="{{ $val('file_path') }}" maxlength="255">
    <div class="hint">Simpan metadata saja; jangan menaruh dokumen pribadi asli di repo.</div>
</div>

<div class="field">
    <label for="notes">Catatan</label>
    <textarea id="notes" name="notes" rows="2" maxlength="2000">{{ $val('notes') }}</textarea>
</div>
