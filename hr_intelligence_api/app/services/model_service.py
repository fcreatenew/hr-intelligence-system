from __future__ import annotations

import json
import logging
import threading
from pathlib import Path

import joblib

from app.core.config import METADATA_PATH, MODEL_PATH
from app.features.features import RAW_COLUMNS_REQUIRED


logger = logging.getLogger("hr_intelligence.model_service")


class ModelNotLoadedError(RuntimeError):
    ...


class ModelService:

    def __init__(
        self,
        model_path: Path = MODEL_PATH,
        metadata_path: Path = METADATA_PATH,
    ):
        self._model_path = model_path
        self._metadata_path = metadata_path

        self._model = None
        self._metadata: dict = {}

        self._lock = threading.Lock()

    def load(self) -> None:

        with self._lock:
            if not self._model_path.exists():
                raise FileNotFoundError(
                    f"Model tidak ditemukan di {self._model_path}"
                )

            if not self._metadata_path.exists():
                raise FileNotFoundError(
                    f"Metadata tidak ditemukan di {self._metadata_path}"
                )

            model = joblib.load(self._model_path)

            metadata = json.loads(
                self._metadata_path.read_text(
                    encoding="utf-8"
                )
            )

            self._validate_feature_schema(metadata)

            self._model = model
            self._metadata = metadata

            logger.info(
                "Model dimuat: %s "
                "(dilatih %s, threshold=%s)",
                self._metadata.get("model"),
                self._metadata.get("dibuat_pada"),
                self._metadata.get("threshold"),
            )

    def _validate_feature_schema(
        self,
        metadata: dict,
    ) -> None:
        expected = set(
            metadata.get(
                "kolom_mentah_dibutuhkan",
                [],
            )
        )

        actual = set(RAW_COLUMNS_REQUIRED)

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
                "Model belum dimuat. "
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
    def threshold(self) -> float:
        return float(
            self._metadata.get(
                "threshold",
                0.5,
            )
        )

model_service = ModelService()