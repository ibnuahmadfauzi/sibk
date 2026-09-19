@extends('layouts.app-2')

@section('page-title', ($isEdit ? 'Ubah' : 'Catat').' Konsultasi - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-105">
        <div class="sibk-page-header mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="{{ $isEdit ? route('consultations.show', $consultation) : route('cases.index', ['tab' => 'konsultasi']) }}" class="btn btn-icon btn-light" aria-label="Kembali">&larr;</a>
                <div class="sibk-page-header__copy m-0">
                    <h1>{{ $isEdit ? 'Ubah' : 'Catat' }} Konsultasi</h1>
                    <p>Konsultasi dicatat sebagai layanan yang telah selesai.</p>
                </div>
            </div>
        </div>

        @include('pages.consultations._edit-modal', ['modal' => false])
    </div>
@endsection

@section('extra-javascript')
    @unless($isEdit)
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const lookup = document.getElementById('student_lookup');
                const studentId = document.getElementById('student_id');
                const temporaryNisn = document.getElementById('temporary_nisn');
                const temporaryName = document.getElementById('temporary_name');
                const options = [...document.querySelectorAll('#student-options option')];

                lookup?.addEventListener('input', () => {
                    studentId.value = options.find((option) => option.value === lookup.value)?.dataset.id ?? '';
                    if (studentId.value) {
                        temporaryNisn.value = '';
                        temporaryName.value = '';
                    }
                });
                temporaryNisn?.addEventListener('input', () => {
                    if (temporaryNisn.value.trim()) {
                        lookup.value = '';
                        studentId.value = '';
                    }
                });
            });
        </script>
    @endunless
@endsection
