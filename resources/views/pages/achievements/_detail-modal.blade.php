<div class="modal-header">
    <h2 class="modal-title fs-5" id="achievement-modal-title">Detail Prestasi</h2>
    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
</div>
<div class="modal-body">
    <h3 class="fs-5 mb-1">{{ $achievement->activity_name }}</h3>
    <p class="text-muted">{{ $achievement->student->name }} &mdash; NISN {{ $achievement->student->nisn }}</p>
    <dl class="row mb-0">
        <dt class="col-sm-4">Jenis / Tingkat</dt><dd class="col-sm-8">{{ $achievement->type->label }} / {{ $achievement->level->label }}</dd>
        <dt class="col-sm-4">Penyelenggara</dt><dd class="col-sm-8">{{ $achievement->organizer }}</dd>
        <dt class="col-sm-4">Tanggal</dt><dd class="col-sm-8">{{ $achievement->achievement_date->locale('id')->translatedFormat('d F Y') }}</dd>
        <dt class="col-sm-4">Hasil</dt><dd class="col-sm-8">{{ $achievement->result }}</dd>
        <dt class="col-sm-4">Pencatat</dt><dd class="col-sm-8">{{ $achievement->recorder->name }}</dd>
    </dl>
</div>
<div class="modal-footer">
    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
    @if($canUpdateAchievement)<a href="{{ route('achievements.edit', $achievement) }}" data-modal-url="{{ route('achievements.edit', [$achievement, 'modal' => 1]) }}" class="btn btn-primary">Edit Prestasi</a>@endif
</div>
