@extends('layouts.app')

@section('title', 'Rekrutmen')

@section('content')
    <div class="row">
        <div>
            <h1>Rekrutmen</h1>
            <p class="sub" style="margin-bottom:0">{{ $openings->total() }} lowongan.</p>
        </div>
        <span style="flex:1"></span>
        @can('create', App\Models\JobOpening::class)
            <a href="{{ route('job-openings.create') }}" class="btn">Tambah lowongan</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('job-openings.index') }}" class="card" style="margin:18px 0">
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
                <label for="department">Departemen</label>
                <select id="department" name="department">
                    <option value="">Semua</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department }}" @selected($filters['department'] === $department)>{{ $department }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field row" style="margin:0;align-self:end">
                <button type="submit" class="btn">Terapkan</button>
                <a href="{{ route('job-openings.index') }}" class="btn secondary">Reset</a>
            </div>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>Posisi</th>
                <th>Departemen</th>
                <th>Jenis</th>
                <th>Kandidat</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($openings as $opening)
                <tr>
                    <td>
                        <a href="{{ route('job-openings.show', $opening) }}">{{ $opening->title }}</a>
                        @if ($opening->location)
                            <div class="muted" style="font-size:12px">{{ $opening->location }}</div>
                        @endif
                    </td>
                    <td>{{ $opening->department }}</td>
                    <td>{{ ucfirst($opening->employment_type) }}</td>
                    <td>{{ $opening->candidates_count }}@if ($opening->quota) <span class="muted">/ {{ $opening->quota }}</span>@endif</td>
                    <td><span class="badge {{ $opening->status === 'dibuka' ? 'ok' : '' }}">{{ ucfirst($opening->status) }}</span></td>
                    <td class="actions"><a href="{{ route('job-openings.show', $opening) }}" class="btn link">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted" style="padding:24px;text-align:center">Belum ada lowongan.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $openings->links() }}</div>
@endsection
