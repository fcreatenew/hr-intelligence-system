"""Batas thread runtime untuk model CPU-only.

Kelima model HR (scikit-learn & lifelines) murni berjalan di CPU dan tidak
punya backend GPU. Operasi matriksnya kecil, tetapi NumPy/BLAS/OpenMP secara
default memakai seluruh core mesin. Di container dengan banyak worker Uvicorn,
tiap worker bisa membuka thread sebanyak jumlah core sehingga saling
mengalahkan (oversubscription) dan latensi justru naik.

Modul ini membatasi jumlah thread lewat env `HR_NUM_THREADS` **sebelum**
NumPy/scikit-learn diimpor. Set `1` untuk beban request-nya kecil, atau biarkan
kosong agar memakai default library (satu thread per core).
"""

from __future__ import annotations

import os


# Variabel yang dibaca oleh OpenBLAS/MKL/OpenMP dan NumPy.
_THREAD_ENV_VARS = (
    "OMP_NUM_THREADS",
    "OPENBLAS_NUM_THREADS",
    "MKL_NUM_THREADS",
    "NUMEXPR_NUM_THREADS",
    "VECLIB_MAXIMUM_THREADS",
)

# Nilai yang sedang aktif (di-set oleh configure_thread_limits).
_ACTIVE_THREAD_LIMIT: int | None = None


def configure_thread_limits() -> int | None:
    """Terapkan batas thread dari `HR_NUM_THREADS` bila diisi.

    Mengembalikan jumlah thread yang dipakai, atau `None` bila env kosong/tidak
    valid (memakai default library). Aman dipanggil berkali-kali (idempoten).
    """
    global _ACTIVE_THREAD_LIMIT

    raw = os.getenv("HR_NUM_THREADS", "").strip()

    if not raw:
        return None

    try:
        value = int(raw)
    except ValueError:
        return None

    if value < 1:
        return None

    for name in _THREAD_ENV_VARS:
        os.environ[name] = str(value)

    _ACTIVE_THREAD_LIMIT = value

    return value


def active_thread_limit() -> int | None:
    """Batas thread yang aktif, atau `None` bila memakai default library."""
    return _ACTIVE_THREAD_LIMIT
