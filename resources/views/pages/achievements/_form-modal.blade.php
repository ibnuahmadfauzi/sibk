<div class="modal-header border-0 pb-2">
    <h2 class="modal-title fs-5 fw-bold" id="achievement-modal-title">{{ $isEdit ? 'Edit Prestasi' : 'Catat Prestasi' }}</h2>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>
<div class="modal-body pt-2">
    <form action="{{ $isEdit ? route('achievements.update', $achievement) : route('achievements.store') }}" method="POST" data-achievement-form data-achievement-students="{{ json_encode($studentOptions) }}" @unless($isEdit) data-autosave-form="achievement" data-autosave-record="new" @endunless>
        @csrf
        @if($isEdit)
            @method('PATCH')
            <input type="hidden" name="expected_updated_at" value="{{ old('expected_updated_at', $achievement->updated_at?->toJSON()) }}">
        @endif
        <div class="row g-3">
            <div class="col-12 position-relative">
                <label for="achievement-student-lookup" class="form-label">Murid <span class="text-danger">*</span></label>
                @if($isEdit)
                    <input id="achievement-student-lookup" class="form-control" value="{{ \App\Support\StudentName::display($achievement->student->name) }} ({{ $achievement->student->nisn }}) · {{ $achievement->student->classMemberships->first()?->classroom?->name ?? 'Rombel belum tercatat' }}" disabled>
                @else
                    <input type="hidden" name="student_id" value="{{ old('student_id', $preselectedStudentId) }}">
                    <input id="achievement-student-lookup" class="form-control" type="search" autocomplete="off" placeholder="Ketik nama atau NISN murid" data-achievement-lookup aria-controls="achievement-student-options" aria-autocomplete="list" aria-expanded="false" required>
                    <div id="achievement-student-options" class="list-group position-absolute start-0 end-0 mx-2 mt-1 shadow-sm d-none withdrawal-student-options" data-achievement-options role="listbox" aria-label="Saran murid"></div>
                    <div class="rounded-3 border bg-primary-subtle px-3 py-2 small mt-2 d-none" data-achievement-selected aria-live="polite"></div>
                @endif
            </div>
            <div class="col-12">
                <label for="activity_name" class="form-label">Kegiatan/Nama Lomba <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="activity_name" name="activity_name" maxlength="250" value="{{ old('activity_name', $achievement?->activity_name) }}" required>
            </div>
            <div class="col-12 col-md-4">
                <label for="type_id" class="form-label">Jenis <span class="text-danger">*</span></label>
                <select class="form-select" id="type_id" name="type_id" required><option value="">Pilih jenis prestasi</option>@foreach($types as $type)<option value="{{ $type->id }}" @selected((string) old('type_id', $achievement?->type_id) === (string) $type->id)>{{ $type->label }}</option>@endforeach</select>
            </div>
            <div class="col-12 col-md-4">
                <label for="level_id" class="form-label">Tingkat <span class="text-danger">*</span></label>
                <select class="form-select" id="level_id" name="level_id" required><option value="">Pilih tingkat</option>@foreach($levels as $level)<option value="{{ $level->id }}" @selected((string) old('level_id', $achievement?->level_id) === (string) $level->id)>{{ $level->label }}</option>@endforeach</select>
            </div>
            <div class="col-12 col-md-4">
                <label for="achievement_date" class="form-label">Tanggal <span class="text-danger">*</span></label>
                <input type="date" class="form-control" id="achievement_date" name="achievement_date" max="{{ today()->toDateString() }}" value="{{ old('achievement_date', $achievement?->achievement_date?->toDateString()) }}" required>
            </div>
            <div class="col-12 col-md-8">
                <label for="organizer" class="form-label">Penyelenggara <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="organizer" name="organizer" maxlength="200" value="{{ old('organizer', $achievement?->organizer) }}" required>
            </div>
            <div class="col-12 col-md-4">
                <label for="result" class="form-label">Hasil/Peringkat <span class="text-danger">*</span></label>
                <input type="text" class="form-control" id="result" name="result" maxlength="250" value="{{ old('result', $achievement?->result) }}" required>
            </div>
        </div>
        <div class="d-flex flex-wrap justify-content-end gap-2 mt-4">
            <span class="small text-muted me-auto align-self-center" data-draft-status aria-live="polite"></span>
            @unless($isEdit)<button type="button" class="btn btn-light" data-clear-draft data-clear-fields>Kosongkan</button>@endunless
            <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Batal</button>
            <button type="submit" class="btn btn-primary">Simpan</button>
        </div>
    </form>
</div>
