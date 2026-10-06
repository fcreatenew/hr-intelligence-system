@php
    $val = fn ($key, $default = '') => old($key, $opening->{$key} ?? $default);
@endphp

<div class="grid">
    <div class="field">
        <label for="title">Judul posisi</label>
        <input id="title" name="title" value="{{ $val('title') }}" required maxlength="150">
        @error('title') <div class="error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label for="department">Departemen</label>
        <input id="department" name="department" value="{{ $val('department') }}" required maxlength="80">
        @error('department') <div class="error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label for="employment_type">Jenis kerja</label>
        <select id="employment_type" name="employment_type" required>
            @foreach ($employmentTypes as $type)
                <option value="{{ $type }}" @selected($val('employment_type') === $type)>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="location">Lokasi</label>
        <input id="location" name="location" value="{{ $val('location') }}" maxlength="120">
    </div>
    <div class="field">
        <label for="quota">Kuota</label>
        <input id="quota" name="quota" type="number" min="1" max="1000" value="{{ $val('quota', 1) }}">
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
        <label for="opened_at">Tanggal dibuka</label>
        <input id="opened_at" name="opened_at" type="date"
               value="{{ optional($opening->opened_at)->format('Y-m-d') ?? $val('opened_at') }}">
    </div>
    <div class="field">
        <label for="closed_at">Tanggal ditutup</label>
        <input id="closed_at" name="closed_at" type="date"
               value="{{ optional($opening->closed_at)->format('Y-m-d') ?? $val('closed_at') }}">
        @error('closed_at') <div class="error">{{ $message }}</div> @enderror
    </div>
</div>

<div class="field">
    <label for="description">Deskripsi</label>
    <textarea id="description" name="description" rows="3" maxlength="5000">{{ $val('description') }}</textarea>
</div>

<div class="field">
    <label for="requirements">Kualifikasi</label>
    <textarea id="requirements" name="requirements" rows="3" maxlength="5000">{{ $val('requirements') }}</textarea>
</div>
