from __future__ import annotations

from typing import Literal, Optional

from pydantic import BaseModel, Field


Intent = Literal[
    "knowledge",
    "employee_insight",
    "hybrid",
    "out_of_scope",
]

UseCase = Literal[
    "turnover",
    "absensi",
    "kinerja",
    "payroll",
    "survival",
]


class ChatMessage(BaseModel):
    role: Literal["user", "assistant"]
    content: str


class AssistantRequest(BaseModel):
    message: str = Field(..., min_length=1, examples=["Bagaimana prosedur cuti tahunan?"])
    session_id: Optional[str] = Field(
        default=None,
        description="ID sesi untuk menyimpan riwayat percakapan.",
    )
    employee_id: Optional[str] = Field(
        default=None,
        description="Karyawan yang dibahas, dipakai untuk pertanyaan prediksi.",
    )
    use_case: Optional[UseCase] = Field(
        default=None,
        description="Batasi prediksi ke satu model; kosongkan untuk deteksi otomatis.",
    )

    model_config = {
        "json_schema_extra": {
            "example": {
                "message": "Berapa risiko turnover karyawan EMP0001?",
                "employee_id": "EMP0001",
            }
        }
    }


class Citation(BaseModel):
    doc_id: str
    title: str
    source: str
    category: str
    score: float
    snippet: str


class ToolCall(BaseModel):
    tool: str
    use_case: Optional[str] = None
    ok: bool
    summary: str
    data: dict = {}


class AssistantResponse(BaseModel):
    answer: str
    intent: Intent
    session_id: str
    citations: list[Citation] = []
    tool_calls: list[ToolCall] = []
    suggestions: list[str] = []
    used_llm: bool = False
    llm_provider: str = "mock"
