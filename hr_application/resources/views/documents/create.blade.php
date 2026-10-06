@extends('layouts.app')

@section('title', 'Tambah dokumen')

@section('content')
    <h1>Tambah dokumen</h1>

    @if ($errors->any())
        <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('employee-documents.store') }}" class="card">
        @csrf
        @include('documents._form')
        <div class="row" style="margin-top:8px">
            <button type="submit" class="btn">Simpan</button>
            <a href="{{ route('employee-documents.index') }}" class="btn secondary">Batal</a>
        </div>
    </form>
@endsection
