<?php $__env->startSection('page-title', 'Atur Penugasan Kelas - Ruang BK'); ?>

<?php $__env->startSection('body'); ?>
    <div class="sibk-dashboard" data-page-id="PG-402">
        <!-- Page Header -->
        <div class="sibk-page-header mb-4">
            <div class="d-flex align-items-center gap-2 mb-2">
                <a href="<?php echo e(route('assignments.classes.index')); ?>" class="sibk-back-link d-inline-flex align-items-center gap-1 text-decoration-none text-muted small fw-semibold">
                    <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m15 18-6-6 6-6"/>
                    </svg>
                    Penugasan Kelas
                </a>
            </div>
            <div class="sibk-page-header__copy">
                <h1>Atur Penugasan Kelas</h1>
                <p>Tetapkan penanggung jawab untuk kelas dan periode tertentu.</p>
            </div>
        </div>

        <?php if($errors->any()): ?>
            <div class="alert alert-danger" role="alert">
                <ul class="mb-0">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <form action="<?php echo e(route('assignments.classes.store')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <!-- Main Panel: Detail Penugasan -->
            <div class="sibk-panel mb-4">
                <div class="sibk-panel__header p-4 border-0 pb-0">
                    <div>
                        <h3 class="sibk-panel__title mb-1">Detail Penugasan</h3>
                        <p class="sibk-panel__subtitle text-muted small">Perubahan tidak menghapus riwayat penugasan sebelumnya.</p>
                    </div>
                </div>
                <div class="sibk-panel__body p-4 pt-2">
                    <div class="row g-3 mb-4">
                        <div class="col-12 col-md-4">
                            <label for="kelas" class="form-label sibk-form-label">Kelas <span class="text-danger">*</span></label>
                            <select class="form-select sibk-form-select" id="kelas" name="classroom_id" required>
                                <option value="">Pilih kelas</option>
                                <?php $__currentLoopData = $classes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $classroom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($classroom->id); ?>" <?php if((string) old('classroom_id', $selectedClass?->id) === (string) $classroom->id): echo 'selected'; endif; ?>><?php echo e($classroom->name); ?> — <?php echo e($classroom->academicYear->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="tahun_ajaran" class="form-label sibk-form-label">Tahun Ajaran <span class="text-danger">*</span></label>
                            <select class="form-select sibk-form-select" id="tahun_ajaran" name="academic_year_id" required>
                                <?php $__currentLoopData = $academicYears; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($year->id); ?>" <?php if((string) old('academic_year_id', $selectedYear?->id) === (string) $year->id): echo 'selected'; endif; ?>><?php echo e($year->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="counselor" class="form-label sibk-form-label">Penanggung Jawab <span class="text-danger">*</span></label>
                            <select class="form-select sibk-form-select" id="counselor" name="user_id" required>
                                <option value="">Pilih Guru BK</option>
                                <?php $__currentLoopData = $counselors; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $guru): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($guru->id); ?>" <?php if((string) old('user_id') === (string) $guru->id): echo 'selected'; endif; ?>><?php echo e($guru->name); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="start_date" class="form-label sibk-form-label">Tanggal Mulai <span class="text-danger">*</span></label>
                            <input type="date" class="form-control sibk-form-control" id="start_date" name="effective_date" value="<?php echo e(old('effective_date', now()->toDateString())); ?>" required>
                        </div>
                        <div class="col-12 col-md-4">
                            <label for="end_date" class="form-label sibk-form-label">Tanggal Akhir</label>
                            <input type="date" class="form-control sibk-form-control" id="end_date" name="effective_until" value="<?php echo e(old('effective_until')); ?>" placeholder="Pilih tanggal bila ada">
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="decision_basis" class="form-label sibk-form-label">Dasar Keputusan / Nomor SK</label>
                            <input type="text" class="form-control sibk-form-control" id="decision_basis" name="decision_number" placeholder="Contoh: SK Pembagian Tugas No. 421/089/2026" value="<?php echo e(old('decision_number')); ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="notes" class="form-label sibk-form-label">Catatan Perubahan</label>
                            <input type="text" class="form-control sibk-form-control" id="notes" name="notes" placeholder="Isi bila pembagian yang sedang berjalan berubah" value="<?php echo e(old('notes')); ?>">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Info Box: Ketentuan Tata Kelola (ASN-01 s.d. ASN-06) -->
            <div class="alert alert-info d-flex align-items-start gap-3 p-3 mb-4 rounded-3 border-0 bg-opacity-10 bg-primary">
                <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="text-primary flex-shrink-0 mt-1">
                    <circle cx="12" cy="12" r="10"/>
                    <line x1="12" y1="16" x2="12" y2="12"/>
                    <line x1="12" y1="8" x2="12.01" y2="8"/>
                </svg>
                <div class="small text-dark">
                    <strong>Ketentuan Tata Kelola Penugasan Guru BK:</strong>
                    <ul class="mb-0 ps-3 mt-1 text-muted">
                        <li>Penetapan penugasan baru akan mengarsipkan penugasan sebelumnya tanpa menghapus rekam jejak histori (<em>ASN-02</em>).</li>
                        <li>Perubahan penugasan kelas di tengah tahun <strong>tidak otomatis memindahkan kasus aktif</strong> yang sedang berjalan (<em>ASN-03</em>). Pengalihan kasus khusus dilakukan terpisah melalui menu <a href="<?php echo e(route('assignments.cases.index')); ?>" class="text-primary fw-semibold text-decoration-none">Pengalihan Kasus</a>.</li>
                    </ul>
                </div>
            </div>

            <!-- Panel: Penugasan Saat Ini -->
            <div class="sibk-panel mb-4">
                <div class="sibk-panel__header p-4 border-0 pb-0">
                    <div>
                        <h3 class="sibk-panel__title mb-1">Penugasan Saat Ini</h3>
                        <p class="sibk-panel__subtitle text-muted small">Periksa kondisi aktif sebelum menyimpan perubahan.</p>
                    </div>
                </div>
                <div class="sibk-panel__body p-4 pt-2">
                    <?php if($currentAssignment): ?>
                    <div class="sibk-target-highlight-box p-3 d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center gap-2">
                            <strong class="text-dark"><?php echo e($currentAssignment->classroom->name); ?></strong>
                            <span class="text-muted">•</span>
                            <span class="fw-semibold text-primary"><?php echo e($currentAssignment->teacher->name); ?></span>
                            <span class="text-muted">•</span>
                            <span class="text-muted small">Berlaku sejak <?php echo e($currentAssignment->effective_from->locale('id')->translatedFormat('d F Y')); ?></span>
                        </div>
                        <span class="sibk-badge sibk-badge--success">Aktif</span>
                    </div>
                    <?php else: ?>
                        <div class="text-muted small">Belum ada penugasan aktif untuk kelas yang dipilih.</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Bottom Actions -->
            <div class="d-flex justify-content-end gap-3 mt-4">
                <a href="<?php echo e(route('assignments.classes.index')); ?>" class="btn btn-outline-secondary px-4 py-2">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary d-inline-flex align-items-center gap-2 px-4 py-2">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"/>
                        <polyline points="17 21 17 13 7 13 7 21"/>
                        <polyline points="7 3 7 8 15 8"/>
                    </svg>
                    Simpan Penugasan
                </button>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app-2', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Tugas\pepe g\smt 2\pk\sibk\sibk\resources\views\pages\assignments\classes\manage.blade.php ENDPATH**/ ?>