@extends('layouts.app')

@section('title', 'Payroll')

@section('content')
    <h1>Payroll</h1>
    <p class="sub">Jalankan penggajian bulanan: hitung komponen dari absensi, tinjau anomali, lalu finalisasi.</p>

    @if ($errors->any())
        <div class="alert err">
            @foreach ($errors->all() as $error)
                <div>{{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="card" style="margin-bottom:22px">
        <h2 style="margin-top:0">Buka periode baru</h2>
        <form method="POST" action="{{ route('payroll-periods.store') }}" class="row">
            @csrf
            <div style="flex:0 0 200px">
                <label for="periode">Periode (YYYY-MM)</label>
                <input type="month" id="periode" name="periode" value="{{ old('periode', $defaultPeriode) }}" required>
            </div>
            <div style="align-self:flex-end">
                <button type="submit" class="btn">Buka periode</button>
            </div>
        </form>
    </div>

    <table>
        <thead>
            <tr>
                <th>Periode</th>
                <th>Status</th>
                <th>Karyawan</th>
                <th>Total</th>
                <th>Dihitung</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($periods as $period)
                <tr>
                    <td><strong>{{ $period->periode }}</strong></td>
                    <td>
                        @if ($period->status === \App\Models\PayrollPeriod::STATUS_FINAL)
                            <span class="badge ok">final</span>
                        @elseif ($period->status === \App\Models\PayrollPeriod::STATUS_CALCULATED)
                            <span class="badge">dihitung</span>
                        @else
                            <span class="badge off">draft</span>
                        @endif
                    </td>
                    <td>{{ $period->employee_count }}</td>
                    <td>Rp {{ number_format((float) $period->total_amount, 0, ',', '.') }}</td>
                    <td class="muted">{{ $period->calculated_at?->format('d M Y H:i') ?? '—' }}</td>
                    <td><a class="btn secondary" href="{{ route('payroll-periods.show', $period) }}">Detail</a></td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted">Belum ada periode payroll.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $periods->links() }}</div>
@endsection
