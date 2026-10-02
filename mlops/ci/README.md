# CI

CI resmi ada di root repo: [`.github/workflows/ci.yml`](../../.github/workflows/ci.yml).

Workflow tersebut dijalankan pada setiap push dan pull request dengan job:

| Job | Cakupan |
| --- | --- |
| `hr-intelligence-api` | Test `hr_intelligence_api` |
| `python-modules` | Test matriks `payroll_intelligence`, `mlops`, `deep_learning`, `ai_hr_assistant` |
| `model-validation` | Gate validasi model terdaftar (`python -m src.cli`) |
| `hr-application` | Test Laravel `hr_application` |
| `hr-dashboard` | Lint, test, dan build `hr_dashboard` |

Template lama `mlops/ci/github-actions/ci.yml` dihapus karena tidak pernah
dijalankan GitHub (bukan di `.github/workflows/`) dan cakupannya lebih sempit.
