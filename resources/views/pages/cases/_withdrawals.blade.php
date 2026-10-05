@php
    $progressLabels = \App\Models\WithdrawalProgress::labels();
@endphp

<div class="sibk-panel mb-4"><div class="sibk-panel__body p-4">
    <form class="sibk-filter-form row g-3 align-items-end" action="{{ route('cases.index') }}" method="GET">
        <input type="hidden" name="tab" value="pengunduran-diri">
        <div class="col-12 col-md-5"><label class="form-label" for="withdrawal-search">Cari Murid</label><input class="form-control" id="withdrawal-search" name="search" value="{{ request('search') }}" placeholder="Nama murid"></div>
        <div class="col-12 col-md-5"><label class="form-label" for="withdrawal-progress-filter">Progres</label><select class="form-select" id="withdrawal-progress-filter" name="progress"><option value="">Semua progres</option>@foreach($progressLabels as $value => $label)<option value="{{ $value }}" @selected(request('progress') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-12 col-md-2"><button class="btn btn-outline-primary w-100" type="submit">Filter</button></div>
    </form>
</div></div>

<div class="table-responsive"><table class="table sibk-table mb-0 align-middle" style="min-width: 900px;"><thead><tr>
    <th scope="col" style="width: 16%; min-width: 145px;">Hari/Tanggal</th>
    <th scope="col" style="width: 28%; min-width: 220px;">Nama/Kelas</th>
    <th scope="col" style="width: 19%; min-width: 150px;">Guru</th>
    <th scope="col" style="width: 25%; min-width: 225px;">Progres</th>
    <th scope="col" style="width: 12%; min-width: 130px;">Aksi</th>
</tr></thead><tbody>
@forelse($withdrawals as $withdrawal)
    @php
        $history = $withdrawal->followUps->map(fn ($followUp): array => [
            'id' => $followUp->id,
            'progress' => $followUp->progress,
            'progressLabel' => $followUp->progressLabel(),
            'followUpDate' => $followUp->follow_up_date->toDateString(),
            'followUpDateFormatted' => $followUp->follow_up_date->locale('id')->translatedFormat('d M Y'),
            'notes' => $followUp->notes,
            'creatorName' => $followUp->creator?->name,
        ])->values();
    @endphp
    <tr>
        <td><div class="fw-semibold text-dark">{{ $withdrawal->recorded_on->locale('id')->translatedFormat('l') }}</div><div class="text-muted small text-nowrap">{{ $withdrawal->recorded_on->locale('id')->translatedFormat('d M Y') }}</div></td>
        <td><div class="fw-semibold text-dark">{{ $withdrawal->student?->name ?? 'Murid tidak tersedia' }}</div><div class="text-muted small">{{ $withdrawal->classroom?->name ?? 'Kelas belum tercatat' }}</div></td>
        <td>{{ $withdrawal->teacher?->name ?? 'Guru tidak tersedia' }}</td>
        <td style="min-width: 240px;">
            <div class="sibk-follow-up-dropdown w-100" data-withdrawal-id="{{ $withdrawal->id }}" style="max-width: 240px;">
                <button type="button" class="btn badge rounded-pill sibk-follow-up-pill sibk-follow-up-pill--withdrawal text-start" data-withdrawal-progress="{{ $history->first()['progress'] ?? $withdrawal->progress }}" id="withdrawal-follow-up-btn-{{ $withdrawal->id }}" data-withdrawal-popover-trigger aria-expanded="false" title="Lihat riwayat progres penanganan" style="white-space: normal !important; line-height: 1.35; max-width: 240px; width: 100%;">
                    <span data-withdrawal-follow-up-label>{{ $history->first()['progressLabel'] ?? $withdrawal->progressLabel() }}</span>
                    <svg class="sibk-follow-up-pill__chevron flex-shrink-0 ms-2" width="12" height="12" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M1.646 4.646a.5.5 0 01.708 0L8 10.293l5.646-5.647a.5.5 0 01.708.708l-6 6a.5.5 0 01-.708 0l-6-6a.5.5 0 010-.708z"/></svg>
                </button>
                <div class="sibk-follow-up-popover" data-withdrawal-popover aria-labelledby="withdrawal-follow-up-btn-{{ $withdrawal->id }}" role="dialog" style="display:none">
                    <h3 class="fw-bold fs-6 text-dark mb-3">Riwayat Progres Penanganan</h3>
                    <div class="sibk-follow-up-history mb-2" data-withdrawal-history>
                        @foreach($history as $item)
                            <div class="sibk-follow-up-entry {{ $loop->first ? 'sibk-follow-up-entry--latest' : '' }}"><div class="sibk-follow-up-entry__date">{{ $item['followUpDateFormatted'] }}</div><div class="sibk-follow-up-entry__type"><span class="sibk-follow-up-entry__dot"></span><span class="badge rounded-pill withdrawal-progress-badge" data-withdrawal-progress="{{ $item['progress'] }}">{{ $item['progressLabel'] }}</span></div>@if($item['notes'])<div class="small text-muted mt-1">{{ $item['notes'] }}</div>@endif</div>
                        @endforeach
                    </div>
                    @can('update', $withdrawal)
                        <div class="sibk-follow-up-add-wrapper"><button type="button" class="btn btn-link sibk-follow-up-add-btn" data-withdrawal-follow-up-open data-withdrawal-id="{{ $withdrawal->id }}" data-student-name="{{ $withdrawal->student?->name }}" data-store-url="{{ route('withdrawals.follow-ups.store', $withdrawal) }}" data-min-date="{{ $withdrawal->recorded_on->toDateString() }}" data-current-progress="{{ $withdrawal->progress }}"><svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2.5" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" d="M12 4.5v15m7.5-7.5h-15"/></svg><span>Tambah Progres Penanganan</span></button></div>
                    @endcan
                </div>
            </div>
        </td>
        <td>
            <div class="d-flex align-items-center gap-1">
                    <button type="button" class="btn btn-icon-action btn-icon-action--info" data-withdrawal-note-toggle data-student-name="{{ $withdrawal->student?->name ?? 'Murid tidak tersedia' }}" aria-controls="withdrawal-note-{{ $withdrawal->id }}" aria-expanded="false" aria-label="Tampilkan catatan pengunduran diri {{ $withdrawal->student?->name }}" title="Tampilkan catatan">
                        <svg width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7"/><path stroke-linecap="round" d="m16 16 5 5"/></svg>
                    </button>
                    @can('update', $withdrawal)
                        <a href="{{ route('withdrawals.edit', $withdrawal) }}" data-modal-url="{{ route('withdrawals.edit', [$withdrawal, 'modal' => 1]) }}" class="btn btn-icon-action btn-icon-action--info" aria-label="Edit pengunduran diri {{ $withdrawal->student?->name }}" title="Edit">
                            <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M16.862 4.487l1.687-1.688a1.875 1.875 0 112.652 2.652L10.582 16.07a4.5 4.5 0 01-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 011.13-1.897l8.932-8.931zm0 0L19.5 7.125"/></svg>
                        </a>
                    @endcan
                    @can('delete', $withdrawal)
                        <form action="{{ route('withdrawals.destroy', $withdrawal) }}" method="POST"
                            data-app-confirm-submit
                            data-confirm-title="Hapus penanganan pengunduran diri?"
                            data-confirm-message="Apakah Anda yakin ingin menghapus penanganan pengunduran diri milik"
                            data-confirm-subject="{{ $withdrawal->student?->name ?? 'Murid tidak tersedia' }}"
                            data-confirm-suffix="? Seluruh riwayat progres penanganan akan ikut terhapus."
                            data-confirm-action="Hapus"
                            data-confirm-tone="danger">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-icon-action btn-icon-action--danger" type="submit" aria-label="Hapus penanganan pengunduran diri {{ $withdrawal->student?->name }}" title="Hapus">
                                <svg width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M14.74 9l-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0"/></svg>
                            </button>
                        </form>
                    @endcan
            </div>
        </td>
    </tr>
    <tr class="sibk-report-detail-row d-none" id="withdrawal-note-{{ $withdrawal->id }}">
        <td colspan="5"><div class="sibk-report-detail-panel"><strong class="d-block mb-1">Catatan</strong><p class="mb-0 text-break sibk-case-detail__text">{{ $withdrawal->note ?: 'Belum ada catatan.' }}</p></div></td>
    </tr>
@empty
    <tr><td colspan="5" class="text-center text-muted py-4">Belum ada progres pengunduran diri yang dapat Anda akses.</td></tr>
@endforelse
</tbody></table></div>
@if($withdrawals->hasPages())<div class="mt-3">{{ $withdrawals->links() }}</div>@endif

<div class="modal fade" id="withdrawal-follow-up-modal" tabindex="-1" aria-labelledby="withdrawal-follow-up-title" aria-hidden="true" data-withdrawal-follow-up-modal><div class="modal-dialog modal-dialog-centered"><form class="modal-content" method="POST" data-withdrawal-follow-up-form>
    @csrf
    <div class="modal-header"><div><h2 class="modal-title fs-5 fw-bold" id="withdrawal-follow-up-title">Tambah Progres Penanganan</h2><div class="small text-muted mt-1" data-follow-up-student></div></div><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
    <div class="modal-body"><div class="alert alert-danger d-none" role="alert" data-follow-up-error></div><div class="mb-3"><label class="form-label fw-semibold" for="withdrawal-follow-up-progress">Progres</label><select class="form-select" id="withdrawal-follow-up-progress" name="progress" required>@foreach($progressLabels as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select></div><div class="mb-3"><label class="form-label fw-semibold" for="withdrawal-follow-up-date">Tanggal</label><input class="form-control" id="withdrawal-follow-up-date" name="follow_up_date" type="date" value="{{ now()->toDateString() }}" max="{{ now()->toDateString() }}" required></div></div>
    <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary" data-follow-up-submit>Simpan</button></div>
</form></div></div>
