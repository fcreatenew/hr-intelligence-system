from __future__ import annotations

from typing import Any

from src.tools.hr_intelligence_client import (
    HrIntelligenceClient,
    HrIntelligenceError,
)
from src.tools.registry import ToolRegistry, ToolResult


USE_CASE_LABELS: dict[str, str] = {
    "turnover": "Risiko turnover",
    "absensi": "Risiko absen tinggi",
    "kinerja": "Kategori kinerja",
    "payroll": "Anomali payroll",
    "survival": "Survival attrition",
}


# Payload contoh untuk tiap use case. Assistant belum punya akses database HR,
# jadi nilai default ini mewakili karyawan "tipikal" dan dapat ditimpa lewat
# parameter `attributes` pada tool. Tujuannya agar model bisa dipanggil
# end-to-end untuk keperluan tanya-jawab dan demo.
DEFAULT_PAYLOADS: dict[str, dict[str, Any]] = {
    "turnover": {
        "usia": 35,
        "masa_kerja_bulan": 60,
        "jam_lembur_bulanan": 10,
        "gaji_bulanan": 9_000_000,
        "tanggal_masuk": "2020-01-15",
        "tanggal_promosi": "2023-01-15",
        "departemen": "IT",
        "status_kerja": "Tetap",
        "pendidikan": "S1",
    },
    "absensi": {
        "usia": 35,
        "masa_kerja_bulan": 60,
        "jarak_km": 10.0,
        "beban_kerja": 60.0,
        "lembur_jam_bulanan": 15.0,
        "kepuasan_kerja": 3.5,
        "riwayat_absen_90hari": 3,
        "departemen": "IT",
        "status_kerja": "Tetap",
        "pendidikan": "S1",
    },
    "kinerja": {
        "usia": 35,
        "masa_kerja_bulan": 60,
        "jam_lembur_bulanan": 12.0,
        "jumlah_training_setahun": 4,
        "proyek_selesai": 8,
        "skor_umpan_balik_360": 3.8,
        "hari_absen_setahun": 6,
        "departemen": "IT",
        "pendidikan": "S1",
    },
    "payroll": {
        "periode": "2025-01",
        "departemen": "IT",
        "level": "Senior",
        "gaji_pokok": 9_000_000,
        "tunjangan": 1_500_000,
        "lembur_jam": 8,
        "tarif_lembur": 75_000,
        "potongan": 500_000,
        "total_gaji": 10_600_000,
    },
    "survival": {
        "usia": 35,
        "gaji_bulanan": 9_000_000,
        "jam_lembur_bulanan": 15.0,
        "kepuasan_kerja": 3.5,
        "bulan_sejak_promosi": 18.0,
        "jarak_km": 10.0,
        "departemen": "IT",
        "pendidikan": "S1",
    },
}


def build_payload(
    use_case: str,
    employee_id: str,
    attributes: dict[str, Any] | None = None,
) -> dict[str, Any]:
    if use_case not in DEFAULT_PAYLOADS:
        raise HrIntelligenceError(f"Use case tidak dikenal: '{use_case}'.")

    payload = dict(DEFAULT_PAYLOADS[use_case])
    payload["employee_id"] = employee_id

    if attributes:
        payload.update({k: v for k, v in attributes.items() if v is not None})

    if use_case == "payroll":
        # Jaga konsistensi total_gaji bila komponen diubah.
        payload.setdefault("total_gaji", 0)
        computed = (
            payload["gaji_pokok"]
            + payload["tunjangan"]
            + payload["lembur_jam"] * payload["tarif_lembur"]
            - payload["potongan"]
        )
        if "total_gaji" not in (attributes or {}):
            payload["total_gaji"] = computed

    return payload


def _summarize(use_case: str, result: dict[str, Any]) -> str:
    if use_case == "turnover":
        return (
            f"Probabilitas keluar {result.get('probabilitas_keluar')} "
            f"(level risiko: {result.get('level_risiko')})."
        )
    if use_case == "absensi":
        return (
            f"Probabilitas absen tinggi {result.get('probabilitas_absen_tinggi')} "
            f"(level risiko: {result.get('level_risiko')})."
        )
    if use_case == "kinerja":
        return (
            f"Kategori kinerja: {result.get('kategori_prediksi')} "
            f"(skor ordinal {result.get('skor_ordinal')})."
        )
    if use_case == "payroll":
        return (
            f"Skor anomali {result.get('skor_anomali')} "
            f"(status: {result.get('level')})."
        )
    if use_case == "survival":
        return (
            f"Risiko relatif {result.get('risiko_relatif')} "
            f"(level: {result.get('level_risiko')}, median survival "
            f"{result.get('median_survival_bulan')} bulan)."
        )

    return "Prediksi selesai."


def build_tool_registry(client: HrIntelligenceClient) -> ToolRegistry:
    registry = ToolRegistry()

    def _make_predict(use_case: str):
        def _predict(
            employee_id: str,
            attributes: dict[str, Any] | None = None,
        ) -> ToolResult:
            tool_name = f"predict_{use_case}"
            try:
                payload = build_payload(use_case, employee_id, attributes)
                result = client.predict(use_case, payload)
            except HrIntelligenceError as exc:
                return ToolResult(
                    tool=tool_name,
                    use_case=use_case,
                    ok=False,
                    summary=f"Gagal memanggil model {use_case}: {exc}",
                    data={},
                    error=str(exc),
                )

            return ToolResult(
                tool=tool_name,
                use_case=use_case,
                ok=True,
                summary=_summarize(use_case, result),
                data=result,
            )

        return _predict

    for use_case, label in USE_CASE_LABELS.items():
        registry.register(
            name=f"predict_{use_case}",
            description=(
                f"Prediksi {label.lower()} karyawan lewat HR Intelligence API."
            ),
            func=_make_predict(use_case),
        )

    def _model_info(use_case: str = "turnover") -> ToolResult:
        try:
            data = client.model_info(use_case)
        except HrIntelligenceError as exc:
            return ToolResult(
                tool="model_info",
                use_case=use_case,
                ok=False,
                summary=f"Gagal mengambil info model {use_case}: {exc}",
                data={},
                error=str(exc),
            )

        return ToolResult(
            tool="model_info",
            use_case=use_case,
            ok=True,
            summary=(
                f"Model {use_case}: {data.get('model')} "
                f"(versi {data.get('versi_model')})."
            ),
            data=data,
        )

    def _list_models() -> ToolResult:
        try:
            data = client.list_use_cases()
        except HrIntelligenceError as exc:
            return ToolResult(
                tool="list_models",
                ok=False,
                summary=f"Gagal mengambil daftar model: {exc}",
                data={},
                error=str(exc),
            )

        return ToolResult(
            tool="list_models",
            ok=True,
            summary=f"{len(data.get('use_cases', []))} model tersedia.",
            data=data,
        )

    def _health() -> ToolResult:
        try:
            data = client.health()
        except HrIntelligenceError as exc:
            return ToolResult(
                tool="health",
                ok=False,
                summary=f"HR Intelligence API tidak sehat: {exc}",
                data={},
                error=str(exc),
            )

        return ToolResult(
            tool="health",
            ok=True,
            summary=(
                f"Status API: {data.get('status')} "
                f"({data.get('models_loaded')}/{data.get('models_total')} model)."
            ),
            data=data,
        )

    registry.register(
        name="model_info",
        description="Ambil metadata & metrik satu model (use case) tertentu.",
        func=_model_info,
    )
    registry.register(
        name="list_models",
        description="Daftar seluruh model/use case yang dilayani HR Intelligence API.",
        func=_list_models,
    )
    registry.register(
        name="health",
        description="Cek status HR Intelligence API dan model yang termuat.",
        func=_health,
    )

    return registry
