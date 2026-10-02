@php
    $val = fn (string $key, $default = '') => old($key, $employee->{$key} ?? $default);
    $dateVal = fn (string $key) => old($key, optional($employee->{$key} ?? null)->format('Y-m-d'));
@endphp

<h2>Identitas</h2>
<div class="card">
    <div class="grid">
        <div class="field">
            <label for="nik">NIK</label>
            <input id="nik" name="nik" value="{{ $val('nik') }}" required>
            @error('nik') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="name">Nama</label>
            <input id="name" name="name" value="{{ $val('name') }}" required>
            @error('name') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="email">Email</label>
            <input id="email" name="email" type="email" value="{{ $val('email') }}" required>
            @error('email') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="phone">Telepon</label>
            <input id="phone" name="phone" value="{{ $val('phone') }}">
        </div>
        <div class="field">
            <label for="department">Departemen</label>
            <input id="department" name="department" value="{{ $val('department') }}" required>
            @error('department') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="position">Jabatan</label>
            <input id="position" name="position" value="{{ $val('position') }}" required>
            @error('position') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="join_date">Tanggal masuk</label>
            <input id="join_date" name="join_date" type="date" value="{{ $dateVal('join_date') }}" required>
            @error('join_date') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="status">Status</label>
            <select id="status" name="status">
                <option value="1" @selected((string) $val('status', '1') === '1')>Aktif</option>
                <option value="0" @selected((string) $val('status', '1') === '0')>Nonaktif</option>
            </select>
        </div>
    </div>
</div>

<h2>Atribut untuk model ML</h2>
<p class="hint" style="margin-top:-6px">
    Kolom yang dikosongkan akan dibiarkan kosong dan diisi nilai default oleh
    mapper saat prediksi dijalankan — bukan dianggap nol.
</p>

<details class="card" open>
    <summary style="cursor:pointer;font-weight:600">Umum (turnover, absensi, kinerja, survival)</summary>
    <div class="grid" style="margin-top:16px">
        <div class="field">
            <label for="tanggal_lahir">Tanggal lahir</label>
            <input id="tanggal_lahir" name="tanggal_lahir" type="date" value="{{ $dateVal('tanggal_lahir') }}">
            @error('tanggal_lahir') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="tanggal_promosi">Tanggal promosi terakhir</label>
            <input id="tanggal_promosi" name="tanggal_promosi" type="date" value="{{ $dateVal('tanggal_promosi') }}">
        </div>
        <div class="field">
            <label for="status_kerja">Status kerja</label>
            <select id="status_kerja" name="status_kerja">
                <option value="">—</option>
                @foreach (['Tetap', 'Kontrak', 'Freelance', 'Magang'] as $opt)
                    <option value="{{ $opt }}" @selected($val('status_kerja') === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="pendidikan">Pendidikan</label>
            <select id="pendidikan" name="pendidikan">
                <option value="">—</option>
                @foreach (['SMA', 'SMK', 'D3', 'S1', 'S2', 'S3'] as $opt)
                    <option value="{{ $opt }}" @selected($val('pendidikan') === $opt)>{{ $opt }}</option>
                @endforeach
            </select>
        </div>
        <div class="field">
            <label for="gaji_bulanan">Gaji bulanan (Rp)</label>
            <input id="gaji_bulanan" name="gaji_bulanan" type="number" min="0" value="{{ $val('gaji_bulanan') }}">
        </div>
        <div class="field">
            <label for="jam_lembur_bulanan">Jam lembur / bulan</label>
            <input id="jam_lembur_bulanan" name="jam_lembur_bulanan" type="number" min="0" max="744" value="{{ $val('jam_lembur_bulanan') }}">
        </div>
        <div class="field">
            <label for="bulan_sejak_promosi">Bulan sejak promosi</label>
            <input id="bulan_sejak_promosi" name="bulan_sejak_promosi" type="number" step="0.1" min="0" value="{{ $val('bulan_sejak_promosi') }}">
        </div>
        <div class="field">
            <label for="jarak_km">Jarak rumah–kantor (km)</label>
            <input id="jarak_km" name="jarak_km" type="number" step="0.1" min="0" value="{{ $val('jarak_km') }}">
        </div>
        <div class="field">
            <label for="kepuasan_kerja">Kepuasan kerja (1–5)</label>
            <input id="kepuasan_kerja" name="kepuasan_kerja" type="number" step="0.01" min="1" max="5" value="{{ $val('kepuasan_kerja') }}">
            @error('kepuasan_kerja') <div class="error">{{ $message }}</div> @enderror
        </div>
    </div>
</details>

<details class="card" style="margin-top:14px">
    <summary style="cursor:pointer;font-weight:600">Absensi &amp; kinerja</summary>
    <div class="grid" style="margin-top:16px">
        <div class="field">
            <label for="beban_kerja">Beban kerja (0–100)</label>
            <input id="beban_kerja" name="beban_kerja" type="number" step="0.1" min="0" max="100" value="{{ $val('beban_kerja') }}">
        </div>
        <div class="field">
            <label for="riwayat_absen_90hari">Riwayat absen 90 hari</label>
            <input id="riwayat_absen_90hari" name="riwayat_absen_90hari" type="number" min="0" max="90" value="{{ $val('riwayat_absen_90hari') }}">
        </div>
        <div class="field">
            <label for="hari_absen_setahun">Hari absen setahun</label>
            <input id="hari_absen_setahun" name="hari_absen_setahun" type="number" min="0" max="365" value="{{ $val('hari_absen_setahun') }}">
        </div>
        <div class="field">
            <label for="jumlah_training_setahun">Training setahun</label>
            <input id="jumlah_training_setahun" name="jumlah_training_setahun" type="number" min="0" value="{{ $val('jumlah_training_setahun') }}">
        </div>
        <div class="field">
            <label for="proyek_selesai">Proyek selesai</label>
            <input id="proyek_selesai" name="proyek_selesai" type="number" min="0" value="{{ $val('proyek_selesai') }}">
        </div>
        <div class="field">
            <label for="skor_umpan_balik_360">Skor umpan balik 360 (1–5)</label>
            <input id="skor_umpan_balik_360" name="skor_umpan_balik_360" type="number" step="0.01" min="1" max="5" value="{{ $val('skor_umpan_balik_360') }}">
            @error('skor_umpan_balik_360') <div class="error">{{ $message }}</div> @enderror
        </div>
    </div>
</details>

<details class="card" style="margin-top:14px">
    <summary style="cursor:pointer;font-weight:600">Payroll</summary>
    <div class="grid" style="margin-top:16px">
        <div class="field">
            <label for="periode">Periode (YYYY-MM)</label>
            <input id="periode" name="periode" placeholder="2026-01" value="{{ $val('periode') }}">
            @error('periode') <div class="error">{{ $message }}</div> @enderror
        </div>
        <div class="field">
            <label for="level">Level</label>
            <input id="level" name="level" value="{{ $val('level') }}">
        </div>
        <div class="field">
            <label for="gaji_pokok">Gaji pokok (Rp)</label>
            <input id="gaji_pokok" name="gaji_pokok" type="number" min="0" value="{{ $val('gaji_pokok') }}">
        </div>
        <div class="field">
            <label for="tunjangan">Tunjangan (Rp)</label>
            <input id="tunjangan" name="tunjangan" type="number" step="0.01" min="0" value="{{ $val('tunjangan') }}">
        </div>
        <div class="field">
            <label for="lembur_jam">Lembur (jam)</label>
            <input id="lembur_jam" name="lembur_jam" type="number" min="0" value="{{ $val('lembur_jam') }}">
        </div>
        <div class="field">
            <label for="tarif_lembur">Tarif lembur (Rp/jam)</label>
            <input id="tarif_lembur" name="tarif_lembur" type="number" step="0.01" min="0" value="{{ $val('tarif_lembur') }}">
        </div>
        <div class="field">
            <label for="potongan">Potongan (Rp)</label>
            <input id="potongan" name="potongan" type="number" step="0.01" min="0" value="{{ $val('potongan') }}">
        </div>
        <div class="field">
            <label for="total_gaji">Total gaji (Rp)</label>
            <input id="total_gaji" name="total_gaji" type="number" step="0.01" min="0" value="{{ $val('total_gaji') }}">
        </div>
    </div>
</details>
