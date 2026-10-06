@extends('layouts.app')

@section('title', 'Katalog pelatihan')

@section('content')
    <div class="row">
        <div>
            <h1>Katalog pelatihan</h1>
            <p class="sub" style="margin-bottom:0">{{ $trainings->total() }} pelatihan.</p>
        </div>
        <span style="flex:1"></span>
        @can('create', App\Models\Training::class)
            <a href="{{ route('trainings.create') }}" class="btn">Tambah pelatihan</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('trainings.index') }}" class="card" style="margin:18px 0">
        <div class="grid">
            <div class="field" style="margin:0">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="margin:0">
                <label for="category">Kategori</label>
                <select id="category" name="category">
                    <option value="">Semua</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field row" style="margin:0;align-self:end">
                <button type="submit" class="btn">Terapkan</button>
                <a href="{{ route('trainings.index') }}" class="btn secondary">Reset</a>
            </div>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>Judul</th>
                <th>Kategori</th>
                <th>Jadwal</th>
                <th>Peserta</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($trainings as $training)
                <tr>
                    <td>
                        <a href="{{ route('trainings.show', $training) }}">{{ $training->title }}</a>
                        @if ($training->provider)
                            <div class="muted" style="font-size:12px">{{ $training->provider }}</div>
                        @endif
                    </td>
                    <td>{{ ucfirst(str_replace('_', ' ', $training->category)) }}</td>
                    <td>
                        {{ $training->start_date?->format('d M Y') }}
                        @if ($training->end_date)
                            <span class="muted">– {{ $training->end_date->format('d M Y') }}</span>
                        @endif
                    </td>
                    <td>{{ $training->participants_count }}@if ($training->quota) <span class="muted">/ {{ $training->quota }}</span>@endif</td>
                    <td><span class="badge {{ $training->status === 'selesai' ? 'ok' : '' }}">{{ ucfirst($training->status) }}</span></td>
                    <td class="actions"><a href="{{ route('trainings.show', $training) }}" class="btn link">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted" style="padding:24px;text-align:center">Belum ada pelatihan.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $trainings->links('pagination::simple-tailwind') }}</div>
@endsection
