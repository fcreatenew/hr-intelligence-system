@extends('layouts.app')

@section('title', 'Review kinerja')

@section('content')
    <div class="row">
        <div>
            <h1>Review kinerja</h1>
            <p class="sub" style="margin-bottom:0">{{ $reviews->total() }} review.</p>
        </div>
        <span style="flex:1"></span>
        @can('create', App\Models\PerformanceReview::class)
            <a href="{{ route('performance-reviews.create') }}" class="btn">Tambah review</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('performance-reviews.index') }}" class="card" style="margin:18px 0">
        <div class="grid">
            <div class="field" style="margin:0">
                <label for="period">Periode</label>
                <input id="period" name="period" value="{{ $filters['period'] }}" placeholder="2025-Q1">
            </div>
            <div class="field" style="margin:0">
                <label for="kategori">Kategori</label>
                <select id="kategori" name="kategori">
                    <option value="">Semua</option>
                    @foreach ($kategoris as $kategori)
                        <option value="{{ $kategori }}" @selected($filters['kategori'] === $kategori)>{{ $kategori }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field row" style="margin:0;align-self:end">
                <button type="submit" class="btn">Terapkan</button>
                <a href="{{ route('performance-reviews.index') }}" class="btn secondary">Reset</a>
            </div>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>Karyawan</th>
                <th>Periode</th>
                <th>Rating</th>
                <th>Kategori</th>
                <th>Penilai</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($reviews as $review)
                <tr>
                    <td>{{ $review->employee?->name ?? '—' }}</td>
                    <td>{{ $review->period }}</td>
                    <td>{{ $review->rating ?? '—' }}</td>
                    <td><span class="badge {{ $review->kategori === 'A' ? 'ok' : '' }}">{{ $review->kategori ?? '—' }}</span></td>
                    <td>{{ $review->reviewer?->name ?? '—' }}</td>
                    <td>{{ ucfirst($review->status) }}</td>
                    <td class="actions">
                        @can('update', $review)
                            <a href="{{ route('performance-reviews.edit', $review) }}" class="btn link">Ubah</a>
                        @endcan
                        @can('delete', $review)
                            <form method="POST" action="{{ route('performance-reviews.destroy', $review) }}" style="display:inline"
                                  onsubmit="return confirm('Hapus review ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn link danger">Hapus</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted" style="padding:24px;text-align:center">Belum ada review.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $reviews->links('pagination::simple-tailwind') }}</div>
@endsection
