"""Konfigurasi test HR Intelligence API.

Test harus berjalan tanpa jaringan. Runtime default mengambil artifact dari
Hugging Face Hub (``HR_MODEL_SOURCE=hub``), jadi di sini sumber diarahkan ke
folder ``models/`` lokal agar test bersifat hermetic. Perilaku produksi tidak
berubah.
"""

import os

os.environ.setdefault("HR_API_KEY", "test-key")
os.environ.setdefault("HR_MODEL_SOURCE", "local")