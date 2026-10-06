"""Audit fairness model turnover (menggantikan audit ad-hoc di notebook).

Notebook `05_fairness_audit_turnover.ipynb` menghitung metrik keadilan dengan
kode yang tidak dapat diuji dan tidak dijalankan berkala. Modul ini memindahkan
logika itu ke fungsi yang dapat diuji + dipanggil CLI/API, sehingga audit bias
menjadi bagian dari pipeline (prasyarat high-risk AI: EU AI Act Annex III,
NYC Local Law 144, UU PDP 27/2022).

Metrik yang dihitung per atribut:
- demographic parity difference  — selisih selection rate antar kelompok
- impact ratio (four-fifths)     — min/max selection rate; < 0.8 = pelanggaran
- equal opportunity difference   — selisih TPR antar kelompok
- equalized odds difference      — maks(selisih TPR, selisih FPR)

Kelompok dibentuk dari atribut yang tersedia di dataset (usia dipecah per
rentang). Dataset ini tidak punya atribut gender/etnis; keterbatasan itu
dilaporkan eksplisit, bukan disembunyikan.
"""

from __future__ import annotations

from dataclasses import dataclass, field
from pathlib import Path

import numpy as np
import pandas as pd
from sklearn.metrics import roc_auc_score

from src.config import DATA_DIR, MODELS_DIR
from src.registry.model_registry import ModelRegistry
from src.train.pipeline import REF_DATE, TARGET, buat_fitur, load_data


# Atribut yang diaudit. `usia` dipecah menjadi rentang agar tiap kelompok punya
# sampel memadai; sisanya dipakai apa adanya.
USIA_BINS = [0, 25, 30, 35, 40, 100]
USIA_LABELS = ["<=25", "26-30", "31-35", "36-40", ">40"]

GROUP_ATTRIBUTES: tuple[str, ...] = ("usia", "departemen", "status_kerja", "pendidikan")

# Ambang four-fifths rule (80%).
IMPACT_RATIO_THRESHOLD = 0.8


@dataclass
class FairnessConfig:
    use_case: str = "turnover"
    version: str | None = None
    data_file: str = "hr_turnover.csv"
    threshold: float | None = None
    min_group_size: int = 20
    impact_ratio_threshold: float = IMPACT_RATIO_THRESHOLD


@dataclass
class GroupMetrics:
    kelompok: str
    n: int
    selection_rate: float
    tpr: float | None
    fpr: float | None
    precision: float | None
    roc_auc: float | None

    def to_dict(self) -> dict:
        return {
            "n": self.n,
            "selection_rate": self.selection_rate,
            "tpr": self.tpr,
            "fpr": self.fpr,
            "precision": self.precision,
            "roc_auc": self.roc_auc,
        }


@dataclass
class AttributeAudit:
    atribut: str
    kelompok: dict[str, GroupMetrics]
    demographic_parity_diff: float | None
    impact_ratio: float | None
    equal_opportunity_diff: float | None
    equalized_odds_diff: float | None
    pelanggaran: list[str] = field(default_factory=list)

    def to_dict(self) -> dict:
        return {
            "atribut": self.atribut,
            "kelompok": {k: v.to_dict() for k, v in self.kelompok.items()},
            "demographic_parity_diff": self.demographic_parity_diff,
            "impact_ratio": self.impact_ratio,
            "equal_opportunity_diff": self.equal_opportunity_diff,
            "equalized_odds_diff": self.equalized_odds_diff,
            "pelanggaran": self.pelanggaran,
        }


def _bucket_usia(series: pd.Series) -> pd.Series:
    return pd.cut(series, bins=USIA_BINS, labels=USIA_LABELS).astype(str)


def build_groups(df: pd.DataFrame, attributes: tuple[str, ...] = GROUP_ATTRIBUTES) -> dict[str, pd.Series]:
    """Bentuk kelompok per atribut; hanya atribut yang ada di data."""
    groups: dict[str, pd.Series] = {}

    for attribute in attributes:
        if attribute == "usia":
            if "usia" in df.columns:
                groups["usia"] = _bucket_usia(df["usia"])
        elif attribute in df.columns:
            groups[attribute] = df[attribute].astype(str)

    return groups


def _group_metrics(
    mask: np.ndarray,
    y_true: np.ndarray,
    y_pred: np.ndarray,
    y_score: np.ndarray | None,
) -> GroupMetrics:
    yt = y_true[mask]
    yp = y_pred[mask]

    tp = int(((yt == 1) & (yp == 1)).sum())
    fn = int(((yt == 1) & (yp == 0)).sum())
    fp = int(((yt == 0) & (yp == 1)).sum())
    tn = int(((yt == 0) & (yp == 0)).sum())

    tpr = tp / (tp + fn) if (tp + fn) else None
    fpr = fp / (fp + tn) if (fp + tn) else None
    precision = tp / (tp + fp) if (tp + fp) else None

    auc = None
    if y_score is not None and len(np.unique(yt)) > 1:
        auc = round(float(roc_auc_score(yt, y_score[mask])), 4)

    return GroupMetrics(
        kelompok="",
        n=int(mask.sum()),
        selection_rate=round(float(yp.mean()), 4),
        tpr=None if tpr is None else round(tpr, 4),
        fpr=None if fpr is None else round(fpr, 4),
        precision=None if precision is None else round(precision, 4),
        roc_auc=auc,
    )


def audit_attribute(
    groups: pd.Series,
    y_true: np.ndarray,
    y_pred: np.ndarray,
    y_score: np.ndarray | None = None,
    attribute: str = "",
    min_group_size: int = 20,
    impact_ratio_threshold: float = IMPACT_RATIO_THRESHOLD,
) -> AttributeAudit:
    """Hitung metrik keadilan untuk satu atribut pelindung."""
    groups = groups.astype(str).reset_index(drop=True)
    metrics: dict[str, GroupMetrics] = {}

    for name in sorted(groups.unique()):
        mask = (groups == name).to_numpy()
        item = _group_metrics(mask, y_true, y_pred, y_score)
        item.kelompok = name
        metrics[name] = item

    # Hanya kelompok dengan sampel memadai yang dipakai untuk disparitas,
    # agar satu kelompok mungil tidak mendikte kesimpulan.
    cukup = {name: m for name, m in metrics.items() if m.n >= min_group_size}

    pelanggaran: list[str] = []

    if len(cukup) < 2:
        return AttributeAudit(
            atribut=attribute,
            kelompok=metrics,
            demographic_parity_diff=None,
            impact_ratio=None,
            equal_opportunity_diff=None,
            equalized_odds_diff=None,
            pelanggaran=pelanggaran,
        )

    rates = [m.selection_rate for m in cukup.values()]
    dp_diff = round(max(rates) - min(rates), 4)
    impact_ratio = round(min(rates) / max(rates), 4) if max(rates) > 0 else None

    tprs = [m.tpr for m in cukup.values() if m.tpr is not None]
    fprs = [m.fpr for m in cukup.values() if m.fpr is not None]

    eo_diff = round(max(tprs) - min(tprs), 4) if len(tprs) >= 2 else None
    eodds_diff = None
    if len(tprs) >= 2 or len(fprs) >= 2:
        tpr_gap = max(tprs) - min(tprs) if len(tprs) >= 2 else 0.0
        fpr_gap = max(fprs) - min(fprs) if len(fprs) >= 2 else 0.0
        eodds_diff = round(max(tpr_gap, fpr_gap), 4)

    if impact_ratio is not None and impact_ratio < impact_ratio_threshold:
        pelanggaran.append(
            f"impact ratio {impact_ratio} < {impact_ratio_threshold} (four-fifths rule)"
        )

    return AttributeAudit(
        atribut=attribute,
        kelompok=metrics,
        demographic_parity_diff=dp_diff,
        impact_ratio=impact_ratio,
        equal_opportunity_diff=eo_diff,
        equalized_odds_diff=eodds_diff,
        pelanggaran=pelanggaran,
    )


def audit_fairness(
    df: pd.DataFrame,
    y_true: np.ndarray,
    y_pred: np.ndarray,
    y_score: np.ndarray | None = None,
    *,
    attributes: tuple[str, ...] = GROUP_ATTRIBUTES,
    min_group_size: int = 20,
    impact_ratio_threshold: float = IMPACT_RATIO_THRESHOLD,
) -> dict:
    """Audit keadilan untuk seluruh atribut; kembalikan dict siap-serialisasi."""
    groups = build_groups(df, attributes)
    audits = [
        audit_attribute(
            series,
            y_true,
            y_pred,
            y_score,
            attribute=name,
            min_group_size=min_group_size,
            impact_ratio_threshold=impact_ratio_threshold,
        )
        for name, series in groups.items()
    ]

    pelanggaran = [
        f"{audit.atribut}: {pesan}"
        for audit in audits
        for pesan in audit.pelanggaran
    ]

    return {
        "samples": int(len(df)),
        "atribut": {audit.atribut: audit.to_dict() for audit in audits},
        "ringkasan": {
            "lolos": not pelanggaran,
            "jumlah_pelanggaran": len(pelanggaran),
            "pelanggaran": pelanggaran,
            "atribut_diaudit": sorted(groups),
            "catatan": (
                "Dataset tidak memuat atribut gender/etnis; audit terbatas pada "
                "atribut yang tersedia."
            ),
        },
    }


def run_fairness_audit(
    config: FairnessConfig | None = None,
    *,
    data_dir: Path = DATA_DIR,
    models_dir: Path = MODELS_DIR,
    df: pd.DataFrame | None = None,
) -> dict:
    """Muat model + data, prediksi, lalu audit fairness.

    `df` dapat diberikan langsung (mis. pada test) untuk melewati pemuatan
    dataset dari disk.
    """
    import joblib

    config = config or FairnessConfig()
    registry = ModelRegistry(models_dir)
    record = registry.get(config.use_case, versi=config.version)

    threshold = config.threshold if config.threshold is not None else (record.threshold or 0.5)

    if df is None:
        df = load_data(data_dir / config.data_file, REF_DATE)

    X = buat_fitur(df, REF_DATE)
    model = joblib.load(Path(record.path) / "turnover_model.joblib")

    proba = model.predict_proba(X)[:, 1]
    pred = (proba >= threshold).astype(int)
    y_true = df[TARGET].astype(int).to_numpy()

    hasil = audit_fairness(
        df.reset_index(drop=True),
        y_true,
        pred,
        proba,
        min_group_size=config.min_group_size,
        impact_ratio_threshold=config.impact_ratio_threshold,
    )

    hasil["versi"] = record.versi
    hasil["model"] = record.model_type
    hasil["threshold"] = threshold

    return hasil
