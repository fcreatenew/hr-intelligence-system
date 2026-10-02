@extends('layouts.app')

@section('title', 'Ubah absensi')

@section('content')
    <h1>Ubah catatan absensi</h1>
    <p class="sub">{{ $attendance->employee?->name }} — {{ $attendance->date->format('d M Y') }}</p>

    @if ($errors->any())
        <div class="alert err">
            <ul style="margin:0 0 0 18px">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form method="POST" action="{{ route('attendances.update', $attendance) }}" class="card">
        @csrf
        @method('PUT')
        @include('attendances._form')

        <div class="row" style="margin-top:18px">
            <button type="submit" class="btn">Simpan perubahan</button>
            <a href="{{ route('attendances.index') }}" class="btn secondary">Batal</a>
        </div>
    </form>
@endsection
