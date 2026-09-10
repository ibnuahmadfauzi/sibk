<?php $__env->startSection('page-title', 'Penugasan & Pengalihan Kasus - Ruang BK'); ?>

<?php $__env->startSection('body'); ?>
    <div class="sibk-dashboard" data-page-id="PG-403">
        <div class="sibk-page-header mb-4"><div class="sibk-page-header__copy"><h1>Penugasan dan Pengalihan Kasus</h1><p>Atur penanggung jawab atau kewenangan tambahan untuk kasus aktif.</p></div></div>
        <?php if($errors->any()): ?><div class="alert alert-danger"><ul class="mb-0"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div><?php endif; ?>

        <div class="sibk-panel mb-4">
            <form action="<?php echo e(route('assignments.cases.index')); ?>" method="GET" class="row g-3 p-4 align-items-end">
                <div class="col-12 col-lg-9">
                    <label class="form-label" for="case_id">Kasus target</label>
                    <select class="form-select" id="case_id" name="case_id" required>
                        <?php $__empty_1 = true; $__currentLoopData = $cases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $caseOption): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <option value="<?php echo e($caseOption->id); ?>" <?php if($selectedCase?->is($caseOption)): echo 'selected'; endif; ?>><?php echo e($caseOption->registration_number); ?> — <?php echo e($caseOption->identityName()); ?> (<?php echo e($caseOption->status->label); ?>)</option>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <option value="">Tidak ada kasus aktif</option>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-12 col-lg-3"><button class="btn btn-outline-primary w-100" type="submit">Pilih Kasus</button></div>
            </form>
        </div>

        <?php if($selectedCase): ?>
            <div class="sibk-panel mb-4">
                <div class="sibk-panel__header p-4 pb-2"><h2 class="sibk-panel__title">Kasus Terpilih</h2></div>
                <div class="sibk-panel__body p-4 pt-2">
                    <div class="d-flex flex-wrap justify-content-between gap-3">
                        <div><strong class="text-primary"><?php echo e($selectedCase->registration_number); ?></strong><span class="mx-2">&bull;</span><?php echo e($selectedCase->identityName()); ?><span class="mx-2">&bull;</span>NISN <?php echo e($selectedCase->identityNisn()); ?></div>
                        <span class="sibk-badge sibk-badge--primary"><?php echo e($selectedCase->status->label); ?></span>
                    </div>
                    <div class="mt-3 small text-muted">
                        Penugasan saat ini:
                        <?php $__empty_1 = true; $__currentLoopData = $selectedCase->assignments->sortByDesc('effective_from'); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                            <span class="d-block"><?php echo e($assignment->teacher->name); ?> — <?php echo e($assignment->assignment_type === 'owner' ? 'Penanggung jawab' : 'Kewenangan tambahan'); ?> (<?php echo e($assignment->effective_from->format('d-m-Y')); ?> s.d. <?php echo e($assignment->effective_until?->format('d-m-Y') ?? 'sekarang'); ?>)</span>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                            <span>belum tersedia.</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="sibk-panel">
                <form action="<?php echo e(route('cases.assign', $selectedCase)); ?>" method="POST" class="row g-4 p-4">
                    <?php echo csrf_field(); ?>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="assignment_type">Jenis perubahan <span class="text-danger">*</span></label>
                        <select class="form-select" id="assignment_type" name="assignment_type" required>
                            <option value="transfer" <?php if(old('assignment_type') === 'transfer'): echo 'selected'; endif; ?>>Pengalihan penanggung jawab</option>
                            <option value="additional" <?php if(old('assignment_type') === 'additional'): echo 'selected'; endif; ?>>Kewenangan tambahan</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label" for="to_user_id">Guru BK penerima <span class="text-danger">*</span></label>
                        <select class="form-select" id="to_user_id" name="to_user_id" required>
                            <option value="">Pilih Guru BK</option>
                            <?php $__currentLoopData = $counselors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $counselor): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($counselor->id); ?>" <?php if((string) old('to_user_id') === (string) $counselor->id): echo 'selected'; endif; ?>><?php echo e($counselor->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-4"><label class="form-label" for="effective_date">Tanggal berlaku <span class="text-danger">*</span></label><input class="form-control" type="date" id="effective_date" name="effective_date" value="<?php echo e(old('effective_date', now()->format('Y-m-d'))); ?>" required></div>
                    <div class="col-12"><label class="form-label" for="reason">Alasan <span class="text-danger">*</span></label><textarea class="form-control" id="reason" name="reason" rows="3" required><?php echo e(old('reason')); ?></textarea></div>
                    <div class="col-12 d-flex justify-content-end gap-2"><a href="<?php echo e(route('cases.show', $selectedCase)); ?>" class="btn btn-outline-secondary">Batal</a><button class="btn btn-primary" type="submit">Simpan Penugasan</button></div>
                </form>
            </div>
        <?php else: ?>
            <div class="sibk-panel p-5 text-center text-muted">Tidak ada kasus aktif yang dapat ditugaskan.</div>
        <?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app-2', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\cadangan\Tugas Kuliah\PGG\Sesmter 2\PK\Website BK\sibk\resources\views/pages/assignments/cases/index.blade.php ENDPATH**/ ?>