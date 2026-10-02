@php
    $val = fn (string $key, $default = '') => old($key, $user->{$key} ?? $default);
@endphp

<div class="card">
    <div class="grid">
        <div class="field">
            <label for="name">Nama</label>
            <input id="name" name="name" value="{{ $val('name') }}" required>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ $val('email') }}" required>
            @error('email') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="role">Peran</label>
            <select id="role" name="role" required>
                @foreach ($roles as $value => $label)
                    <option value="{{ $value }}" @selected($val('role') === $value)>{{ $label }}</option>
                @endforeach
            </select>
            @error('role') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="password">Kata sandi @if ($optionalPassword) (kosongkan bila tidak diubah) @endif</label>
            <input id="password" name="password" type="password" autocomplete="new-password" @unless ($optionalPassword) required @endunless>
            <div class="hint">Minimal 8 karakter.</div>
            @error('password') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="password_confirmation">Ulangi kata sandi</label>
            <input id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password">
        </div>
    </div>
</div>
