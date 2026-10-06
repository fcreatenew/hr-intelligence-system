@php
    $val = fn ($key, $default = '') => old($key, $review->{$key} ?? $default);
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
    </div>
    <div class="field">
        <label for="review_date">Tanggal review</label>
        <input id="review_date" name="review_date" type="date"
               value="{{ optional($review->review_date)->format('Y-m-d') ?? $val('review_date') }}">
    </div>
    <div class="field">
        <label for="rating">Rating (1–5)</label>
        <select id="rating" name="rating">
            <option value="">— Belum dinilai —</option>
            @for ($i = 1; $i <= 5; $i++)
                <option value="{{ $i }}" @selected((int) $val('rating') === $i)>{{ $i }}</option>
            @endfor
        </select>
        <div class="hint">Kategori (A–D) dihitung otomatis dari rating.</div>
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
    <label for="strengths">Kekuatan</label>
    <textarea id="strengths" name="strengths" rows="2" maxlength="2000">{{ $val('strengths') }}</textarea>
</div>
<div class="field">
    <label for="improvements">Area perbaikan</label>
    <textarea id="improvements" name="improvements" rows="2" maxlength="2000">{{ $val('improvements') }}</textarea>
</div>
