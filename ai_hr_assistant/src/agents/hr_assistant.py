from __future__ import annotations

from src.agents.router import RouteDecision, route
from src.config import RAG_TOP_K
from src.llm.prompts import load_prompt
from src.llm.provider import LLMProvider, get_llm
from src.memory.session import SessionMemory
from src.rag.service import RagService
from src.retrieval.vector_store import ScoredChunk
from src.schemas.chat import (
    AssistantResponse,
    ChatMessage,
    Citation,
    ToolCall,
)
from src.tools.prediction_tools import USE_CASE_LABELS
from src.tools.registry import ToolRegistry, ToolResult


PROMPT_FOR_USE_CASE = {
    "payroll": "payroll/anomaly-explainer.md",
    "turnover": "recruitment/retention.md",
    "survival": "recruitment/retention.md",
}


class HrAssistantAgent:
    """Agent utama: routing intent -> retrieval dan/atau tool -> jawaban.

    Alur mengikuti prinsip retrieval-first: knowledge base diambil lebih dulu,
    lalu tool prediksi dipanggil hanya bila intent memang meminta hasil model.
    """

    def __init__(
        self,
        rag: RagService,
        tools: ToolRegistry,
        llm: LLMProvider | None = None,
        memory: SessionMemory | None = None,
    ):
        self._rag = rag
        self._tools = tools
        self._llm = llm or get_llm()
        self._memory = memory or SessionMemory()

    @property
    def llm_provider(self) -> str:
        return self._llm.name

    @property
    def rag(self) -> RagService:
        return self._rag

    @property
    def tools(self) -> ToolRegistry:
        return self._tools

    @property
    def memory(self) -> SessionMemory:
        return self._memory

    # -- Publik -------------------------------------------------------------
    def ask(
        self,
        message: str,
        session_id: str | None = None,
        employee_id: str | None = None,
        use_case: str | None = None,
    ) -> AssistantResponse:
        session_id = session_id or self._memory.new_session_id()
        history = self._memory.history(session_id)

        if employee_id is None:
            employee_id = self._memory.last_employee_id(session_id)

        decision = route(message, employee_id=employee_id, forced_use_case=use_case)

        citations, chunks = self._retrieve(message)
        tool_calls = self._run_tools(decision)

        answer = self._compose(message, decision, chunks, tool_calls, history)
        suggestions = self._suggest(decision)

        self._memory.append(session_id, ChatMessage(role="user", content=message))
        self._memory.append(
            session_id, ChatMessage(role="assistant", content=answer)
        )

        return AssistantResponse(
            answer=answer,
            intent=decision.intent,
            session_id=session_id,
            citations=citations,
            tool_calls=tool_calls,
            suggestions=suggestions,
            used_llm=self._llm.name != "mock",
            llm_provider=self._llm.name,
        )

    # -- Retrieval ----------------------------------------------------------
    def _retrieve(self, message: str) -> tuple[list[Citation], list[ScoredChunk]]:
        results = self._rag.search(message, top_k=RAG_TOP_K)

        citations = [
            Citation(
                doc_id=result.chunk.doc_id,
                title=result.chunk.title,
                source=result.chunk.source,
                category=result.chunk.category,
                score=result.score,
                snippet=result.chunk.text[:280],
            )
            for result in results
        ]

        return citations, results

    # -- Tools --------------------------------------------------------------
    def _run_tools(self, decision: RouteDecision) -> list[ToolCall]:
        calls: list[ToolCall] = []

        if decision.wants_model_info:
            calls.append(self._call("list_models"))

        if decision.intent != "employee_insight":
            return calls

        employee_id = decision.employee_id or "EMPLOYEE"

        for use_case in decision.use_cases:
            tool_name = f"predict_{use_case}"

            if tool_name not in self._tools:
                continue

            calls.append(
                self._call(
                    tool_name,
                    employee_id=employee_id,
                )
            )

        return calls

    def _call(self, name: str, **kwargs) -> ToolCall:
        result: ToolResult = self._tools.call(name, **kwargs)

        return ToolCall(
            tool=result.tool,
            use_case=result.use_case,
            ok=result.ok,
            summary=result.summary,
            data=result.data,
        )

    # -- Komposisi jawaban --------------------------------------------------
    def _compose(
        self,
        message: str,
        decision: RouteDecision,
        chunks: list[ScoredChunk],
        tool_calls: list[ToolCall],
        history: list[ChatMessage],
    ) -> str:
        if self._llm.name != "mock":
            generated = self._compose_with_llm(message, decision, chunks, tool_calls)

            if generated:
                return generated

        return self._compose_deterministic(message, decision, chunks, tool_calls)

    def _compose_deterministic(
        self,
        message: str,
        decision: RouteDecision,
        chunks: list[ScoredChunk],
        tool_calls: list[ToolCall],
    ) -> str:
        parts: list[str] = []

        if decision.intent == "out_of_scope":
            return (
                "Maaf, saya belum bisa memproses pesan itu. "
                "Coba tanyakan kebijakan/prosedur HR atau prediksi karyawan "
                "(mis. \"berapa risiko turnover karyawan EMP0001?\")."
            )

        if tool_calls:
            parts.append("**Hasil prediksi model HR**")

            for call in tool_calls:
                if call.ok:
                    parts.append(f"* {call.summary}")
                else:
                    parts.append(f"* {call.summary}")

            parts.append(
                "Catatan: skor prediksi adalah sinyal awal untuk ditindaklanjuti, "
                "bukan dasar keputusan otomatis (PHK, sanksi, atau promosi)."
            )

        if chunks:
            if parts:
                parts.append("")
            parts.append("**Dasar kebijakan/prosedur**")

            for result in chunks:
                heading = result.chunk.heading or result.chunk.title
                snippet = self._first_lines(result.chunk.text, limit=3)
                parts.append(f"* {heading} — {snippet} ({result.chunk.title})")

        if not parts:
            return (
                "Saya belum menemukan informasi yang relevan di knowledge base HR. "
                "Silakan perjelas pertanyaan atau hubungi tim HR untuk memastikan."
            )

        return "\n".join(parts)

    def _compose_with_llm(
        self,
        message: str,
        decision: RouteDecision,
        chunks: list[ScoredChunk],
        tool_calls: list[ToolCall],
    ) -> str:
        system_prompt = load_prompt("system/base.md")

        prompt_key = next(
            (
                PROMPT_FOR_USE_CASE[use_case]
                for use_case in decision.use_cases
                if use_case in PROMPT_FOR_USE_CASE
            ),
            "hr/policy-qa.md",
        )
        system_prompt = f"{system_prompt}\n\n{load_prompt(prompt_key)}"

        context = "\n\n".join(
            f"[{result.chunk.title} — {result.chunk.heading}]\n{result.chunk.text}"
            for result in chunks
        )

        tool_context = "\n".join(
            f"- {call.tool}: {call.summary} | data={call.data}" for call in tool_calls
        )

        user_prompt = (
            f"Pertanyaan pengguna:\n{message}\n\n"
            f"Konteks knowledge base:\n{context or '(tidak ada)'}\n\n"
            f"Hasil tool prediksi:\n{tool_context or '(tidak ada)'}\n\n"
            "Susun jawaban dalam Bahasa Indonesia yang ringkas dan akurat."
        )

        try:
            return self._llm.complete(system_prompt, user_prompt).strip()
        except Exception:  # noqa: BLE001 - fallback ke komposer deterministik
            return ""

    # -- Saran lanjutan -----------------------------------------------------
    def _suggest(self, decision: RouteDecision) -> list[str]:
        suggestions: list[str] = []

        for use_case in decision.use_cases:
            label = USE_CASE_LABELS.get(use_case, use_case)
            suggestions.append(f"Jelaskan arti {label.lower()} dan tindak lanjutnya.")

        if not suggestions:
            suggestions = [
                "Bagaimana prosedur pengajuan cuti tahunan?",
                "Kapan gaji dibayarkan setiap bulan?",
                "Berapa risiko turnover karyawan EMP0001?",
            ]

        return suggestions[:3]

    @staticmethod
    def _first_lines(text: str, limit: int = 3) -> str:
        lines = [
            line.strip().lstrip("*-").strip()
            for line in text.splitlines()
            if line.strip()
            and not line.strip().startswith("#")
            and not line.strip().startswith(">")
        ]

        return " ".join(lines[:limit])[:220]
