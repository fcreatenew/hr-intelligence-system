@extends('layouts.app')

@section('title', $opening->title)

@section('content')
    <div class="row">
        <div>
            <h1>{{ $opening->title }}</h1>
            <p class="sub" style="margin-bottom:0">
                {{ $opening->department }} · {{ ucfirst($opening->employment_type) }}
                @if ($opening->location) · {{ $opening->location }} @endif
                · <span class="badge {{ $opening->status === 'dibuka' ? 'ok' : '' }}">{{ ucfirst($opening->status) }}</span>
            </p>
        </div>
        <span style="flex:1"></span>
        @can('update', $opening)
            <a href="{{ route('job-openings.edit', $opening) }}" class="btn secondary">Ubah</a>
        @endcan
        @can('delete', $opening)
            <form method="POST" action="{{ route('job-openings.destroy', $opening) }}"
                  onsubmit="return confirm('Hapus lowongan ini beserta kandidatnya?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn danger">Hapus</button>
            </form>
        @endcan
    </div>

    @if ($opening->description)
        <div class="card" style="margin:18px 0">
            <h2 style="margin-top:0;font-size:16px">Deskripsi</h2>
            <p style="white-space:pre-line;margin:0">{{ $opening->description }}</p>
            @if ($opening->requirements)
                <h2 style="margin:16px 0 6px;font-size:16px">Kualifikasi</h2>
                <p style="white-space:pre-line;margin:0">{{ $opening->requirements }}</p>
            @endif
        </div>
    @endif

    <div class="grid" style="grid-template-columns:2fr 1fr;align-items:start;gap:18px">
        <div>
            <h2 style="font-size:16px">Kandidat ({{ $opening->candidates->count() }})</h2>
            <table>
                <thead>
                    <tr>
                        <th>Nama</th>
                        <th>Tahap</th>
                        <th>Ekspektasi</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($opening->candidates as $candidate)
                        <tr>
                            <td>
                                {{ $candidate->name }}
                                <div class="muted" style="font-size:12px">{{ $candidate->email }}</div>
                            </td>
                            <td><span class="badge {{ $candidate->stage === 'diterima' ? 'ok' : '' }}">{{ str_replace('_', ' ', ucfirst($candidate->stage)) }}</span></td>
                            <td>{{ $candidate->expected_salary ? 'Rp '.number_format($candidate->expected_salary, 0, ',', '.') : '—' }}</td>
                            <td class="actions">
                                @can('update', $candidate)
                                    <a href="{{ route('candidates.edit', $candidate) }}" class="btn link">Ubah</a>
                                    @if ($candidate->stage !== 'diterima')
                                        <form method="POST" action="{{ route('candidates.hire', $candidate) }}" style="display:inline">
                                            @csrf
                                            <button type="submit" class="btn link">Terima</button>
                                        </form>
                                    @endif
                                @endcan
                                @can('delete', $candidate)
                                    <form method="POST" action="{{ route('candidates.destroy', $candidate) }}" style="display:inline"
                                          onsubmit="return confirm('Hapus kandidat ini?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn link danger">Hapus</button>
                                    </form>
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="muted" style="padding:24px;text-align:center">Belum ada kandidat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @can('create', App\Models\Candidate::class)
            <div class="card">
                <h2 style="margin-top:0;font-size:16px">Tambah kandidat</h2>
                @include('recruitment._candidate-form', ['action' => route('job-openings.candidates.store', $opening)])
            </div>
        @endcan
    </div>
@endsection
