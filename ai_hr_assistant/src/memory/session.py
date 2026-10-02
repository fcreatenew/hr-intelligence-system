from __future__ import annotations

import threading
import uuid
from collections import OrderedDict

from src.schemas.chat import ChatMessage


class SessionMemory:
    """Riwayat percakapan in-memory per sesi.

    Menyimpan sejumlah pesan terakhir saja agar konteks tetap ringkas dan
    penggunaan memori terkendali. Antarmuka ini bisa diganti penyimpanan
    persisten (Redis/DB) tanpa mengubah agent.
    """

    def __init__(self, max_messages: int = 12, max_sessions: int = 500):
        self._max_messages = max_messages
        self._max_sessions = max_sessions
        self._sessions: OrderedDict[str, list[ChatMessage]] = OrderedDict()
        self._lock = threading.Lock()

    @staticmethod
    def new_session_id() -> str:
        return uuid.uuid4().hex

    def _touch(self, session_id: str) -> list[ChatMessage]:
        if session_id not in self._sessions:
            self._sessions[session_id] = []

        self._sessions.move_to_end(session_id)

        while len(self._sessions) > self._max_sessions:
            self._sessions.popitem(last=False)

        return self._sessions[session_id]

    def append(self, session_id: str, message: ChatMessage) -> None:
        with self._lock:
            history = self._touch(session_id)
            history.append(message)

            if len(history) > self._max_messages:
                del history[: len(history) - self._max_messages]

    def history(self, session_id: str) -> list[ChatMessage]:
        with self._lock:
            return list(self._sessions.get(session_id, []))

    def clear(self, session_id: str) -> None:
        with self._lock:
            self._sessions.pop(session_id, None)

    def last_employee_id(self, session_id: str) -> str | None:
        """Karyawan terakhir yang dibahas, untuk menyambung percakapan."""
        for message in reversed(self.history(session_id)):
            if message.role != "user":
                continue

            from src.agents.router import detect_employee_id

            found = detect_employee_id(message.content)
            if found:
                return found

        return None
