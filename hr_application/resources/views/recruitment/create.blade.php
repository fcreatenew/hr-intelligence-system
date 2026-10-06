@extends('layouts.app')

@section('title', 'Tambah lowongan')

@section('content')
    <h1>Tambah lowongan</h1>
    <p class="sub">Lowongan <em>draft</em> belum dipublikasikan ke kandidat.</p>

    @if ($errors->any())
        <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('job-openings.store') }}" class="card">
        @csrf
        @include('recruitment._form')
        <div class="row" style="margin-top:8px">
            <button type="submit" class="btn">Simpan</button>
            <a href="{{ route('job-openings.index') }}" class="btn secondary">Batal</a>
        </div>
    </form>
@endsection
