# HR Intelligence System

<p align="center">
  <strong>A modular platform for HR workflows, analytics, machine learning, and AI services.</strong>
</p>

<p align="center">
  <a href="https://github.com/fcreatenew/hr-intelligence-system/actions/workflows/ci.yml"><img alt="CI" src="https://github.com/fcreatenew/hr-intelligence-system/actions/workflows/ci.yml/badge.svg?branch=main"></a>
  <a href="https://www.python.org/"><img alt="Python 3.14" src="https://img.shields.io/badge/Python-3.14-3776AB?logo=python&logoColor=white"></a>
  <a href="https://nodejs.org/"><img alt="Node.js 24" src="https://img.shields.io/badge/Node.js-24-339933?logo=nodedotjs&logoColor=white"></a>
  <a href="https://www.php.net/"><img alt="PHP 8.4" src="https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white"></a>
</p>

**HR Intelligence System** is a modular engineering project exploring how human resources workflows, analytics, machine learning, and AI services can work together—from data validation and model evaluation to APIs, applications, dashboards, and MLOps.

The project began with **employee turnover prediction** and is expanding into a broader platform:

- **HR application** — HRIS workflows and employee operations
- **HR dashboard** — workforce analytics and visualization
- **HR Intelligence API** — model inference and service integration
- **Payroll intelligence** — payroll analytics and anomaly detection
- **Machine learning and deep learning** — reproducible experiments and model evaluation
- **AI HR Assistant** — LLM- and RAG-based assistant components
- **MLOps** — model validation, versioning, and workflow tooling

> **Project status:** Actively evolving. Capabilities, maturity, and deployment requirements vary by component. Review each component's documentation and tests before using it in production.

## Quick Links

| Goal | Start here |
|---|---|
| Understand the architecture and scope | [Architecture](#architecture) |
| Run the stack locally | [Docker Compose](#run-with-docker) |
| Set up a specific component | [Component Guides](#components) |
| Review model results and limitations | [Model Results](./MODEL_RESULTS.md) |
| Explore planned work | [Roadmap](./ROADMAP.md) |
| Contribute code or documentation | [CONTRIBUTING.md](./CONTRIBUTING.md) and [CONTRIBUTE.md](./CONTRIBUTE.md) |
| Report a security issue | [Security Policy](./SECURITY.md) |
| Review the license migration checklist | [License Migration Checklist](./LICENSE_MIGRATION_CHECKLIST.md) |

**Privacy first:** Use synthetic or properly authorized data for development and demonstrations. Never commit real employee records, credentials, secrets, or confidential HR information.

## AI and Machine Learning Models

Model artifacts used or evaluated by this project are available in the [HR Intelligence Models collection on Hugging Face](https://huggingface.co/fcreatenew/hr-intelligence-models/tree/main).

Review each model's files, model card, usage instructions, limitations, and license before use. Ensure that the model and its weights permit your intended use. The software repository license does not automatically apply to AI models or datasets.

Models included in this repository are documented in [models/README.md](./models/README.md). For the AI assistant, see [ai_hr_assistant/README.md](./ai_hr_assistant/README.md).

The user guide is available in both formats:
- [PANDUAN_PENGGUNAAN.md](./PANDUAN_PENGGUNAAN.md) — Markdown guide
- [PANDUAN_PENGGUNAAN.pdf](./PANDUAN_PENGGUNAAN.pdf) — PDF guide (15 pages)

The files retain their existing names for compatibility; the main README is written in English.

---

## Architecture

The repository follows a **modular, multi-project architecture**. Each component has its own source code, dependencies, tests, and documentation.

- **hr_application** — Laravel-based HRIS
- **hr_dashboard** — React analytics dashboard
- **hr_intelligence_api** — FastAPI service for ML inference
- **payroll_intelligence** — payroll analytics and anomaly detection
- **deep_learning** — neural network experiments
- **ai_hr_assistant** — LLM and RAG assistant
- **mlops** — model lifecycle and validation tooling

### Design Principles

- Give each project a clearly defined responsibility.
- Allow components to be developed independently.
- Integrate services through explicit contracts.
- Version machine learning models.
- Keep technical documentation with each component.
- Never store credentials or secrets in the repository.

---

## Components

### hr_application/

A Laravel/PHP-based Human Resource Information System (HRIS) for HR staff and employee operations. It also consumes services from the HR Intelligence API and Payroll Intelligence.

**Status:** Core HRIS workflows are implemented, including employee management, employee history, attendance, leave, payroll, reporting, audit trails, and user management.

Implemented capabilities include:
- Integration with the ML API across all five use cases
- Employee record-to-model input mapping through EmployeeFeatureMapper
- Scheduled batch predictions (monthly, weekly, and quarterly)
- Prediction storage and history
- Authentication, profiles, password resets, and admin / hr-manager roles
- **Employee Management:** CRUD operations, search, filtering, pagination, and termination
- **Employee History:** timelines for transfers, positions, salaries, and status
- **Attendance:** daily records, period summaries, and CSV imports from attendance systems
- **Leave:** requests, approvals, leave balances, and team calendars
- **Payroll:** pay periods, attendance-based calculations, finalization, payslips, and exports
- **Reporting:** headcount, turnover, and departmental costs, with CSV export
- **Audit Trail** and **User Management**
- Attendance-related model features derived from attendance records rather than entered manually

Planned HR process modules:

| # | Module | Potential ML integration |
|---|---|---|
| 1 | Recruitment / Applicant Tracking System | Resume screening and person-job fit |
| 2 | Onboarding / Offboarding | Turnover and survival analysis |
| 3 | Performance Management | Performance prediction and high-potential identification |
| 4 | Training and Development | Training recommendations |
| 5 | Compensation and Benefits | Payroll anomaly detection and forecasting |
| 6 | Advanced Time and Attendance | Absence prediction |
| 7 | Employee Self-Service | — |
| 8 | Document Management | — |
| 9 | Compliance and Statutory Reporting | — |

For module details, payroll formulas, and prediction schedules, see [hr_application/README.md](./hr_application/README.md). Development phases and completion criteria are tracked in [ROADMAP.md](./ROADMAP.md).

**Technology:** Laravel, PHP

### hr_intelligence_api/

A service for machine learning inference and model access through an API.

Capabilities include:
- Predictions for turnover, absenteeism, performance, payroll anomalies, and survival analysis
- Batch prediction
- Model information for each use case
- Health checks
- API authentication

**Technology:** Python, FastAPI, scikit-learn

Documentation: [hr_intelligence_api/README.md](./hr_intelligence_api/README.md)

### hr_dashboard/

A React and Vite application for HR data visualization and analysis.

Pages include Overview, Workforce, Turnover, Attendance, Payroll, HR KPIs, and Models. The dashboard can read from the HR Intelligence API and Payroll Intelligence API. It runs in **demo mode** by default using local sample data; set the environment variable VITE_DATA_MODE=live to use live APIs.

**Technology:** React, Vite, Recharts

Run locally from the component directory with npm install followed by npm run dev.

Documentation: [hr_dashboard/README.md](./hr_dashboard/README.md)

### payroll_intelligence/

A project for payroll analytics and intelligence:
- Payroll data ingestion and transformation
- Salary component calculations
- Payroll analytics by summary, department, period, and overtime
- Payroll anomaly detection using Isolation Forest
- Payroll forecasting using Holt and seasonal methods
- REST API and command-line interface

**Technology:** Python, FastAPI, scikit-learn, statsmodels

Run tests from the component directory with python -m pytest tests/ -q. Start the API with uvicorn src.api:app --port 8300.

Documentation: [payroll_intelligence/README.md](./payroll_intelligence/README.md)

### deep_learning/

Neural network experiments using a pure NumPy multilayer perceptron (MLP) for HR use cases, evaluated against classical baselines.

- MLP with backpropagation and early stopping, without PyTorch or TensorFlow
- Turnover feature contract aligned with the HR Intelligence API
- Logistic Regression and majority-class baselines for comparison
- JSON experiment records, CLI, and API

**Technology:** Python, NumPy, FastAPI

Run tests from the component directory with python -m pytest tests/ -q. Compare models with python -m src.cli compare.

Documentation: [deep_learning/README.md](./deep_learning/README.md)

### ai_hr_assistant/

An AI assistant for HR use cases built around large language models (LLMs).

Focus areas include LLMs, Retrieval-Augmented Generation (RAG), knowledge bases, embeddings, vector search, and AI tools.

The assistant can answer questions about policies and procedures using a knowledge base with retrieval and citations. It can also call the five existing ML use cases—turnover, absenteeism, performance, payroll anomalies, and survival—through the HR Intelligence API.

**Technology:** Python, FastAPI, LLMs, RAG

Documentation: [ai_hr_assistant/README.md](./ai_hr_assistant/README.md)

### mlops/

Tools for managing the machine learning lifecycle:
- Model registry, using model metadata as the source of truth
- Model validation as a deployment-readiness gate
- Population Stability Index (PSI) drift detection and Prometheus-format metrics
- REST API and validation CLI
- Docker Compose, GitHub Actions CI workflows, alert rules, and monitoring checklists

**Technology:** Python, FastAPI, Docker, CI/CD

Run tests from the component directory with python -m pytest tests/ -q. Run python -m src.cli to validate all models as a CI gate.

Documentation: [mlops/README.md](./mlops/README.md)

---

## Repository Structure

The following tree highlights the main files and directories. Individual components may contain additional files.

- **data/** — sample and synthetic HR datasets
- **requirements/base.txt** — shared Python dependency definitions
- **scripts/** — dataset generation, guide generation, and verification utilities
- **notebooks/** — model training, evaluation, fairness, and significance experiments
- **models/** — versioned model artifacts and evaluation reports
- **hr_intelligence_api/** — ML inference API
- **hr_application/** — Laravel HRIS
- **hr_dashboard/** — React dashboard
- **payroll_intelligence/** — payroll analytics service
- **deep_learning/** — neural network experiments
- **ai_hr_assistant/** — HR assistant and knowledge resources
- **mlops/** — model validation, monitoring, and CI tooling

The shared Python dependency source of truth is requirements/base.txt. The root .python-version file specifies Python 3.14 for Python modules.

---

## Current Machine Learning Models

The HR Intelligence API exposes **five machine learning use cases**. They are available through the API and can be called by the HR application and AI assistant.

| # | Use case | Endpoint | Model | Version | Primary metric |
|---|---|---|---|---|---|
| 1 | Turnover | POST /predict | Soft Voting ensemble (Logistic Regression + Random Forest) | v2_mitigated | ROC-AUC 0.6993 |
| 2 | Absenteeism | POST /predict/absensi | Logistic Regression | v1 | ROC-AUC 0.8287 |
| 3 | Performance | POST /predict/kinerja | Ordinal Logistic Regression | v1 | Macro-F1 0.6357 |
| 4 | Payroll anomaly | POST /predict/payroll | Isolation Forest | v1 | PR-AUC 0.8709 |
| 5 | Survival analysis | POST /predict/survival | Cox Proportional Hazards | v1 | C-index 0.8144 |

Three turnover artifacts are available: v1, v2, and v2_mitigated. The reweighted v2_mitigated model is the default API model.

A deep learning turnover model (NumPy MLP) is also available in deep_learning/ for comparison. On the current dataset, the classical baseline performs better.

For feature engineering, evaluation, metadata, fairness audits, and significance tests, see:
- [models/README.md](./models/README.md)
- [MODEL_RESULTS.md](./MODEL_RESULTS.md)

## Additional HR Machine Learning Use Cases

Machine learning can support HR use cases beyond turnover prediction. Other use cases, including recruitment, natural language processing, high-potential identification, and workforce segmentation, are described with data and references in [USE_CASES.md](./USE_CASES.md).

Detailed model comparisons and fairness audit results are available in [MODEL_RESULTS.md](./MODEL_RESULTS.md).

---

## Getting Started

Clone the repository and enter its directory:

    git clone <repository-url>
    cd hr-intelligence-system

Each component has its own environment and setup instructions. Required runtime versions:
- **Python 3.14** for Python modules, pinned in .python-version and enforced in CI and Dockerfiles
- **Node.js 24** for hr_dashboard
- **PHP 8.4** for hr_application

See section 3 of PANDUAN_PENGGUNAAN.md for component-specific details.

Start with the documentation for the component you want to develop:
- [hr_application/README.md](./hr_application/README.md)
- [hr_intelligence_api/README.md](./hr_intelligence_api/README.md)
- [hr_dashboard/README.md](./hr_dashboard/README.md)
- [payroll_intelligence/README.md](./payroll_intelligence/README.md)
- [deep_learning/README.md](./deep_learning/README.md)
- [ai_hr_assistant/README.md](./ai_hr_assistant/README.md)
- [mlops/README.md](./mlops/README.md)

## Run with Docker

Start the core stack with Docker Compose:

    HR_API_KEY=<secret> docker compose up --build

| Service | URL | Purpose |
|---|---|---|
| hr-api | http://localhost:8000 | HR Intelligence API and model predictions |
| payroll-api | http://localhost:8300 | Payroll analytics and anomaly scores |
| hr-app | http://localhost:8080 | Laravel HRIS for attendance, leave, and payroll |
| dashboard | http://localhost:8081 | React dashboard |
| assistant | http://localhost:8100 | AI HR Assistant |

The HR application is configured to reach the HR API and Payroll API through the internal Compose network. Set Laravel's APP_KEY environment variable to a valid value such as APP_KEY=base64:....

The dashboard uses Nginx as a reverse proxy. Configure API upstreams through HR_API_UPSTREAM and PAYROLL_API_UPSTREAM if needed; see hr_dashboard/nginx.conf.template. The defaults are configured for Docker Compose.

## Free Demo and Deployment

For deployment options such as Render, Cloudflare Pages, Koyeb, Oracle Cloud, and Hugging Face Hub, see:
- [deploy/DEPLOY.md](./deploy/DEPLOY.md) — deployment guide and render.yaml
- [hf/space/](./hf/space) — static showcase for Hugging Face Spaces
- [scripts/upload_to_hf.py](./scripts/upload_to_hf.py) — upload models and datasets to Hugging Face Hub

In brief, models and datasets can be hosted on Hugging Face Hub; the dashboard can run on Cloudflare Pages in demo mode without a backend or on Render; Python APIs and Laravel can run as Render web services; and the full always-on stack can run on Oracle Cloud Always Free using deploy/oracle-cloud/.

For contribution guidelines, see [CONTRIBUTE.md](./CONTRIBUTE.md).

---

## Project Philosophy

This project is about more than building a single machine learning model. It explores how to build an end-to-end system:

    Data
     ↓
    Data Science
     ↓
    Machine Learning
     ↓
    Model
     ↓
    API
     ↓
    Application
     ↓
    Dashboard
     ↓
    AI
     ↓
    MLOps
     ↓
    Production

Models should not remain isolated in notebooks; they should be developed into components that can be integrated into practical applications.

> **Build → Learn → Share → Improve**

---

## License

### Source Code

The project's source code is licensed under the **GNU Affero General Public License v3.0 only (AGPL-3.0-only)**. See [LICENSE](./LICENSE) for the full text. This applies to project code, including ai_hr_assistant/, unless a file or component explicitly states a different license.

AGPL-3.0-only permits use, modification, and distribution, including commercial use. Distribution of modified versions or making covered software available over a network may trigger requirements to provide the corresponding source code. Read the license text for the complete terms.

### Documentation

Documentation created specifically for this project—such as guides, tutorials, diagrams, and explanatory materials—is licensed under **Creative Commons Attribution 4.0 International (CC BY 4.0)** as described in [DOCUMENTATION_LICENSE.md](./DOCUMENTATION_LICENSE.md). Third-party materials and software code snippets are not automatically covered by the documentation license.

### Models, Datasets, and Third-Party Components

The main software license does **not automatically apply** to AI models, model weights, tokenizers, datasets, dependencies, or third-party assets. Check the license and notices accompanying each artifact before using, modifying, or redistributing it. See [models/README.md](./models/README.md), the [Hugging Face model collection](https://huggingface.co/fcreatenew/hr-intelligence-models/tree/main), and [THIRD_PARTY_NOTICES.md](./THIRD_PARTY_NOTICES.md).

### Previous Versions and Attribution

The current license change does not revoke MIT permissions already granted to recipients of earlier versions or copies. Copyright and license notices applicable to third-party components must be preserved.

If you find a file with conflicting license or ownership notices, please open an issue before reusing or redistributing it.
