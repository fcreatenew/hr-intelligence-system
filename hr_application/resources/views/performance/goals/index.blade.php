@extends('layouts.app')

@section('title', 'Sasaran kinerja')

@section('content')
    <div class="row">
        <div>
            <h1>Sasaran kinerja</h1>
            <p class="sub" style="margin-bottom:0">{{ $goals->total() }} sasaran.</p>
        </div>
        <span style="flex:1"></span>
        @can('create', App\Models\PerformanceGoal::class)
            <a href="{{ route('performance-goals.create') }}" class="btn">Tambah sasaran</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('performance-goals.index') }}" class="card" style="margin:18px 0">
        <div class="grid">
            <div class="field" style="margin:0">
                <label for="period">Periode</label>
                <input id="period" name="period" value="{{ $filters['period'] }}" placeholder="2025-Q1">
            </div>
            <div class="field" style="margin:0">
                <label for="employee_id">Karyawan</label>
                <select id="employee_id" name="employee_id">
                    <option value="">Semua</option>
                    @foreach ($employees as $employee)
                        <option value="{{ $employee->id }}" @selected($filters['employee_id'] == $employee->id)>{{ $employee->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="margin:0">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field row" style="margin:0;align-self:end">
                <button type="submit" class="btn">Terapkan</button>
                <a href="{{ route('performance-goals.index') }}" class="btn secondary">Reset</a>
            </div>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>Sasaran</th>
                <th>Karyawan</th>
                <th>Periode</th>
                <th>Bobot</th>
                <th>Target / Capaian</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($goals as $goal)
                <tr>
                    <td>{{ $goal->title }}</td>
                    <td>{{ $goal->employee?->name ?? '—' }}</td>
                    <td>{{ $goal->period }}</td>
                    <td>{{ $goal->weight }}%</td>
                    <td>{{ $goal->target ?? '—' }} / {{ $goal->achievement ?? '—' }}</td>
                    <td><span class="badge {{ $goal->status === 'selesai' ? 'ok' : '' }}">{{ ucfirst($goal->status) }}</span></td>
                    <td class="actions">
                        @can('update', $goal)
                            <a href="{{ route('performance-goals.edit', $goal) }}" class="btn link">Ubah</a>
                        @endcan
                        @can('delete', $goal)
                            <form method="POST" action="{{ route('performance-goals.destroy', $goal) }}" style="display:inline"
                                  onsubmit="return confirm('Hapus sasaran ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn link danger">Hapus</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="7" class="muted" style="padding:24px;text-align:center">Belum ada sasaran.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $goals->links('pagination::simple-tailwind') }}</div>
@endsection
