@extends('layouts.app')

@section('title', 'Ubah kandidat')

@section('content')
    <h1>Ubah kandidat</h1>
    <p class="sub">{{ $candidate->jobOpening?->title }}</p>

    @if ($errors->any())
        <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <div class="card">
        @include('recruitment._candidate-form', [
            'action' => route('candidates.update', $candidate),
            'method' => 'PUT',
        ])
    </div>
@endsection
