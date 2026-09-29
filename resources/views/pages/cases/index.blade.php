@extends('layouts.app-2')

@section('page-title', 'Layanan BK - Ruang BK')

@section('body')
    <div class="sibk-dashboard" data-page-id="PG-101">
        @if(session('success'))<x-notification-toast>{{ session('success') }}</x-notification-toast>@endif
        @if($errors->any())<x-notification-toast tone="error">{{ $errors->first() }}</x-notification-toast>@endif
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy"><h1>Layanan BK</h1><p>Catatan permasalahan, konsultasi, dan progres penanganan pengunduran diri murid.</p></div>
        </div>

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-4">
            <ul class="nav nav-pills gap-2 mb-0">
                <li class="nav-item"><a class="nav-link {{ $activeTab === 'kasus' ? 'active' : '' }}" href="{{ route('cases.index', ['tab' => 'kasus']) }}">Catatan Permasalahan</a></li>
                @can('viewAny', \App\Models\Consultation::class)<li class="nav-item"><a class="nav-link {{ $activeTab === 'konsultasi' ? 'active' : '' }}" href="{{ route('cases.index', ['tab' => 'konsultasi']) }}">Sesi Bimbingan & Konsultasi</a></li>@endcan
                @can('viewAny', \App\Models\WithdrawalProgress::class)<li class="nav-item"><a class="nav-link {{ $activeTab === 'pengunduran-diri' ? 'active' : '' }}" href="{{ route('cases.index', ['tab' => 'pengunduran-diri']) }}">Pengunduran Diri</a></li>@endcan
            </ul>
            @if($activeTab === 'konsultasi' && $canCreateConsultation)<a href="{{ route('consultations.create') }}" class="btn btn-primary">Catat Konsultasi</a>@elseif($activeTab === 'kasus' && $canCreateCase)<a href="{{ route('cases.create') }}" class="btn btn-primary">Catat Permasalahan</a>@elseif($activeTab === 'pengunduran-diri' && $canCreateWithdrawal)<button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#withdrawal-create-modal" @disabled($withdrawalStudents->isEmpty())>Catat Pengunduran Diri</button>@endif
        </div>

        @if($activeTab === 'kasus')
            <div class="sibk-panel mb-4"><div class="sibk-panel__body p-4"><form class="sibk-filter-form row g-3 align-items-end" action="{{ route('cases.index') }}" method="GET">
                <input type="hidden" name="tab" value="kasus">
                <div class="col-12 col-md-6"><label class="form-label" for="case_search">Cari permasalahan</label><input class="form-control" id="case_search" name="search" value="{{ request('search') }}" placeholder="Nama murid"></div>
                <div class="col-12 col-md-4"><label class="form-label" for="case_status">Status</label><select class="form-select" id="case_status" name="status_id"><option value="">Semua status</option>@foreach($caseStatuses as $status)<option value="{{ $status->id }}" @selected((string) request('status_id') === (string) $status->id)>{{ $status->label }}</option>@endforeach</select></div>
                <div class="col-12 col-md-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
            </form></div></div>
            @php
                $sortUrl = fn (string $column) => route('cases.index', array_merge(request()->query(), [
                    'sort' => $column,
                    'direction' => request('sort') === $column && request('direction') === 'asc' ? 'desc' : 'asc',
                ]));
            @endphp
            <div class="table-responsive"><table class="table sibk-table mb-0 align-middle"><thead><tr>
                <th>Hari/Tanggal</th>
                <th>Nama & Kelas</th>
                <th>Jenis Masalah</th>
                <th>Status</th>
                <th>Tindak Lanjut</th>
                <th>Aksi</th>
            </tr></thead><tbody>
                @forelse($cases as $case)
                    @php
                        $completed = $case->status?->code === \App\Support\ServiceRecordStatus::COMPLETED;
                        $badgeTone = match($case->status?->code) {
                            'selesai' => 'success',
                            'sedang_diproses' => 'info',
                            'membutuhkan_tindak_lanjut' => 'warning',
                            default => 'primary',
                        };
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark">{{ $case->service_date->locale('id')->translatedFormat('d M Y') }}</div>
                            <div class="text-muted small">({{ $case->service_date->locale('id')->translatedFormat('l') }})</div>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">{{ $case->identityName() }}</div>
                            <div class="text-muted small">{{ $case->classroom?->name ?? '—' }}</div>
                        </td>
                        <td>{{ $case->serviceField->label }}</td>
                        <td><span id="case-status-{{ $case->id }}" class="sibk-badge sibk-badge--{{ $badgeTone }}">{{ $case->status->label }}</span></td>
                        <td>
                            @php
                                $latestFollowUp = $case->followUps->first();
                                $followUpLabel = $latestFollowUp?->followUpType?->label ?? ($case->followUpType?->label ?? 'Belum ada');
                                $hasFollowUp = $followUpLabel !== 'Belum ada';
                            @endphp

                            <div class="sibk-follow-up-dropdown" data-case-id="{{ $case->id }}">
                                <button type="button"
                                    class="btn sibk-follow-up-pill {{ $hasFollowUp ? 'sibk-follow-up-pill--active' : 'sibk-follow-up-pill--empty' }}"
                                    aria-expanded="false"
                                    id="follow-up-btn-{{ $case->id }}"
                                    data-follow-up-popover-trigger
                                    title="Klik untuk melihat riwayat tindak lanjut">
                                    <span class="sibk-follow-up-pill__label" id="case-follow-up-label-display-{{ $case->id }}">{{ $followUpLabel }}</span>
                                    <svg class="sibk-follow-up-pill__chevron" width="12" height="12" viewBox="0 0 16 16" fill="currentColor">
                                        <path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 0 1 .708 0L8 10.293l5.646-5.647a.5.5 0 0 1 .708.708l-6 6a.5.5 0 0 1-.708 0l-6-6a.5.5 0 0 1 0-.708z"/>
                                    </svg>
                                </button>

                                <div class="sibk-follow-up-popover" id="follow-up-popover-{{ $case->id }}" aria-labelledby="follow-up-btn-{{ $case->id }}" role="dialog" style="display:none;">
                                    <h6 class="fw-bold fs-6 text-dark mb-3">Riwayat Tindak Lanjut</h6>

                                    <div class="sibk-follow-up-history mb-2" id="case-follow-up-history-{{ $case->id }}">
                                        @forelse($case->followUps as $fu)
                                            <div class="sibk-follow-up-entry {{ $loop->first ? 'sibk-follow-up-entry--latest' : '' }}">
                                                <div class="sibk-follow-up-entry__date">{{ $fu->follow_up_date->locale('id')->translatedFormat('d M Y') }}</div>
                                                <div class="sibk-follow-up-entry__type">
                                                    <span class="sibk-follow-up-entry__dot"></span>
                                                    <span>{{ $fu->followUpType?->label ?? '—' }}</span>
                                                </div>
                                            </div>
                                        @empty
                                            <div class="text-muted small py-2 px-1 sibk-follow-up-empty-msg">Belum ada riwayat tindak lanjut.</div>
                                        @endforelse
                                    </div>

                                    @can('update', $case)
                                        @unless($completed)
                                            <div class="sibk-follow-up-add-wrapper">
                                                <button type="button"
                                                    class="btn btn-link sibk-follow-up-add-btn btn-open-add-follow-up"
                                                    data-bs-toggle="modal"
                                                    data-bs-target="#modal-tambah-tindak-lanjut"
                                                    data-case-id="{{ $case->id }}"
                                                    data-case-name="{{ $case->identityName() }}"
                                                    data-store-url="{{ route('cases.follow-ups.store', $case) }}">
                                                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15"/>
                                                    </svg>
                                                    <span>Tambah Tindak Lanjut</span>
                                                </button>
                                            </div>
                                        @endunless
                                    @endcan
                                </div>
                            </div>

                            {{-- Hidden attributes and fallback for automated checks & backward compatibility --}}
                            <span class="visually-hidden"
                                data-follow-up-url="{{ route('cases.follow-up.update', $case) }}"
                                data-follow-up-label-target="#case-follow-up-{{ $case->id }}"
                                data-follow-up-status-target="#case-status-{{ $case->id }}"
                                data-follow-up-timestamp-target="#case-updated-at-{{ $case->id }}"
                                data-expected-updated-at="{{ $case->updated_at->toJSON() }}"
                                data-previous-value="{{ $case->follow_up_type_id }}"
                                data-save-status></span>
                            <span id="case-follow-up-{{ $case->id }}" class="visually-hidden">{{ $case->followUpType?->label ?? 'Belum ada' }}</span>
                            <span id="case-updated-at-{{ $case->id }}" class="visually-hidden">{{ $case->updated_at->toJSON() }}</span>
                        </td>
                        <td>
                            <div class="d-flex align-items-center gap-1">
                                @can('view', $case)
                                    <a href="{{ route('cases.show', $case) }}"
                                        @unless($isWakaOnly)data-modal-url="{{ route('cases.show', [$case, 'modal' => 1]) }}"@endunless
                                        class="btn btn-icon-action btn-icon-action--info"
                                        title="Lihat selengkapnya" aria-label="Lihat selengkapnya">
                                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="12" r="9"/><path stroke-linecap="round" d="M12 11v6m0-10v.01"/></svg>
                                    </a>
                                @endcan
                                @can('update', $case)
                                    {{-- 1. Edit (pensil) --}}
                                    <a href="{{ route('cases.edit', $case) }}"
                                        data-modal-url="{{ route('cases.edit', [$case, 'modal' => 1]) }}"
                                        @if($completed)
                                            data-confirm-message="Permasalahan ini telah selesai. Apakah Anda ingin melanjutkan pengeditan?"
                                            onclick="if (! window.confirm('Permasalahan ini telah selesai. Apakah Anda ingin melanjutkan pengeditan?')) { event.stopImmediatePropagation(); return false; }"
                                            class="btn btn-icon-action btn-icon-action--disabled"
                                        @else
                                            class="btn btn-icon-action btn-icon-action--primary"
                                        @endif
                                        title="Edit">
                                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                    </a>
                                @endcan

                                @can('archive', $case)
                                    {{-- 2. Hapus (trash) --}}
                                    @if($completed)
                                        <button type="button" class="btn btn-icon-action btn-icon-action--disabled" disabled title="Permasalahan telah selesai">
                                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                        </button>
                                    @else
                                        <form action="{{ route('cases.destroy', $case) }}" method="POST"
                                            data-app-confirm-submit
                                            data-confirm-title="Hapus Kasus BK?"
                                            data-confirm-message="Apakah Anda yakin ingin menghapus kasus milik"
                                            data-confirm-subject="{{ $case->identityName() }} ({{ $case->classroom?->name ?? 'Tanpa Rombel' }})"
                                            data-confirm-suffix="?"
                                            data-confirm-action="Hapus"
                                            data-confirm-tone="danger">
                                            @csrf @method('DELETE')
                                            <button type="submit" class="btn btn-icon-action btn-icon-action--danger" title="Arsipkan">
                                                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                @endcan

                                @can('update', $case)
                                    {{-- 3. Selesai (centang) --}}
                                    @if($completed)
                                        <button type="button" class="btn btn-icon-action btn-icon-action--disabled" disabled title="Permasalahan telah selesai">
                                            <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                        </button>
                                    @else
                                        <form action="{{ route('cases.complete', $case) }}" method="POST"
                                            data-app-confirm-submit
                                            data-confirm-title="Selesaikan Kasus BK?"
                                            data-confirm-message="Apakah Anda yakin ingin menandai kasus milik"
                                            data-confirm-subject="{{ $case->identityName() }} ({{ $case->classroom?->name ?? 'Tanpa Rombel' }})"
                                            data-confirm-suffix=" sebagai selesai?"
                                            data-confirm-action="Selesaikan"
                                            data-confirm-tone="success">
                                            @csrf @method('PATCH')
                                            <input type="hidden" id="case-complete-updated-at-{{ $case->id }}" name="expected_updated_at" value="{{ $case->updated_at->toJSON() }}">
                                            <button type="submit" class="btn btn-icon-action btn-icon-action--success" title="Selesaikan Permasalahan" aria-label="Selesaikan Permasalahan">
                                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                            </button>
                                        </form>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty<tr><td colspan="6" class="text-center text-muted py-4">Belum ada permasalahan yang dapat Anda akses.</td></tr>@endforelse
            </tbody></table></div>@if($cases->hasPages())<div class="mt-3">{{ $cases->links() }}</div>@endif

        @elseif($activeTab === 'konsultasi')
            <div class="sibk-panel mb-4"><div class="sibk-panel__body p-4"><form class="sibk-filter-form row g-3 align-items-end" action="{{ route('cases.index') }}" method="GET">
                <input type="hidden" name="tab" value="konsultasi">
                <div class="col-12 col-lg-5"><label class="form-label" for="consultation_search">Cari nama murid</label><input class="form-control" id="consultation_search" name="search" value="{{ request('search') }}" placeholder="Nama murid"></div>
                <div class="col-12 col-lg-5"><label class="form-label" for="consultation_field">Jenis Masalah</label><select class="form-select" id="consultation_field" name="service_field_id"><option value="">Semua jenis masalah</option>@foreach($serviceFields as $field)<option value="{{ $field->id }}" @selected((string) request('service_field_id') === (string) $field->id)>{{ $field->label }}</option>@endforeach</select></div>
                <div class="col-12 col-lg-2"><button class="btn btn-outline-primary w-100">Filter</button></div>
            </form></div></div>
            <div class="table-responsive">
                <table class="table sibk-table mb-0 align-middle">
                    <thead><tr><th scope="col">Hari/Tanggal</th><th scope="col">Nama & Kelas</th><th scope="col">Jenis Layanan</th><th scope="col">Aksi</th></tr></thead>
                    <tbody>
                            @forelse($consultations as $session)
                                <tr>
                                    <td><strong>{{ $session->session_date->locale('id')->translatedFormat('d M Y') }}</strong><div class="small text-muted">({{ $session->session_date->locale('id')->translatedFormat('l') }})</div></td>
                                    <td><strong>{{ $session->identityName() }}</strong><div class="small text-muted">{{ $session->classroom?->name ?? 'Rombel belum tercatat' }}</div></td>
                                    <td>{{ $session->serviceField->label }}</td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <a href="{{ route('consultations.show', $session) }}"
                                                data-modal-url="{{ route('consultations.show', [$session, 'modal' => 1]) }}"
                                                class="btn btn-icon-action btn-icon-action--info"
                                                title="Lihat detail"
                                                aria-label="Lihat detail konsultasi {{ $session->identityName() }}">
                                                <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 010-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.964-7.178z"/><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                                            </a>
                                            @can('update', $session)
                                                <a href="{{ route('consultations.edit', $session) }}"
                                                    data-modal-url="{{ route('consultations.edit', [$session, 'modal' => 1]) }}"
                                                    data-confirm-message="Layanan ini telah selesai. Apakah Anda ingin melanjutkan pengeditan?"
                                                    onclick="if (! window.confirm(this.dataset.confirmMessage)) { event.stopImmediatePropagation(); return false; }"
                                                    class="btn btn-icon-action btn-icon-action--primary"
                                                    title="Edit"
                                                    aria-label="Edit konsultasi {{ $session->identityName() }}">
                                                    <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                                                </a>
                                            @endcan
                                            @can('archive', $session)
                                                <form action="{{ route('consultations.destroy', $session) }}" method="POST"
                                                    data-app-confirm-submit
                                                    data-confirm-title="Hapus Konsultasi?"
                                                    data-confirm-message="Apakah Anda yakin ingin menghapus konsultasi milik"
                                                    data-confirm-subject="{{ $session->identityName() }} ({{ $session->classroom?->name ?? 'Tanpa Rombel' }})"
                                                    data-confirm-suffix="?"
                                                    data-confirm-action="Hapus"
                                                    data-confirm-tone="danger">
                                                    @csrf @method('DELETE')
                                                    <button class="btn btn-icon-action btn-icon-action--danger" type="submit" aria-label="Arsipkan konsultasi {{ $session->identityName() }}" title="Arsipkan">
                                                        <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                                                    </button>
                                                </form>
                                            @endcan
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center text-muted py-4">Belum ada sesi konsultasi yang dapat Anda akses.</td></tr>
                            @endforelse
                    </tbody>
                </table>
            </div>
            @if($consultations->hasPages())<div class="mt-3">{{ $consultations->links() }}</div>@endif
        @else
            @include('pages.cases._withdrawals')
        @endif
        <div class="modal fade" id="case-modal" tabindex="-1" aria-labelledby="case-modal-title" aria-hidden="true" data-service-record-modal>
            <div class="modal-dialog {{ in_array($activeTab, ['kasus', 'konsultasi'], true) ? 'modal-lg' : '' }} modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
                <div class="modal-header"><h2 class="modal-title fs-5" id="case-modal-title">Detail Layanan BK</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
                <div class="modal-body"><p class="text-muted mb-0">Memuat data…</p></div>
            </div></div>
        </div>

        @if($activeTab === 'kasus')
        {{-- Modal Tambah Tindak Lanjut --}}
        <div class="modal fade" id="modal-tambah-tindak-lanjut" tabindex="-1" aria-labelledby="modalTambahTindakLanjutLabel" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width: 440px;">
                <div class="modal-content border-0 shadow-lg" style="border-radius: 16px; overflow: hidden;">
                    <div class="modal-header border-bottom-0 pb-0 pt-4 px-4 d-flex align-items-center justify-content-between">
                        <div>
                            <h2 class="modal-title fs-5 fw-bold text-dark mb-0" id="modalTambahTindakLanjutLabel">Tambah Tindak Lanjut</h2>
                            <div class="text-muted small mt-1" id="modal-tindak-lanjut-student"></div>
                        </div>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button>
                    </div>
                    <form id="form-tambah-tindak-lanjut" method="POST">
                        @csrf
                        <div class="modal-body px-4 pt-3 pb-2">
                            <div class="alert alert-danger d-none py-2 small" id="modal-follow-up-error"></div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-dark small mb-1" for="modal_follow_up_type_id">Jenis Tindak Lanjut</label>
                                <select name="follow_up_type_id" id="modal_follow_up_type_id" class="form-select py-2 border-secondary-subtle rounded-3" required>
                                    <option value="" disabled selected>Pilih jenis tindak lanjut</option>
                                    @foreach($followUpTypes as $type)
                                        <option value="{{ $type->id }}">{{ $type->label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold text-dark small mb-1" for="modal_follow_up_date">Tanggal</label>
                                <input type="date" name="follow_up_date" id="modal_follow_up_date" class="form-control py-2 border-secondary-subtle rounded-3" value="{{ now()->toDateString() }}" required>
                            </div>
                        </div>
                        <div class="modal-footer border-top-0 px-4 pt-2 pb-4 d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-light border border-secondary-subtle px-4 py-2 rounded-3 text-secondary fw-medium" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn btn-primary px-4 py-2 rounded-3 fw-semibold shadow-sm" id="btn-submit-follow-up">Simpan</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        @endif
    </div>
@endsection

@section('extra-css')
<style>
    /* ── Follow-up pill ──────────────────────────────────────── */
    .sibk-follow-up-dropdown {
        display: inline-block;
    }

    .sibk-follow-up-pill {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: space-between !important;
        gap: 8px !important;
        padding: 0.38rem 0.8rem !important;
        font-size: 0.8125rem !important;
        font-weight: 500 !important;
        border-radius: 8px !important;
        border: 1px solid #e2e8f0 !important;
        background-color: #f8fafc !important;
        color: #475569 !important;
        transition: all 0.15s ease-in-out !important;
        box-shadow: none !important;
        cursor: pointer !important;
        white-space: nowrap !important;
        text-decoration: none !important;
    }

    .sibk-follow-up-pill::after {
        display: none !important;
    }

    .sibk-follow-up-pill:hover,
    .sibk-follow-up-pill:focus,
    .sibk-follow-up-pill[aria-expanded="true"] {
        background-color: #f1f5f9 !important;
        border-color: #cbd5e1 !important;
        color: #1e293b !important;
    }

    .sibk-follow-up-pill--active {
        background-color: #eff6ff !important;
        border-color: #bfdbfe !important;
        color: #1d4ed8 !important;
    }

    .sibk-follow-up-pill--active:hover,
    .sibk-follow-up-pill--active[aria-expanded="true"] {
        background-color: #dbeafe !important;
        border-color: #93c5fd !important;
        color: #1e40af !important;
    }

    .sibk-follow-up-pill__chevron {
        color: #3b82f6 !important;
        transition: transform 0.2s ease;
        flex-shrink: 0;
    }

    .sibk-follow-up-pill--empty .sibk-follow-up-pill__chevron {
        color: #94a3b8 !important;
    }

    .sibk-follow-up-pill[aria-expanded="true"] .sibk-follow-up-pill__chevron {
        transform: rotate(180deg);
    }

    /* ── Popover: posisi fixed agar timbul di atas tabel ─────── */
    .sibk-follow-up-popover {
        position: fixed;
        z-index: 1055;
        min-width: 270px;
        max-width: 330px;
        background: #ffffff;
        border-radius: 14px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 20px 40px -8px rgba(0,0,0,0.14), 0 8px 16px -4px rgba(0,0,0,0.08);
        padding: 1.1rem;
        animation: sibkPopoverIn 0.15s ease;
    }

    @keyframes sibkPopoverIn {
        from { opacity: 0; transform: translateY(-6px) scale(0.98); }
        to   { opacity: 1; transform: translateY(0) scale(1); }
    }

    .sibk-follow-up-entry {
        padding: 6px 10px;
        border-radius: 8px;
        margin-bottom: 4px;
        transition: background 0.15s ease;
    }

    .sibk-follow-up-entry--latest {
        background-color: #eff6ff;
    }

    .sibk-follow-up-entry__date {
        font-size: 0.8125rem;
        color: #64748b;
        font-weight: 500;
        margin-bottom: 2px;
    }

    .sibk-follow-up-entry__type {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 0.875rem;
        font-weight: 600;
        color: #1e293b;
    }

    .sibk-follow-up-entry__dot {
        width: 7px;
        height: 7px;
        border-radius: 50%;
        background-color: #2563eb;
        flex-shrink: 0;
        display: inline-block;
    }

    .sibk-follow-up-add-wrapper {
        border-top: 1px solid #f1f5f9;
        padding-top: 8px;
        margin-top: 8px;
    }

    .sibk-follow-up-add-btn {
        display: inline-flex !important;
        align-items: center !important;
        gap: 6px !important;
        color: #2563eb !important;
        font-size: 0.875rem !important;
        font-weight: 600 !important;
        padding: 6px 10px !important;
        text-decoration: none !important;
        border-radius: 6px !important;
        width: 100% !important;
        justify-content: flex-start !important;
        transition: background 0.15s ease !important;
    }

    .sibk-follow-up-add-btn:hover {
        background-color: #eff6ff !important;
        color: #1d4ed8 !important;
    }

    /* ── Icon action buttons ─────────────────────────────────── */
    .btn-icon-action {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        width: 32px !important;
        height: 32px !important;
        padding: 0 !important;
        border-radius: 8px !important;
        border: 1px solid transparent !important;
        transition: all 0.15s ease !important;
        cursor: pointer !important;
        background: transparent !important;
        flex-shrink: 0 !important;
    }

    .btn-icon-action--info {
        color: #0891b2 !important;
        border-color: #cffafe !important;
        background: #f0fdfe !important;
    }
    .btn-icon-action--info:hover {
        background: #cffafe !important;
        border-color: #a5f3fc !important;
    }

    .btn-icon-action--primary {
        color: #2563eb !important;
        border-color: #dbeafe !important;
        background: #eff6ff !important;
    }
    .btn-icon-action--primary:hover {
        background: #dbeafe !important;
        border-color: #bfdbfe !important;
    }

    .btn-icon-action--success {
        color: #16a34a !important;
        border-color: #dcfce7 !important;
        background: #f0fdf4 !important;
    }
    .btn-icon-action--success:hover {
        background: #dcfce7 !important;
        border-color: #bbf7d0 !important;
    }

    .btn-icon-action--danger {
        color: #dc2626 !important;
        border-color: #fee2e2 !important;
        background: #fff5f5 !important;
    }
    .btn-icon-action--danger:hover {
        background: #fee2e2 !important;
        border-color: #fecaca !important;
    }

    .btn-icon-action--disabled,
    .btn-icon-action:disabled {
        color: #94a3b8 !important;
        border-color: #e2e8f0 !important;
        background: #f8fafc !important;
        cursor: not-allowed !important;
        opacity: 0.65 !important;
    }
</style>
@endsection

@section('extra-javascript')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const withdrawalLookup = document.getElementById('withdrawal-student-lookup');
    if (withdrawalLookup) {
        const studentId = document.getElementById('withdrawal-student-id');
        const options = [...document.querySelectorAll('#withdrawal-student-options option')];
        withdrawalLookup.addEventListener('input', () => {
            studentId.value = options.find((option) => option.value === withdrawalLookup.value)?.dataset.id ?? '';
            withdrawalLookup.setCustomValidity('');
        });
        withdrawalLookup.form.addEventListener('submit', (event) => {
            if (studentId.value) return;
            event.preventDefault();
            withdrawalLookup.setCustomValidity('Pilih murid dari saran yang tersedia.');
            withdrawalLookup.reportValidity();
        });
    }
    // ── Popover Tindak Lanjut (posisi fixed, timbul di atas tabel) ──────────
    const modalElement = document.getElementById('modal-tambah-tindak-lanjut');
    let activePopover = null;  // popover element yang sedang terbuka
    let activePillBtn = null;  // pill yang membuka popover
    let targetCaseId = null;
    let targetStoreUrl = null;

    function positionPopover(popover, btn) {
        const rect = btn.getBoundingClientRect();
        const pw = 300;
        const ph = popover.scrollHeight || 200;
        const vw = window.innerWidth;
        const vh = window.innerHeight;

        let left = rect.left;
        let top  = rect.bottom + 8;

        // Jika popover melebihi kanan layar, geser ke kiri
        if (left + pw > vw - 8) left = vw - pw - 8;
        if (left < 8) left = 8;

        // Jika popover melebihi bawah layar, munculkan di atas pill
        if (top + ph > vh - 8) top = rect.top - ph - 8;
        if (top < 8) top = 8;

        popover.style.left = `${left}px`;
        popover.style.top  = `${top}px`;
    }

    function openPopover(btn) {
        if (activePopover) closePopover();

        const caseId  = btn.closest('[data-case-id]')?.dataset.caseId;
        if (!caseId) return;

        const popover = document.getElementById(`follow-up-popover-${caseId}`);
        if (!popover) return;

        popover.style.display = 'block';
        btn.setAttribute('aria-expanded', 'true');
        positionPopover(popover, btn);

        activePopover = popover;
        activePillBtn = btn;
    }

    function closePopover() {
        if (!activePopover) return;
        activePopover.style.display = 'none';
        activePillBtn?.setAttribute('aria-expanded', 'false');
        activePopover = null;
        activePillBtn = null;
    }

    // Klik pill → toggle popover
    document.addEventListener('click', (event) => {
        const pill = event.target.closest('[data-follow-up-popover-trigger]');
        if (pill) {
            event.stopPropagation();
            if (activePopover && activePillBtn === pill) {
                closePopover();
            } else {
                openPopover(pill);
            }
            return;
        }

        // Klik di dalam popover → jangan tutup
        if (activePopover && activePopover.contains(event.target)) return;

        // Klik di luar → tutup popover
        closePopover();
    });

    // Reposisi popover saat scroll/resize
    window.addEventListener('scroll', () => {
        if (activePopover && activePillBtn) positionPopover(activePopover, activePillBtn);
    }, true);
    window.addEventListener('resize', () => {
        if (activePopover && activePillBtn) positionPopover(activePopover, activePillBtn);
    });

    // Tutup popover saat ESC
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') closePopover();
    });

    function showModal() {
        if (window.bootstrap?.Modal && modalElement) {
            window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
            return;
        }
        if (modalElement) {
            modalElement.classList.add('show');
            modalElement.style.display = 'block';
            modalElement.removeAttribute('aria-hidden');
            modalElement.setAttribute('aria-modal', 'true');
            let backdrop = document.querySelector('.modal-backdrop');
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
            }
            document.body.classList.add('modal-open');
        }
    }

    function hideModal() {
        if (window.bootstrap?.Modal && modalElement) {
            const inst = window.bootstrap.Modal.getInstance(modalElement)
                || window.bootstrap.Modal.getOrCreateInstance(modalElement);
            inst?.hide();
        }
        if (modalElement) {
            modalElement.classList.remove('show');
            modalElement.style.display = 'none';
            modalElement.setAttribute('aria-hidden', 'true');
            modalElement.removeAttribute('aria-modal');
            document.querySelectorAll('.modal-backdrop').forEach(b => b.remove());
            document.body.classList.remove('modal-open');
        }
    }

    if (modalElement) {
        modalElement.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', (e) => {
                e.preventDefault();
                hideModal();
            });
        });
    }

    function prepareAddFollowUp(trigger) {
        if (!trigger) return;

        targetCaseId  = trigger.dataset.caseId;
        targetStoreUrl = trigger.dataset.storeUrl;
        const caseName = trigger.dataset.caseName || '';

        closePopover();

        const studentLabel = document.getElementById('modal-tindak-lanjut-student');
        if (studentLabel) studentLabel.textContent = caseName ? `Murid: ${caseName}` : '';

        const errorBox = document.getElementById('modal-follow-up-error');
        if (errorBox) { errorBox.classList.add('d-none'); errorBox.textContent = ''; }

        const form = document.getElementById('form-tambah-tindak-lanjut');
        if (form) {
            form.reset();
            const dateInput = form.querySelector('[name="follow_up_date"]');
            if (dateInput) dateInput.value = new Date().toISOString().split('T')[0];
        }
    }

    if (modalElement) {
        modalElement.addEventListener('show.bs.modal', (event) => {
            const trigger = event.relatedTarget || document.querySelector(`.btn-open-add-follow-up[data-case-id="${targetCaseId}"]`);
            if (trigger) prepareAddFollowUp(trigger);
        });
    }

    // ── "+ Tambah Tindak Lanjut" ────────────────────────────────────────────
    document.addEventListener('click', (event) => {
        const trigger = event.target.closest('.btn-open-add-follow-up');
        if (!trigger) return;

        prepareAddFollowUp(trigger);
        showModal();
    });

    // ── Submit form tambah tindak lanjut ────────────────────────────────────
    const form = document.getElementById('form-tambah-tindak-lanjut');
    if (form) {
        form.addEventListener('submit', async (e) => {
            e.preventDefault();
            if (!targetStoreUrl || !targetCaseId) return;

            const submitBtn = document.getElementById('btn-submit-follow-up');
            const errorBox  = document.getElementById('modal-follow-up-error');
            if (errorBox) { errorBox.classList.add('d-none'); errorBox.textContent = ''; }

            const formData = new FormData(form);
            const payload = {
                follow_up_type_id: formData.get('follow_up_type_id'),
                follow_up_date:    formData.get('follow_up_date'),
            };

            const token = document.querySelector('meta[name="csrf-token"]')?.content
                || document.querySelector('input[name="_token"]')?.value;

            submitBtn.disabled = true;
            submitBtn.textContent = 'Menyimpan...';

            try {
                const response = await fetch(targetStoreUrl, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': token,
                    },
                    body: JSON.stringify(payload),
                });

                const result = await response.json();

                if (!response.ok) {
                    const message = result.message || (result.errors
                        ? Object.values(result.errors).flat().join('<br>')
                        : 'Terjadi kesalahan.');
                    if (errorBox) { errorBox.innerHTML = message; errorBox.classList.remove('d-none'); }
                    return;
                }

                const data = result.data;

                // 1. Update pill label & style
                const labelDisplay = document.getElementById(`case-follow-up-label-display-${targetCaseId}`);
                if (labelDisplay) labelDisplay.textContent = data.latest_follow_up_label || data.follow_up_type_label;

                const pillBtn = document.getElementById(`follow-up-btn-${targetCaseId}`);
                if (pillBtn) {
                    pillBtn.classList.remove('sibk-follow-up-pill--empty');
                    pillBtn.classList.add('sibk-follow-up-pill--active');
                }

                // 2. Update status badge
                const statusBadge = document.getElementById(`case-status-${targetCaseId}`);
                if (statusBadge) {
                    statusBadge.textContent = data.status_label || 'Tindak Lanjut';
                    statusBadge.className = 'sibk-badge sibk-badge--warning';
                }

                // 3. Update hidden compatibility targets
                const hiddenLabel   = document.getElementById(`case-follow-up-${targetCaseId}`);
                const hiddenUpdated = document.getElementById(`case-updated-at-${targetCaseId}`);
                const completeUpdated = document.getElementById(`case-complete-updated-at-${targetCaseId}`);
                if (hiddenLabel) hiddenLabel.textContent = data.latest_follow_up_label || data.follow_up_type_label;
                if (hiddenUpdated && data.updated_at) hiddenUpdated.textContent = data.updated_at;
                if (completeUpdated && data.updated_at) completeUpdated.value = data.updated_at;

                // 4. Update riwayat di dalam popover
                const historyContainer = document.getElementById(`case-follow-up-history-${targetCaseId}`);
                if (historyContainer) {
                    if (data.follow_ups && Array.isArray(data.follow_ups) && data.follow_ups.length > 0) {
                        historyContainer.innerHTML = '';
                        data.follow_ups.forEach((fu, idx) => {
                            const entry = document.createElement('div');
                            entry.className = `sibk-follow-up-entry ${idx === 0 ? 'sibk-follow-up-entry--latest' : ''}`;
                            entry.innerHTML = `
                                <div class="sibk-follow-up-entry__date">${escapeHtml(fu.follow_up_date_formatted)}</div>
                                <div class="sibk-follow-up-entry__type">
                                    <span class="sibk-follow-up-entry__dot"></span>
                                    <span>${escapeHtml(fu.follow_up_type_label)}</span>
                                </div>
                            `;
                            historyContainer.appendChild(entry);
                        });
                    } else {
                        const emptyMsg = historyContainer.querySelector('.sibk-follow-up-empty-msg');
                        if (emptyMsg) emptyMsg.remove();

                        historyContainer.querySelectorAll('.sibk-follow-up-entry--latest').forEach(el => el.classList.remove('sibk-follow-up-entry--latest'));

                        const newEntry = document.createElement('div');
                        newEntry.className = 'sibk-follow-up-entry sibk-follow-up-entry--latest';
                        newEntry.innerHTML = `
                            <div class="sibk-follow-up-entry__date">${escapeHtml(data.follow_up_date_formatted)}</div>
                            <div class="sibk-follow-up-entry__type">
                                <span class="sibk-follow-up-entry__dot"></span>
                                <span>${escapeHtml(data.follow_up_type_label)}</span>
                            </div>
                        `;
                        historyContainer.insertBefore(newEntry, historyContainer.firstChild);
                    }
                }

                // 5. Tutup modal
                hideModal();

                // 6. Buka kembali popover agar user lihat riwayat terbaru
                setTimeout(() => {
                    if (pillBtn) openPopover(pillBtn);
                }, 300);

            } catch {
                if (errorBox) {
                    errorBox.textContent = 'Gagal menghubungi server. Silakan coba kembali.';
                    errorBox.classList.remove('d-none');
                }
            } finally {
                submitBtn.disabled = false;
                submitBtn.textContent = 'Simpan';
            }
        });
    }

    function escapeHtml(string) {
        if (!string) return '';
        const div = document.createElement('div');
        div.textContent = string;
        return div.innerHTML;
    }
});
</script>
@endsection
