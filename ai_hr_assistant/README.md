# AI HR Assistant

Project untuk membangun **AI Assistant khusus Human Resources (HR)** menggunakan Large Language Model (LLM), Retrieval-Augmented Generation (RAG), embeddings, dan AI tools.

Project ini bertujuan membuat interface berbasis AI yang dapat membantu pengguna berinteraksi dengan knowledge dan layanan HR menggunakan bahasa natural.

---

# Tujuan

AI HR Assistant menjadi layer **Artificial Intelligence dan Natural Language Interface** dalam HR Intelligence System.

```text
User
 │
 ▼
AI HR Assistant
 │
 ├── LLM
 ├── RAG
 ├── Knowledge Base
 ├── Embeddings
 ├── Vector Search
 └── AI Tools
 │
 ▼
HR Information / HR Services
```

---

# Fitur

## HR Question Answering

Assistant dapat digunakan untuk menjawab pertanyaan berdasarkan knowledge HR.

Contoh:

```text
"Bagaimana prosedur pengajuan cuti?"

"Apa dokumen yang diperlukan untuk proses tertentu?"

"Bagaimana prosedur reimbursement?"
```

Jawaban dapat menggunakan knowledge yang tersedia di knowledge base.

---

## RAG

Retrieval-Augmented Generation digunakan agar LLM dapat mengambil informasi dari sumber knowledge sebelum menghasilkan jawaban.

```text
User Question
      │
      ▼
   Retrieval
      │
      ▼
Relevant Documents
      │
      ▼
     LLM
      │
      ▼
    Answer
```

---

## Knowledge Base

Knowledge HR dapat berasal dari:

* HR Policy
* Procedure
* FAQ
* Regulation
* Documentation
* Internal knowledge

Struktur:

```text
knowledge/
├── policies/
├── procedures/
├── regulations/
└── faq/
```

---

## Embeddings

Dokumen dan query dapat diubah menjadi vector representation.

```text
Document
   ↓
Embedding Model
   ↓
Vector
   ↓
Vector Store
```

Query pengguna kemudian digunakan untuk mencari dokumen yang relevan.

---

## AI Tools

Assistant dapat dikembangkan dengan tools untuk menjalankan fungsi tertentu.

Contoh:

* Employee lookup
* Leave information
* Attendance information
* Payroll information
* HR reporting

Tool harus memiliki batas akses dan validasi yang jelas.

---

# Struktur Project

```text
ai_hr_assistant/
│
├── README.md
├── requirements.txt
├── .env.example
│
├── knowledge/
│   ├── policies/
│   ├── procedures/
│   ├── regulations/
│   └── faq/
│
├── prompts/
│   ├── system/
│   ├── hr/
│   ├── payroll/
│   └── recruitment/
│
├── src/
│   ├── main.py
│   ├── config.py
│   ├── api/
│   ├── agents/
│   ├── llm/
│   ├── rag/
│   ├── embeddings/
│   ├── retrieval/
│   ├── tools/
│   ├── memory/
│   ├── schemas/
│   └── services/
│
└── tests/
```

---

# Prompt Engineering

Prompt dikelola secara terstruktur berdasarkan kebutuhan.

Contoh:

```text
prompts/
├── system/
├── hr/
├── payroll/
└── recruitment/
```

Tujuannya agar prompt dapat:

* Versioned
* Tested
* Reused
* Evaluated
* Dikembangkan secara terpisah dari source code

---

# AI Architecture

Arsitektur yang direncanakan:

```text
                    ┌───────────────┐
                    │     User      │
                    └───────┬───────┘
                            │
                            ▼
                    ┌───────────────┐
                    │ AI HR Assistant│
                    └───────┬───────┘
                            │
                 ┌──────────┴──────────┐
                 ▼                     ▼
           ┌───────────┐        ┌───────────┐
           │    RAG    │        │ AI Tools  │
           └─────┬─────┘        └─────┬─────┘
                 │                    │
                 ▼                    ▼
           ┌───────────┐        ┌───────────┐
           │ Knowledge │        │ HR System │
           │   Base    │        │    API    │
           └───────────┘        └───────────┘
                 │                    │
                 └──────────┬─────────┘
                            ▼
                         ┌──────┐
                         │ LLM  │
                         └──────┘
```

---

# Teknologi

Teknologi yang dapat digunakan:

* Python
* LLM
* RAG
* Embedding Models
* Vector Database
* REST API
* Prompt Engineering

Model dan provider LLM dapat diganti sesuai kebutuhan arsitektur.

---

# Prinsip Pengembangan

* Retrieval-first architecture
* Modular AI components
* Prompt versioning
* Evaluation-driven development
* Explainable retrieval
* Controlled tool access
* Separation antara knowledge dan application logic

---

# Implementasi

Modul berikut sudah diimplementasikan pada folder `src/`.

| Modul | Tanggung jawab |
| ----- | -------------- |
| `config.py` | Konfigurasi (env): API HR, LLM, embeddings, retrieval |
| `schemas/chat.py` | Kontrak request/response (`AssistantRequest`, `AssistantResponse`) |
| `embeddings/embedder.py` | Antarmuka `Embedder` + `HashingEmbedder` lokal deterministik |
| `llm/provider.py` | Antarmuka `LLMProvider` + `MockLLM` (offline) & `OpenAILLM` (opsional) |
| `llm/prompts.py` | Pemuat prompt terstruktur dari `prompts/` |
| `rag/loader.py` | Pembaca markdown, chunker per heading, tag dokumen |
| `rag/service.py` | `RagService`: knowledge base + retriever |
| `retrieval/vector_store.py` | Vector store in-memory (cosine similarity) |
| `retrieval/retriever.py` | Retriever hybrid: BM25 ringan + skor semantik |
| `tools/hr_intelligence_client.py` | Klien HTTP ke HR Intelligence API (retry, auth, error mapping) |
| `tools/prediction_tools.py` | AI tools untuk 5 model + `model_info`, `list_models`, `health` |
| `tools/registry.py` | Registry tool dengan batas akses (hanya tool terdaftar) |
| `agents/router.py` | Deteksi intent, use case, dan employee id dari pesan |
| `agents/hr_assistant.py` | Agent: retrieval + tool -> jawaban + sitasi |
| `memory/session.py` | Riwayat percakapan per sesi (in-memory, terbatas) |
| `services/container.py` | Perakitan dependensi |
| `api/routes.py`, `main.py` | REST API FastAPI |

## Alur Agent

```text
Pesan pengguna
   │
   ▼
Router (intent: knowledge / employee_insight / hybrid)
   │
   ├── RAG  -> ambil chunk knowledge base (sitasi)
   └── Tools -> panggil model ML lewat HR Intelligence API
   │
   ▼
Komposisi jawaban (LLM bila aktif, jika tidak deterministik)
   │
   ▼
AssistantResponse (answer, citations, tool_calls, suggestions)
```

## Integrasi dengan Model ML

Assistant terhubung ke lima model yang sudah ada lewat `HR Intelligence API`:

| Use case | Tool | Endpoint API |
| -------- | ---- | ------------ |
| turnover | `predict_turnover` | `POST /predict` |
| absensi | `predict_absensi` | `POST /predict/absensi` |
| kinerja | `predict_kinerja` | `POST /predict/kinerja` |
| payroll | `predict_payroll` | `POST /predict/payroll` |
| survival | `predict_survival` | `POST /predict/survival` |

Selain itu tersedia tool `model_info`, `list_models`, dan `health`.

Karena assistant belum memiliki akses ke database HR, tiap tool memakai
**payload default** karyawan tipikal yang dapat ditimpa lewat parameter
`attributes`. Tujuannya agar model dapat dipanggil end-to-end; integrasi ke data
karyawan sebenarnya dapat ditambahkan tanpa mengubah agent maupun registry.

## Endpoint REST

| Method | Path | Keterangan |
| ------ | ---- | ---------- |
| `GET` | `/health` | Status assistant, knowledge base, dan koneksi API HR |
| `GET` | `/tools` | Daftar tool yang tersedia |
| `GET` | `/knowledge` | Daftar dokumen knowledge base |
| `GET` | `/models` | Daftar model dari HR Intelligence API |
| `POST` | `/chat` | Tanya jawab assistant |

Contoh:

```bash
curl -X POST http://127.0.0.1:8100/chat \
  -H "Content-Type: application/json" \
  -d '{"message":"Berapa risiko turnover karyawan EMP0001?","employee_id":"EMP0001"}'
```

## Menjalankan

```bash
# 1. Jalankan HR Intelligence API (terminal terpisah)
cd ../hr_intelligence_api
HR_API_KEY=dev-secret uvicorn app.main:app --port 8000

# 2. Jalankan AI HR Assistant
cd ai_hr_assistant
cp .env.example .env          # isi HR_INTELLIGENCE_API_KEY
uvicorn src.main:app --port 8100
```

## Testing

```bash
cd ai_hr_assistant
python -m pytest tests/ -q
```

Test memakai `MockLLM` dan `httpx.MockTransport`, sehingga tidak membutuhkan
API HR yang berjalan maupun kunci API sungguhan.

---

# Pengembangan

Masuk ke directory:

```bash
cd ai_hr_assistant
```

Buat environment:

```bash
python -m venv .venv
```

Aktifkan:

```bash
source .venv/bin/activate
```

Install dependency:

```bash
pip install -r requirements.txt
```

Environment variable dapat disimpan dalam:

```text
.env
```

Gunakan:

```text
.env.example
```

sebagai template konfigurasi.

---

## License

Private / Proprietary.

Hak penggunaan, distribusi, modifikasi, dan akses terhadap source code mengikuti kebijakan pemilik proyek.

© 2026 All Rights Reserved.