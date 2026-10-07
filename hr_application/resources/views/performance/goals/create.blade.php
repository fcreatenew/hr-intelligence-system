@extends('layouts.app')

@section('title', 'Tambah sasaran kinerja')

@section('content')
    <h1>Tambah sasaran kinerja</h1>

    @if ($errors->any())
        <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('performance-goals.store') }}" class="card">
        @csrf
        @include('performance.goals._form')
        <div class="row" style="margin-top:8px">
            <button type="submit" class="btn">Simpan</button>
            <a href="{{ route('performance-goals.index') }}" class="btn secondary">Batal</a>
        </div>
    </form>
@endsection
