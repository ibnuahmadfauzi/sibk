<div class="modal fade" id="achievement-import-modal" tabindex="-1" aria-labelledby="achievement-import-title" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <form class="modal-content" action="{{ route('achievements.import') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="modal-header">
                <h2 class="modal-title fs-5" id="achievement-import-title">Impor Prestasi dari Excel</h2>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
            </div>
            <div class="modal-body">
                <p id="achievement-import-help">Gunakan berkas .xlsx. Maksimal 1.000 baris dan 2 MB. Seluruh baris diperiksa sebelum disimpan.</p>
                <details class="mb-3" @if($errors->has('file')) open @endif>
                    <summary title="Lihat syarat file Excel">Format dan kode isian Excel</summary>
                    <div class="mt-2 small">
                        <p>Kolom berurutan: <strong>nisn, jenis, tingkat, kegiatan, penyelenggara, tanggal, hasil</strong>. Tanggal diisi YYYY-MM-DD.</p>
                        <p>Kode jenis: {{ $types->pluck('code')->join(', ') }}.</p>
                        <p class="mb-0">Kode tingkat: {{ $levels->pluck('code')->join(', ') }}.</p>
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
