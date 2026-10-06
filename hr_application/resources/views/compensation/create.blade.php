@extends('layouts.app')

@section('title', 'Tambah komponen kompensasi')

@section('content')
    <h1>Tambah komponen kompensasi</h1>

    @if ($errors->any())
        <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('compensation-components.store') }}" class="card">
        @csrf
        @include('compensation._form')
        <div class="row" style="margin-top:8px">
            <button type="submit" class="btn">Simpan</button>
            <a href="{{ route('compensation-components.index') }}" class="btn secondary">Batal</a>
        </div>
    </form>
@endsection
