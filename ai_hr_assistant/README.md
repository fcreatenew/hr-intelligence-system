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

Alur implementasi:

```text
knowledge/*.md
   │  loader.py        potong per heading, lalu per ukuran + overlap
   ▼
Chunk
   │  embedder.py      LSA (TF-IDF + SVD) → vektor semantik
   ▼
Vector Store (cosine)
   │
   ▼
Hybrid Retriever
   ├─ jalur leksikal : BM25 ringan (presisi istilah)
   ├─ jalur semantik : cosine embedding (kedekatan makna)
   ├─ fusion         : RRF (default) / linear / lexical / semantic
   └─ MMR            : buang hasil redundan (opsional, aktif default)
   ▼
RagService → agent → jawaban + sitasi
```

### Retrieval hybrid

Dua jalur retrieval sengaja dipadukan karena sifatnya saling melengkapi:

| Jalur | Kuat pada | Lemah pada |
| --- | --- | --- |
| Leksikal (BM25) | istilah persis, mis. "cuti tahunan" | sinonim ("izin tidak masuk") |
| Semantik (embedding) | kemiripan makna & variasi kata | istilah langka/akronim |

Hasil digabung dengan **Reciprocal Rank Fusion (RRF)** — menggabungkan
*peringkat* alih-alih skor, sehingga tidak perlu menyeimbangkan skala skor kedua
jalur. Mode `linear` (rata-rata berbobot), `lexical`, dan `semantic` tersedia
untuk perbandingan.

**MMR (Maximum Marginal Relevance)** membuang chunk yang isinya hampir sama,
sehingga `top_k` hasil saling melengkapi. Ini penting setelah chunking overlap,
karena potongan berdekatan bisa sangat mirip.

### Chunking

Dua tingkat agar potongan fokus namun tidak kehilangan konteks:

1. Batas `##`/`###` — satu potongan = satu sub-topik.
2. Batas ukuran (`CHUNK_MAX_CHARS`) + **overlap** (`CHUNK_OVERLAP_CHARS`) —
   potongan panjang dipecah sadar-kalimat, dan ekor potongan sebelumnya
   diulang di awal potongan berikutnya.

Baris `> **Tag:**` dibuang dari isi chunk (sudah diekstrak terpisah), agar tidak
muncul chunk "hantu" yang hanya berisi metadata.

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

Dokumen dan query diubah menjadi vector representation. Tiga backend tersedia
dan dapat ditukar lewat `EMBEDDING_BACKEND` tanpa mengubah alur RAG:

| Backend | Cara kerja | Sifat |
| --- | --- | --- |
| `lsa` *(default)* | TF-IDF + SVD (Latent Semantic Analysis) | semantik, deterministik, tanpa model eksternal, perlu `fit` pada korpus |
| `hashing` | feature hashing token + bigram | leksikal, paling ringan, tanpa `fit` |
| `neural` | sentence-transformers (mis. `paraphrase-multilingual-MiniLM-L12-v2`) | semantik terbaik & lintas bahasa, butuh paket + unduhan model |

```text
Document
   ↓
Embedding Model (lsa / hashing / neural)
   ↓
Vector
   ↓
Vector Store
```

Query pengguna kemudian digunakan untuk mencari dokumen yang relevan.

> **Catatan jujur:** `lsa` menangkap kemiripan makna jauh lebih baik daripada
> `hashing` (mis. kueri "uang lembur dibayar bagaimana" mencocokkan dokumen
> lembur dengan skor semantik ~0.70 vs ~0.26), tetapi ia masih berbasis
> statistik korpus — bukan embedding neural. Untuk kualitas terbaik, aktifkan
> `EMBEDDING_BACKEND=neural`.

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
| `embeddings/embedder.py` | Antarmuka `Embedder` + `LsaEmbedder` (semantik, default), `HashingEmbedder`, `NeuralEmbedder` |
| `llm/provider.py` | Antarmuka `LLMProvider` + `MockLLM` (offline) & `OpenAILLM` (opsional) |
| `llm/prompts.py` | Pemuat prompt terstruktur dari `prompts/` |
| `rag/loader.py` | Pembaca markdown, chunker dua tingkat (heading + ukuran/overlap), tag dokumen |
| `rag/service.py` | `RagService`: knowledge base + retriever (embedder, fusion, MMR) |
| `retrieval/vector_store.py` | Vector store in-memory (cosine similarity) + akses vektor untuk MMR |
| `retrieval/retriever.py` | Retriever hybrid: BM25 + semantik, fusion RRF/linear, MMR |
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

Seluruh endpoint **kecuali `/health`** mewajibkan header `X-API-Key`. Bila
`ASSISTANT_API_KEY` belum diset di server, endpoint terproteksi menolak semua
request (fail closed).

| Method | Path | Auth | Keterangan |
| ------ | ---- | ---- | ---------- |
| `GET` | `/health` | publik | Status assistant, knowledge base, dan koneksi API HR |
| `GET` | `/tools` | API key | Daftar tool yang tersedia |
| `GET` | `/knowledge` | API key | Daftar dokumen knowledge base |
| `GET` | `/models` | API key | Daftar model dari HR Intelligence API |
| `POST` | `/chat` | API key | Tanya jawab assistant |

Contoh:

```bash
curl -X POST http://127.0.0.1:8100/chat \
  -H "Content-Type: application/json" \
  -H "X-API-Key: $ASSISTANT_API_KEY" \
  -d '{"message":"Berapa risiko turnover karyawan EMP0001?","employee_id":"EMP0001"}'
```

## Menjalankan

```bash
# 1. Jalankan HR Intelligence API (terminal terpisah)
cd ../hr_intelligence_api
HR_API_KEY=dev-secret uvicorn app.main:app --port 8000

# 2. Jalankan AI HR Assistant
cd ai_hr_assistant
cp .env.example .env          # isi HR_INTELLIGENCE_API_KEY & ASSISTANT_API_KEY
ASSISTANT_API_KEY=dev-assistant uvicorn src.main:app --port 8100
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