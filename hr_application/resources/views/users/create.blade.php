@extends('layouts.app')

@section('title', 'Tambah pengguna')

@section('content')
    <h1>Tambah pengguna</h1>
    <p class="sub">Akun ini dapat masuk ke HRIS sesuai perannya.</p>

    @if ($errors->any())
        <div class="alert err">
            <strong>{{ $errors->count() }} kolom perlu diperbaiki:</strong>
            <ul style="margin:6px 0 0 18px">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('users.store') }}">
        @csrf
        @include('users._form', ['optionalPassword' => false])

        <div class="row" style="margin-top:20px">
            <button type="submit" class="btn">Simpan</button>
            <a href="{{ route('users.index') }}" class="btn secondary">Batal</a>
        </div>
    </form>
@endsection
