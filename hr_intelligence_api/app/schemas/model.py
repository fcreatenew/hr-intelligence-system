from typing import Optional

from pydantic import BaseModel


class ModelInfoResponse(BaseModel):
    use_case: Optional[str] = None
    versi_model: Optional[str] = None
    label: Optional[str] = None

    model: str
    dibuat_pada: str
    threshold: Optional[float] = None
    fitur_numerik: list[str] = []
    fitur_kategorik: list[str] = []
    metrik: dict = {}
    catatan: str = ""


class UseCaseInfo(BaseModel):
    use_case: str
    label: str
    versi_model: str
    model: str
    threshold: Optional[float] = None
    predict_path: str
    loaded: bool


class UseCaseListResponse(BaseModel):
    use_cases: list[UseCaseInfo]
