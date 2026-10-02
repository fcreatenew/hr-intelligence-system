from __future__ import annotations

from dataclasses import dataclass
from typing import Any, Callable


@dataclass
class ToolResult:
    tool: str
    ok: bool
    summary: str
    data: dict[str, Any]
    use_case: str | None = None
    error: str | None = None


class ToolError(RuntimeError):
    ...


class ToolRegistry:
    """Registry tool dengan batas akses yang jelas.

    Setiap tool punya nama, deskripsi, dan fungsi. Agent hanya boleh memanggil
    tool yang terdaftar — tidak ada eksekusi dinamis dari nama sembarang.
    """

    def __init__(self):
        self._tools: dict[str, tuple[str, Callable[..., ToolResult]]] = {}

    def register(
        self,
        name: str,
        description: str,
        func: Callable[..., ToolResult],
    ) -> None:
        if name in self._tools:
            raise ValueError(f"Tool '{name}' sudah terdaftar.")

        self._tools[name] = (description, func)

    def __contains__(self, name: str) -> bool:
        return name in self._tools

    def names(self) -> list[str]:
        return sorted(self._tools)

    def describe(self) -> list[dict[str, str]]:
        return [
            {"name": name, "description": description}
            for name, (description, _) in sorted(self._tools.items())
        ]

    def call(self, name: str, **kwargs) -> ToolResult:
        if name not in self._tools:
            raise ToolError(f"Tool tidak terdaftar: '{name}'.")

        _, func = self._tools[name]

        return func(**kwargs)
