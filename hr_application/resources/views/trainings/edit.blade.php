@extends('layouts.app')

@section('title', 'Ubah pelatihan')

@section('content')
    <h1>Ubah pelatihan</h1>
    <p class="sub">{{ $training->title }}</p>

    @if ($errors->any())
        <div class="alert err">{{ $errors->first() }}</div>
    @endif

    <form method="POST" action="{{ route('trainings.update', $training) }}" class="card">
        @csrf
        @method('PUT')
        @include('trainings._form')
        <div class="row" style="margin-top:8px">
            <button type="submit" class="btn">Simpan perubahan</button>
            <a href="{{ route('trainings.show', $training) }}" class="btn secondary">Batal</a>
        </div>
    </form>

    @can('delete', $training)
        <form method="POST" action="{{ route('trainings.destroy', $training) }}" style="margin-top:16px"
              onsubmit="return confirm('Hapus pelatihan ini beserta partisipannya?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn danger">Hapus pelatihan</button>
        </form>
    @endcan
@endsection
