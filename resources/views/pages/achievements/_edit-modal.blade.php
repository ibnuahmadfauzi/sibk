<div class="modal-header">
    <h2 class="modal-title fs-5" id="achievement-modal-title">Edit Prestasi</h2>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>
<form action="{{ route('achievements.update', $achievement) }}" method="POST">
    @csrf
    @method('PATCH')
    <input type="hidden" name="expected_updated_at" value="{{ old('expected_updated_at', $achievement->updated_at?->toJSON()) }}">
    <div class="modal-body">
        <div class="row g-3">
            <div class="col-12">
                <label class="form-label">Murid</label>
                <input class="form-control" value="{{ $achievement->student->name }} — {{ $achievement->student->nisn }}" disabled>
            </div>
            <div class="col-12 col-md-6">
                <label for="type_id" class="form-label">Jenis Prestasi <span class="text-danger">*</span></label>
                <select class="form-select" id="type_id" name="type_id" required>
                    @foreach($types as $type)<option value="{{ $type->id }}" @selected((string) old('type_id', $achievement->type_id) === (string) $type->id)>{{ $type->label }}</option>@endforeach
                </select>
            </div>
            <div class="col-12 col-md-6">
                <label for="level_id" class="form-label">Tingkat <span class="text-danger">*</span></label>
                <select class="form-select" id="level_id" name="level_id" required>
                    @foreach($levels as $level)<option value="{{ $level->id }}" @selected((string) old('level_id', $achievement->level_id) === (string) $level->id)>{{ $level->label }}</option>@endforeach
                </select>
            </div>
            <div class="col-12">
                <label for="activity_name" class="form-label">Nama Kegiatan <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="activity_name" name="activity_name" maxlength="250" value="{{ old('activity_name', $achievement->activity_name) }}" required>
            </div>
            <div class="col-12 col-md-7">
                <label for="organizer" class="form-label">Penyelenggara <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="organizer" name="organizer" maxlength="200" value="{{ old('organizer', $achievement->organizer) }}" required>
            </div>
            <div class="col-12 col-md-5">
                <label for="achievement_date" class="form-label">Tanggal <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="achievement_date" name="achievement_date" max="{{ today()->toDateString() }}" value="{{ old('achievement_date', $achievement->achievement_date?->toDateString()) }}" required>
            </div>
            <div class="col-12">
                <label for="result" class="form-label">Hasil atau Peringkat <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="result" name="result" maxlength="250" value="{{ old('result', $achievement->result) }}" required>
            </div>
        </div>
    </div>
    <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </div>
</form>
