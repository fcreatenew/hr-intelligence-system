@extends('layouts.app')

@section('title', 'Saldo cuti')

@section('content')
    <div class="row">
        <div>
            <h1>Kuota cuti {{ $year }}</h1>
            <p class="sub" style="margin-bottom:0">
                Setiap jenis cuti memotong kuota jenisnya sendiri. Angka di tiap kolom adalah sisa/jatah hari.
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
                @foreach ($types as $type)
                    <th>{{ ucfirst($type) }}</th>
                @endforeach
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
                    @foreach ($types as $type)
                        @php($balance = $row['balances'][$type])
                        <td>
                            <strong>{{ $balance->remaining_days }}</strong><span class="muted">/{{ $balance->entitled_days }}</span>
                        </td>
                    @endforeach
                    <td>{{ $row['pending_days'] ?: '—' }}</td>
                    <td class="actions">
                        <a href="{{ route('leave-requests.create', ['employee_id' => $row['employee']->id]) }}" class="btn link">Ajukan</a>
                    </td>
                </tr>
            @empty
                <tr><td colspan="{{ count($types) + 3 }}" class="muted" style="padding:24px;text-align:center">Belum ada karyawan.</td></tr>
            @endforelse
        </tbody>
    </table>
@endsection
