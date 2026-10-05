<div class="modal fade" id="achievement-import-modal" tabindex="-1" aria-labelledby="achievement-import-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content" action="{{ route('achievements.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="achievement-import-title">Impor Prestasi dari Excel</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p id="achievement-import-help" class="mb-3">Pilih berkas .xlsx. Maksimal 1.000 baris dan 2 MB. Semua baris diperiksa sebelum disimpan.</p>
                <details class="sibk-import-guide border rounded p-3 mb-3" @if($errors->has('file')) open @endif>
                    <summary title="Lihat syarat file Excel" class="fw-semibold">Format dan kode isian Excel</summary>
                    <div class="mt-3 small">
                        <p class="mb-2">Baris pertama berisi nama kolom berikut, sesuai urutan:</p>
                        <p class="d-flex flex-wrap gap-1 mb-3">
                            @foreach(['nisn', 'jenis', 'tingkat', 'kegiatan', 'penyelenggara', 'tanggal', 'hasil'] as $column)
                                <code class="border rounded px-2 py-1">{{ $column }}</code>
                            @endforeach
                        </p>
                        <p class="mb-3">Isi tanggal dengan format <code>YYYY-MM-DD</code> (contoh: <code>2026-09-18</code>) atau tanggal Excel.</p>
                        <div class="row g-3">
                            <div class="col-12 col-md-6">
                                <h3 class="fs-6 mb-2">Kode jenis</h3>
                                <dl class="row gy-1 mb-0">
                                    @foreach($types as $type)
                                        <dt class="col-6 fw-normal">{{ $type->label }}</dt>
                                        <dd class="col-6 mb-0"><code class="text-break">{{ $type->code }}</code></dd>
                                    @endforeach
                                </dl>
                            </div>
                            <div class="col-12 col-md-6">
                                <h3 class="fs-6 mb-2">Kode tingkat</h3>
                                <dl class="row gy-1 mb-0">
                                    @foreach($levels as $level)
                                        <dt class="col-6 fw-normal">{{ $level->label }}</dt>
                                        <dd class="col-6 mb-0"><code class="text-break">{{ $level->code }}</code></dd>
                                    @endforeach
                                </dl>
                            </div>
                        </div>
                    </div>
                </details>
                @if($errors->has('file'))
                    <div class="alert alert-danger" role="alert" id="achievement-import-errors">
                        <strong>Perbaiki berkas, lalu pilih kembali untuk mengimpor.</strong>
                        <ul class="mb-0 mt-2">@foreach($errors->get('file') as $error)<li>{{ $error }}</li>@endforeach</ul>
                    </div>
                @endif
                <label for="achievement_import_file" class="form-label">Berkas Excel</label>
                <input type="file" id="achievement_import_file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet" aria-describedby="achievement-import-help{{ $errors->has('file') ? ' achievement-import-errors' : '' }}" @if($errors->has('file')) aria-invalid="true" @endif required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-primary">Impor Prestasi</button>
            </div>
        </form>
    </div>
</div>
