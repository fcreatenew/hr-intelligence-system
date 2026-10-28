# Model cards

This directory records the intended use, evaluation context, known limitations, and release checks for each HR ML use case.

## Important status

The metrics currently published in `MODEL_RESULTS.md` and `models/README.md` must not be interpreted as evidence of production readiness. Some evaluations use synthetic data. Do not use these models as the sole basis for hiring, termination, promotion, compensation, or other decisions that materially affect an individual.

## Required fields for every model release

- Model/use-case name and immutable version or artifact hash
- Intended use and explicitly out-of-scope uses
- Dataset source, license/permission, date range, cohort size, label definition, and synthetic-vs-real status
- Feature list, preprocessing, missing-data behavior, and leakage checks
- Train/validation/test split strategy and reproducibility seed
- Metrics with uncertainty where possible, baseline comparison, and calibration
- Fairness evaluation methodology, sample sizes, limitations, and mitigation status
- Known failure modes, monitoring, rollback, and human-review requirements
- Model artifact license and dependency/provenance information

## Current use-case cards

| Use case | Card | Notes |
|---|---|---|
| Turnover / attrition | [turnover.md](turnover.md) | Synthetic-data and fairness caveats must remain visible |
| Absenteeism | [absenteeism.md](absenteeism.md) | Validate labels and temporal leakage |
| Performance | [performance.md](performance.md) | High risk of proxy discrimination; advisory only |
| Payroll anomaly | [payroll-anomaly.md](payroll-anomaly.md) | An anomaly is a review signal, not proof of fraud |
| Survival / tenure | [survival.md](survival.md) | Validate censoring assumptions and calibration |

Do not mark a card production-ready until dataset provenance, rights, holdout evaluation, privacy, and fairness review have evidence attached.
