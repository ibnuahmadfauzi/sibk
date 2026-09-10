<?php $__env->startSection('page-title', 'Buat Kasus Baru - Ruang BK'); ?>

<?php $__env->startSection('body'); ?>
    <div class="sibk-dashboard" data-page-id="PG-102">
        <div class="sibk-page-header mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="<?php echo e(route('cases.index')); ?>" class="btn btn-icon btn-light" aria-label="Kembali">←</a>
                <div class="sibk-page-header__copy m-0"><h1 class="mb-1">Buat Kasus Baru</h1><p class="mb-0">Catat informasi awal layanan BK secara terstruktur.</p></div>
            </div>
        </div>

        <?php if($errors->any()): ?>
            <div class="alert alert-danger" role="alert"><ul class="mb-0"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div>
        <?php endif; ?>

        <form action="<?php echo e(route('cases.store')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <div class="sibk-panel mb-4 border-0 shadow-sm">
                <div class="sibk-panel__body p-4 p-md-5">
                    <h4 class="fs-5 mb-1 text-dark fw-bold">Murid dan Sumber Kasus</h4>
                    <p class="text-muted small mb-4">Pilih murid dalam scope Anda atau gunakan identitas sementara bila master belum tersedia.</p>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label for="student" class="form-label sibk-form-label">Murid Master</label>
                            <select class="form-select sibk-form-select" id="student" name="student_id">
                                <option value="">Pilih murid atau isi identitas sementara</option>
                                <?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($student->id); ?>" data-nisn="<?php echo e($student->nisn); ?>" <?php if((string) old('student_id', $preselectedStudentId) === (string) $student->id): echo 'selected'; endif; ?>><?php echo e($student->name); ?> — <?php echo e($student->nisn); ?> (<?php echo e($student->classMemberships->first()?->classroom?->name ?? 'Tanpa kelas aktif'); ?>)</option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="temporary_nisn" class="form-label sibk-form-label">NISN Sementara</label>
                            <input class="form-control sibk-form-control" id="temporary_nisn" name="temporary_nisn" value="<?php echo e(old('temporary_nisn')); ?>" maxlength="20" inputmode="numeric" placeholder="Isi bila murid belum tersedia">
                        </div>
                        <div class="col-md-3">
                            <label for="temporary_name" class="form-label sibk-form-label">Nama Sementara</label>
                            <input class="form-control sibk-form-control" id="temporary_name" name="temporary_name" value="<?php echo e(old('temporary_name')); ?>" maxlength="150" placeholder="Nama sesuai informasi awal">
                        </div>
                        <div class="col-md-4">
                            <label for="sumber" class="form-label sibk-form-label">Sumber Kasus</label>
                            <select class="form-select sibk-form-select" id="sumber" name="case_source_id" required>
                                <option value="">Pilih sumber kasus</option>
                                <?php $__currentLoopData = $caseSources; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $source): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($source->id); ?>" <?php if((string) old('case_source_id') === (string) $source->id): echo 'selected'; endif; ?>><?php echo e($source->label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="tanggal" class="form-label sibk-form-label">Tanggal Layanan</label>
                            <input type="date" class="form-control sibk-form-control" id="tanggal" name="service_date" value="<?php echo e(old('service_date', today()->toDateString())); ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label for="bidang" class="form-label sibk-form-label">Bidang Layanan BK</label>
                            <select class="form-select sibk-form-select" id="bidang" name="service_field_id" required>
                                <option value="">Pilih bidang layanan</option>
                                <?php $__currentLoopData = $serviceFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($field->id); ?>" <?php if((string) old('service_field_id') === (string) $field->id): echo 'selected'; endif; ?>><?php echo e($field->label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-12">
                            <label for="referrer" class="form-label sibk-form-label">Pihak Perujuk</label>
                            <input class="form-control sibk-form-control" id="referrer" name="referrer" value="<?php echo e(old('referrer')); ?>" placeholder="Isi bila sumber kasus berasal dari rujukan">
                        </div>
                    </div>
                </div>
            </div>

            <div class="sibk-panel mb-4 border-0 shadow-sm">
                <div class="sibk-panel__body p-4 p-md-5">
                    <h4 class="fs-5 mb-1 text-dark fw-bold">Informasi Kasus</h4>
                    <p class="text-muted small mb-4">Tuliskan informasi yang diperlukan untuk memulai penanganan.</p>
                    <div class="row g-4">
                        <div class="col-md-6"><label for="initial_info" class="form-label sibk-form-label">Informasi Awal</label><textarea class="form-control sibk-form-control" id="initial_info" name="initial_info" rows="4" required><?php echo e(old('initial_info')); ?></textarea></div>
                        <div class="col-md-6"><label for="initial_action" class="form-label sibk-form-label">Penanganan Awal</label><textarea class="form-control sibk-form-control" id="initial_action" name="initial_action" rows="4" required><?php echo e(old('initial_action')); ?></textarea></div>
                        <div class="col-12"><label for="internal_note" class="form-label sibk-form-label">Catatan Internal</label><textarea class="form-control sibk-form-control" id="internal_note" name="internal_note" rows="2" placeholder="Hanya terlihat bagi Guru BK yang memiliki penugasan aktif"><?php echo e(old('internal_note')); ?></textarea></div>
                    </div>
                </div>
            </div>

            <div class="sibk-panel mb-4 border-0 shadow-sm">
                <div class="sibk-panel__body p-4 p-md-5">
                    <h4 class="fs-5 mb-1 text-dark fw-bold">Data e-Tatib Terkait</h4>
                    <p class="text-muted small mb-4">Pilih record resmi dengan NISN yang sama. Wajib bila sumber kasus adalah e-Tatib.</p>
                    <?php $__empty_1 = true; $__currentLoopData = $etatibRecords; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $record): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <div class="form-check border rounded p-3 mb-2 ps-5" data-etatib-nisn="<?php echo e($record->nisn); ?>">
                            <input class="form-check-input" type="checkbox" name="etatib_record_ids[]" value="<?php echo e($record->id); ?>" id="etatib-<?php echo e($record->id); ?>" <?php if(in_array($record->id, old('etatib_record_ids', []))): echo 'checked'; endif; ?>>
                            <label class="form-check-label w-100" for="etatib-<?php echo e($record->id); ?>">
                                <span class="fw-semibold"><?php echo e($record->violation_type); ?></span>
                                <span class="text-muted small d-block">NISN <?php echo e($record->nisn); ?> · <?php echo e($record->occurred_at->locale('id')->translatedFormat('d M Y H:i')); ?> · <?php echo e($record->points); ?> poin</span>
                            </label>
                        </div>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <p class="text-muted mb-0">Belum ada data e-Tatib aktif. Admin IT perlu menjalankan sinkronisasi setelah connector tersedia.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-3 mb-5">
                <a href="<?php echo e(route('cases.index')); ?>" class="btn btn-outline-secondary px-4">Batal</a>
                <button type="submit" class="btn btn-primary px-4">Simpan Kasus</button>
            </div>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('extra-javascript'); ?>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const student = document.getElementById('student');
            const temporaryNisn = document.getElementById('temporary_nisn');
            const temporaryName = document.getElementById('temporary_name');
            const records = document.querySelectorAll('[data-etatib-nisn]');

            const refreshEtatib = () => {
                const masterNisn = student?.selectedOptions[0]?.dataset.nisn ?? '';
                const selectedNisn = masterNisn || temporaryNisn?.value.trim() || '';

                records.forEach((record) => {
                    const matches = selectedNisn !== '' && record.dataset.etatibNisn === selectedNisn;
                    record.classList.toggle('d-none', !matches);
                    if (!matches) record.querySelector('input').checked = false;
                });
            };

            student?.addEventListener('change', () => {
                if (student.value !== '') {
                    temporaryNisn.value = '';
                    temporaryName.value = '';
                }
                refreshEtatib();
            });
            temporaryNisn?.addEventListener('input', () => {
                if (temporaryNisn.value.trim() !== '') student.value = '';
                refreshEtatib();
            });
            refreshEtatib();
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app-2', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Tugas\pepe g\smt 2\pk\sibk\sibk\resources\views/pages/cases/create.blade.php ENDPATH**/ ?>