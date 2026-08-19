@extends('layouts.app-2')

@section('page-title', 'Buat Kasus Baru - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <!-- Header -->
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('cases.index') }}" class="btn btn-icon btn-light" aria-label="Kembali">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </a>
                <div class="sibk-page-header__copy m-0">
                    <h1 class="mb-1">Buat Kasus Baru</h1>
                    <p class="mb-0">Catat informasi awal layanan BK secara terstruktur.</p>
                </div>
            </div>
        </div>

        <form action="#" method="POST">
            <!-- Bagian 1: Murid dan Sumber Kasus -->
            <div class="sibk-panel mb-4 border-0 shadow-sm">
                <div class="sibk-panel__body p-4 p-md-5">
                    <div class="d-flex gap-3 mb-4">
                        <div class="flex-shrink-0">
                            <div class="d-flex align-items-center justify-content-center rounded-circle bg-primary bg-opacity-10 text-primary" style="width: 48px; height: 48px;">
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                                    <circle cx="12" cy="7" r="4"></circle>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h4 class="fs-5 mb-1 text-dark fw-bold">Murid dan Sumber Kasus</h4>
                            <p class="text-muted small mb-0">Tentukan murid, asal informasi, dan konteks awal kasus.</p>
                        </div>
                    </div>
                    
                    <div class="row g-4 ms-0 ms-md-5 ps-0 ps-md-2">
                        <div class="col-md-6">
                            <label for="student" class="form-label sibk-form-label text-dark fw-medium">Murid</label>
                            <div class="position-relative">
                                <svg class="position-absolute top-50 translate-middle-y ms-3 text-muted" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"></circle><line x1="21" y1="21" x2="16.65" y2="16.65"></line></svg>
                                <select class="form-select sibk-form-select ps-5 bg-light border-0" id="student">
                                    <option selected disabled>Cari dan pilih murid</option>
                                    <option value="1">Murid A - X RPL 1</option>
                                    <option value="2">Murid B - X RPL 2</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label for="sumber" class="form-label sibk-form-label text-dark fw-medium">Sumber Kasus</label>
                            <select class="form-select sibk-form-select bg-light border-0" id="sumber">
                                <option selected disabled>Pilih sumber kasus</option>
                                <option value="e-tatib">e-Tatib</option>
                                <option value="rujukan">Rujukan</option>
                                <option value="inisiatif">Murid datang sendiri</option>
                                <option value="temuan">Temuan</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="tanggal" class="form-label sibk-form-label text-dark fw-medium">Tanggal Layanan</label>
                            <input type="date" class="form-control sibk-form-control bg-light border-0" id="tanggal" placeholder="Pilih tanggal">
                        </div>
                        <div class="col-md-4">
                            <label for="bidang" class="form-label sibk-form-label text-dark fw-medium">Bidang Layanan BK</label>
                            <select class="form-select sibk-form-select bg-light border-0" id="bidang">
                                <option selected disabled>Pilih bidang layanan</option>
                                <option value="pribadi">Pribadi</option>
                                <option value="belajar">Belajar</option>
                                <option value="sosial">Sosial</option>
                                <option value="karier">Karier</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="perujuk" class="form-label sibk-form-label text-dark fw-medium">Pihak Perujuk</label>
                            <input type="text" class="form-control sibk-form-control bg-light border-0" id="perujuk" placeholder="Isi jika sumber kasus berasal dari rujukan">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian 2: Informasi Kasus -->
            <div class="sibk-panel mb-4 border-0 shadow-sm">
                <div class="sibk-panel__body p-4 p-md-5">
                    <div class="d-flex gap-3 mb-4">
                        <div class="flex-shrink-0">
                            <div class="d-flex align-items-center justify-content-center rounded-circle text-danger" style="width: 48px; height: 48px; background-color: #FFF0F0;">
                                <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                    <polyline points="14 2 14 8 20 8"></polyline>
                                    <line x1="16" y1="13" x2="8" y2="13"></line>
                                    <line x1="16" y1="17" x2="8" y2="17"></line>
                                    <polyline points="10 9 9 9 8 9"></polyline>
                                </svg>
                            </div>
                        </div>
                        <div>
                            <h4 class="fs-5 mb-1 text-dark fw-bold">Informasi Kasus</h4>
                            <p class="text-muted small mb-0">Tuliskan informasi yang diperlukan untuk memulai penanganan.</p>
                        </div>
                    </div>
                    
                    <div class="row g-4 ms-0 ms-md-5 ps-0 ps-md-2">
                        <div class="col-md-6">
                            <label for="informasi_awal" class="form-label sibk-form-label text-dark fw-medium">Informasi Awal</label>
                            <textarea class="form-control sibk-form-control bg-light border-0" id="informasi_awal" rows="4" placeholder="Tuliskan ringkasan informasi awal"></textarea>
                        </div>
                        <div class="col-md-6">
                            <label for="penanganan_awal" class="form-label sibk-form-label text-dark fw-medium">Penanganan Awal</label>
                            <textarea class="form-control sibk-form-control bg-light border-0" id="penanganan_awal" rows="4" placeholder="Tuliskan penanganan awal yang dilakukan"></textarea>
                        </div>
                        <div class="col-12">
                            <label for="catatan_internal" class="form-label sibk-form-label text-dark fw-medium">Catatan Internal</label>
                            <textarea class="form-control sibk-form-control bg-light border-0" id="catatan_internal" rows="2" placeholder="Catatan tambahan bila diperlukan"></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bagian 3: Data e-Tatib Terkait -->
            <div class="sibk-panel mb-4 border-0 shadow-sm">
                <div class="sibk-panel__body p-4 p-md-5">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-4">
                        <div class="d-flex gap-3 w-100">
                            <div class="flex-shrink-0">
                                <div class="d-flex align-items-center justify-content-center rounded-circle bg-success bg-opacity-10 text-success" style="width: 48px; height: 48px;">
                                    <svg viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <ellipse cx="12" cy="5" rx="9" ry="3"></ellipse>
                                        <path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"></path>
                                        <path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"></path>
                                    </svg>
                                </div>
                            </div>
                            <div class="flex-grow-1 d-flex flex-column flex-md-row justify-content-between align-items-md-center gap-3">
                                <div>
                                    <h4 class="fs-5 mb-1 text-dark fw-bold">Data e-Tatib Terkait</h4>
                                    <p class="text-muted small mb-4">Hubungkan data e-Tatib yang relevan bila kasus berkaitan dengan pelanggaran.</p>
                                    <p class="text-muted small mb-0">Belum ada data e-Tatib yang dipilih.</p>
                                </div>
                                <button type="button" class="btn fw-bold px-4 py-2 rounded-pill shadow-sm" style="color: #2b5cff; background-color: #ffffff; border: 1px solid #e2e8f0;">Pilih Data e-Tatib</button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Actions -->
            <div class="d-flex justify-content-end gap-3 mt-4 mb-5">
                <a href="{{ route('cases.index') }}" class="btn fw-bold px-5 py-2 rounded-pill shadow-sm" style="color: #2b5cff; background-color: #ffffff; border: 1px solid #e2e8f0;">Batal</a>
                <button type="submit" class="btn btn-primary fw-bold px-5 py-2 rounded-pill shadow-sm border-0" style="background-color: #2b5cff;">Simpan Kasus</button>
            </div>
        </form>
    </div>
@endsection
