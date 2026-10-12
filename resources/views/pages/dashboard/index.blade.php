@extends('layouts.app-2')
@section('page-title', 'Dashboard | Aplikasi BK')

@section('body')
    @include('pages.dashboard.html')
    @can('create', \App\Models\Consultation::class)
        <div class="modal fade" id="consultation-modal" tabindex="-1" aria-labelledby="case-modal-title" aria-hidden="true" data-service-record-modal>
            <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
                <div class="modal-content">
                    <div class="modal-header"><h2 class="modal-title fs-5" id="case-modal-title">Catat Konsultasi</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                    <div class="modal-body"><p class="text-muted mb-0">Memuat data…</p></div>
                </div>
            </div>
            <div data-modal-submit-error>
                <x-notification-toast tone="error" title="Gagal menyimpan"><span data-modal-submit-error-message></span></x-notification-toast>
            </div>
        </div>
    @endcan
@endsection
