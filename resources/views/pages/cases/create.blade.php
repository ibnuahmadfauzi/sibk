@extends('layouts.app-2')

@section('page-title', 'Catat Permasalahan - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-102">
        <div class="sibk-page-header mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ route('cases.index') }}" class="btn btn-icon btn-light" aria-label="Kembali">←</a>
                <div class="sibk-page-header__copy m-0"><h1 class="mb-1">Catat Permasalahan</h1><p class="mb-0">Catat informasi awal layanan BK secara terstruktur.</p></div>
            </div>
        </div>

        @if($errors->any())
            <div class="alert alert-danger" role="alert"><ul class="mb-0">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <div class="sibk-panel mb-4 border-0 shadow-sm">
            <div class="sibk-panel__body p-4">
                <form action="{{ route('cases.create') }}" method="GET" id="etatib-filter-form">
                    <label for="etatib_temporary_nisn_filter" class="form-label sibk-form-label mb-2">Cari e-Tatib untuk Identitas Sementara</label>
                    <div class="row g-3">
                        <div class="col-12 col-md-8">
                            <input class="form-control sibk-form-control" id="etatib_temporary_nisn_filter" name="temporary_nisn" value="{{ $temporaryNisnFilter }}" maxlength="20" inputmode="numeric" pattern="[0-9]{1,20}" placeholder="Masukkan NISN yang sama persis">
                        </div>
                        <div class="col-12 col-md-4">
                            <button type="submit" class="btn btn-outline-primary w-100">Tampilkan Data e-Tatib</button>
                        </div>
                    </div>
                </form>

                <div class="row g-3 mt-1">
                    <div class="col-12 col-md-4">
                        <label for="temporary_nisn" class="form-label sibk-form-label">NISN Sementara</label>
                        <input form="case-create-form" class="form-control sibk-form-control" id="temporary_nisn" name="temporary_nisn" value="{{ old('temporary_nisn', $temporaryNisnFilter) }}" maxlength="20" inputmode="numeric" placeholder="Isi bila murid belum tersedia">
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="temporary_name" class="form-label sibk-form-label">Nama Sementara</label>
                        <input form="case-create-form" class="form-control sibk-form-control" id="temporary_name" name="temporary_name" value="{{ old('temporary_name') }}" maxlength="150" placeholder="Nama sesuai informasi awal">
                    </div>
                </div>
            </div>
        </div>

        <form action="{{ route('cases.store') }}" method="POST" id="case-create-form" data-autosave-form="case" data-autosave-record="new">
            @csrf
            @if($preselectedStudentId)
                <input type="hidden" name="student_id" value="{{ old('student_id', $preselectedStudentId) }}">
            @endif
            <div class="visually-hidden" aria-hidden="true">
                @foreach($students as $student)
                    @php $membership = $student->classMemberships->first(); @endphp
                    <span>{{ $student->name }} — {{ $student->nisn }} ({{ $membership?->classroom?->name ?? 'Tanpa kelas aktif' }}){{ $student->usesProvisionalData($membership) ? ' — Sementara' : '' }}</span>
                @endforeach
            </div>

            <div class="sibk-panel mb-4 border-0 shadow-sm">
                <div class="sibk-panel__body p-4 p-md-5">
                    <h4 class="fs-5 mb-1 text-dark fw-bold">Murid dan Sumber Permasalahan</h4>
                    <p class="text-muted small mb-4">Pilih murid yang Anda tangani. Jika belum terdaftar, isi NISN dan nama sementara.</p>
                    <div class="row g-4">
                        <div class="col-md-4">
                            <label for="sumber" class="form-label sibk-form-label">Sumber</label>
                            <select class="form-select sibk-form-select" id="sumber" name="case_source_id" required>
                                <option value="">Pilih sumber</option>
                                @foreach($caseSources as $source)<option value="{{ $source->id }}" data-code="{{ $source->code }}" @selected((string) old('case_source_id') === (string) $source->id)>{{ $source->label }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="service_field_id" class="form-label sibk-form-label">Jenis Masalah</label>
                            <select class="form-select sibk-form-select" id="service_field_id" name="service_field_id" required>
                                <option value="">Pilih jenis masalah</option>
                                @foreach($serviceFields as $field)<option value="{{ $field->id }}" @selected((string) old('service_field_id') === (string) $field->id)>{{ $field->label }}</option>@endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="tanggal" class="form-label sibk-form-label">Tanggal Layanan</label>
                            <input type="date" class="form-control sibk-form-control" id="tanggal" name="service_date" value="{{ old('service_date', today()->toDateString()) }}" required>
                        </div>
                        @php
                            $selectedSource = $caseSources->firstWhere('id', old('case_source_id'));
                            $isRujukanInitial = $selectedSource?->code === 'rujukan';
                        @endphp
                        <div @class(['col-12', 'd-none' => ! $isRujukanInitial]) id="referrer-group">
                            <label for="referrer" class="form-label sibk-form-label">Pihak Perujuk</label>
                            <input class="form-control sibk-form-control" id="referrer" name="referrer" value="{{ old('referrer') }}" placeholder="Isi bila sumber permasalahan berasal dari rujukan">
                        </div>
                    </div>
                </div>
            </div>

            <div class="sibk-panel mb-4 border-0 shadow-sm">
                <div class="sibk-panel__body p-4 p-md-5">
                    <h4 class="fs-5 mb-1 text-dark fw-bold">Informasi Permasalahan</h4>
                    <p class="text-muted small mb-4">Tuliskan informasi yang diperlukan untuk memulai penanganan.</p>
                    <div class="row g-4">
                        <div class="col-md-6"><label for="initial_info" class="form-label sibk-form-label">Informasi Awal</label><textarea class="form-control sibk-form-control" id="initial_info" name="initial_info" rows="4" required>{{ old('initial_info') }}</textarea></div>
                        <div class="col-md-6"><label for="initial_action" class="form-label sibk-form-label">Penanganan Awal</label><textarea class="form-control sibk-form-control" id="initial_action" name="initial_action" rows="4" required>{{ old('initial_action') }}</textarea></div>
                        <div class="col-12"><label for="internal_note" class="form-label sibk-form-label">Catatan Internal</label><textarea class="form-control sibk-form-control" id="internal_note" name="internal_note" rows="2" placeholder="Hanya terlihat bagi Guru BK yang memiliki penugasan aktif">{{ old('internal_note') }}</textarea></div>
                    </div>
                </div>
            </div>

            <div class="sibk-panel mb-4 border-0 shadow-sm">
                <div class="sibk-panel__body p-4 p-md-5">
                    <h4 class="fs-5 mb-1 text-dark fw-bold">Data e-Tatib Terkait</h4>
                    <p class="text-muted small mb-4">Pilih pelanggaran dengan NISN yang sama. Wajib untuk permasalahan dari e-Tatib.</p>
                    @if($etatibRecordsCapped)
                        <div class="alert alert-info py-2">Daftar data e-Tatib dibatasi pada {{ $etatibRecords->count() }} record terbaru. Gunakan pencarian NISN yang sama persis untuk mempersempit hasil.</div>
                    @endif
                    @forelse($etatibRecords as $record)
                        <div class="form-check border rounded p-3 mb-2 ps-5" data-etatib-nisn="{{ $record->nisn }}">
                            <input class="form-check-input" type="checkbox" name="etatib_record_ids[]" value="{{ $record->id }}" id="etatib-{{ $record->id }}" @checked(in_array($record->id, old('etatib_record_ids', [])))>
                            <label class="form-check-label w-100" for="etatib-{{ $record->id }}">
                                <span class="fw-semibold">{{ $record->violation_type }}</span>
                                <span class="text-muted small d-block">
                                    NISN {{ $record->nisn }} · {{ $record->occurred_at->locale('id')->translatedFormat('d M Y H:i') }} · {{ $record->points }} poin
                                </span>
                                <span class="text-muted small d-block">
                                    Total resmi {{ $record->source_total_points ?? '-' }} · Kelas {{ $record->source_classroom_name ?: '-' }} · Pencatat {{ $record->recorded_by_name ?: '-' }}
                                </span>
                            </label>
                        </div>
                    @empty
                        <p class="text-muted mb-0">Belum ada data e-Tatib. Hubungi Admin IT untuk memeriksa koneksi.</p>
                    @endforelse
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mb-5">
                <span class="small text-muted me-auto align-self-center" data-draft-status aria-live="polite"></span>
                <button type="button" class="btn btn-light" data-clear-draft>Hapus Draft</button>
                <a href="{{ route('cases.index') }}" class="btn btn-outline-secondary px-4">Batal</a>
                <button type="submit" class="btn btn-primary px-4">Simpan</button>
            </div>
        </form>
    </div>
@endsection

@section('extra-javascript')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const temporaryNisn = document.getElementById('temporary_nisn');
            const temporaryName = document.getElementById('temporary_name');
            const filterNisn = document.getElementById('etatib_temporary_nisn_filter');
            const sumber = document.getElementById('sumber');
            const referrer = document.getElementById('referrer');
            const records = document.querySelectorAll('[data-etatib-nisn]');

            const refreshEtatib = () => {
                const selectedNisn = temporaryNisn?.value.trim() || filterNisn?.value.trim() || '';

                records.forEach((record) => {
                    const matches = selectedNisn !== '' && record.dataset.etatibNisn === selectedNisn;
                    record.classList.toggle('d-none', !matches);
                    if (!matches) record.querySelector('input').checked = false;
                });
            };

            const referrerGroup = document.getElementById('referrer-group');

            const updateReferrerState = () => {
                const selectedOption = sumber?.selectedOptions[0];
                const isRujukan = (selectedOption?.dataset?.code === 'rujukan') ||
                                  (selectedOption?.text?.trim().toLowerCase() === 'rujukan');
                if (referrerGroup) {
                    referrerGroup.classList.toggle('d-none', !isRujukan);
                    if (!isRujukan && referrer) {
                        referrer.value = '';
                    }
                }
            };

            filterNisn?.addEventListener('input', () => {
                if (temporaryNisn && (!temporaryNisn.value || temporaryNisn.dataset.synced === 'true')) {
                    temporaryNisn.value = filterNisn.value;
                    temporaryNisn.dataset.synced = 'true';
                }
                refreshEtatib();
            });

            temporaryNisn?.addEventListener('input', () => {
                if (temporaryNisn) temporaryNisn.dataset.synced = 'false';
                refreshEtatib();
            });

            sumber?.addEventListener('change', updateReferrerState);

            refreshEtatib();
            updateReferrerState();
        });
    </script>
@endsection
