@extends('layouts.app')

@section('title', 'Kompensasi')

@section('content')
    <div class="row">
        <div>
            <h1>Komponen kompensasi</h1>
            <p class="sub" style="margin-bottom:0">{{ $components->total() }} komponen.</p>
        </div>
        <span style="flex:1"></span>
        @can('create', App\Models\CompensationComponent::class)
            <a href="{{ route('compensation-components.create') }}" class="btn">Tambah komponen</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('compensation-components.index') }}" class="card" style="margin:18px 0">
        <div class="grid">
            <div class="field" style="margin:0">
                <label for="type">Tipe</label>
                <select id="type" name="type">
                    <option value="">Semua</option>
                    @foreach ($types as $type)
                        <option value="{{ $type }}" @selected($filters['type'] === $type)>{{ ucfirst(str_replace('_', ' ', $type)) }}</option>
                    @endforeach
                </select>
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
            <div class="field row" style="margin:0;align-self:end">
                <button type="submit" class="btn">Terapkan</button>
                <a href="{{ route('compensation-components.index') }}" class="btn secondary">Reset</a>
            </div>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>Komponen</th>
                <th>Karyawan</th>
                <th>Tipe</th>
                <th>Nominal</th>
                <th>Berlaku</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($components as $component)
                <tr>
                    <td>{{ $component->name }}@if ($component->recurring) <span class="badge">rutin</span>@endif</td>
                    <td>{{ $component->employee?->name ?? '—' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $component->type)) }}</td>
                    <td>Rp {{ number_format($component->amount, 0, ',', '.') }}</td>
                    <td>
                        {{ $component->effective_date?->format('d M Y') ?? '—' }}
                        @if ($component->end_date)
                            <span class="muted">– {{ $component->end_date->format('d M Y') }}</span>
                        @endif
                    </td>
                    <td class="actions">
                        @can('update', $component)
                            <a href="{{ route('compensation-components.edit', $component) }}" class="btn link">Ubah</a>
                        @endcan
                        @can('delete', $component)
                            <form method="POST" action="{{ route('compensation-components.destroy', $component) }}" style="display:inline"
                                  onsubmit="return confirm('Hapus komponen ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn link danger">Hapus</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted" style="padding:24px;text-align:center">Belum ada komponen.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $components->links('pagination::simple-tailwind') }}</div>
@endsection
