@extends('layouts.app')

@section('title', 'Masuk')

@section('content')
    <div style="max-width:380px;margin:40px auto">
        <h1>Masuk</h1>
        <p class="sub">Gunakan akun HR untuk mengelola data karyawan.</p>

        @if ($errors->any())
            <div class="alert err">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('login.store') }}" class="card">
            @csrf

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            </div>

            <div class="field">
                <label for="password">Kata sandi</label>
                <input id="password" name="password" type="password" required>
            </div>

            <div class="field row">
                <input id="remember" name="remember" type="checkbox" value="1" style="width:auto">
                <label for="remember" style="margin:0;font-weight:400">Ingat saya</label>
            </div>

            <button type="submit" class="btn" style="width:100%">Masuk</button>
        </form>

        <p class="sub" style="text-align:center;margin-top:16px">
            <a href="{{ route('password.request') }}">Lupa kata sandi?</a>
        </p>
    </div>
@endsection
