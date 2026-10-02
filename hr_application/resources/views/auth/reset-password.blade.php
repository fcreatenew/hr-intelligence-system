@extends('layouts.app')

@section('title', 'Atur Ulang Kata Sandi')

@section('content')
    <div style="max-width:380px;margin:40px auto">
        <h1>Atur ulang kata sandi</h1>
        <p class="sub">Buat kata sandi baru untuk akun Anda.</p>

        @if ($errors->any())
            <div class="alert err">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.update') }}" class="card">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email', $email) }}" required>
            </div>

            <div class="field">
                <label for="password">Kata sandi baru</label>
                <input id="password" name="password" type="password" required autofocus>
            </div>

            <div class="field">
                <label for="password_confirmation">Ulangi kata sandi baru</label>
                <input id="password_confirmation" name="password_confirmation" type="password" required>
            </div>

            <button type="submit" class="btn" style="width:100%">Simpan kata sandi</button>
        </form>
    </div>
@endsection
