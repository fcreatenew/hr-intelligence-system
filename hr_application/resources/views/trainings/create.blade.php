@extends('layouts.app')

@section('title', 'Tambah pelatihan')

@section('content')
    <h1>Tambah pelatihan</h1>
    <p class="sub">Isi detail pelatihan. Status <em>draft</em> belum dihitung sebagai partisipasi.</p>

    @if ($errors->any())
        <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('trainings.store') }}" class="card">
        @csrf
        @include('trainings._form')
        <div class="row" style="margin-top:8px">
            <button type="submit" class="btn">Simpan</button>
            <a href="{{ route('trainings.index') }}" class="btn secondary">Batal</a>
        </div>
    </form>
@endsection
