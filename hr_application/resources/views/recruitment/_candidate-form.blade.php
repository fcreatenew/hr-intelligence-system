@php
    $candidate = $candidate ?? null;
    $val = fn ($key, $default = '') => old($key, $candidate->{$key} ?? $default);
@endphp

<form method="POST" action="{{ $action }}">
    @csrf
    @isset($method)
        @method($method)
    @endisset
    <div class="field">
        <label for="name">Nama</label>
        <input id="name" name="name" value="{{ $val('name') }}" required maxlength="120">
    </div>
    <div class="field">
        <label for="email">Email</label>
        <input id="email" name="email" type="email" value="{{ $val('email') }}" required maxlength="150">
    </div>
    <div class="field">
        <label for="phone">Telepon</label>
        <input id="phone" name="phone" value="{{ $val('phone') }}" maxlength="30">
    </div>
    <div class="field">
        <label for="source">Sumber</label>
        <input id="source" name="source" value="{{ $val('source') }}" maxlength="40">
    </div>
    <div class="field">
        <label for="stage">Tahap</label>
        <select id="stage" name="stage" required>
            @foreach ($stages as $stage)
                <option value="{{ $stage }}" @selected($val('stage', 'melamar') === $stage)>{{ str_replace('_', ' ', ucfirst($stage)) }}</option>
            @endforeach
        </select>
    </div>
    <div class="field">
        <label for="expected_salary">Ekspektasi gaji (Rp)</label>
        <input id="expected_salary" name="expected_salary" type="number" min="0" step="100000" value="{{ $val('expected_salary') }}">
    </div>
    <div class="field">
        <label for="applied_at">Tanggal melamar</label>
        <input id="applied_at" name="applied_at" type="date"
               value="{{ optional($candidate?->applied_at)->format('Y-m-d') ?? $val('applied_at', now()->format('Y-m-d')) }}">
    </div>
    <div class="field">
        <label for="notes">Catatan</label>
        <textarea id="notes" name="notes" rows="2" maxlength="2000">{{ $val('notes') }}</textarea>
    </div>
    <button type="submit" class="btn">Simpan</button>
</form>
