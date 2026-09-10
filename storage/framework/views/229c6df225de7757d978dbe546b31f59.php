<?php $__env->startSection('page-title', 'Detail Kasus - Ruang BK'); ?>

<?php $__env->startSection('body'); ?>
    <div class="sibk-dashboard" data-page-id="PG-103">
        <?php if(session('success')): ?>
            <div class="alert alert-success" role="alert"><?php echo e(session('success')); ?></div>
        <?php endif; ?>
        <div class="sibk-page-header mb-4 d-flex flex-wrap justify-content-between gap-3">
            <div class="sibk-page-header__copy">
                <a href="<?php echo e(route('cases.index')); ?>" class="text-decoration-none small">&larr; Kembali ke daftar</a>
                <h1 class="mb-1"><?php echo e($case->registration_number); ?></h1>
                <p class="mb-0"><?php echo e($case->identityName()); ?> &bull; NISN <?php echo e($case->identityNisn()); ?></p>
            </div>
            <div class="d-flex flex-wrap align-items-start gap-2">
                <?php if(auth()->user()?->hasRole('guru_bk')): ?>
                    <a href="<?php echo e(route('corrections.create', ['target_type' => 'case', 'target_id' => $case->id])); ?>" class="btn btn-outline-secondary">Ajukan Koreksi</a>
                <?php endif; ?>
                <?php if($canAssignCase): ?>
                    <a href="<?php echo e(route('assignments.cases.index', ['case_id' => $case->id])); ?>" class="btn btn-outline-secondary">Atur Penugasan</a>
                <?php endif; ?>
                <?php if($canUpdateCase): ?>
                    <a href="<?php echo e(route('cases.follow-ups.create', $case)); ?>" class="btn btn-outline-primary">Tambah Tindak Lanjut</a>
                    <a href="<?php echo e(route('cases.resolve.form', $case)); ?>" class="btn btn-primary">Selesaikan Kasus</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-xl-8">
                <div class="sibk-panel mb-4">
                    <div class="sibk-panel__header p-4 pb-2"><h2 class="sibk-panel__title">Informasi Kasus</h2></div>
                    <div class="sibk-panel__body p-4 pt-2">
                        <div class="row g-3 mb-4">
                            <div class="col-sm-6"><span class="d-block text-muted small">Status</span><span class="sibk-badge sibk-badge--primary"><?php echo e($case->status->label); ?></span></div>
                            <div class="col-sm-6"><span class="d-block text-muted small">Tanggal layanan</span><strong><?php echo e($case->service_date->locale('id')->translatedFormat('d F Y')); ?></strong></div>
                            <div class="col-sm-6"><span class="d-block text-muted small">Sumber</span><strong><?php echo e($case->source->label); ?></strong></div>
                            <div class="col-sm-6"><span class="d-block text-muted small">Bidang layanan</span><strong><?php echo e($case->serviceField->label); ?></strong></div>
                            <div class="col-sm-6"><span class="d-block text-muted small">Perujuk</span><strong><?php echo e($case->referrer ?: '-'); ?></strong></div>
                        </div>
                        <h3 class="fs-6 fw-bold">Informasi awal</h3>
                        <p class="text-break"><?php echo e($case->initial_info); ?></p>
                        <h3 class="fs-6 fw-bold">Tindakan awal</h3>
                        <p class="text-break mb-0"><?php echo e($case->initial_action ?: '-'); ?></p>
                        <?php if($canViewInternal): ?>
                            <hr>
                            <h3 class="fs-6 fw-bold">Catatan internal</h3>
                            <p class="text-break mb-0"><?php echo e($case->internal_note ?: '-'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="sibk-panel mb-4">
                    <div class="sibk-panel__header p-4 pb-2"><h2 class="sibk-panel__title">Riwayat Tindak Lanjut</h2></div>
                    <div class="table-responsive">
                        <table class="table sibk-table mb-0">
                            <thead><tr><th>Rencana</th><th>Jenis</th><th>Status</th><th>Pelaksana</th><th>Hasil</th><th></th></tr></thead>
                            <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $case->followUps->sortByDesc('planned_date'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $followUp): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><?php echo e($followUp->planned_date->locale('id')->translatedFormat('d M Y')); ?></td>
                                    <td><?php echo e($followUp->type->label); ?></td>
                                    <td><span class="sibk-badge sibk-badge--primary"><?php echo e($followUp->status->label); ?></span></td>
                                    <td><?php echo e($followUp->recorder->name); ?></td>
                                    <td><?php echo e(\Illuminate\Support\Str::limit($followUp->result ?: '-', 80)); ?></td>
                                    <td>
                                        <?php if($canUpdateCase && $followUp->recorded_by === auth()->id()): ?>
                                            <a href="<?php echo e(route('cases.follow-ups.edit', [$case, $followUp])); ?>" class="fw-semibold text-decoration-none">Ubah</a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">Belum ada tindak lanjut.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="sibk-panel mb-4">
                    <div class="sibk-panel__header p-4 pb-2"><h2 class="sibk-panel__title">Data e-Tatib Tertaut</h2></div>
                    <div class="table-responsive">
                        <table class="table sibk-table mb-0">
                            <thead><tr><th>Waktu</th><th>Pelanggaran</th><th>Kategori</th><th>Poin</th><th>Status sumber</th></tr></thead>
                            <tbody>
                            <?php $__empty_1 = true; $__currentLoopData = $case->etatibRecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                                <tr>
                                    <td><?php echo e($record->occurred_at->locale('id')->translatedFormat('d M Y H:i')); ?></td>
                                    <td><?php echo e($record->violation_type); ?></td>
                                    <td><?php echo e($record->category ?: '-'); ?></td>
                                    <td><?php echo e($record->points ?? '-'); ?></td>
                                    <td><?php echo e($record->source_status ?: '-'); ?></td>
                                </tr>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">Tidak ada record e-Tatib tertaut.</td></tr>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-12 col-xl-4">
                <div class="sibk-panel mb-4">
                    <div class="sibk-panel__header p-4 pb-2"><h2 class="sibk-panel__title">Penugasan Kasus</h2></div>
                    <div class="sibk-panel__body p-4 pt-2">
                        <?php $__empty_1 = true; $__currentLoopData = $case->assignments->sortByDesc('effective_from'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="border-bottom py-2">
                                <strong class="d-block"><?php echo e($assignment->teacher->name); ?></strong>
                                <span class="small text-muted"><?php echo e($assignment->assignment_type === 'owner' ? 'Penanggung jawab' : 'Kewenangan tambahan'); ?> &bull; <?php echo e($assignment->effective_from->format('d-m-Y')); ?> s.d. <?php echo e($assignment->effective_until?->format('d-m-Y') ?? 'sekarang'); ?></span>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="text-muted mb-0">Belum ada penugasan.</p>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="sibk-panel mb-4">
                    <div class="sibk-panel__header p-4 pb-2"><h2 class="sibk-panel__title">Koordinasi Waka</h2></div>
                    <div class="sibk-panel__body p-4 pt-2">
                        <?php $__empty_1 = true; $__currentLoopData = $case->coordinations->sortByDesc('coordinated_at'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $coordination): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <div class="border-bottom py-3">
                                <div class="d-flex justify-content-between gap-2"><strong><?php echo e($coordination->waka->name); ?></strong><span class="sibk-badge sibk-badge--primary"><?php echo e($coordination->status->label); ?></span></div>
                                <p class="small mt-2 mb-1"><?php echo e($coordination->coordination_need); ?></p>
                                <?php if($coordination->result): ?><p class="small text-muted mb-1">Hasil: <?php echo e($coordination->result); ?></p><?php endif; ?>
                                <?php if($canCoordinateCase && $coordination->status->code === 'menunggu'): ?>
                                    <form action="<?php echo e(route('cases.coordinations.update', [$case, $coordination])); ?>" method="POST" class="mt-2">
                                        <?php echo csrf_field(); ?> <?php echo method_field('PATCH'); ?>
                                        <select name="status_id" class="form-select form-select-sm mb-2" required>
                                            <option value="">Pilih status akhir</option>
                                            <?php $__currentLoopData = $coordinationEndStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($status->id); ?>"><?php echo e($status->label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                        </select>
                                        <textarea name="result" class="form-control form-control-sm mb-2" rows="2" placeholder="Hasil koordinasi atau alasan pembatalan"></textarea>
                                        <button class="btn btn-outline-primary btn-sm" type="submit">Perbarui</button>
                                    </form>
                                <?php endif; ?>
                            </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <p class="text-muted">Belum ada koordinasi.</p>
                        <?php endif; ?>

                        <?php if($canCoordinateCase && $wakaUsers->isNotEmpty()): ?>
                            <form action="<?php echo e(route('cases.coordinations.store', $case)); ?>" method="POST" class="mt-3">
                                <?php echo csrf_field(); ?>
                                <label class="form-label" for="waka_user_id">Tujuan koordinasi</label>
                                <select class="form-select mb-2" id="waka_user_id" name="waka_user_id" required>
                                    <option value="">Pilih Waka Kesiswaan</option>
                                    <?php $__currentLoopData = $wakaUsers; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $waka): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($waka->id); ?>"><?php echo e($waka->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <textarea class="form-control mb-2" name="coordination_need" rows="3" placeholder="Kebutuhan koordinasi" required></textarea>
                                <button type="submit" class="btn btn-outline-primary w-100">Catat Koordinasi</button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if($case->closed_at): ?>
                    <div class="sibk-panel">
                        <div class="sibk-panel__header p-4 pb-2"><h2 class="sibk-panel__title">Penyelesaian</h2></div>
                        <div class="sibk-panel__body p-4 pt-2">
                            <p><span class="d-block text-muted small">Tanggal selesai</span><?php echo e($case->closed_at->locale('id')->translatedFormat('d F Y')); ?></p>
                            <p><span class="d-block text-muted small">Hasil akhir</span><?php echo e($case->final_result); ?></p>
                            <p><span class="d-block text-muted small">Ringkasan</span><?php echo e($case->resolution_summary); ?></p>
                            <p class="mb-0"><span class="d-block text-muted small">Rencana lanjutan</span><?php echo e($case->continued_plan ?: '-'); ?></p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app-2', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Tugas\pepe g\smt 2\pk\sibk\sibk\resources\views\pages\cases\show.blade.php ENDPATH**/ ?>