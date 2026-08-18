@extends('layouts.app-2')

@section('page-title', 'Buat Jadwal Konsultasi - Ruang BK')

@section('body')
    <div class="sibk-dashboard">
        <!-- Header -->
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ url('consultations') }}" class="btn btn-icon btn-light" aria-label="Kembali">
                    <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="19" y1="12" x2="5" y2="12"></line>
                        <polyline points="12 19 5 12 12 5"></polyline>
                    </svg>
                </a>
                <div class="sibk-page-header__copy m-0">
                    <h1 class="mb-1">Buat Sesi Bimbingan</h1>
                    <p class="mb-0">Jadwalkan atau catat sesi bimbingan baru untuk murid.</p>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12 col-lg-8">
                <div class="sibk-panel">
                    <div class="sibk-panel__body p-4 p-md-5">
                        <form action="#" method="POST" class="row g-4">
                            
                            <!-- Bagian 1: Peserta -->
                            <div class="col-12">
                                <h4 class="fs-5 mb-3 text-dark fw-bold">1. Peserta Bimbingan</h4>
                                
                                <div class="mb-3">
                                    <label for="student_search" class="form-label sibk-form-label">Cari & Pilih Murid <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control sibk-form-control mb-2" id="student_search" placeholder="Ketik nama atau NISN untuk mencari...">
                                    <!-- Simulasi hasil terpilih -->
                                    <div class="p-3 border rounded bg-light d-flex justify-content-between align-items-center">
                                        <div>
                                            <div class="fw-bold">Ahmad Fauzi</div>
                                            <div class="small text-muted">X RPL 1 • NISN: 0012345678</div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-danger">Hapus</button>
                                    </div>
                                </div>
                            </div>

                            <hr class="text-light my-4">

                            <!-- Bagian 2: Informasi Sesi -->
                            <div class="col-12">
                                <h4 class="fs-5 mb-3 text-dark fw-bold">2. Informasi Layanan</h4>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="jenis" class="form-label sibk-form-label">Jenis Layanan <span class="text-danger">*</span></label>
                                        <select class="form-select sibk-form-select" id="jenis">
                                            <option selected disabled>Pilih jenis layanan...</option>
                                            <option value="pribadi">Bimbingan Pribadi</option>
                                            <option value="sosial">Bimbingan Sosial</option>
                                            <option value="belajar">Bimbingan Belajar</option>
                                            <option value="karir">Bimbingan Karir</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6">
                                        <label for="sumber" class="form-label sibk-form-label">Sumber Panggilan / Rujukan</label>
                                        <select class="form-select sibk-form-select" id="sumber">
                                            <option selected>Inisiatif Murid (Datang Sendiri)</option>
                                            <option value="guru_bk">Panggilan Guru BK</option>
                                            <option value="wali_kelas">Rujukan Wali Kelas</option>
                                            <option value="orang_tua">Permintaan Orang Tua</option>
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label for="topik" class="form-label sibk-form-label">Topik / Permasalahan <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control sibk-form-control" id="topik" placeholder="Contoh: Kesulitan mengatur waktu belajar">
                                    </div>
                                </div>
                            </div>

                            <hr class="text-light my-4">

                            <!-- Bagian 3: Jadwal -->
                            <div class="col-12">
                                <h4 class="fs-5 mb-3 text-dark fw-bold">3. Jadwal Sesi</h4>
                                
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label for="tanggal" class="form-label sibk-form-label">Tanggal <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control sibk-form-control" id="tanggal">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="waktu" class="form-label sibk-form-label">Waktu Mulai <span class="text-danger">*</span></label>
                                        <input type="time" class="form-control sibk-form-control" id="waktu">
                                    </div>
                                </div>
                            </div>

                            <div class="col-12 mt-5">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="{{ url('consultations') }}" class="btn btn-light">Batal</a>
                                    <button type="button" class="btn btn-primary">Simpan & Jadwalkan</button>
                                </div>
                            </div>

                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-12 col-lg-4 mt-4 mt-lg-0">
                <div class="sibk-panel">
                    <div class="sibk-panel__body p-4 bg-light rounded">
                        <h5 class="fw-bold mb-3">Informasi</h5>
                        <p class="small text-muted mb-2">
                            Gunakan halaman ini untuk menjadwalkan sesi layanan konseling ke depan, atau mencatat sesi yang sudah berlangsung secara spontan.
                        </p>
                        <p class="small text-muted mb-0">
                            Murid akan menerima notifikasi (jika diaktifkan) terkait jadwal bimbingan ini.
                        </p>
                    </div>
                </div>
            </div>
        </div>

    </div>
@endsection
