# Model card: employee survival / tenure

- **Status:** Research / evaluation only.
- **Task:** Estimate a survival/tenure distribution under the assumptions of the selected survival model.
- **Reported metric:** `MODEL_RESULTS.md` reports C-index 0.8144. This alone does not establish calibration or suitability for individual decisions.
- **Data provenance:** Document observation window, event definition, censoring rules, cohort size, and synthetic-vs-real status for the exact artifact.
- **Known risks:** Informative censoring, changing organizational policy, temporal drift, and spurious relationships between employee characteristics and observed tenure.
- **Out of scope:** Selecting employees for retention programs solely by predicted tenure, or decisions to terminate or deny opportunities.
- **Required checks:** Validate censoring assumptions, use temporal holdouts, assess calibration of survival probabilities, and compare performance across cohorts with adequate sample sizes.
- **Human review:** Use only for aggregate workforce planning until the evidence supports a narrower use.
