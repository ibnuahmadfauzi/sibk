@extends('layouts.app-2')

@section('page-title', ($isEdit ? 'Ubah' : 'Catat').' Konsultasi - Ruang BK')

@section('body')
    @php
        $userDisplayName = auth()->user()?->name ?? 'Guru BK';
        $studentLookupData = $isEdit ? [] : $students->map(function ($student) {
            $classroom = $student->classMemberships->first()?->classroom;

            return [
                'id' => $student->id,
                'nisn' => $student->nisn,
                'name' => $student->name,
                'classroom' => $classroom?->name ?? 'Rombel belum tercatat',
                'classroom_id' => $student->classMemberships->first()?->classroom_id,
            ];
        })->values();
    @endphp

    <div class="sibk-dashboard py-3" data-page-id="PG-105">
        {{-- Header Halaman --}}
        <div class="d-flex align-items-center justify-content-between mb-4">
            <div class="d-flex align-items-center gap-3">
                @if($isEdit)
                    <x-back-button :href="route('consultations.show', $consultation)" label="Kembali ke detail konsultasi" />
                @else
                    <x-back-button :href="route('cases.index', ['tab' => 'konsultasi'])" label="Kembali ke daftar konsultasi" />
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
                const hiddenStudentId = document.getElementById('hidden_student_id');
                const studentNisnInput = document.getElementById('student_nisn');
                const studentNameInput = document.getElementById('student_name');
                const manualClassroomSelect = document.getElementById('manual_classroom_select');
                const studentLookupResults = document.getElementById('student_lookup_results');

                function hideStudentLookup() {
                    if (!studentLookupResults) return;

                    studentLookupResults.classList.add('d-none');
                    studentLookupResults.innerHTML = '';
                    studentNisnInput?.setAttribute('aria-expanded', 'false');
                    studentNameInput?.setAttribute('aria-expanded', 'false');
                }

                function selectStudent(student) {
                    if (hiddenStudentId) hiddenStudentId.value = student.id;
                    if (studentNisnInput) studentNisnInput.value = student.nisn || '';
                    if (studentNameInput) studentNameInput.value = student.name || '';
                    if (manualClassroomSelect) manualClassroomSelect.value = student.classroom_id || '';
                    hideStudentLookup();
                }

                function escapeHtml(str) {
                    if (!str) return '';
                    return String(str)
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                }

                function renderStudentLookup(query) {
                    if (!studentLookupResults) {
                        hideStudentLookup();
                        return;
                    }

                    const term = String(query || '').trim().toLowerCase();
                    if (!term) {
                        hideStudentLookup();
                        return;
                    }

                    const matches = students.filter(student =>
                        String(student.nisn || '').toLowerCase().includes(term)
                        || String(student.name || '').toLowerCase().includes(term)
                    ).slice(0, 8);

                    studentLookupResults.innerHTML = '';
                    studentLookupResults.classList.remove('d-none');
                    studentNisnInput?.setAttribute('aria-expanded', 'true');
                    studentNameInput?.setAttribute('aria-expanded', 'true');

                    if (matches.length === 0) {
                        studentLookupResults.innerHTML = '<div class="px-3 py-2 small text-secondary">Murid tidak ditemukan. Silakan lanjutkan isi data secara manual.</div>';
                        return;
                    }

                    matches.forEach(student => {
                        const option = document.createElement('button');
                        option.type = 'button';
                        option.className = 'list-group-item list-group-item-action px-3 py-2 text-start';
                        option.setAttribute('role', 'option');
                        option.innerHTML = `<span class="d-block fw-semibold text-dark">${escapeHtml(student.name)}</span><span class="small text-secondary">NISN: ${escapeHtml(student.nisn)} &bull; Rombel: ${escapeHtml(student.classroom)}</span>`;
                        option.addEventListener('click', () => selectStudent(student));
                        studentLookupResults.appendChild(option);
                    });
                }

                [studentNisnInput, studentNameInput].forEach(input => {
                    input?.addEventListener('input', () => {
                        if (hiddenStudentId) hiddenStudentId.value = '';
                        renderStudentLookup(input.value);
                    });
                    input?.addEventListener('focus', () => renderStudentLookup(input.value));
                    input?.addEventListener('keydown', event => {
                        if (event.key === 'Escape') hideStudentLookup();
                        if (event.key === 'ArrowDown') {
                            const firstOption = studentLookupResults?.querySelector('button');
                            if (firstOption) {
                                event.preventDefault();
                                firstOption.focus();
                            }
                        }
                    });
                });

                document.addEventListener('click', event => {
                    if (!studentLookupResults?.contains(event.target)
                        && event.target !== studentNisnInput
                        && event.target !== studentNameInput) {
                        hideStudentLookup();
                    }
                });

                if (hiddenStudentId && hiddenStudentId.value) {
                    const selected = students.find(s => String(s.id) === String(hiddenStudentId.value));
                    if (selected && (!studentNisnInput?.value || !studentNameInput?.value)) {
                        selectStudent(selected);
                    }
                }
            });
        </script>
    @endunless
@endsection
