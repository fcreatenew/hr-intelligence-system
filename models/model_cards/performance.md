# Model card: performance

- **Status:** Research / evaluation only; high-risk advisory use.
- **Task:** Estimate the performance category defined by the training label.
- **Reported metric:** `MODEL_RESULTS.md` reports macro-F1 0.6357 for the documented model. Confirm the exact artifact, label mapping, and holdout methodology before interpretation.
- **Data provenance:** Dataset source, permission, cohort composition, and synthetic-vs-real status must be recorded for the exact release.
- **Known risks:** Historical ratings can encode manager bias, unequal opportunity, disability/accommodation effects, and proxy discrimination. Ordinal labels may not be comparable across teams.
- **Out of scope:** Automated ranking, promotion, compensation, performance warnings, or termination.
- **Required checks:** Validate rating consistency, evaluator effects, temporal/group-aware holdout, subgroup uncertainty, and calibration where appropriate.
- **Human review:** A score must never replace documented feedback, employee context, or a fair review and appeal process.
