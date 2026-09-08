<?php $__env->startSection('page-title', 'Daftar Murid - Ruang BK'); ?>

<?php $__env->startSection('body'); ?>
    <div class="sibk-dashboard" data-page-id="PG-201">
        <div class="sibk-page-header d-flex flex-wrap justify-content-between gap-3 mb-4"><div class="sibk-page-header__copy"><h1>Daftar Murid</h1><p>Cari murid dan buka profil layanan sesuai kewenangan Anda.</p></div><a href="<?php echo e(route('achievements.index')); ?>" class="btn btn-outline-primary">Kelola Prestasi</a></div>
        <div class="sibk-panel mb-4"><div class="sibk-panel__body p-4"><form class="row g-3 align-items-end" action="<?php echo e(route('students.index')); ?>" method="GET">
            <div class="col-12 col-md-7"><label class="form-label" for="student_search">Cari murid</label><input class="form-control" id="student_search" name="search" value="<?php echo e(request('search')); ?>" placeholder="Nama atau NISN"></div>
            <div class="col-12 col-md-3"><label class="form-label" for="student_class">Kelas</label><select class="form-select" id="student_class" name="classroom_id"><option value="">Semua kelas</option><?php $__currentLoopData = $classrooms; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $classroom): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($classroom->id); ?>" <?php if((string) request('classroom_id') === (string) $classroom->id): echo 'selected'; endif; ?>><?php echo e($classroom->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
            <div class="col-12 col-md-2"><button class="btn btn-primary w-100">Cari</button></div>
        </form></div></div>

        <div class="table-responsive"><table class="table sibk-table mb-0"><thead><tr><th>NISN</th><th>Nama Murid</th><th>Kelas Aktif</th><th>Kasus Aktif</th><th>Tindak Lanjut</th><th></th></tr></thead><tbody>
            <?php $__empty_1 = true; $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                <?php
                    $membership = $student->classMemberships->first();
                    $activeCases = $student->cases->whereNull('closed_at');
                    $nextFollowUp = $student->cases->flatMap->followUps->filter(fn ($item) => $item->planned_date->gte(today()) && $item->status?->code !== 'dibatalkan')->sortBy('planned_date')->first();
                ?>
                <tr><td class="fw-semibold"><?php echo e($student->nisn); ?></td><td class="fw-semibold"><?php echo e($student->name); ?></td><td><?php echo e($membership?->classroom?->name ?? '—'); ?></td><td><span class="fw-semibold <?php echo e($activeCases->isNotEmpty() ? 'text-primary' : 'text-muted'); ?>"><?php echo e($activeCases->count()); ?></span></td><td><?php echo e($nextFollowUp?->planned_date?->locale('id')->translatedFormat('d M Y') ?? 'Belum ada'); ?></td><td><a href="<?php echo e(route('students.show', $student)); ?>" class="fw-bold text-decoration-none">Buka</a></td></tr>
            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?><tr><td colspan="6" class="text-center text-muted py-4">Tidak ada data murid yang sesuai.</td></tr><?php endif; ?>
        </tbody></table></div><?php if($students->hasPages()): ?><div class="mt-3"><div class="text-muted small mb-2">Menampilkan <?php echo e($students->count()); ?> dari <?php echo e($students->total()); ?> murid</div><?php echo e($students->links()); ?></div><?php endif; ?>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app-2', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Projects\PPG\sibk\resources\views/pages/students/index.blade.php ENDPATH**/ ?>