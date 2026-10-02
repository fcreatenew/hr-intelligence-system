@extends('layouts.app')

@section('title', 'Tambah absensi')

@section('content')
    <h1>Tambah catatan absensi</h1>
    <p class="sub">Satu karyawan hanya boleh punya satu catatan per tanggal.</p>

    @if ($errors->any())
        <div class="alert err">
            <ul style="margin:0 0 0 18px">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('attendances.store') }}" class="card">
        @csrf
        @include('attendances._form')

        <div class="row" style="margin-top:18px">
            <button type="submit" class="btn">Simpan</button>
            <a href="{{ route('attendances.index') }}" class="btn secondary">Batal</a>
        </div>
    </form>
@endsection
