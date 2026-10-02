@extends('layouts.app')

@section('title', 'Pengguna')

@section('content')
    <div class="row">
        <div>
            <h1>Pengguna</h1>
            <p class="sub" style="margin-bottom:0">{{ $users->total() }} akun pengguna.</p>
        </div>
        <span style="flex:1"></span>
        @can('create', App\Models\User::class)
            <a href="{{ route('users.create') }}" class="btn">Tambah pengguna</a>
        @endcan
    </div>

    <form method="GET" action="{{ route('users.index') }}" class="card" style="margin:18px 0">
        <div class="grid">
            <div class="field" style="margin:0">
                <label for="q">Cari</label>
                <input id="q" name="q" value="{{ $filters['q'] }}" placeholder="Nama atau email">
            </div>
            <div class="field" style="margin:0">
                <label for="role">Peran</label>
                <select id="role" name="role">
                    <option value="">Semua</option>
                    @foreach ($roles as $value => $label)
                        <option value="{{ $value }}" @selected($filters['role'] === $value)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div class="field row" style="margin:0;align-self:end">
                <button type="submit" class="btn">Terapkan</button>
                <a href="{{ route('users.index') }}" class="btn secondary">Reset</a>
            </div>
        </div>
    </form>

    <table>
        <thead>
            <tr>
                <th>Nama</th>
                <th>Email</th>
                <th>Peran</th>
                <th>Terdaftar</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @forelse ($users as $user)
                <tr>
                    <td>
                        {{ $user->name }}
                        @if ($user->is(auth()->user()))
                            <span class="badge">Anda</span>
                        @endif
                    </td>
                    <td class="muted">{{ $user->email }}</td>
                    <td><span class="badge">{{ App\Support\UserRoles::label($user->role) }}</span></td>
                    <td class="muted">{{ $user->created_at?->format('d M Y') ?? '—' }}</td>
                    <td class="actions">
                        @can('update', $user)
                            <a href="{{ route('users.edit', $user) }}" class="btn link">Ubah</a>
                        @endcan
                        @can('update', $user)
                            <form method="POST" action="{{ route('users.send-reset', $user) }}">
                                @csrf
                                <button type="submit" class="btn link">Kirim tautan reset</button>
                            </form>
                        @endcan
                        @can('delete', $user)
                            <form method="POST" action="{{ route('users.destroy', $user) }}"
                                  onsubmit="return confirm('Hapus akun {{ $user->name }}? Tindakan ini tidak dapat dibatalkan.')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn link" style="color:#b42318">Hapus</button>
                            </form>
                        @endcan
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="muted" style="padding:24px;text-align:center">
                        Belum ada pengguna yang cocok dengan filter ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="pagination">{{ $users->links('pagination::simple-tailwind') }}</div>
@endsection
