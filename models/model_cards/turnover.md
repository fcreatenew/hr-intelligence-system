# Model card: employee turnover

- **Status:** Research / evaluation only; not approved for automated employment decisions.
- **Task:** Estimate a turnover-related outcome from the documented feature schema.
- **Versions:** See `models/README.md` and the active model registry for the exact artifact.
- **Reported evaluation:** `MODEL_RESULTS.md` reports ROC-AUC 0.6993 for the v2 turnover model. The reported evaluation context and confidence interval are described there; do not compare versions without matching splits and data.
- **Data provenance:** Some project evaluation datasets are synthetic. The dataset source, permissions, and cohort composition must be checked for the exact artifact before use.
- **Known risks:** Label leakage, dataset shift, proxies for protected characteristics, unequal error rates, poor calibration, and self-fulfilling feedback loops.
- **Out of scope:** Automated termination, hiring rejection, promotion, compensation, or employee ranking.
- **Required before any consequential use:** Document dataset rights and provenance; evaluate on a representative temporal/group-aware holdout; report calibration and subgroup uncertainty; obtain privacy and fairness review; retain human review and an appeal path.
- **Monitoring:** Track input drift, calibration, subgroup performance where sample sizes permit, and changes in outcome prevalence. Define a rollback threshold before deployment.

The reported metric is not a guarantee of accuracy for a different organization or population.
