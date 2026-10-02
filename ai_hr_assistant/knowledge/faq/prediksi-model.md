# FAQ Prediksi dan Model HR

> **Tag:** faq, prediksi, model, turnover, absensi, kinerja, payroll, survival

## Model HR apa saja yang tersedia?

HR Intelligence System menyediakan lima model yang bisa dipakai lewat asisten:

| Use case | Yang diprediksi |
| -------- | --------------- |
| turnover | probabilitas karyawan keluar |
| absensi | probabilitas absen tinggi |
| kinerja | kategori kinerja (Rendah/Sedang/Tinggi) |
| payroll | anomali payroll |
| survival | risiko relatif attrition & median survival |

## Bagaimana menanyakan prediksi lewat asisten?

Sebutkan karyawan dan jenis prediksi, misalnya:

```text
"Berapa risiko turnover karyawan EMP0001?"
"Prediksi absen tinggi untuk EMP0002."
"Kategori kinerja EMP0003."
"Apakah payroll EMP0004 anomali?"
```

Asisten akan memanggil model yang sesuai lewat HR Intelligence API.

## Apa arti level risiko?

Untuk model klasifikasi, level risiko dibagi:

* **Rendah** — di bawah 0,25
* **Sedang** — 0,25 sampai di bawah 0,50
* **Tinggi** — 0,50 ke atas

Ambang batas tiap model bisa berbeda dan tercantum pada metadata model.

## Apakah skor prediksi bisa dipakai untuk memutuskan PHK?

Tidak. Skor prediksi hanya sinyal awal. Keputusan yang berdampak pada karyawan
harus diverifikasi manusia dengan bukti obyektif. Lihat kebijakan kepatuhan
analitik AI.

## Apa itu median survival?

Median survival adalah estimasi waktu (dalam bulan) sampai separuh populasi
serupa mengalami kejadian keluar. Nilai ini membantu memahami *kapan*, bukan
hanya *apakah*, seorang karyawan berisiko keluar.
