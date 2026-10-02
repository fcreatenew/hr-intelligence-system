@extends('layouts.app')

@section('title', 'Tambah karyawan')

@section('content')
    <h1>Tambah karyawan</h1>
    <p class="sub">Data ini menjadi sumber fitur untuk seluruh model HR Intelligence.</p>

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

    <form method="POST" action="{{ route('employees.store') }}">
        @csrf
        @include('employees._form')

        <div class="row" style="margin-top:20px">
            <button type="submit" class="btn">Simpan</button>
            <a href="{{ route('employees.index') }}" class="btn secondary">Batal</a>
        </div>
    </form>
@endsection
