@php
    $val = fn ($key, $default = '') => old($key, $training->{$key} ?? $default);
@endphp

<div class="grid">
    <div class="field">
        <label for="title">Judul pelatihan</label>
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
        @error('category') <div class="error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label for="provider">Penyelenggara</label>
        <input id="provider" name="provider" value="{{ $val('provider') }}" maxlength="120">
    </div>
    <div class="field">
        <label for="mode">Mode</label>
        <select id="mode" name="mode" required>
            @foreach ($modes as $mode)
                <option value="{{ $mode }}" @selected($val('mode') === $mode)>{{ ucfirst($mode) }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="start_date">Tanggal mulai</label>
        <input id="start_date" name="start_date" type="date" required
               value="{{ optional($training->start_date)->format('Y-m-d') ?? $val('start_date') }}">
        @error('start_date') <div class="error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label for="end_date">Tanggal selesai</label>
        <input id="end_date" name="end_date" type="date"
               value="{{ optional($training->end_date)->format('Y-m-d') ?? $val('end_date') }}">
        @error('end_date') <div class="error">{{ $message }}</div> @enderror
    </div>
    <div class="field">
        <label for="duration_hours">Durasi (jam)</label>
        <input id="duration_hours" name="duration_hours" type="number" min="1" max="1000" value="{{ $val('duration_hours') }}">
    </div>
    <div class="field">
        <label for="cost">Biaya (Rp)</label>
        <input id="cost" name="cost" type="number" min="0" step="1000" value="{{ $val('cost', 0) }}">
    </div>
    <div class="field">
        <label for="quota">Kuota peserta</label>
        <input id="quota" name="quota" type="number" min="1" max="10000" value="{{ $val('quota') }}">
        <div class="hint">Kosongkan bila tidak dibatasi.</div>
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
    <label for="description">Deskripsi</label>
    <textarea id="description" name="description" rows="4" maxlength="2000">{{ $val('description') }}</textarea>
</div>
