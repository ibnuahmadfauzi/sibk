<?php $__env->startSection('page-title', 'Penugasan Kelas - Ruang BK'); ?>

<?php $__env->startSection('body'); ?>
    <div class="sibk-dashboard" data-page-id="PG-401">
        <?php if(session('success')): ?>
            <div class="alert alert-success" role="alert"><?php echo e(session('success')); ?></div>
        <?php endif; ?>

        <!-- Header -->
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
            <div class="sibk-page-header__copy">
                <h1>Penugasan Kelas</h1>
                <p>Daftar penanggung jawab layanan BK per kelas.</p>
            </div>
            <?php if($canManage): ?>
            <div class="sibk-page-header__actions">
                <a href="<?php echo e(route('assignments.classes.manage')); ?>" class="btn btn-primary d-inline-flex align-items-center gap-2">
                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2"/><path d="M2 20c0-3.9 2.7-7 6-7s6 3.1 6 7M14 14c3.6 0 6 2.6 6 6"/>
                    </svg>
                    Atur Penugasan
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Filter Panel -->
        <div class="sibk-panel mb-4">
            <div class="sibk-panel__body p-4">
                <form class="sibk-filter-form row g-3 align-items-end" action="<?php echo e(route('assignments.classes.index')); ?>" method="GET">
                    <div class="col-12 col-md-3">
                        <label for="tahun_ajaran" class="form-label sibk-form-label">Tahun Ajaran</label>
                        <select class="form-select sibk-form-select" id="tahun_ajaran" name="academic_year_id">
                            <option value="">Semua tahun ajaran</option>
                            <?php $__currentLoopData = $academicYears; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($year->id); ?>" <?php if((string) request('academic_year_id') === (string) $year->id): echo 'selected'; endif; ?>><?php echo e($year->name); ?></option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label for="search_kelas" class="form-label sibk-form-label">Cari Kelas</label>
                        <input type="text" class="form-control sibk-form-control" id="search_kelas" name="search_kelas" value="<?php echo e(request('search_kelas')); ?>" placeholder="Nama kelas">
                    </div>
                    <div class="col-12 col-md-3">
                        <label for="status" class="form-label sibk-form-label">Status</label>
                        <select class="form-select sibk-form-select" id="status" name="status">
                            <option value="">Semua status</option>
                            <option value="aktif" <?php if(request('status') === 'aktif'): echo 'selected'; endif; ?>>Aktif</option>
                            <option value="nonaktif" <?php if(request('status') === 'nonaktif'): echo 'selected'; endif; ?>>Tidak Aktif</option>
                        </select>
                    </div>
                    <div class="col-12 col-md-2">
                        <button type="submit" class="btn btn-outline-primary w-100 sibk-btn-apply d-inline-flex align-items-center justify-content-center gap-1">
                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/>
                            </svg>
                            Filter
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Table -->
        <div class="table-responsive">
            <table class="table sibk-table mb-0">
                <thead>
                    <tr>
                        <th>Kelas</th>
                        <th>Penanggung Jawab</th>
                        <th>Mulai Berlaku</th>
                        <th>Akhir Berlaku</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php $__empty_1 = true; $__currentLoopData = $assignments; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $assignment): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <?php ($isActive = $assignment->effective_from->lte(now()) && ($assignment->effective_until === null || $assignment->effective_until->gte(now()))); ?>
                        <tr>
                            <td class="fw-bold text-dark"><?php echo e($assignment->classroom->name); ?></td>
                            <td class="fw-semibold text-primary"><?php echo e($assignment->teacher->name); ?></td>
                            <td><?php echo e($assignment->effective_from->locale('id')->translatedFormat('d M Y')); ?></td>
                            <td class="text-muted"><?php echo e($assignment->effective_until?->locale('id')->translatedFormat('d M Y') ?? '—'); ?></td>
                            <td>
                                <span class="sibk-badge sibk-badge--<?php echo e($isActive ? 'success' : 'neutral'); ?>">
                                    <?php echo e($isActive ? 'Aktif' : 'Tidak Aktif'); ?>

                                </span>
                            </td>
                            <td>
                                <?php if($canManage): ?>
                                    <a href="<?php echo e(route('assignments.classes.manage', ['classroom_id' => $assignment->classroom_id, 'academic_year_id' => $assignment->academic_year_id])); ?>" class="fw-bold text-decoration-none text-primary">
                                        Atur
                                    </a>
                                <?php else: ?>
                                    <span class="text-muted">—</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4 text-muted">Belum ada penugasan kelas yang dicatat.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="text-muted small fw-medium mt-2">
            Menampilkan <?php echo e($assignments->isEmpty() ? 0 : 1); ?>–<?php echo e($assignments->count()); ?> dari <?php echo e($assignments->count()); ?> penugasan kelas
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app-2', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Tugas\pepe g\smt 2\pk\sibk\sibk\resources\views\pages\assignments\classes\index.blade.php ENDPATH**/ ?>