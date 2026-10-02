@extends('layouts.app')

@section('title', 'Ubah ' . $employee->name)

@section('content')
    <h1>Ubah karyawan</h1>
    <p class="sub">{{ $employee->name }} — {{ $employee->nik }}</p>

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

    <form method="POST" action="{{ route('employees.update', $employee) }}">
        @csrf
        @method('PUT')
        @include('employees._form')

        <div class="row" style="margin-top:20px">
            <button type="submit" class="btn">Simpan perubahan</button>
            <a href="{{ route('employees.show', $employee) }}" class="btn secondary">Batal</a>
        </div>
    </form>
@endsection
