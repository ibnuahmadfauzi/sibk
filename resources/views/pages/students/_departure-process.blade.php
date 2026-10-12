<section class="sibk-panel mb-4" aria-labelledby="departure-process-title">
    <div class="sibk-panel__header p-4 pb-0">
        <h2 class="sibk-panel__title" id="departure-process-title">Proses keluar murid</h2>
    </div>
    <div class="sibk-panel__body p-4">
        @if($departure)
            <dl class="row mb-4">
                <dt class="col-sm-4">Jenis</dt><dd class="col-sm-8">{{ $departure->typeLabel() }}</dd>
                <dt class="col-sm-4">Status</dt><dd class="col-sm-8">{{ $departure->statusLabel() }}</dd>
                <dt class="col-sm-4">Tanggal pencatatan</dt><dd class="col-sm-8">{{ $departure->reported_at->locale('id')->translatedFormat('d M Y') }}</dd>
                <dt class="col-sm-4">Tanggal efektif</dt><dd class="col-sm-8">{{ $departure->effective_date?->locale('id')->translatedFormat('d M Y') ?? '-' }}</dd>
                <dt class="col-sm-4">Ringkasan rekomendasi</dt><dd class="col-sm-8">{{ $departure->recommendation_summary ?: '-' }}</dd>
                <dt class="col-sm-4">Catatan keputusan</dt><dd class="col-sm-8">{{ $departure->decision_note ?: '-' }}</dd>
            </dl>
        @endif

        @if($canCreateDeparture || $canUpdateDeparture)
            <form method="post" action="{{ $canUpdateDeparture ? route('students.departure.update', $student) : route('students.departure.store', $student) }}" class="row g-3 mb-4" data-autosave-form="student-departure" data-autosave-record="{{ $student->id }}">
                @csrf
                @if($canUpdateDeparture) @method('PATCH') @endif
                <div class="col-md-6">
                    <label class="form-label" for="departure_type">Jenis keluar</label>
                    <select class="form-select" id="departure_type" name="departure_type" required>
                        @foreach([
                            'lulus' => 'Lulus',
                            'pindah' => 'Pindah',
                            'mengundurkan_diri' => 'Mengundurkan diri',
                            'keluar_lainnya' => 'Keluar lainnya',
                        ] as $value => $label)
                            <option value="{{ $value }}" @selected(old('departure_type', $departure?->departure_type) === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                @unless($canUpdateDeparture)
                    <div class="col-md-6">
                        <label class="form-label" for="reported_at">Tanggal pencatatan</label>
                        <input class="form-control" id="reported_at" name="reported_at" type="date" max="{{ today()->toDateString() }}" value="{{ old('reported_at', today()->toDateString()) }}" required>
                    </div>
                @endunless
                <div class="col-12">
                    <label class="form-label" for="recommendation_summary">Ringkasan rekomendasi</label>
                    <textarea class="form-control" id="recommendation_summary" name="recommendation_summary" maxlength="500" rows="3">{{ old('recommendation_summary', $departure?->recommendation_summary) }}</textarea>
                </div>
                <div class="col-12 d-flex align-items-center gap-2"><span class="small text-muted me-auto" data-draft-status aria-live="polite"></span><button class="btn btn-light" type="button" data-clear-draft>Hapus Draft</button><button class="btn btn-primary" type="submit">{{ $canUpdateDeparture ? 'Perbarui proses' : ($departure ? 'Buka kembali proses' : 'Catat proses') }}</button></div>
            </form>
        @endif

        @if($canFinalizeDeparture)
            <form method="post" action="{{ route('students.departure.finalize', $student) }}" class="row g-3">
                @csrf
                <div class="col-md-6">
                    <label class="form-label" for="effective_date">Tanggal efektif resmi keluar</label>
                    <input class="form-control" id="effective_date" name="effective_date" type="date" value="{{ old('effective_date') }}">
                </div>
                <div class="col-12">
                    <label class="form-label" for="decision_note">Catatan keputusan</label>
                    <textarea class="form-control" id="decision_note" name="decision_note" maxlength="500" rows="3">{{ old('decision_note') }}</textarea>
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                    <button class="btn btn-outline-secondary" type="submit" name="decision" value="batal">Tetapkan Batal</button>
                    <button class="btn btn-primary" type="submit" name="decision" value="resmi_keluar">Tetapkan Resmi Keluar</button>
                </div>
            </form>
        @endif

        @if(!$departure && !$canCreateDeparture)
            <p class="text-muted mb-0">Belum ada proses keluar murid.</p>
        @endif
    </div>
</section>
