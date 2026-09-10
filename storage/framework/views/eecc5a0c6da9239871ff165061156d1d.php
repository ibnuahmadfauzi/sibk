<?php $__env->startSection('page-title', 'Selesaikan Kasus - Ruang BK'); ?>

<?php $__env->startSection('body'); ?>
    <div class="sibk-dashboard" data-page-id="PG-106">
        <div class="sibk-page-header mb-4"><div class="sibk-page-header__copy"><a href="<?php echo e(route('cases.show', $case)); ?>" class="text-decoration-none small">&larr; Kembali ke detail kasus</a><h1>Selesaikan Kasus</h1><p><?php echo e($case->registration_number); ?> &bull; <?php echo e($case->identityName()); ?></p></div></div>
        <?php if($errors->any()): ?><div class="alert alert-danger"><ul class="mb-0"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div><?php endif; ?>
        <div class="sibk-panel">
            <form action="<?php echo e(route('cases.resolve', $case)); ?>" method="POST" class="row g-4 p-4">
                <?php echo csrf_field(); ?>
                <div class="col-12 col-md-5"><label class="form-label" for="closed_at">Tanggal selesai <span class="text-danger">*</span></label><input class="form-control" type="date" id="closed_at" name="closed_at" value="<?php echo e(old('closed_at', now()->format('Y-m-d'))); ?>" max="<?php echo e(now()->format('Y-m-d')); ?>" required></div>
                <div class="col-12"><label class="form-label" for="final_result">Hasil akhir <span class="text-danger">*</span></label><textarea class="form-control" id="final_result" name="final_result" rows="4" required><?php echo e(old('final_result')); ?></textarea></div>
                <div class="col-12"><label class="form-label" for="resolution_summary">Ringkasan penyelesaian <span class="text-danger">*</span></label><textarea class="form-control" id="resolution_summary" name="resolution_summary" rows="4" required><?php echo e(old('resolution_summary')); ?></textarea></div>
                <div class="col-12"><label class="form-label" for="continued_plan">Rencana lanjutan</label><textarea class="form-control" id="continued_plan" name="continued_plan" rows="3"><?php echo e(old('continued_plan')); ?></textarea></div>
                <div class="col-12 d-flex justify-content-end gap-2"><a href="<?php echo e(route('cases.show', $case)); ?>" class="btn btn-outline-secondary">Batal</a><button type="submit" class="btn btn-primary">Konfirmasi Penyelesaian</button></div>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app-2', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Tugas\pepe g\smt 2\pk\sibk\sibk\resources\views\pages\cases\resolve.blade.php ENDPATH**/ ?>