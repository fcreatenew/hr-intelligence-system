@extends('layouts.app')

@section('title', $label)

@section('content')
    <div class="row">
        <div>
            <h1>{{ $label }}</h1>
            <p class="sub" style="margin-bottom:0">{{ $predictions->count() }} hasil prediksi terbaru.</p>
        </div>
        <span style="flex:1"></span>
        <a href="{{ route('hr-intelligence.dashboard') }}" class="btn secondary">Semua use case</a>
        <form method="POST" action="{{ route('hr-intelligence.use-case.run', $useCase) }}">
            @csrf
            <button type="submit" class="btn">Jalankan prediksi</button>
        </form>
    </div>

    @if ($predictions->isEmpty())
        <div class="card" style="margin-top:18px">
            <p class="muted" style="margin:0">Belum ada hasil untuk use case ini. Tekan "Jalankan prediksi" untuk mengantrikan batch.</p>
        </div>
    @else
        <table style="margin-top:18px">
            <thead>
                <tr>
                    <th>Karyawan</th>
                    <th>Skor</th>
                    <th>Label</th>
                    <th>Versi model</th>
                    <th>Waktu</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($predictions as $p)
                    <tr>
                        <td>{{ $p->employee_id }}</td>
                        <td>{{ number_format($p->skor, 3, ',', '.') }}</td>
                        <td>
                            <span class="badge {{ $p->is_flag ? 'off' : 'ok' }}">{{ $p->label }}</span>
                        </td>
                        <td class="muted">{{ $p->model_version ?: '—' }}</td>
                        <td class="muted">{{ $p->predicted_at?->format('d M Y H:i') ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
