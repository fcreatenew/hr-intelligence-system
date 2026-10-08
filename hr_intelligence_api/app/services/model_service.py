from __future__ import annotations

import json
import logging
import threading
from pathlib import Path

import joblib

from app.core import hf_store
from app.core.config import MODEL_VERSION, use_case_paths
from app.features.features import RAW_COLUMNS_REQUIRED


logger = logging.getLogger("hr_intelligence.model_service")


class ModelNotLoadedError(RuntimeError):
    ...


class UnknownUseCaseError(KeyError):
    ...


def _resolve(ref: str | Path) -> Path:
    """Path lokal artifact: ``Path`` dipakai apa adanya, ``str`` diambil dari Hub."""
    if isinstance(ref, Path):
        return ref

    return hf_store.fetch(ref)


class ModelService:
    """Memuat satu artifact model + metadata untuk sebuah use case.

    Artifact diambil dari Hugging Face Hub lewat ``hf_store`` (default). Path
    yang diberikan adalah path **relatif** ke root ``models/`` di Hub
    (mis. ``v2_mitigated/turnover_model.joblib``). Untuk test hermetic, ``Path``
    lokal masih diterima.

    Turnover mempertahankan validasi skema fitur lama (kolom mentah harus sama
    dengan features.py). Use case lain tidak punya kontrak kolom mentah yang
    ketat, jadi validasi hanya dilakukan bila expected_raw_columns diberikan.
    """

    def __init__(
        self,
        use_case: str = "turnover",
        model_path: str | Path | None = None,
        metadata_path: str | Path | None = None,
        expected_raw_columns: list[str] | None = None,
    ):
        self.use_case = use_case

        if model_path is None or metadata_path is None:
            default_model, default_metadata = use_case_paths(use_case)
            model_path = model_path or default_model
            metadata_path = metadata_path or default_metadata

        self._model_ref = model_path
        self._metadata_ref = metadata_path
        self._expected_raw_columns = expected_raw_columns

        self._model = None
        self._metadata: dict = {}
        self._resolved_model_path: Path | None = None

        self._lock = threading.Lock()

    def load(self) -> None:

        with self._lock:
            try:
                model_path = _resolve(self._model_ref)
                metadata_path = _resolve(self._metadata_ref)
            except Exception as exc:  # noqa: BLE001 - beri pesan konteks jelas
                raise FileNotFoundError(
                    f"Gagal mengambil artifact '{self.use_case}' dari "
                    f"Hugging Face Hub ({hf_store.HF_MODEL_REPO}): {exc}"
                ) from exc

            if not model_path.exists():
                raise FileNotFoundError(
                    f"Model tidak ditemukan di {model_path}"
                )

            if not metadata_path.exists():
                raise FileNotFoundError(
                    f"Metadata tidak ditemukan di {metadata_path}"
                )

            model = joblib.load(model_path)

            metadata = json.loads(
                metadata_path.read_text(
                    encoding="utf-8"
                )
            )

            self._validate_feature_schema(metadata)

            self._model = model
            self._metadata = metadata
            self._resolved_model_path = model_path

            logger.info(
                "Model '%s' dimuat dari %s: %s "
                "(dilatih %s, threshold=%s)",
                self.use_case,
                hf_store.source(),
                self._metadata.get("model"),
                self._metadata.get("dibuat_pada")
                or self._metadata.get("dilatih_pada"),
                self._metadata.get("threshold"),
            )

    def _validate_feature_schema(
        self,
        metadata: dict,
    ) -> None:
        if self._expected_raw_columns is None:
            return

        expected = set(
            metadata.get(
                "kolom_mentah_dibutuhkan",
                [],
            )
        )

        actual = set(self._expected_raw_columns)

        if expected and expected != actual:
            raise ValueError(
                "Ketidakcocokan skema fitur antara "
                "model_metadata.json dan features.py. "
                f"Diharapkan: {expected}, "
                f"didapat: {actual}. "
                "Kemungkinan model perlu dilatih ulang "
                "atau features.py perlu disamakan."
            )

    def require_loaded(self):
        if self._model is None:
            raise ModelNotLoadedError(
                f"Model '{self.use_case}' belum dimuat. "
                "Cek log startup aplikasi."
            )

        return self._model

    @property
    def is_loaded(self) -> bool:
        return self._model is not None

    @property
    def model(self):
        return self.require_loaded()

    @property
    def metadata(self) -> dict:
        return self._metadata

    @property
    def label(self) -> str:
        return str(
            self._metadata.get("tujuan")
            or self._metadata.get("target")
            or self.use_case
        )

    @property
    def threshold(self) -> float:
        return float(
            self._metadata.get(
                "threshold",
                0.5,
            )
        )

    @property
    def model_version(self) -> str:
        """Versi model aktif (mis. "v2"). Bukan nama algoritma."""
        version = self._metadata.get("versi_model")

        if not version and self.use_case == "turnover":
            version = MODEL_VERSION

        return str(version or "unknown")

    @property
    def trained_at(self) -> str:
        return str(
            self._metadata.get("dibuat_pada")
            or self._metadata.get("dilatih_pada")
            or "unknown"
        )


# Service turnover (default) — dipertahankan demi kompatibilitas kode lama.
model_service = ModelService(
    use_case="turnover",
    expected_raw_columns=RAW_COLUMNS_REQUIRED,
)


_registry: dict[str, ModelService] = {
    "turnover": model_service,
}

# Use case lain: skema fitur divalidasi lewat schema Pydantic, bukan kolom mentah.
for _use_case in ("absensi", "kinerja", "payroll", "survival"):
    _registry[_use_case] = ModelService(use_case=_use_case)


def get_model_service(use_case: str) -> ModelService:
    if use_case not in _registry:
        raise UnknownUseCaseError(
            f"Use case tidak dikenal: {use_case}"
        )

    return _registry[use_case]


def model_services() -> dict[str, ModelService]:
    return dict(_registry)


def load_all_models() -> dict[str, str]:
    """Muat semua model di registry. Mengembalikan status per use case."""
    status: dict[str, str] = {}

    for use_case, service in _registry.items():
        try:
            service.load()
            status[use_case] = "ok"
        except Exception as exc:  # noqa: BLE001 - startup harus tetap jalan
            status[use_case] = f"gagal: {exc}"
            logger.exception(
                "Gagal memuat model use case '%s'.",
                use_case,
            )

    return status
