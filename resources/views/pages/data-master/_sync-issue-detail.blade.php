<div class="row g-3 small">
    @if($record)
        <div class="col-md-6">
            <h3 class="fs-6 fw-bold">Data e-Tatib</h3>
            <p class="mb-0">Nama: {{ \App\Support\StudentName::display($issue->input_name ?: $record->source_student_name) ?: 'Tidak tersedia' }}<br>
                NISN: {{ $issue->nisn ?: ($record->nisn ?: 'Tidak tersedia') }}<br>
                Kelas: {{ $record->source_classroom_name ?: 'Tidak tersedia' }}<br>
                Tanggal kejadian: {{ $record->occurred_at?->locale('id')->translatedFormat('d M Y') ?? 'Tidak tersedia' }}</p>
            @if($issue->issue_code === 'source_identity_mismatch')
                <p class="mt-2 mb-0">Data tersimpan sebelumnya: {{ \App\Support\StudentName::display($record->source_student_name) ?: 'Nama tidak tersedia' }}, NISN {{ $record->nisn }}. Kelas dan tanggal di atas berasal dari data sebelumnya.</p>
            @endif
        </div>
        <div class="col-md-6">
            <h3 class="fs-6 fw-bold">Data sekolah</h3>
            @if($masterStudent)
                <p class="mb-0">Nama: {{ \App\Support\StudentName::display($masterStudent->name) }}<br>NISN: {{ $masterStudent->nisn }}</p>
                @unless($record->student)<p class="mb-0">Calon murid dengan NISN sama; belum dicocokkan.</p>@endunless
                @if($record->occurred_at)
                    <p class="mt-2 mb-0">Kelas pada tanggal kejadian:
                        @forelse($memberships as $membership)
                            {{ $membership->classroom?->name ?? 'Kelas tidak tersedia' }} ({{ $membership->academicYear?->name ?? 'Tahun ajaran tidak tersedia' }}){{ $loop->last ? '' : ', ' }}
                        @empty
                            Tidak tercatat
                        @endforelse
                    </p>
                @else
                    <p class="mt-2 mb-0">Kelas pada tanggal kejadian tidak dapat dibandingkan karena tanggal atau tahun ajaran tidak tersedia.</p>
                @endif
            @else
                <p class="mb-0">Murid belum dicocokkan dengan data sekolah.</p>
            @endif
        </div>
    @else
        <div class="col-12">
            @if($issue->entity_type === 'etatib_record')
                <p class="mb-0">Data e-Tatib: {{ \App\Support\StudentName::display($issue->input_name) ?: 'Nama tidak tersedia' }}; NISN {{ $issue->nisn ?: 'tidak tersedia' }}. Catatan sumber tidak tersedia untuk perbandingan rinci.</p>
            @else
                <p class="mb-0">Data lokal: {{ $local ?: (\App\Support\StudentName::display($issue->input_name) ?: ($issue->nisn ? 'NISN '.$issue->nisn : 'Tidak tersedia')) }}. Periksa data sumber resmi sebelum mencatat tindak lanjut.</p>
            @endif
        </div>
    @endif
    @if($issue->entity_type === 'etatib_record' && in_array($issue->issue_code, ['student_not_found', 'student_name_mismatch'], true))
        <div class="col-12"><a href="{{ route('data-master.etatib.conflicts.index') }}">Cocokkan identitas murid</a></div>
    @endif
</div>

@if(data_get($issue->details, 'review.action'))
    <p class="small mt-3 mb-0">Keputusan terakhir: {{ match (data_get($issue->details, 'review.action')) { 'use_school' => 'Kelas saat kejadian sesuai data sekolah', 'use_etatib' => 'Kelas saat kejadian sesuai data e-Tatib', default => 'Perlu koreksi data' } }}
        @if(data_get($issue->details, 'review.choice.classroom')) — {{ data_get($issue->details, 'review.choice.classroom') }} @endif<br>
        Oleh {{ $reviewer?->name ?? 'Petugas tidak tersedia' }}
        @if(data_get($issue->details, 'review.reviewed_at')) · {{ \Carbon\Carbon::parse(data_get($issue->details, 'review.reviewed_at'))->locale('id')->translatedFormat('d M Y, H.i') }} @endif
        @if(data_get($issue->details, 'review.note'))<br>Catatan: {{ data_get($issue->details, 'review.note') }}@endif
    </p>
@endif

@if($issue->resolved_at)
    <p class="small mt-3 mb-0">Masalah ini sudah selesai.</p>
@else
    <form method="POST" action="{{ route('data-master.sync-issues.update', $issue) }}" class="mt-3">
        @csrf
        @method('PATCH')
        <input type="hidden" name="_sync_issue" value="{{ $issue->id }}">
        <fieldset>
            <legend class="fs-6 fw-bold">{{ $issue->issue_code === 'student_classroom_mismatch' ? 'Kelas '.(\App\Support\StudentName::display($masterStudent?->name ?? $issue->input_name) ?: 'murid').' pada tanggal kejadian' : 'Pilih tindak lanjut' }}</legend>
            @if($issue->issue_code === 'student_classroom_mismatch' && $record?->student_id && $record->occurred_at && $memberships->isNotEmpty())
                <p class="small mb-2">Pilih kelas yang benar berdasarkan bukti pada tanggal kejadian.</p>
                @foreach($memberships as $membership)
                    @if($membership->classroom)
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="action" id="action-school-{{ $issue->id }}-{{ $membership->id }}" value="use_school" data-sync-membership="{{ $membership->id }}" @checked(old('action') === 'use_school' && old('membership_id') == $membership->id) required>
                            <label class="form-check-label" for="action-school-{{ $issue->id }}-{{ $membership->id }}">{{ $membership->classroom->name }} ({{ $membership->academicYear?->name }}) — sesuai data sekolah</label>
                        </div>
                    @endif
                @endforeach
                <div class="form-check">
                    <input class="form-check-input" type="radio" name="action" id="action-etatib-{{ $issue->id }}" value="use_etatib" @checked(old('action') === 'use_etatib') required>
                    <label class="form-check-label" for="action-etatib-{{ $issue->id }}">{{ $record->source_classroom_name }} — sesuai data e-Tatib</label>
                </div>
                <input type="hidden" name="membership_id" value="" data-sync-membership-input>
                <p class="small text-muted mt-2 mb-0">Keputusan ini hanya berlaku untuk pelanggaran ini. Jika riwayat kelas sekolah keliru, perbaiki melalui sumber resmi.</p>
            @endif
            <div class="form-check">
                <input class="form-check-input" type="radio" name="action" id="action-correction-{{ $issue->id }}" value="source_correction" @checked(old('action') === 'source_correction') required>
                <label class="form-check-label" for="action-correction-{{ $issue->id }}">Belum dapat menentukan; perlu koreksi data</label>
            </div>
        </fieldset>
        <label for="review-note-{{ $issue->id }}" class="form-label mt-3">Catatan singkat (wajib jika perlu koreksi sumber)</label>
        <textarea id="review-note-{{ $issue->id }}" name="note" class="form-control" rows="2" maxlength="1000">{{ old('note', data_get($issue->details, 'review.note')) }}</textarea>
        <button type="submit" class="btn btn-primary mt-3">Simpan tindakan</button>
    </form>
@endif
