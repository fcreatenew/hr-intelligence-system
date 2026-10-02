@extends('layouts.app')

@section('title', 'Laporan HR')

@section('content')
    <div class="row">
        <div>
            <h1>Laporan HR</h1>
            <p class="sub" style="margin-bottom:0">
                Headcount, rasio turnover, biaya gaji, dan rata-rata ketidakhadiran per departemen.
            </p>
        </div>
        <span style="flex:1"></span>
        <a href="{{ route('reports.export', ['from' => $filters['from'], 'to' => $filters['to']]) }}" class="btn">Ekspor CSV</a>
    </div>

    <form method="GET" action="{{ route('reports.index') }}" class="card" style="margin:18px 0">
        <div class="grid">
            <div class="field" style="margin:0">
                <label for="from">Dari</label>
                <input id="from" name="from" type="date" value="{{ $filters['from'] }}">
            </div>
            <div class="field" style="margin:0">
                <label for="to">Sampai</label>
                <input id="to" name="to" type="date" value="{{ $filters['to'] }}">
            </div>
            <div class="field row" style="margin:0;align-self:end">
                <button type="submit" class="btn">Terapkan</button>
                <a href="{{ route('reports.index') }}" class="btn secondary">Reset</a>
            </div>
        </div>
        <p class="hint" style="margin-top:10px">
            Periode hanya memengaruhi kolom ketidakhadiran. Rasio turnover dihitung dari
            status karyawan saat ini karena tanggal keluar belum tersimpan di data karyawan.
        </p>
    </form>

    <div class="grid" style="margin-bottom:22px">
        <div class="card">
            <div class="muted" style="font-size:12px">Total karyawan</div>
            <div style="font-size:26px;font-weight:600">{{ $totals['total'] }}</div>
            <div class="muted" style="font-size:12px">{{ $totals['aktif'] }} aktif &middot; {{ $totals['non_aktif'] }} non-aktif</div>
        </div>
        <div class="card">
            <div class="muted" style="font-size:12px">Rasio turnover</div>
            <div style="font-size:26px;font-weight:600">{{ $totals['turnover_rate'] }}%</div>
            <div class="muted" style="font-size:12px">non-aktif / total</div>
        </div>
        <div class="card">
            <div class="muted" style="font-size:12px">Total gaji bulanan</div>
            <div style="font-size:26px;font-weight:600">Rp {{ number_format($totals['total_gaji'], 0, ',', '.') }}</div>
            <div class="muted" style="font-size:12px">karyawan aktif</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Departemen</th>
                <th>Karyawan</th>
                <th>Aktif</th>
                <th>Turnover</th>
                <th>Total gaji</th>
                <th>Rata-rata tidak hadir</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($departments as $row)
                <tr>
                    <td><strong>{{ $row['department'] }}</strong></td>
                    <td>{{ $row['total'] }}</td>
                    <td>{{ $row['aktif'] }}</td>
                    <td>{{ $row['turnover_rate'] }}%</td>
                    <td>Rp {{ number_format($row['total_gaji'], 0, ',', '.') }}</td>
                    <td>{{ $row['rata_tidak_hadir'] }} hari</td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted" style="padding:24px;text-align:center">Belum ada data karyawan.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
