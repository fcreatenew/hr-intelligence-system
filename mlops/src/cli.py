from __future__ import annotations

import sys

from src.registry.model_registry import ModelRegistry
from src.validation.model_validator import validate_all


def main() -> int:
    """Validasi seluruh model terdaftar; exit code 1 bila ada yang gagal.

    Dipakai sebagai gate di CI sebelum model dianggap layak deploy.
    """
    registry = ModelRegistry()
    hasil = validate_all(registry)

    for item in hasil["hasil"]:
        status = "LULUS" if item["lulus"] else "GAGAL"
        print(f"[{status}] {item['use_case']} ({item['versi']})")
        for check in item["checks"]:
            tanda = "ok" if check["lulus"] else "!!"
            print(f"   {tanda} {check['nama']}: {check['detail']}")

    print()
    print(
        f"Total: {hasil['jumlah']} model, "
        f"semua lulus: {hasil['lulus_semua']}"
    )

    return 0 if hasil["lulus_semua"] else 1


if __name__ == "__main__":
    sys.exit(main())
