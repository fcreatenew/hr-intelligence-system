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
└── src/
    ├── agents/
    ├── llm/
    ├── rag/
    ├── embeddings/
    ├── retrieval/
    ├── tools/
    ├── memory/
    ├── schemas/
    └── services/
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