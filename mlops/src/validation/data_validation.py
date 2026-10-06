"""Validasi asupan data: cek dataset sebelum dipakai training/retraining.

Dipakai untuk memastikan data **nyata/anonim** yang masuk memenuhi kontrak
kolom, rentang nilai, dan tidak membocorkan data pribadi. Semua pemeriksaan
bersifat *fail-loud*: mengembalikan daftar masalah agar pemanggil memutuskan
(pipeline CI memakai exit code).

Kontrak kolom turnover sengaja dipakai ulang dari pipeline training
(`RAW_COLUMNS_REQUIRED`) sehingga validator tidak pernah menyimpang dari model.
"""

from __future__ import annotations

from dataclasses import dataclass, field
from pathlib import Path

import pandas as pd

from src.config import DATA_DIR
from src.train.pipeline import RAW_COLUMNS_REQUIRED, TARGET


# Kolom yang berpotensi mengidentifikasi orang (harus dianonimkan/dihapus).
PII_COLUMNS: tuple[str, ...] = (
    "nama",
    "nik",
    "no_ktp",
    "ktp",
    "email",
    "telepon",
    "no_hp",
    "alamat",
    "npwp",
    "rekening",
)

# Rentang nilai wajar untuk kolom numerik turnover.
NUMERIC_RANGES: dict[str, tuple[float, float]] = {
    "usia": (15, 70),
    "masa_kerja_bulan": (0, 600),
    "jam_lembur_bulanan": (0, 200),
    "gaji_bulanan": (0, 500_000_000),
}

CATEGORICAL_DOMAINS: dict[str, set[str]] = {
    "departemen": {
        "Finance", "HR", "IT", "Legal", "Marketing", "Operations", "Sales",
    },
    "status_kerja": {"Tetap", "Kontrak", "Freelance"},
    "pendidikan": {"SMA", "Diploma", "S1", "S2"},
}


# Kolom yang secara kontrak boleh banyak kosong (mis. belum pernah promosi).
NULLABLE_COLUMNS: tuple[str, ...] = ("tanggal_promosi",)


@dataclass
class DataValidationConfig:
    use_case: str = "turnover"
    required_columns: list[str] = field(
        default_factory=lambda: list(RAW_COLUMNS_REQUIRED) + [TARGET]
    )
    target_column: str = TARGET
    min_rows: int = 100
    max_missing_ratio: float = 0.2
    check_pii: bool = True
    nullable_columns: tuple[str, ...] = NULLABLE_COLUMNS


def _load(source: pd.DataFrame | str | Path) -> pd.DataFrame:
    if isinstance(source, pd.DataFrame):
        return source

    path = Path(source)
    if not path.is_absolute():
        path = DATA_DIR / path
    if not path.exists():
        raise FileNotFoundError(f"Dataset tidak ditemukan: {path}")

    return pd.read_csv(path)


def validate_dataset(
    source: pd.DataFrame | str | Path,
    config: DataValidationConfig | None = None,
) -> dict:
    """Validasi dataset; kembalikan dict `{lulus, masalah, ringkasan}`."""
    config = config or DataValidationConfig()
    df = _load(source)

    masalah: list[dict] = []

    def tambah(jenis: str, detail: str) -> None:
        masalah.append({"jenis": jenis, "detail": detail})

    # 1. Ukuran minimum.
    if len(df) < config.min_rows:
        tambah(
            "jumlah_baris",
            f"Dataset hanya {len(df)} baris; minimal {config.min_rows}.",
        )

    # 2. Kolom wajib.
    kurang = [c for c in config.required_columns if c not in df.columns]
    if kurang:
        tambah("kolom_hilang", f"Kolom wajib belum ada: {kurang}.")

    # 3. PII tidak boleh ada.
    if config.check_pii:
        pii = [c for c in df.columns if c.strip().lower() in PII_COLUMNS]
        if pii:
            tambah(
                "pii",
                f"Kolom berpotensi PII terdeteksi dan harus dihapus/dianonimkan: {pii}.",
            )

    # 4. Rasio nilai kosong (kolom nullable dikecualikan).
    if len(df) > 0:
        for kolom, rasio in df.isna().mean().items():
            if kolom in config.nullable_columns:
                continue
            if rasio > config.max_missing_ratio:
                tambah(
                    "nilai_kosong",
                    f"Kolom '{kolom}' memiliki {rasio:.1%} nilai kosong "
                    f"(ambang {config.max_missing_ratio:.0%}).",
                )

    # 5. Rentang numerik.
    for kolom, (batas_bawah, batas_atas) in NUMERIC_RANGES.items():
        if kolom not in df.columns:
            continue
        nilai = pd.to_numeric(df[kolom], errors="coerce").dropna()
        if nilai.empty:
            continue
        if nilai.min() < batas_bawah or nilai.max() > batas_atas:
            tambah(
                "rentang_nilai",
                f"Kolom '{kolom}' di luar rentang wajar "
                f"[{batas_bawah}, {batas_atas}]: min={nilai.min()}, max={nilai.max()}.",
            )

    # 6. Domain kategori.
    for kolom, domain in CATEGORICAL_DOMAINS.items():
        if kolom not in df.columns:
            continue
        tak_dikenal = sorted(set(df[kolom].dropna().astype(str)) - domain)
        if tak_dikenal:
            tambah(
                "kategori_tak_dikenal",
                f"Kolom '{kolom}' punya nilai di luar domain: {tak_dikenal}.",
            )

    # 7. Target biner.
    if config.target_column in df.columns:
        nilai_target = set(
            pd.to_numeric(df[config.target_column], errors="coerce").dropna().unique()
        )
        if not nilai_target <= {0, 1}:
            tambah(
                "target_tidak_biner",
                f"Kolom target '{config.target_column}' harus 0/1; "
                f"ditemukan {sorted(nilai_target)}.",
            )

    return {
        "use_case": config.use_case,
        "lulus": not masalah,
        "masalah": masalah,
        "ringkasan": {
            "baris": int(len(df)),
            "kolom": int(len(df.columns)),
            "jumlah_masalah": len(masalah),
        },
    }
