@extends('layouts.app-2')

@section('page-title', ($isEdit ? 'Ubah' : 'Catat').' Konsultasi - Ruang BK')

@section('body')
    @php
        $userDisplayName = auth()->user()?->name ?? 'Guru BK';
        $selectedStudent = $isEdit ? null : $students->firstWhere('id', old('student_id', $preselectedStudentId));
        $studentLookupData = $isEdit ? [] : $students->map(function ($student) {
            $classroom = $student->classMemberships->first()?->classroom;

            return [
                'id' => $student->id,
                'nisn' => $student->nisn,
                'name' => $student->name,
                'classroom' => $classroom?->name ?? 'Rombel belum tercatat',
            ];
        })->values();
    @endphp

    <div class="sibk-dashboard py-3" data-page-id="PG-105">
        {{-- Header Halaman --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-3">
                @if($isEdit)
                    <a href="{{ route('consultations.show', $consultation) }}" class="btn btn-light rounded-circle shadow-sm border d-flex align-items-center justify-content-center text-dark flex-shrink-0" style="width: 44px; height: 44px;" aria-label="Kembali ke detail konsultasi">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                @else
                    <a href="{{ route('cases.index', ['tab' => 'konsultasi']) }}" class="btn btn-light rounded-circle shadow-sm border d-flex align-items-center justify-content-center text-dark flex-shrink-0" style="width: 44px; height: 44px;" aria-label="Kembali ke daftar konsultasi">
                        <svg width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                        </svg>
                    </a>
                @endif
                <div>
                    <h1 class="h4 fw-bold mb-1 text-dark">{{ $isEdit ? 'Ubah' : 'Catat' }} Konsultasi</h1>
                    <p class="text-secondary small mb-0">Konsultasi dicatat sebagai layanan yang telah selesai.</p>
                </div>
            </div>

            {{-- User Profile Badge --}}
            <div class="d-none d-md-flex align-items-center gap-2 px-3 py-2 bg-white rounded-pill border shadow-sm">
                <span class="rounded-circle bg-light d-flex align-items-center justify-content-center text-primary" style="width: 30px; height: 30px;">
                    <svg width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                    </svg>
                </span>
                <span class="small fw-semibold text-dark">{{ $userDisplayName }}</span>
                <svg width="14" height="14" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" class="text-muted ms-1" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"></path>
                </svg>
            </div>
        </div>

        @if($isEdit)
            <div class="sibk-panel"><div class="sibk-panel__body p-4">@include('pages.consultations._edit-content', ['modal' => false])</div></div>
        @else
            @include('pages.consultations._edit-modal', ['modal' => false])
        @endif
    </div>
@endsection

@section('extra-javascript')
    @unless($isEdit)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const students = @json($studentLookupData);
                const form = document.getElementById('consultation-create-form');
                const lookup = document.getElementById('student_lookup');
                const studentId = document.getElementById('student_id');
                const studentName = document.getElementById('student_name_display');
                const studentClassroom = document.getElementById('student_classroom_display');
                const results = document.getElementById('student_lookup_results');
                const manualToggle = document.getElementById('student_manual_toggle');
                const manualFields = document.getElementById('student_manual_fields');
                const temporaryNisn = document.getElementById('temporary_nisn');
                const temporaryName = document.getElementById('temporary_name');
                const temporaryClassroom = document.getElementById('temporary_classroom_id');

                const hideResults = () => {
                    results?.classList.add('d-none');
                    lookup?.setAttribute('aria-expanded', 'false');
                };

                const selectStudent = (student) => {
                    studentId.value = student.id;
                    lookup.value = `${student.nisn} — ${student.name}`;
                    studentName.value = student.name;
                    studentClassroom.value = student.classroom;
                    temporaryNisn.value = '';
                    temporaryName.value = '';
                    temporaryClassroom.value = '';
                    hideResults();
                };

                const renderResults = () => {
                    if (!results || lookup.disabled) return hideResults();
                    const term = lookup.value.trim().toLocaleLowerCase('id');
                    results.replaceChildren();
                    if (!term) return hideResults();

                    const matches = students.filter((student) =>
                        String(student.nisn).toLocaleLowerCase('id').includes(term)
                        || student.name.toLocaleLowerCase('id').includes(term)
                    ).slice(0, 6);

                    if (matches.length === 0) {
                        const empty = document.createElement('div');
                        empty.className = 'px-3 py-2 small text-secondary bg-white';
                        empty.textContent = 'Murid tidak ditemukan. Gunakan input manual.';
                        results.appendChild(empty);
                    } else {
                        matches.forEach((student) => {
                            const option = document.createElement('button');
                            const nisn = document.createElement('strong');
                            const name = document.createElement('span');
                            const classroom = document.createElement('span');
                            option.type = 'button';
                            option.className = 'list-group-item list-group-item-action px-3 py-2 text-start';
                            option.setAttribute('role', 'option');
                            nisn.className = 'd-block small text-primary';
                            nisn.textContent = student.nisn;
                            name.className = 'd-block fw-semibold text-dark';
                            name.textContent = student.name;
                            classroom.className = 'd-block small text-secondary';
                            classroom.textContent = student.classroom;
                            option.append(nisn, name, classroom);
                            option.addEventListener('click', () => selectStudent(student));
                            results.appendChild(option);
                        });
                    }
                    results.classList.remove('d-none');
                    lookup.setAttribute('aria-expanded', 'true');
                };

                const setManualMode = (manual, clear = true) => {
                    form.dataset.manualMode = manual ? 'true' : 'false';
                    manualFields.classList.toggle('d-none', !manual);
                    [temporaryNisn, temporaryName, temporaryClassroom].forEach((field) => { field.disabled = !manual; });
                    lookup.disabled = manual;
                    manualToggle.textContent = manual ? 'Kembali cari murid lokal' : 'Murid belum ada? Isi manual';
                    manualToggle.setAttribute('aria-expanded', manual ? 'true' : 'false');
                    if (clear) {
                        studentId.value = '';
                        lookup.value = '';
                        studentName.value = '';
                        studentClassroom.value = '';
                        if (!manual) {
                            temporaryNisn.value = '';
                            temporaryName.value = '';
                            temporaryClassroom.value = '';
                        }
                    }
                    hideResults();
                    (manual ? temporaryNisn : lookup).focus();
                };

                lookup?.addEventListener('input', () => {
                    studentId.value = '';
                    studentName.value = '';
                    studentClassroom.value = '';
                    renderResults();
                });
                lookup?.addEventListener('focus', renderResults);
                lookup?.addEventListener('keydown', (event) => {
                    if (event.key === 'Escape') hideResults();
                    if (event.key === 'ArrowDown') {
                        const firstResult = results?.querySelector('button');
                        if (firstResult) {
                            event.preventDefault();
                            firstResult.focus();
                        }
                    }
                });
                manualToggle?.addEventListener('click', () => setManualMode(form.dataset.manualMode !== 'true'));
                document.addEventListener('click', (event) => {
                    if (!results?.contains(event.target) && event.target !== lookup) hideResults();
                });

                window.setTimeout(() => {
                    const selected = students.find((student) => String(student.id) === String(studentId.value));
                    const manual = !selected && Boolean(temporaryNisn.value || temporaryName.value || temporaryClassroom.value);
                    if (selected) selectStudent(selected);
                    setManualMode(manual, false);
                }, 0);
            });
        </script>
    @endunless
@endsection
