@extends('layouts.app')

@section('title', 'Jejak Audit')

@section('content')
    <h1>Jejak Audit</h1>
    <p class="sub">Riwayat perubahan data HR: siapa, kapan, dan nilai apa yang berubah.</p>

    <form method="GET" class="row" style="margin-bottom:20px">
        <div style="flex:0 0 180px">
            <label for="event">Aksi</label>
            <select id="event" name="event">
                <option value="">Semua</option>
                @foreach ($events as $event)
                    <option value="{{ $event }}" @selected($filters['event'] === $event)>{{ $event }}</option>
                @endforeach
            </select>
        </div>
        <div style="flex:0 0 260px">
            <label for="type">Jenis data</label>
            <select id="type" name="type">
                <option value="">Semua</option>
                @foreach ($types as $type)
                    <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ class_basename($type) }}</option>
                @endforeach
            </select>
        </div>
        <div style="align-self:flex-end">
            <button type="submit" class="btn">Filter</button>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>Waktu</th>
                <th>Pengguna</th>
                <th>Aksi</th>
                <th>Data</th>
                <th>Perubahan</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($logs as $log)
                <tr>
                    <td class="muted">{{ $log->created_at?->format('d M Y H:i:s') }}</td>
                    <td>{{ $log->user?->name ?? 'Sistem' }}</td>
                    <td>
                        @if ($log->event === 'created')
                            <span class="badge ok">dibuat</span>
                        @elseif ($log->event === 'deleted')
                            <span class="badge off">dihapus</span>
                        @else
                            <span class="badge">diubah</span>
                        @endif
                    </td>
                    <td>{{ $log->auditable_label }} #{{ $log->auditable_id }}</td>
                    <td>
                        @if ($log->event === 'updated')
                            @foreach (($log->new_values ?? []) as $field => $value)
                                <div class="hint">
                                    <strong>{{ $field }}</strong>:
                                    {{ is_scalar($log->old_values[$field] ?? null) ? $log->old_values[$field] : json_encode($log->old_values[$field] ?? null) }}
                                    →
                                    {{ is_scalar($value) ? $value : json_encode($value) }}
                                </div>
                            @endforeach
                        @elseif ($log->event === 'created')
                            <span class="muted">data baru dibuat</span>
                        @else
                            <span class="muted">data dihapus</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted">Belum ada jejak audit.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $logs->links() }}</div>
@endsection
