@extends('layouts.app')

@section('title', 'Lupa Kata Sandi')

@section('content')
    <div style="max-width:380px;margin:40px auto">
        <h1>Lupa kata sandi</h1>
        <p class="sub">Masukkan email akun HR Anda; kami akan mengirim tautan untuk mengatur ulang kata sandi.</p>

        @if (session('status'))
            <div class="alert ok">{{ session('status') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert err">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="card">
            @csrf

            <div class="field">
                <label for="email">Email</label>
                <input id="email" name="email" type="email" value="{{ old('email') }}" required autofocus>
            </div>

            <button type="submit" class="btn" style="width:100%">Kirim tautan reset</button>
        </form>

        <p class="sub" style="text-align:center;margin-top:16px">
            <a href="{{ route('login') }}">Kembali ke halaman masuk</a>
        </p>
    </div>
@endsection
