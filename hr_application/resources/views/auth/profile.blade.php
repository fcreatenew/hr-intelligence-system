@extends('layouts.app')

@section('title', 'Profil Saya')

@section('content')
    <div style="max-width:480px;margin:24px auto">
        <h1>Profil saya</h1>
        <p class="sub">Perbarui nama, email, atau kata sandi akun Anda.</p>

        @if (session('status'))
            <div class="alert ok">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert err">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="card">
            @csrf
            @method('PUT')

            <div class="field">
                <label for="name">Nama</label>
                <input id="name" name="name" value="{{ old('name', $user->name) }}" required>
            </div>

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $user->email) }}" required>
            </div>

            <div class="field">
                <label for="current_password">Kata sandi saat ini</label>
                <input id="current_password" name="current_password" type="password" required>
                <div class="hint">Wajib diisi untuk mengonfirmasi perubahan.</div>
            </div>

            <div class="field">
                <label for="password">Kata sandi baru (opsional)</label>
                <input id="password" name="password" type="password">
            </div>

            <div class="field">
                <label for="password_confirmation">Ulangi kata sandi baru</label>
                <input id="password_confirmation" name="password_confirmation" type="password">
            </div>

            <button type="submit" class="btn">Simpan perubahan</button>
        </form>
    </div>
@endsection
