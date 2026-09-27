@extends('layouts.app-2')

@section('page-title', 'Periksa Sinkronisasi - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-501">
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy m-0">
                <a href="{{ route('data-master.index', ['tab' => 'sinkronisasi']) }}">← Kembali ke Sinkronisasi</a>
                <h1 class="mb-1 mt-2">Periksa data sinkronisasi</h1>
                <p class="mb-0">{{ $issue->summary }}</p>
            </div>
        </div>

        @if(session('success'))
            <x-notification-toast tone="success">{{ session('success') }}</x-notification-toast>
        @endif
        @if($errors->any())
            <div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>
        @endif

        <section class="sibk-panel mb-4" aria-labelledby="issue-comparison-title">
            <div class="sibk-panel__body p-4">
                <h2 class="fs-5 fw-bold" id="issue-comparison-title">Data yang perlu dibandingkan</h2>
                <p>Sumber: {{ $issue->syncRun?->source === 'etatib' ? 'e-Tatib' : 'Dapodik' }}</p>
                @if($record)
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h3 class="fs-6 fw-bold">Data e-Tatib</h3>
                            <p>Nama: {{ $issue->input_name ?: ($record->source_student_name ?: 'Tidak tersedia') }}<br>
                                NISN: {{ $issue->nisn ?: ($record->nisn ?: 'Tidak tersedia') }}<br>
                                Kelas: {{ $record->source_classroom_name ?: 'Tidak tersedia' }}<br>
                                Tanggal kejadian: {{ $record->occurred_at?->locale('id')->translatedFormat('d M Y') ?? 'Tidak tersedia' }}</p>
                            @if($issue->issue_code === 'source_identity_mismatch')
                                <p>Data tersimpan sebelumnya: {{ $record->source_student_name ?: 'Nama tidak tersedia' }}, NISN {{ $record->nisn }}. Kelas dan tanggal di atas berasal dari data sebelumnya.</p>
                            @endif
                        </div>
                        <div class="col-md-6">
                            <h3 class="fs-6 fw-bold">Data sekolah</h3>
                            @if($masterStudent)
                                <p>Nama: {{ $masterStudent->name }}<br>NISN: {{ $masterStudent->nisn }}</p>
                                @unless($record->student)<p>Calon murid dengan NISN sama; belum dicocokkan.</p>@endunless
                                @if($record->occurred_at)
                                    <p>Kelas pada tanggal kejadian:
                                        @forelse($memberships as $membership)
                                            {{ $membership->classroom?->name ?? 'Kelas tidak tersedia' }} ({{ $membership->academicYear?->name ?? 'Tahun ajaran tidak tersedia' }}){{ $loop->last ? '' : ', ' }}
                                        @empty
                                            Tidak tercatat
                                        @endforelse
                                    </p>
                                @else
                                    <p>Kelas pada tanggal kejadian tidak dapat dibandingkan karena tanggal atau tahun ajaran tidak tersedia.</p>
                                @endif
                            @else
                                <p>Murid belum dicocokkan dengan data sekolah.</p>
                            @endif
                        </div>
                    </div>
                @else
                    @if($issue->entity_type === 'etatib_record')
                        <p>Data e-Tatib: {{ $issue->input_name ?: 'Nama tidak tersedia' }}; NISN {{ $issue->nisn ?: 'tidak tersedia' }}.</p>
                        <p>Catatan sumber tidak tersedia untuk perbandingan rinci.</p>
                    @else
                        <p>Data lokal: {{ $local ?: ($issue->input_name ?: ($issue->nisn ? 'NISN '.$issue->nisn : 'Tidak tersedia')) }}</p>
                        <p>Perbandingan langsung dengan data sumber tidak tersedia untuk catatan ini. Periksa data sumber resmi sebelum mencatat tindak lanjut.</p>
                    @endif
                @endif
                @if($issue->entity_type === 'etatib_record' && in_array($issue->issue_code, ['student_not_found', 'student_name_mismatch'], true))
                    <a href="{{ route('data-master.etatib.conflicts.index') }}">Cocokkan identitas murid</a>
                @endif
            </div>
        </section>

        <section class="sibk-panel" aria-labelledby="issue-review-title">
            <div class="sibk-panel__body p-4">
                <h2 class="fs-5 fw-bold" id="issue-review-title">Catatan pemeriksaan</h2>
                @if(data_get($issue->details, 'review.status'))
                    <p>Terakhir diperiksa: {{ data_get($issue->details, 'review.status') === 'source_correction' ? 'Perlu koreksi sumber' : 'Sudah diperiksa' }}<br>
                        Oleh: {{ $reviewer?->name ?? 'Petugas tidak tersedia' }}<br>
                        Waktu: {{ data_get($issue->details, 'review.reviewed_at') ? \Carbon\Carbon::parse(data_get($issue->details, 'review.reviewed_at'))->locale('id')->translatedFormat('d M Y, H.i') : 'Tidak tersedia' }}<br>
                        Catatan: {{ data_get($issue->details, 'review.note') }}</p>
                @endif
                @if($issue->resolved_at)
                    <p>Masalah ini sudah selesai.</p>
                @else
                    <p>Catatan pemeriksaan tidak mengubah data sekolah atau sumber. Masalah tetap terbuka sampai data cocok.</p>
                    <form method="POST" action="{{ route('data-master.sync-issues.update', $issue) }}">
                        @csrf
                        @method('PATCH')
                        <div class="mb-3">
                            <label for="review-status" class="form-label">Hasil pemeriksaan</label>
                            <select id="review-status" name="status" class="form-select" required>
                                <option value="">Pilih hasil</option>
                                <option value="reviewed" @selected(old('status', data_get($issue->details, 'review.status')) === 'reviewed')>Sudah diperiksa</option>
                                <option value="source_correction" @selected(old('status', data_get($issue->details, 'review.status')) === 'source_correction')>Perlu koreksi sumber</option>
                            </select>
                        </div>
                        <div class="mb-3">
                            <label for="review-note" class="form-label">Catatan singkat</label>
                            <textarea id="review-note" name="note" class="form-control" rows="3" maxlength="1000" required>{{ old('note', data_get($issue->details, 'review.note')) }}</textarea>
                        </div>
                        <button type="submit" class="btn btn-primary">Simpan pemeriksaan</button>
                    </form>
                @endif
            </div>
        </section>
    </div>
@endsection
