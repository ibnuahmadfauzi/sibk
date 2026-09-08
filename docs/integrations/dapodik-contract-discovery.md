# Lembar Discovery Kontrak Integrasi Dapodik

Dokumen ini mengumpulkan bukti resmi yang diperlukan sebelum driver production Dapodik dapat diajukan ke admission gate. Kolom jawaban sengaja dibiarkan kosong dan harus diisi oleh pemilik kontrak/provider bersama Admin IT. Jangan menyimpan token, kata sandi, atau raw payload yang belum disanitasi di dokumen ini.

## Identitas dokumen

- Pemilik kontrak/provider:
- Admin IT penanggung jawab:
- Versi dokumentasi provider:
- Tanggal dokumentasi diakses:
- Tautan atau nomor dokumen resmi:
- Kontak gangguan provider:

## Autentikasi dan credential read-only

- Mekanisme autentikasi:
- Header/parameter autentikasi:
- Prosedur penerbitan, rotasi, revokasi, dan kedaluwarsa credential:
- Scope/role/permission credential:
- Bukti credential least-privilege dan read-only:
- Bukti bahwa endpoint/method tulis tidak dapat diakses credential:
- Contoh request tersanitasi:

## Origin, TLS, proxy, dan jaringan

- Base URL/origin production exact termasuk scheme dan effective port:
- Origin non-production yang diizinkan:
- Apakah jaringan privat diperlukan:
- DNS/IP range resmi dan prosedur perubahan:
- Persyaratan Host dan SNI:
- Versi TLS minimum dan rantai sertifikat:
- Kebutuhan mutual TLS:
- Proxy resmi bila ada:
- Perilaku redirect:
- Bukti bahwa origin, proxy, dan TLS berasal dari dokumentasi/provider:

## Identitas sekolah dan sumber

- Nama field identitas sekolah/sumber:
- Tipe, format, dan nullability:
- Nilai identitas SMKN 1 Surabaya yang diharapkan:
- Endpoint atau bagian response yang melaporkan identitas:
- Bukti bahwa identitas stabil dan unik:
- Perilaku bila identitas tidak cocok:

## Endpoint dan method

Isi satu baris per operasi resmi. Jangan menambahkan endpoint atau method berdasarkan dugaan.

| Tujuan operasi | Method | Path | Query/path parameter | Auth scope | Content type | Status sukses | Status error |
|---|---|---|---|---|---|---|---|
|  |  |  |  |  |  |  |  |

## Contoh response tersanitasi

- Sumber fixture/contoh resmi:
- Cara sanitasi dan pihak yang memverifikasi:
- Lokasi fixture sintetis yang aman untuk test:
- Contoh response sukses tersanitasi:

```json
```

- Contoh response kosong tersanitasi:

```json
```

- Contoh response error tersanitasi:

```json
```

## Schema dan pemetaan

Isi seluruh collection, envelope, dan field yang diperlukan. Field di luar kebutuhan snapshot harus ditandai untuk diabaikan.

| JSON path/field | Tipe | Nullable | Wajib | Semantik | Contoh sintetis | Target snapshot atau diabaikan |
|---|---|---|---|---|---|---|
|  |  |  |  |  |  |  |

- Field/marker versi kontrak:
- Aturan unknown field:
- Aturan missing collection:
- Aturan tipe/nullability yang salah:
- Field immutable dan mutable:
- Aturan collision `source_id` lintas tahun ajaran:

## Pagination dan urutan

- Model pagination:
- Nama dan tipe cursor/page/offset:
- Ukuran page minimum/default/maksimum:
- Penanda page terakhir:
- Total count dan keandalannya:
- Stabilitas urutan selama pagination:
- Perilaku data berubah di tengah pagination:
- Batas page per operasi:

## Full partial dan deletion semantics

- Cara kontrak menandai full snapshot:
- Cara kontrak menandai partial/delta snapshot:
- Apakah marker wajib pada setiap page:
- Bukti/provenance yang menyatakan snapshot lengkap:
- Arti collection kosong pada full snapshot:
- Arti record hilang dari full snapshot:
- Tombstone/deletion marker dan semantiknya:
- Apakah deactivation boleh dilakukan dan pada kondisi apa:
- Watermark/revision/sequence untuk delta:

## Waktu dan timezone

- Timezone sumber:
- Format timestamp:
- Presisi timestamp:
- Semantik `updated_at`, `effective_at`, dan waktu kejadian:
- Daylight saving/offset behavior bila relevan:

## Batas payload dan resource

- Batas byte maksimum per response:
- Batas byte maksimum seluruh operasi:
- Batas record per page:
- Batas record seluruh snapshot:
- Perilaku response terkompresi dan batas setelah dekompresi:
- Batas waktu pemrosesan yang didukung provider:

## Rate limit, timeout, retry, dan backoff

- Rate limit per credential/origin/endpoint:
- Header kuota dan reset:
- Timeout koneksi:
- Timeout response/page:
- Hard deadline seluruh operasi:
- Error/status yang boleh di-retry:
- Maksimum retry:
- Strategi backoff dan jitter:
- Penanganan `Retry-After`:
- Idempotency guarantee untuk request baca:

## Concurrency, backpressure, dan queue

- Jumlah request paralel yang diizinkan:
- Apakah pagination wajib serial:
- Batas operasi sinkronisasi bersamaan:
- Strategi backpressure yang disahkan:
- Kebutuhan queue atau lock renewal:
- Bukti worst-case pagination/retry/import muat dalam hard deadline di bawah lease:

## Error model dan gangguan provider

- Envelope error dan field aman untuk logging:
- Pemetaan status/error autentikasi:
- Pemetaan rate limit:
- Pemetaan gangguan sementara/permanen:
- Apakah body error dapat memuat credential/PII:
- Prosedur outage dan eskalasi:
- Prosedur melanjutkan sinkronisasi setelah gangguan:
- Retensi data lama saat provider gagal:

## Evidence snapshot dan validator

- Field reported source identifier:
- Contract marker/provenance:
- Cara menghitung page count:
- Cara menghitung record count:
- Cara menghitung processed byte count:
- Bukti completeness yang dapat diverifikasi executable:
- Aturan validator untuk schema, tipe, nullability, identitas, batas, dan collision:
- Fixture sintetis success, malformed, partial, full-empty, oversized, dan identity mismatch:

## Keputusan admission

- [ ] Dokumentasi autentikasi disahkan.
- [ ] Origin/endpoint/method disahkan.
- [ ] Identitas sekolah/sumber disahkan.
- [ ] Schema dan mapping snapshot disahkan.
- [ ] Pagination, full/partial, deletion, dan timezone disahkan.
- [ ] Limits, timeout, retry/backoff, rate limit, concurrency, backpressure, dan queue disahkan.
- [ ] TLS, proxy, DNS/IP, dan perilaku jaringan disahkan.
- [ ] Bukti credential least-privilege/read-only disahkan.
- [ ] Fixture sintetis dan validator executable disahkan.
- [ ] Prosedur gangguan provider disahkan.

Keputusan: 

Catatan penolakan atau syarat:

Pemilik kontrak/provider dan tanggal:

Admin IT dan tanggal:
