@extends('layouts.app-2')

@section('page-title', 'Catat Prestasi - Ruang BK')

@section('body')
    <div class="sibk-dashboard">

        {{-- Header --}}
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ url()->previous() }}" class="btn btn-icon btn-light" aria-label="Kembali">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2"
                        stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </a>
                <div class="sibk-page-header__copy m-0">
                    <h1 class="mb-1">Catat Prestasi</h1>
                    <p class="mb-0">Tambahkan atau perbarui riwayat prestasi murid.</p>
                </div>
            </div>
        </div>

        <form action="#" method="POST" enctype="multipart/form-data" class="d-flex flex-column gap-4">
            @csrf

            {{-- Bagian 1: Informasi Prestasi --}}
            <div class="sibk-panel">
                <div class="sibk-panel__body p-4 p-md-5">
                    <h2 class="sibk-section-title mb-4">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6" />
                            <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18" />
                            <path d="M4 22h16" />
                            <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22" />
                            <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22" />
                            <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z" />
                        </svg>
                        Informasi Prestasi
                    </h2>

                    <div class="row g-3">
                        {{-- Murid --}}
                        <div class="col-12 col-md-6 col-lg-5">
                            <label for="murid_search" class="form-label sibk-form-label">Murid</label>
                            <div class="sibk-input-search-wrapper">
                                <svg class="sibk-input-search-icon" viewBox="0 0 24 24" width="16" height="16"
                                    fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" aria-hidden="true">
                                    <circle cx="11" cy="11" r="8" />
                                    <line x1="21" y1="21" x2="16.65" y2="16.65" />
                                </svg>
                                <input type="text" class="form-control sibk-form-control sibk-form-control--search"
                                    id="murid_search" name="murid_search"
                                    placeholder="Cari dan pilih murid" autocomplete="off">
                            </div>
                        </div>

                        {{-- Jenis Prestasi --}}
                        <div class="col-12 col-md-3 col-lg-4">
                            <label for="jenis_prestasi" class="form-label sibk-form-label">Jenis Prestasi</label>
                            <select class="form-select sibk-form-select" id="jenis_prestasi" name="jenis_prestasi">
                                <option selected disabled value="">Pilih jenis prestasi</option>
                                <option value="akademik">Akademik</option>
                                <option value="olahraga">Olahraga</option>
                                <option value="seni">Seni &amp; Budaya</option>
                                <option value="ilmiah">Karya Ilmiah</option>
                                <option value="organisasi">Organisasi</option>
                                <option value="lainnya">Lainnya</option>
                            </select>
                        </div>

                        {{-- Tingkat --}}
                        <div class="col-12 col-md-3 col-lg-3">
                            <label for="tingkat" class="form-label sibk-form-label">Tingkat</label>
                            <select class="form-select sibk-form-select" id="tingkat" name="tingkat">
                                <option selected disabled value="">Pilih tingkat</option>
                                <option value="sekolah">Sekolah</option>
                                <option value="kota">Kota / Kabupaten</option>
                                <option value="provinsi">Provinsi</option>
                                <option value="nasional">Nasional</option>
                                <option value="internasional">Internasional</option>
                            </select>
                        </div>

                        {{-- Nama Kegiatan --}}
                        <div class="col-12 col-md-5">
                            <label for="nama_kegiatan" class="form-label sibk-form-label">Nama Kegiatan</label>
                            <input type="text" class="form-control sibk-form-control" id="nama_kegiatan"
                                name="nama_kegiatan" placeholder="Masukkan nama kegiatan">
                        </div>

                        {{-- Penyelenggara --}}
                        <div class="col-12 col-md-4">
                            <label for="penyelenggara" class="form-label sibk-form-label">Penyelenggara</label>
                            <input type="text" class="form-control sibk-form-control" id="penyelenggara"
                                name="penyelenggara" placeholder="Masukkan penyelenggara">
                        </div>

                        {{-- Tanggal --}}
                        <div class="col-12 col-md-3">
                            <label for="tanggal" class="form-label sibk-form-label">Tanggal</label>
                            <input type="date" class="form-control sibk-form-control" id="tanggal" name="tanggal">
                        </div>
                    </div>
                </div>
            </div>

            {{-- Bagian 2: Hasil dan Bukti --}}
            <div class="sibk-panel">
                <div class="sibk-panel__body p-4 p-md-5">
                    <h2 class="sibk-section-title mb-4">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor"
                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M15 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7Z" />
                            <path d="M14 2v4a2 2 0 0 0 2 2h4" />
                            <path d="M10 9H8" />
                            <path d="M16 13H8" />
                            <path d="M16 17H8" />
                        </svg>
                        Hasil dan Bukti
                    </h2>

                    <div class="row g-3">
                        {{-- Hasil atau Peringkat --}}
                        <div class="col-12 col-md-5">
                            <label for="hasil" class="form-label sibk-form-label">Hasil atau Peringkat</label>
                            <input type="text" class="form-control sibk-form-control" id="hasil" name="hasil"
                                placeholder="Masukkan hasil">
                        </div>

                        {{-- Status Verifikasi --}}
                        <div class="col-12 col-md-4">
                            <label for="status_verifikasi" class="form-label sibk-form-label">Status Verifikasi</label>
                            <select class="form-select sibk-form-select" id="status_verifikasi"
                                name="status_verifikasi">
                                <option selected disabled value="">Pilih status</option>
                                <option value="belum_diverifikasi">Belum Diverifikasi</option>
                                <option value="terverifikasi">Terverifikasi</option>
                                <option value="ditolak">Ditolak</option>
                            </select>
                        </div>

                        {{-- Bukti Prestasi --}}
                        <div class="col-12 col-md-3">
                            <label for="bukti" class="form-label sibk-form-label">Bukti Prestasi</label>
                            <div class="sibk-file-upload">
                                <input type="file" class="sibk-file-upload__input visually-hidden" id="bukti"
                                    name="bukti" accept=".pdf,.jpg,.jpeg,.png"
                                    aria-describedby="bukti-hint">
                                <label for="bukti" class="sibk-file-upload__label btn btn-outline-primary d-inline-flex align-items-center gap-2">
                                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none"
                                        stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                        stroke-linejoin="round" aria-hidden="true">
                                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                                        <polyline points="17 8 12 3 7 8" />
                                        <line x1="12" y1="3" x2="12" y2="15" />
                                    </svg>
                                    <span id="bukti-label-text">Pilih Dokumen</span>
                                </label>
                                <p class="form-text mt-1 mb-0" id="bukti-hint">PDF, JPG, atau PNG</p>
                            </div>
                        </div>

                        {{-- Catatan --}}
                        <div class="col-12">
                            <label for="catatan" class="form-label sibk-form-label">Catatan</label>
                            <textarea class="form-control sibk-form-control" id="catatan" name="catatan" rows="3"
                                placeholder="Tambahkan catatan seperlunya"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            {{-- Action Buttons --}}
            <div class="d-flex justify-content-end gap-2 pb-4">
                <a href="{{ url()->previous() }}" class="btn btn-light">Batal</a>
                <button type="submit" class="btn btn-primary">Simpan Prestasi</button>
            </div>

        </form>
    </div>
@endsection

@section('extra-javascript')
<script>
    // Preview nama file saat dipilih
    (function () {
        const input = document.getElementById('bukti');
        const labelText = document.getElementById('bukti-label-text');
        if (!input || !labelText) return;

        input.addEventListener('change', function () {
            labelText.textContent = (this.files && this.files.length > 0)
                ? this.files[0].name
                : 'Pilih Dokumen';
        });
    })();
</script>
@endsection

