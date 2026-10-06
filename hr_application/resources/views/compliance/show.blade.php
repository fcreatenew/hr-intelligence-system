@extends('layouts.app')

@section('title', 'Detail laporan statutori')

@section('content')
    <div class="row">
        <div>
            <h1>{{ ucfirst(str_replace('_', ' ', $report->type)) }}</h1>
            <p class="sub" style="margin-bottom:0">
                Periode {{ $report->period }} ·
                <span class="badge {{ $report->status === 'final' ? 'ok' : '' }}">{{ ucfirst($report->status) }}</span>
            </p>
        </div>
        <span style="flex:1"></span>
        @can('update', $report)
            @if ($report->status !== 'final')
                <form method="POST" action="{{ route('statutory-reports.finalize', $report) }}">
                    @csrf
                    <button type="submit" class="btn">Tandai final</button>
                </form>
            @endif
        @endcan
        <a href="{{ route('statutory-reports.index') }}" class="btn secondary">Kembali</a>
    </div>

    <div class="alert" style="margin-top:16px">
        Angka di bawah adalah estimasi berbasis data payroll. Verifikasi dengan aturan resmi sebelum pelaporan.
    </div>

    <div class="card" style="margin-top:16px">
        <dl>
            @foreach ((array) $report->payload as $key => $value)
                <dt>{{ ucfirst(str_replace('_', ' ', $key)) }}</dt>
                <dd>{{ is_numeric($value) ? number_format((float) $value, 2, ',', '.') : $value }}</dd>
            @endforeach
            <dt>Dibuat</dt>
            <dd>{{ $report->generated_at?->format('d M Y H:i') ?? '—' }}</dd>
            <dt>Dibuat oleh</dt>
            <dd>{{ $report->generator?->name ?? '—' }}</dd>
        </dl>
    </div>

    @can('delete', $report)
        <form method="POST" action="{{ route('statutory-reports.destroy', $report) }}" style="margin-top:16px"
              onsubmit="return confirm('Hapus laporan ini?')">
            @csrf
            @method('DELETE')
            <button type="submit" class="btn danger">Hapus laporan</button>
        </form>
    @endcan
@endsection
