@extends('layouts.app')

@section('title', 'Payroll '.$period->periode)

@section('content')
    <h1>Payroll {{ $period->periode }}</h1>
    <p class="sub">
        Status:
        @if ($period->isFinal())
            <span class="badge ok">final</span>
        @elseif ($period->isCalculated())
            <span class="badge">dihitung</span>
        @else
            <span class="badge off">draft</span>
        @endif
    </p>

    @if ($errors->any())
        <div class="alert err">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="row" style="margin-bottom:22px">
        @can('calculate', $period)
            <form method="POST" action="{{ route('payroll-periods.calculate', $period) }}">
                @csrf
                <button type="submit" class="btn">{{ $period->isCalculated() ? 'Hitung ulang' : 'Hitung payroll' }}</button>
            </form>
        @endcan

        @can('finalize', $period)
            <form method="POST" action="{{ route('payroll-periods.finalize', $period) }}"
                  onsubmit="return confirm('Finalisasi membekukan angka periode ini. Lanjutkan?')">
                @csrf
                <button type="submit" class="btn">Finalisasi</button>
            </form>
        @endcan

        @if ($period->isCalculated())
            <a class="btn secondary" href="{{ route('payroll-periods.export', $period) }}">Ekspor CSV</a>
        @endif

        <a class="btn secondary" href="{{ route('payroll-periods.index') }}">Kembali</a>
    </div>

    <div class="grid" style="margin-bottom:26px">
        <div class="card"><div class="muted">Karyawan</div><h2 style="margin:6px 0 0">{{ $summary['employee_count'] }}</h2></div>
        <div class="card"><div class="muted">Total gaji pokok</div><h2 style="margin:6px 0 0">Rp {{ number_format($summary['total_gaji_pokok'], 0, ',', '.') }}</h2></div>
        <div class="card"><div class="muted">Total tunjangan</div><h2 style="margin:6px 0 0">Rp {{ number_format($summary['total_tunjangan'], 0, ',', '.') }}</h2></div>
        <div class="card"><div class="muted">Total biaya lembur</div><h2 style="margin:6px 0 0">Rp {{ number_format($summary['total_biaya_lembur'], 0, ',', '.') }}</h2></div>
        <div class="card"><div class="muted">Total potongan</div><h2 style="margin:6px 0 0">Rp {{ number_format($summary['total_potongan'], 0, ',', '.') }}</h2></div>
        <div class="card"><div class="muted">Total dibayarkan</div><h2 style="margin:6px 0 0">Rp {{ number_format($summary['total_gaji'], 0, ',', '.') }}</h2></div>
    </div>

    @if ($period->isCalculated())
        <p class="sub">
            Anomali: <strong>{{ $summary['jumlah_anomali'] }}</strong> ·
            Perlu ditinjau: <strong>{{ $summary['jumlah_perlu_ditinjau'] }}</strong>
        </p>
    @endif

    <table>
        <thead>
            <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Departemen</th>
                <th>Pokok</th>
                <th>Tunjangan</th>
                <th>Lembur</th>
                <th>Potongan</th>
                <th>Total</th>
                <th>Anomali</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($items as $item)
                <tr>
                    <td class="muted">{{ $item->employee->nik }}</td>
                    <td>
                        {{ $item->employee->name }}
                        <div><a class="btn link" style="padding:0;font-size:12px" href="{{ route('payroll-periods.slip', [$period, $item]) }}">Lihat slip</a></div>
                    </td>
                    <td>{{ $item->departemen }}</td>
                    <td>Rp {{ number_format((float) $item->gaji_pokok, 0, ',', '.') }}</td>
                    <td>Rp {{ number_format((float) $item->tunjangan, 0, ',', '.') }}</td>
                    <td>{{ $item->lembur_jam }} jam<br><span class="muted">Rp {{ number_format((float) $item->biaya_lembur, 0, ',', '.') }}</span></td>
                    <td>Rp {{ number_format((float) $item->potongan, 0, ',', '.') }}</td>
                    <td><strong>Rp {{ number_format((float) $item->total_gaji, 0, ',', '.') }}</strong></td>
                    <td>
                        @if ($item->level_anomali === 'Anomali')
                            <span class="badge" style="border-color:#fecdca;background:#fef3f2;color:#b42318">{{ $item->level_anomali }}</span>
                        @elseif ($item->level_anomali)
                            <span class="badge">{{ $item->level_anomali }}</span>
                        @else
                            <span class="muted">—</span>
                        @endif
                        @if ($item->alasan_anomali && $item->alasan_anomali !== '-')
                            <div class="hint">{{ $item->alasan_anomali }}</div>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="muted">Belum ada baris payroll. Jalankan "Hitung payroll".</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $items->links() }}</div>
@endsection
