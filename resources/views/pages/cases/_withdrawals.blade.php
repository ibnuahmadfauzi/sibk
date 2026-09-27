<div class="sibk-panel mb-4"><div class="sibk-panel__body p-4">
    <form class="sibk-filter-form row g-3 align-items-end" action="{{ route('cases.index') }}" method="GET">
        <input type="hidden" name="tab" value="pengunduran-diri">
        <div class="col-12 col-md-5"><label class="form-label" for="withdrawal-search">Nama murid</label><input class="form-control" id="withdrawal-search" name="search" value="{{ request('search') }}" placeholder="Cari nama murid"></div>
        <div class="col-12 col-md-5"><label class="form-label" for="withdrawal-progress-filter">Progres</label><select class="form-select" id="withdrawal-progress-filter" name="progress"><option value="">Semua progres</option>@foreach(\App\Models\WithdrawalProgress::labels() as $value => $label)<option value="{{ $value }}" @selected(request('progress') === $value)>{{ $label }}</option>@endforeach</select></div>
        <div class="col-12 col-md-2"><button class="btn btn-outline-primary w-100" type="submit">Filter</button></div>
    </form>
</div></div>

<div class="table-responsive"><table class="table sibk-table mb-0 align-middle"><thead><tr>
    <th scope="col">No</th><th scope="col">Nama Guru</th><th scope="col">Tanggal</th><th scope="col">Nama Siswa</th><th scope="col">Kelas</th><th scope="col">Progres Penanganan</th><th scope="col">Catatan</th><th scope="col">Aksi</th>
</tr></thead><tbody>
    @forelse($withdrawals as $withdrawal)
        <tr>
            <td>{{ $withdrawals->firstItem() + $loop->index }}</td>
            <td>{{ $withdrawal->teacher?->name ?? 'Guru tidak tersedia' }}</td>
            <td>{{ $withdrawal->recorded_on->locale('id')->translatedFormat('d M Y') }}</td>
            <td>{{ $withdrawal->student?->name ?? 'Murid tidak tersedia' }}</td>
            <td>{{ $withdrawal->classroom?->name ?? 'Kelas belum tercatat' }}</td>
            <td>
                @can('update', $withdrawal)
                    <form method="POST" action="{{ route('withdrawals.progress.update', $withdrawal) }}" data-withdrawal-progress-form>
                        @csrf @method('PATCH')
                        <select name="progress" class="form-select form-select-sm sibk-withdrawal-progress sibk-withdrawal-progress--{{ $withdrawal->progress }}" aria-label="Progres penanganan {{ $withdrawal->student?->name }}" data-withdrawal-progress>
                            @foreach(\App\Models\WithdrawalProgress::labels() as $value => $label)<option value="{{ $value }}" @selected($withdrawal->progress === $value)>{{ $label }}</option>@endforeach
                        </select>
                    </form>
                @else
                    <span class="sibk-badge sibk-badge--{{ match($withdrawal->progress) { 'at_tu' => 'success', 'at_bk' => 'warning', default => 'danger' } }}">{{ $withdrawal->progressLabel() }}</span>
                @endcan
            </td>
            <td>{{ $withdrawal->note ?: '—' }}</td>
            <td><button type="button" class="btn btn-sm p-0 sibk-icon-button sibk-report-control" data-report-detail-toggle data-report-detail-name="{{ $withdrawal->student?->name }}" aria-controls="withdrawal-detail-{{ $withdrawal->id }}" aria-expanded="false" aria-label="Tampilkan detail layanan {{ $withdrawal->student?->name }}" title="Tampilkan detail layanan"><svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="10.8" cy="10.8" r="6.3"/><path d="m15.4 15.4 4.3 4.3"/></svg></button></td>
        </tr>
        <tr class="sibk-report-detail-row d-none" id="withdrawal-detail-{{ $withdrawal->id }}"><td colspan="8"><div class="sibk-report-detail-panel"><strong>Alasan pengunduran diri</strong><p class="mb-0 mt-1">{{ $withdrawal->reason }}</p></div></td></tr>
    @empty
        <tr><td colspan="8" class="text-center text-muted py-4">Belum ada progres pengunduran diri yang dapat Anda akses.</td></tr>
    @endforelse
</tbody></table></div>
@if($withdrawals->hasPages())<div class="mt-3">{{ $withdrawals->links() }}</div>@endif

@if($canCreateWithdrawal && $withdrawalStudents->isNotEmpty())
    <div class="modal fade" id="withdrawal-create-modal" tabindex="-1" aria-labelledby="withdrawal-create-title" aria-hidden="true" data-withdrawal-create-modal @if($errors->any() && old('_form') === 'withdrawal') data-show-on-error @endif>
        <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable"><div class="modal-content">
            <div class="modal-header"><h2 class="modal-title fs-5" id="withdrawal-create-title">Catat Pengunduran Diri</h2><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Tutup"></button></div>
            <form method="POST" action="{{ route('withdrawals.store') }}">
                @csrf<input type="hidden" name="_form" value="withdrawal">
                <div class="modal-body">
                    @if($errors->any() && old('_form') === 'withdrawal')<div class="alert alert-danger" role="alert">{{ $errors->first() }}</div>@endif
                    <p class="text-muted small">Catatan ini memantau penanganan di BK dan tidak menetapkan murid resmi keluar.</p>
                    <div class="mb-3"><label class="form-label" for="withdrawal-student">Nama murid</label><select class="form-select" id="withdrawal-student" name="student_id" required><option value="">Pilih murid</option>@foreach($withdrawalStudents as $student)<option value="{{ $student->id }}" @selected(old('student_id') == $student->id)>{{ $student->name }} · NISN {{ $student->nisn }}</option>@endforeach</select></div>
                    <div class="mb-3"><label class="form-label" for="withdrawal-date">Tanggal</label><input class="form-control" type="date" id="withdrawal-date" name="recorded_on" value="{{ old('recorded_on', today()->toDateString()) }}" max="{{ today()->toDateString() }}" required></div>
                    <div class="mb-3"><label class="form-label" for="withdrawal-reason">Alasan pengunduran diri</label><textarea class="form-control" id="withdrawal-reason" name="reason" rows="3" maxlength="2000" required>{{ old('reason') }}</textarea></div>
                    <div class="mb-3"><label class="form-label" for="withdrawal-progress">Progres penanganan</label><select class="form-select" id="withdrawal-progress" name="progress" required>@foreach(\App\Models\WithdrawalProgress::labels() as $value => $label)<option value="{{ $value }}" @selected(old('progress', \App\Models\WithdrawalProgress::PROGRESS_IN_PROGRESS) === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div><label class="form-label" for="withdrawal-note">Catatan</label><textarea class="form-control" id="withdrawal-note" name="note" rows="2" maxlength="1000">{{ old('note') }}</textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
            </form>
        </div></div>
    </div>
@endif
