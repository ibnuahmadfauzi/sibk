# Changelog

## v3.2

- Menambahkan mirror Markdown untuk PRD Aplikasi BK v1.0 dan SRS Aplikasi BK v1.0.
- DOCX tetap menjadi baseline manusia/final; Markdown menjadi mirror AI-readable.
- Agent diarahkan membaca `requirements-index.md` lebih dulu.
- Saat requirement diperlukan, agent memprioritaskan selective reading dari `.md`.
- Mengurangi kebutuhan parsing DOCX dan context yang tidak relevan.
- Backend tetap belum dikembangkan.

## v3.1

- Menetapkan PRD Aplikasi BK v1.0 sebagai baseline final produk.
- Menetapkan SRS Aplikasi BK v1.0 sebagai baseline final spesifikasi MVP.
- Menambahkan kedua file final ke `docs/requirements/`.
- Menambahkan `docs/requirements-index.md` untuk selective reading dan penghematan context/token.
- Rules tidak lagi hanya menyebut PRD/SRS generik; path baseline final dibuat eksplisit.
- Menambahkan aturan agar PRD/SRS tidak dibuka pada task visual murni.
- Backend tetap belum dikembangkan.

## v3

- Menambahkan backend guardrail.
- Menambahkan workflow `/plan-backend`.
- Menambahkan template `docs/api-contract.md`.
- Menegaskan backend belum aktif dikembangkan.
- Menambahkan pola service/repository + mock adapter pada frontend.
- Melarang task frontend membuat backend secara otomatis.
- Tetap menggunakan Penpot Hi-Fi + Style Guide sebagai source of truth visual.
- Wireframe low-fidelity tetap sebagai artefak proses, bukan context coding frontend.