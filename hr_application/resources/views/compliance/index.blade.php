@extends('layouts.app')

@section('title', 'Kepatuhan & laporan statutori')

@section('content')
    <h1>Kepatuhan & laporan statutori</h1>
    <p class="sub">
        Draft laporan dihitung dari data payroll periode terpilih.
        Nilai bersifat <strong>estimasi</strong> dan wajib ditinjau sebelum pelaporan resmi.
    </p>

    @can('create', App\Models\StatutoryReport::class)
        <form method="POST" action="{{ route('statutory-reports.store') }}" class="card" style="margin-bottom:18px">
            @csrf
            <div class="grid">
                <div class="field" style="margin:0">
                    <label for="type">Jenis laporan</label>
                    <select id="type" name="type" required>
                        @foreach ($types as $type)
                            <option value="{{ $type }}">{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="field" style="margin:0">
                    <label for="period">Periode (YYYY-MM)</label>
                    <input id="period" name="period" required placeholder="{{ now()->format('Y-m') }}"
                           value="{{ old('period', now()->format('Y-m')) }}">
                    @error('period') <div class="error">{{ $message }}</div> @enderror
                </div>
                <div class="field" style="margin:0;align-self:end">
                    <button type="submit" class="btn">Buat laporan</button>
                </div>
            </div>
        </form>
    @endcan

    <table>
        <thead>
            <tr>
                <th>Jenis</th>
                <th>Periode</th>
                <th>Status</th>
                <th>Dibuat</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reports as $report)
                <tr>
                    <td>{{ ucfirst(str_replace('_', ' ', $report->type)) }}</td>
                    <td>{{ $report->period }}</td>
                    <td><span class="badge {{ $report->status === 'final' ? 'ok' : '' }}">{{ ucfirst($report->status) }}</span></td>
                    <td>{{ $report->generated_at?->format('d M Y H:i') ?? '—' }}</td>
                    <td class="actions"><a href="{{ route('statutory-reports.show', $report) }}" class="btn link">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted" style="padding:24px;text-align:center">Belum ada laporan.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $reports->links('pagination::simple-tailwind') }}</div>
@endsection
