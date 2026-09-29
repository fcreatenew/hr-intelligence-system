from pydantic import BaseModel


class ModelInfoResponse(BaseModel):
    model: str
    dibuat_pada: str
    threshold: float
    fitur_numerik: list[str]
    fitur_kategorik: list[str]
    metrik_holdout_test: dict
    catatan: str