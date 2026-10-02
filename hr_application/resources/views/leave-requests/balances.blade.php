@extends('layouts.app')

@section('title', 'Saldo cuti')

@section('content')
    <div class="row">
        <div>
            <h1>Saldo cuti {{ $year }}</h1>
            <p class="sub" style="margin-bottom:0">
                Cuti tahunan memotong saldo; cuti sakit/melahirkan/duka tidak.
            </p>
        </div>
        <span style="flex:1"></span>
        <a href="{{ route('leave-requests.index') }}" class="btn secondary">Daftar pengajuan</a>
    </div>

    <form method="GET" action="{{ route('leave-requests.balances') }}" class="card" style="margin:18px 0">
        <div class="grid">
            <div class="field" style="margin:0">
                <label for="year">Tahun</label>
                <input id="year" name="year" type="number" min="2000" max="2100" value="{{ $year }}">
            </div>
            <div class="field row" style="margin:0;align-self:end">
                <button type="submit" class="btn">Terapkan</button>
            </div>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>Karyawan</th>
                <th>Jatah</th>
                <th>Terpakai</th>
                <th>Tersisa</th>
                <th>Menunggu</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>
                        <a href="{{ route('employees.show', $row['employee']) }}">{{ $row['employee']->name }}</a>
                        <div class="muted" style="font-size:12px">{{ $row['employee']->nik }}</div>
                    </td>
                    <td>{{ $row['balance']->entitled_days }}</td>
                    <td>{{ $row['balance']->used_days }}</td>
                    <td><strong>{{ $row['balance']->remaining_days }}</strong></td>
                    <td>{{ $row['pending_days'] ?: '—' }}</td>
                    <td class="actions">
                        <a href="{{ route('leave-requests.create', ['employee_id' => $row['employee']->id]) }}" class="btn link">Ajukan</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted" style="padding:24px;text-align:center">Belum ada karyawan.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
