@extends('layouts.app')

@section('title', 'Ubah dokumen')

@section('content')
    <h1>Ubah dokumen</h1>

    @if ($errors->any())
        <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('employee-documents.update', $document) }}" class="card">
        @csrf
        @method('PUT')
        @include('documents._form')
        <div class="row" style="margin-top:8px">
            <button type="submit" class="btn">Simpan</button>
            <a href="{{ route('employee-documents.index') }}" class="btn secondary">Batal</a>
        </div>
    </form>
@endsection
