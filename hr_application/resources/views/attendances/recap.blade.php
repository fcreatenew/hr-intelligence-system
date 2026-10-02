@extends('layouts.app')

@section('title', 'Rekap absensi')

@section('content')
    <div class="row">
        <div>
            <h1>Rekap absensi</h1>
            <p class="sub" style="margin-bottom:0">
                Dua kolom terakhir adalah angka yang benar-benar dikirim ke model ML —
                dihitung dari catatan di tabel ini, bukan diisi manual.
            </p>
        </div>
        <span style="flex:1"></span>
        <a href="{{ route('attendances.index') }}" class="btn secondary">Daftar absensi</a>
    </div>

    <form method="GET" action="{{ route('attendances.recap') }}" class="card" style="margin:18px 0">
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
                <a href="{{ route('attendances.recap') }}" class="btn secondary">Reset</a>
            </div>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>Karyawan</th>
                <th>Hadir</th>
                <th>Terlambat</th>
                <th>Tidak hadir</th>
                <th>Cuti</th>
                <th>Total terlambat</th>
                <th>Total lembur</th>
                <th>absen 90hr</th>
                <th>absen 1thn</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>
                        <a href="{{ route('employees.show', $row['employee']) }}">{{ $row['employee']->name }}</a>
                        <div class="muted" style="font-size:12px">{{ $row['employee']->nik }}</div>
                    </td>
                    <td>{{ $row['hadir'] }}</td>
                    <td>{{ $row['terlambat'] }}</td>
                    <td>{{ $row['tidak_hadir'] }}</td>
                    <td>{{ $row['cuti'] }}</td>
                    <td>{{ $row['late_minutes'] }} mnt</td>
                    <td>{{ $row['overtime_minutes'] }} mnt</td>
                    <td><strong>{{ $row['riwayat_absen_90hari'] }}</strong></td>
                    <td><strong>{{ $row['hari_absen_setahun'] }}</strong></td>
                </tr>
            @empty
                <tr><td colspan="9" class="muted" style="padding:24px;text-align:center">Belum ada karyawan.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
