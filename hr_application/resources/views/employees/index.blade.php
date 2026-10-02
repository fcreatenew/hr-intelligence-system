@extends('layouts.app')

@section('title', 'Karyawan')

@section('content')
    <div class="row">
        <div>
            <h1>Karyawan</h1>
            <p class="sub" style="margin-bottom:0">{{ $employees->total() }} karyawan terdaftar.</p>
        </div>
        <span class="spacer" style="flex:1"></span>
        @can('create', App\Models\Employee::class)
            <a href="{{ route('employees.create') }}" class="btn">Tambah karyawan</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('employees.index') }}" class="card" style="margin:18px 0">
        <div class="grid">
            <div class="field" style="margin:0">
                <label for="q">Cari</label>
                <input id="q" name="q" value="{{ $filters['q'] }}" placeholder="Nama, NIK, atau email">
            </div>
            <div class="field" style="margin:0">
                <label for="department">Departemen</label>
                <select id="department" name="department">
                    <option value="">Semua</option>
                    @foreach ($departments as $dept)
                        <option value="{{ $dept }}" @selected($filters['department'] === $dept)>{{ $dept }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field" style="margin:0">
                <label for="status">Status</label>
                <select id="status" name="status">
                    <option value="">Semua</option>
                    <option value="aktif" @selected($filters['status'] === 'aktif')>Aktif</option>
                    <option value="nonaktif" @selected($filters['status'] === 'nonaktif')>Nonaktif</option>
                </select>
            </div>
            <div class="field row" style="margin:0;align-self:end">
                <button type="submit" class="btn">Terapkan</button>
                <a href="{{ route('employees.index') }}" class="btn secondary">Reset</a>
            </div>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>NIK</th>
                <th>Nama</th>
                <th>Departemen</th>
                <th>Jabatan</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($employees as $employee)
                <tr>
                    <td>{{ $employee->nik }}</td>
                    <td>
                        <a href="{{ route('employees.show', $employee) }}">{{ $employee->name }}</a>
                        <div class="muted" style="font-size:12px">{{ $employee->email }}</div>
                    </td>
                    <td>{{ $employee->department }}</td>
                    <td>{{ $employee->position }}</td>
                    <td>
                        <span class="badge {{ $employee->status ? 'ok' : 'off' }}">
                            {{ $employee->status ? 'Aktif' : 'Nonaktif' }}
                        </span>
                    </td>
                    <td class="actions">
                        @can('update', $employee)
                            <a href="{{ route('employees.edit', $employee) }}" class="btn link">Ubah</a>
                        @endcan
                        @can('delete', $employee)
                            <form method="POST" action="{{ route('employees.destroy', $employee) }}"
                                  onsubmit="return confirm('Hapus {{ $employee->name }}? Tindakan ini tidak dapat dibatalkan.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn link" style="color:#b42318">Hapus</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="muted" style="padding:24px;text-align:center">
                        Belum ada karyawan yang cocok dengan filter ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $employees->links('pagination::simple-tailwind') }}</div>
@endsection
