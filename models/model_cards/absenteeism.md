# Model card: absenteeism

- **Status:** Research / evaluation only; not approved for automated employment decisions.
- **Task:** Estimate an absenteeism-related outcome as defined by the training label.
- **Reported metric:** `MODEL_RESULTS.md` reports ROC-AUC 0.8287 for the current documented model. Verify the associated dataset, split, and artifact version before relying on this number.
- **Data provenance:** Confirm whether the exact training/evaluation data are synthetic, their permissions, date range, and label construction before release.
- **Known risks:** Medical or disability inference, confusing approved leave with unplanned absence, shift/calendar effects, and temporal leakage.
- **Out of scope:** Disciplinary action, attendance penalties, or inference about an employee's health.
- **Required checks:** Validate label definitions, exclude approved leave where appropriate, use temporal holdouts, report calibration and subgroup error rates, and review privacy implications.
- **Human review:** Treat outputs only as aggregate planning signals; do not make individual employment decisions from a score.
