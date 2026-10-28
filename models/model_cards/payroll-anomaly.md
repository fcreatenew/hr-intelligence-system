# Model card: payroll anomaly detection

- **Status:** Decision-support research; not an automated fraud detector.
- **Task:** Flag payroll records that differ from patterns learned by the anomaly model.
- **Reported metric:** `MODEL_RESULTS.md` reports PR-AUC 0.8709. Verify the test-set prevalence, split, and exact artifact before comparing this metric with another system.
- **Data provenance:** Record the source, permissions, pay periods, employee population, and synthetic-vs-real status of the exact dataset.
- **Known risks:** Legitimate one-off payments, bonuses, overtime, statutory changes, new hires, and small groups may be flagged. Anomaly scores can be affected by data-entry errors and changing policy.
- **Out of scope:** Automatically withholding pay, accusing an employee of fraud, or initiating disciplinary action.
- **Required checks:** Evaluate precision/recall at the actual review capacity, include known legitimate exceptions, monitor drift by pay period, and log reviewer outcomes.
- **Human review:** Every alert requires verification against source records and applicable payroll rules.
