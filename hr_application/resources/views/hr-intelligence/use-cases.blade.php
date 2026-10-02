@extends('layouts.app')

@section('title', 'Prediksi ML')

@section('content')
    <div>
        <h1>Prediksi ML</h1>
        <p class="sub">Hasil prediksi terbaru dari lima use case model HR Intelligence.</p>
    </div>

    <div class="grid">
        @foreach ($useCases as $uc)
            <div class="card">
                <h2 style="margin-top:0">{{ $uc['label'] }}</h2>
                <p class="muted" style="margin:0 0 12px">
                    @if ($uc['versi_model'])
                        Model {{ $uc['versi_model'] }}
                    @else
                        Metadata model tidak tersedia
                    @endif
                </p>

                <dl style="grid-template-columns:120px 1fr">
                    <dt>Total terbaru</dt><dd>{{ $uc['total'] }}</dd>
                    <dt>Ditandai</dt><dd>{{ $uc['flagged'] }}</dd>
                    <dt>Terakhir</dt><dd>{{ $uc['terakhir'] ? \Illuminate\Support\Carbon::parse($uc['terakhir'])->format('d M Y H:i') : '—' }}</dd>
                </dl>

                <div class="actions" style="margin-top:16px">
                    <a href="{{ route('hr-intelligence.use-case.show', $uc['key']) }}" class="btn secondary">Lihat hasil</a>
                    <form method="POST" action="{{ route('hr-intelligence.use-case.run', $uc['key']) }}">
                        @csrf
                        <button type="submit" class="btn">Jalankan</button>
                    </form>
                </div>
            </div>
        @endforeach
    </div>
@endsection
