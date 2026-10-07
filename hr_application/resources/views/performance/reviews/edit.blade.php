@extends('layouts.app')

@section('title', 'Ubah review kinerja')

@section('content')
    <h1>Ubah review kinerja</h1>

    @if ($errors->any())
        <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('performance-reviews.update', $review) }}" class="card">
        @csrf
        @method('PUT')
        @include('performance.reviews._form')
        <div class="row" style="margin-top:8px">
            <button type="submit" class="btn">Simpan</button>
            <a href="{{ route('performance-reviews.index') }}" class="btn secondary">Batal</a>
        </div>
    </form>
@endsection
