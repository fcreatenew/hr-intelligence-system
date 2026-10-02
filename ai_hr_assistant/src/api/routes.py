from __future__ import annotations

from fastapi import APIRouter, Depends

from src.api.auth import rate_limiter
from src.schemas.chat import AssistantRequest, AssistantResponse
from src.services.container import get_container


router = APIRouter()


@router.get("/health", tags=["monitoring"])
async def health() -> dict:
    container = get_container()

    try:
        api_status: dict = {"reachable": True, **container.client.health()}
    except Exception as exc:  # noqa: BLE001 - health tidak boleh gagal total
        api_status = {"reachable": False, "error": str(exc)}

    return {
        "status": "ok",
        "llm_provider": container.agent.llm_provider,
        "knowledge_documents": len(container.rag.documents),
        "knowledge_chunks": len(container.rag.chunks),
        "knowledge_categories": container.rag.categories(),
        "tools": container.tools.names(),
        "hr_intelligence_api": api_status,
    }


@router.get("/tools", tags=["assistant"], dependencies=[Depends(rate_limiter)])
async def list_tools() -> dict:
    container = get_container()

    return {"tools": container.tools.describe()}


@router.get("/knowledge", tags=["assistant"], dependencies=[Depends(rate_limiter)])
async def list_knowledge() -> dict:
    container = get_container()

    return {
        "documents": [
            {
                "doc_id": doc.doc_id,
                "title": doc.title,
                "category": doc.category,
                "source": doc.source,
                "tags": doc.tags,
            }
            for doc in container.rag.documents
        ],
        "categories": container.rag.categories(),
    }


@router.get("/models", tags=["assistant"], dependencies=[Depends(rate_limiter)])
async def list_models() -> dict:
    container = get_container()

    try:
        return container.client.list_use_cases()
    except Exception as exc:  # noqa: BLE001 - diteruskan sebagai 502
        raise HTTPException(
            status_code=502,
            detail=f"HR Intelligence API tidak tersedia: {exc}",
        ) from exc


@router.post(
    "/chat",
    response_model=AssistantResponse,
    tags=["assistant"],
    dependencies=[Depends(rate_limiter)],
)
async def chat(request: AssistantRequest) -> AssistantResponse:
    container = get_container()

    return container.agent.ask(
        message=request.message,
        session_id=request.session_id,
        employee_id=request.employee_id,
        use_case=request.use_case,
    )
