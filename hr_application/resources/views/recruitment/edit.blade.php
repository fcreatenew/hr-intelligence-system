@extends('layouts.app')

@section('title', 'Ubah lowongan')

@section('content')
    <h1>Ubah lowongan</h1>

    @if ($errors->any())
        <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('job-openings.update', $opening) }}" class="card">
        @csrf
        @method('PUT')
        @include('recruitment._form')
        <div class="row" style="margin-top:8px">
            <button type="submit" class="btn">Simpan</button>
            <a href="{{ route('job-openings.show', $opening) }}" class="btn secondary">Batal</a>
        </div>
    </form>
@endsection
