@extends('layouts.app')

@section('title', 'Dokumen karyawan')

@section('content')
    <div class="row">
        <div>
            <h1>Dokumen karyawan</h1>
            <p class="sub" style="margin-bottom:0">{{ $documents->total() }} dokumen.</p>
        </div>
        <span style="flex:1"></span>
        @can('create', App\Models\EmployeeDocument::class)
            <a href="{{ route('employee-documents.create') }}" class="btn">Tambah dokumen</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('employee-documents.index') }}" class="card" style="margin:18px 0">
        <div class="grid">
            <div class="field" style="margin:0">
                <label for="category">Kategori</label>
                <select id="category" name="category">
                    <option value="">Semua</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ ucfirst(str_replace('_', ' ', $category)) }}</option>
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
                <a href="{{ route('employee-documents.index') }}" class="btn secondary">Reset</a>
            </div>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>Dokumen</th>
                <th>Karyawan</th>
                <th>Kategori</th>
                <th>Berlaku sampai</th>
                <th>Status</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($documents as $document)
                @php
                    $expiringSoon = $document->expires_at
                        && $document->status !== 'kedaluwarsa'
                        && $document->expires_at->isBetween(now(), now()->addDays(30));
                @endphp
                <tr>
                    <td>{{ $document->title }}</td>
                    <td>{{ $document->employee?->name ?? '—' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $document->category)) }}</td>
                    <td>
                        {{ $document->expires_at?->format('d M Y') ?? '—' }}
                        @if ($expiringSoon)
                            <span class="badge">segera berakhir</span>
                        @endif
                    </td>
                    <td><span class="badge {{ $document->status === 'aktif' ? 'ok' : '' }}">{{ ucfirst($document->status) }}</span></td>
                    <td class="actions">
                        @can('update', $document)
                            <a href="{{ route('employee-documents.edit', $document) }}" class="btn link">Ubah</a>
                        @endcan
                        @can('delete', $document)
                            <form method="POST" action="{{ route('employee-documents.destroy', $document) }}" style="display:inline"
                                  onsubmit="return confirm('Hapus dokumen ini?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn link danger">Hapus</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted" style="padding:24px;text-align:center">Belum ada dokumen.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $documents->links('pagination::simple-tailwind') }}</div>
@endsection
