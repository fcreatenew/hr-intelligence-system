from __future__ import annotations

from functools import lru_cache

from src.agents.hr_assistant import HrAssistantAgent
from src.llm.provider import get_llm
from src.memory.session import SessionMemory
from src.rag.service import RagService
from src.tools.hr_intelligence_client import HrIntelligenceClient
from src.tools.prediction_tools import build_tool_registry
from src.tools.registry import ToolRegistry


class Container:
    """Perakitan dependensi (dependency wiring) untuk AI HR Assistant."""

    def __init__(
        self,
        rag: RagService | None = None,
        tools: ToolRegistry | None = None,
        client: HrIntelligenceClient | None = None,
        memory: SessionMemory | None = None,
    ):
        self.rag = rag or RagService()
        self.client = client or HrIntelligenceClient()
        self.tools = tools or build_tool_registry(self.client)
        self.memory = memory or SessionMemory()
        self.agent = HrAssistantAgent(
            rag=self.rag,
            tools=self.tools,
            llm=get_llm(),
            memory=self.memory,
        )


@lru_cache(maxsize=1)
def get_container() -> Container:
    return Container()
