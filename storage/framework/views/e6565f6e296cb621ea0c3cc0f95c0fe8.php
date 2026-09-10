<?php $__env->startSection('page-title', ($isEdit ? 'Ubah' : 'Tambah').' Tindak Lanjut - Ruang BK'); ?>

<?php $__env->startSection('body'); ?>
    <div class="sibk-dashboard" data-page-id="PG-104">
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy">
                <a href="<?php echo e(route('cases.show', $case)); ?>" class="text-decoration-none small">&larr; Kembali ke detail kasus</a>
                <h1><?php echo e($isEdit ? 'Ubah' : 'Tambah'); ?> Tindak Lanjut</h1>
                <p><?php echo e($case->registration_number); ?> &bull; <?php echo e($case->identityName()); ?></p>
            </div>
        </div>
        <?php if($errors->any()): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div>
        <?php endif; ?>
        <div class="sibk-panel">
            <form action="<?php echo e($isEdit ? route('cases.follow-ups.update', [$case, $followUp]) : route('cases.follow-ups.store', $case)); ?>" method="POST" class="row g-4 p-4">
                <?php echo csrf_field(); ?>
                <?php if($isEdit): ?> <?php echo method_field('PATCH'); ?> <?php endif; ?>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="follow_up_type_id">Jenis tindak lanjut <span class="text-danger">*</span></label>
                    <select class="form-select" id="follow_up_type_id" name="follow_up_type_id" required>
                        <option value="">Pilih jenis</option>
                        <?php $__currentLoopData = $followUpTypes; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $type): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($type->id); ?>" <?php if((string) old('follow_up_type_id', $followUp?->follow_up_type_id) === (string) $type->id): echo 'selected'; endif; ?>><?php echo e($type->label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-12 col-md-6">
                    <label class="form-label" for="status_id">Status pelaksanaan <span class="text-danger">*</span></label>
                    <select class="form-select" id="status_id" name="status_id" required>
                        <option value="">Pilih status</option>
                        <?php $__currentLoopData = $followUpStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($status->id); ?>" <?php if((string) old('status_id', $followUp?->status_id) === (string) $status->id): echo 'selected'; endif; ?>><?php echo e($status->label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </select>
                </div>
                <div class="col-12 col-md-6"><label class="form-label" for="planned_date">Tanggal rencana <span class="text-danger">*</span></label><input class="form-control" type="date" id="planned_date" name="planned_date" value="<?php echo e(old('planned_date', $followUp?->planned_date?->format('Y-m-d') ?? now()->format('Y-m-d'))); ?>" required></div>
                <div class="col-12 col-md-6"><label class="form-label" for="execution_date">Tanggal pelaksanaan</label><input class="form-control" type="date" id="execution_date" name="execution_date" value="<?php echo e(old('execution_date', $followUp?->execution_date?->format('Y-m-d'))); ?>"></div>
                <div class="col-12"><label class="form-label" for="result">Hasil</label><textarea class="form-control" id="result" name="result" rows="4"><?php echo e(old('result', $followUp?->result)); ?></textarea><div class="form-text">Wajib diisi apabila status pelaksanaan Terlaksana.</div></div>
                <div class="col-12"><label class="form-label" for="next_plan">Rencana berikutnya</label><textarea class="form-control" id="next_plan" name="next_plan" rows="3"><?php echo e(old('next_plan', $followUp?->next_plan)); ?></textarea></div>
                <div class="col-12 d-flex justify-content-end gap-2"><a href="<?php echo e(route('cases.show', $case)); ?>" class="btn btn-outline-secondary">Batal</a><button type="submit" class="btn btn-primary">Simpan Tindak Lanjut</button></div>
            </form>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app-2', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Tugas\pepe g\smt 2\pk\sibk\sibk\resources\views/pages/cases/follow-up.blade.php ENDPATH**/ ?>