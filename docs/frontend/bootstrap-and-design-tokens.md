# Bootstrap dan Design Tokens

## Tujuan

Bootstrap menjadi fondasi komponen. Design tokens menjadi satu-satunya tempat untuk mengubah warna, radius, bayangan, dan karakter visual SIBK. Style guide dapat disesuaikan kemudian tanpa mengedit setiap halaman.

## Aturan utama

1. Gunakan versi Bootstrap yang sudah ada di repository setelah audit.
2. Jangan melakukan upgrade versi mayor dalam pekerjaan UI biasa.
3. Bila pipeline Sass tersedia, override variabel Bootstrap sebelum import Bootstrap dan keluarkan CSS custom properties dari sumber token yang sama.
4. Bila pipeline hanya CSS, simpan token pada satu file tema yang dimuat setelah Bootstrap.
5. Jangan mendefinisikan warna atau shadow langsung pada template halaman.
6. Gunakan nama token berdasarkan fungsi, bukan nama warna seperti `blue-1` atau `cream-2`.
7. Komponen khusus memakai namespace `.sibk-*` agar tidak bertabrakan dengan Bootstrap.

## Token awal

Nilai berikut adalah titik awal implementasi, bukan style guide permanen. Perubahan warna hanya dilakukan pada sumber token.

```css
:root {
  /* Brand dan navigasi */
  --sibk-color-primary: #2f5b85;
  --sibk-color-primary-hover: #244867;
  --sibk-color-primary-contrast: #ffffff;
  --sibk-color-sidebar: #2f5b85;
  --sibk-color-sidebar-text: #ffffff;
  --sibk-color-nav-active-bg: #f3e8cd;
  --sibk-color-nav-active-text: #243449;

  /* Permukaan dan teks */
  --sibk-color-page: #eef3f8;
  --sibk-color-surface: #f7f9fc;
  --sibk-color-surface-raised: #ffffff;
  --sibk-color-text: #1f2937;
  --sibk-color-text-muted: #5f6b7a;
  --sibk-color-border: #cdd6e1;

  /* Status */
  --sibk-color-success: #18794e;
  --sibk-color-warning: #8a5a00;
  --sibk-color-danger: #b42318;
  --sibk-color-info: #175cd3;

  /* Fokus dan bentuk */
  --sibk-focus-ring: rgba(29, 78, 216, 0.35);
  --sibk-radius-sm: 0.5rem;
  --sibk-radius-md: 0.75rem;
  --sibk-radius-lg: 1rem;

  /* Soft UI yang dibatasi */
  --sibk-shadow-raised: 0.5rem 0.5rem 1rem rgba(55, 74, 96, 0.14),
    -0.5rem -0.5rem 1rem rgba(255, 255, 255, 0.8);
  --sibk-shadow-inset: inset 0.2rem 0.2rem 0.45rem rgba(55, 74, 96, 0.16),
    inset -0.2rem -0.2rem 0.45rem rgba(255, 255, 255, 0.75);

  /* Pemetaan dasar Bootstrap */
  --bs-primary: var(--sibk-color-primary);
  --bs-primary-rgb: 47, 91, 133;
  --bs-body-bg: var(--sibk-color-page);
  --bs-body-color: var(--sibk-color-text);
  --bs-border-color: var(--sibk-color-border);
  --bs-border-radius: var(--sibk-radius-sm);
  --bs-border-radius-lg: var(--sibk-radius-md);
  --bs-focus-ring-color: var(--sibk-focus-ring);
}
```

Jika warna primer berubah, nilai `--bs-primary-rgb` harus ikut diperbarui pada sumber token yang sama. Jangan menyalin nilai RGB tersebut ke komponen lain.

## Pemetaan komponen Bootstrap

Gunakan custom properties komponen Bootstrap bila tersedia pada versi yang terpasang.

```css
.btn-primary {
  --bs-btn-bg: var(--sibk-color-primary);
  --bs-btn-border-color: var(--sibk-color-primary);
  --bs-btn-hover-bg: var(--sibk-color-primary-hover);
  --bs-btn-hover-border-color: var(--sibk-color-primary-hover);
  --bs-btn-color: var(--sibk-color-primary-contrast);
  --bs-btn-hover-color: var(--sibk-color-primary-contrast);
  --bs-btn-focus-shadow-rgb: 47, 91, 133;
}

.sibk-sidebar {
  color: var(--sibk-color-sidebar-text);
  background: var(--sibk-color-sidebar);
}

.sibk-nav-link.is-active {
  color: var(--sibk-color-nav-active-text);
  background: var(--sibk-color-nav-active-bg);
  box-shadow: var(--sibk-shadow-inset);
}

.sibk-surface {
  color: var(--sibk-color-text);
  background: var(--sibk-color-surface);
  border: 1px solid var(--sibk-color-border);
  border-radius: var(--sibk-radius-md);
}
```

Jika versi Bootstrap belum mendukung custom properties komponen, lakukan pemetaan melalui file override Sass/CSS pusat. Jangan membuat override terpisah pada setiap halaman.

## Aturan komponen

- Gunakan `.btn`, `.form-control`, `.form-select`, `.table`, `.card`, `.modal`, `.offcanvas`, `.alert`, `.badge`, `.nav`, dan grid Bootstrap sebagai basis.
- Buat komponen SIBK hanya ketika pola memiliki makna atau struktur khusus proyek.
- Gunakan variant semantik seperti `primary`, `success`, `warning`, dan `danger`; jangan mengandalkan warna tanpa label.
- Hindari `!important`. Jika diperlukan karena batas versi Bootstrap, dokumentasikan alasan pada file style yang sama.
- Jangan menggandakan markup modal, alert, empty state, pagination, filter bar, atau form feedback.
- Setiap komponen interaktif memiliki default, hover, focus-visible, active, disabled, loading, dan error sesuai relevansi.

## Gerbang perubahan token

Perubahan token harus diperiksa pada:

- sidebar dan menu aktif;
- tombol dan tautan;
- input, select, checkbox, radio, dan focus ring;
- kartu, modal, dropdown, offcanvas, dan tabel;
- badge, alert, serta status kasus;
- mobile dan desktop;
- kontras teks serta elemen fokus.
