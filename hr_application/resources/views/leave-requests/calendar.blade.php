@extends('layouts.app')

@section('title', 'Kalender cuti tim')

@section('content')
    <div class="row">
        <div>
            <h1>Kalender cuti tim</h1>
            <p class="sub" style="margin-bottom:0">
                Cuti disetujui bulan {{ $month->translatedFormat('F Y') }}.
                {{ $employeeCount }} karyawan, {{ $leaveDayCount }} hari cuti.
            </p>
        </div>
        <span style="flex:1"></span>
        <a href="{{ route('leave-requests.index') }}" class="btn secondary">Daftar pengajuan</a>
        <a href="{{ route('leave-requests.create') }}" class="btn">Ajukan cuti</a>
    </div>

    <div class="row" style="margin:18px 0">
        <a href="{{ route('leave-requests.calendar', ['month' => $previousMonth]) }}" class="btn secondary">&larr; Sebelumnya</a>
        <strong style="align-self:center">{{ $month->translatedFormat('F Y') }}</strong>
        <a href="{{ route('leave-requests.calendar', ['month' => $nextMonth]) }}" class="btn secondary">Berikutnya &rarr;</a>
        <span style="flex:1"></span>
        <form method="GET" action="{{ route('leave-requests.calendar') }}" class="row" style="margin:0">
            <input type="month" name="month" value="{{ $month->format('Y-m') }}" aria-label="Pilih bulan">
            <button type="submit" class="btn">Tampilkan</button>
        </form>
    </div>

    <table class="calendar">
        <thead>
            <tr>
                @foreach ($weekdays as $weekday)
                    <th style="text-align:center">{{ $weekday }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach (array_chunk($days, 7) as $week)
                <tr>
                    @foreach ($week as $day)
                        @php
                            $cellBg = ! $day['inMonth'] ? '#f7f7f8' : ($day['isWeekend'] || $day['holiday'] ? '#fff6f6' : '#fff');
                        @endphp
                        <td style="vertical-align:top;height:110px;background:{{ $cellBg }}">
                            <div class="muted" style="font-size:12px;font-weight:{{ $day['isToday'] ? '700' : '400' }}">
                                {{ $day['date']->day }}
                                @if ($day['holiday'])
                                    <span title="{{ $day['holiday'] }}" style="color:#b91c1c">•</span>
                                @endif
                            </div>
                            @if ($day['holiday'])
                                <div style="font-size:11px;color:#b91c1c;line-height:1.2">{{ $day['holiday'] }}</div>
                            @endif
                            @foreach ($day['leaves'] as $leave)
                                <div style="margin-top:4px;font-size:12px;line-height:1.3">
                                    <a href="{{ route('leave-requests.show', $leave['id']) }}">{{ $leave['name'] }}</a>
                                    <div class="muted">{{ $leave['type'] }}</div>
                                </div>
                            @endforeach
                        </td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>

    <p class="muted" style="margin-top:12px;font-size:13px">
        Hanya pengajuan berstatus <strong>disetujui</strong> yang tampil. Sel abu-abu
        berada di luar bulan terpilih dan tetap ditampilkan agar pekan tidak terpotong.
    </p>
@endsection
