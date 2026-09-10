<?php $__env->startSection('page-title', ($isEdit ? 'Ubah' : 'Catat').' Konsultasi - Ruang BK'); ?>

<?php $__env->startSection('body'); ?>
    <div class="sibk-dashboard" data-page-id="PG-105">
        <div class="sibk-page-header mb-4">
            <div class="d-flex align-items-center gap-3">
                <a href="<?php echo e($isEdit ? route('consultations.show', $consultation) : route('cases.index', ['tab' => 'konsultasi'])); ?>" class="btn btn-icon btn-light" aria-label="Kembali">&larr;</a>
                <div class="sibk-page-header__copy m-0"><h1><?php echo e($isEdit ? 'Ubah' : 'Catat'); ?> Konsultasi</h1><p>Metadata, ringkasan umum, dan catatan privat disimpan terpisah.</p></div>
            </div>
        </div>

        <?php if($errors->any()): ?>
            <div class="alert alert-danger"><ul class="mb-0"><?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><li><?php echo e($error); ?></li><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></ul></div>
        <?php endif; ?>

        <form action="<?php echo e($isEdit ? route('consultations.update', $consultation) : route('consultations.store')); ?>" method="POST">
            <?php echo csrf_field(); ?>
            <?php if($isEdit): ?> <?php echo method_field('PATCH'); ?> <?php endif; ?>

            <div class="sibk-panel mb-4">
                <div class="sibk-panel__header p-4 pb-0"><h2 class="sibk-panel__title">Murid dan Konteks Layanan</h2></div>
                <div class="sibk-panel__body p-4 row g-4">
                    <?php if($isEdit): ?>
                        <div class="col-12"><label class="form-label">Murid</label><div class="form-control bg-light"><?php echo e($consultation->identityName()); ?> — NISN <?php echo e($consultation->identityNisn()); ?></div></div>
                    <?php else: ?>
                        <div class="col-12 col-md-6">
                            <label class="form-label" for="student_id">Murid Master</label>
                            <select class="form-select" id="student_id" name="student_id">
                                <option value="">Pilih murid atau isi identitas sementara</option>
                                <?php $__currentLoopData = $students; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $student): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($student->id); ?>" data-nisn="<?php echo e($student->nisn); ?>" <?php if((string) old('student_id', $preselectedStudentId) === (string) $student->id): echo 'selected'; endif; ?>><?php echo e($student->name); ?> — <?php echo e($student->nisn); ?> (<?php echo e($student->classMemberships->first()?->classroom?->name ?? 'Tanpa kelas aktif'); ?>)</option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </select>
                        </div>
                        <div class="col-12 col-md-3"><label class="form-label" for="temporary_nisn">NISN Sementara</label><input class="form-control" id="temporary_nisn" name="temporary_nisn" value="<?php echo e(old('temporary_nisn')); ?>" inputmode="numeric" maxlength="20"></div>
                        <div class="col-12 col-md-3"><label class="form-label" for="temporary_name">Nama Sementara</label><input class="form-control" id="temporary_name" name="temporary_name" value="<?php echo e(old('temporary_name')); ?>" maxlength="150"></div>
                    <?php endif; ?>

                    <div class="col-12 col-md-6">
                        <label class="form-label" for="case_id">Kasus Terkait</label>
                        <select class="form-select" id="case_id" name="case_id">
                            <option value="">Tidak terkait kasus khusus</option>
                            <?php $__currentLoopData = $cases; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $case): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <option value="<?php echo e($case->id); ?>" data-nisn="<?php echo e($case->identityNisn()); ?>" <?php if((string) old('case_id', $consultation?->case_id) === (string) $case->id): echo 'selected'; endif; ?>><?php echo e($case->registration_number); ?> — <?php echo e($case->identityName()); ?> (<?php echo e($case->status->label); ?>)</option>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="service_field_id">Jenis Layanan <span class="text-danger">*</span></label>
                        <select class="form-select" id="service_field_id" name="service_field_id" required><option value="">Pilih jenis</option><?php $__currentLoopData = $serviceFields; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $field): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($field->id); ?>" <?php if((string) old('service_field_id', $consultation?->service_field_id) === (string) $field->id): echo 'selected'; endif; ?>><?php echo e($field->label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select>
                    </div>
                    <div class="col-12 col-md-3">
                        <label class="form-label" for="status_id">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="status_id" name="status_id" required><option value="">Pilih status</option><?php $__currentLoopData = $consultationStatuses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $status): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($status->id); ?>" <?php if((string) old('status_id', $consultation?->status_id) === (string) $status->id): echo 'selected'; endif; ?>><?php echo e($status->label); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select>
                    </div>
                    <div class="col-12 col-md-8"><label class="form-label" for="topic">Topik / Permasalahan Awal <span class="text-danger">*</span></label><input class="form-control" id="topic" name="topic" value="<?php echo e(old('topic', $consultation?->topic)); ?>" maxlength="250" required></div>
                    <div class="col-12 col-md-4"><label class="form-label" for="referral_source">Sumber Rujukan</label><input class="form-control" id="referral_source" name="referral_source" value="<?php echo e(old('referral_source', $consultation?->referral_source)); ?>" maxlength="150"></div>
                </div>
            </div>

            <div class="sibk-panel mb-4">
                <div class="sibk-panel__header p-4 pb-0"><h2 class="sibk-panel__title">Jadwal dan Ringkasan Umum</h2></div>
                <div class="sibk-panel__body p-4 row g-4">
                    <div class="col-12 col-md-4"><label class="form-label" for="session_date">Tanggal Sesi <span class="text-danger">*</span></label><input type="date" class="form-control" id="session_date" name="session_date" value="<?php echo e(old('session_date', $consultation?->session_date?->format('Y-m-d') ?? today()->format('Y-m-d'))); ?>" required></div>
                    <div class="col-6 col-md-2"><label class="form-label" for="starts_at">Jam Mulai</label><input type="time" class="form-control" id="starts_at" name="starts_at" value="<?php echo e(old('starts_at', $consultation?->starts_at ? substr($consultation->starts_at, 0, 5) : null)); ?>"></div>
                    <div class="col-6 col-md-2"><label class="form-label" for="ends_at">Jam Selesai</label><input type="time" class="form-control" id="ends_at" name="ends_at" value="<?php echo e(old('ends_at', $consultation?->ends_at ? substr($consultation->ends_at, 0, 5) : null)); ?>"></div>
                    <div class="col-12 col-md-4"><label class="form-label" for="follow_up_date">Jadwal Tindak Lanjut</label><input type="date" class="form-control" id="follow_up_date" name="follow_up_date" value="<?php echo e(old('follow_up_date', $consultation?->follow_up_date?->format('Y-m-d'))); ?>"></div>
                    <div class="col-12"><label class="form-label" for="general_summary">Ringkasan Umum</label><textarea class="form-control" id="general_summary" name="general_summary" rows="4" placeholder="Ringkasan yang diizinkan untuk tata kelola umum"><?php echo e(old('general_summary', $consultation?->general_summary)); ?></textarea><div class="form-text">Wajib untuk sesi berstatus Terlaksana.</div></div>
                </div>
            </div>

            <div class="sibk-panel mb-4">
                <div class="sibk-panel__header p-4 pb-0"><h2 class="sibk-panel__title">Catatan Profesional Privat</h2><p class="sibk-panel__subtitle">Bagian ini hanya dapat dibaca Guru BK dengan kewenangan profesional yang sah.</p></div>
                <div class="sibk-panel__body p-4 row g-4">
                    <div class="col-12"><label class="form-label" for="sensitive_content">Uraian / Proses Bimbingan</label><textarea class="form-control" id="sensitive_content" name="sensitive_content" rows="5"><?php echo e(old('sensitive_content', $consultation?->privateNote?->sensitive_content)); ?></textarea></div>
                    <div class="col-12 col-md-6"><label class="form-label" for="internal_note">Catatan Internal</label><textarea class="form-control" id="internal_note" name="internal_note" rows="4"><?php echo e(old('internal_note', $consultation?->privateNote?->internal_note)); ?></textarea></div>
                    <div class="col-12 col-md-6"><label class="form-label" for="conclusion">Kesimpulan / Solusi</label><textarea class="form-control" id="conclusion" name="conclusion" rows="4"><?php echo e(old('conclusion', $consultation?->privateNote?->conclusion)); ?></textarea></div>
                    <div class="col-12"><label class="form-label" for="follow_up_plan">Rencana Lanjutan</label><textarea class="form-control" id="follow_up_plan" name="follow_up_plan" rows="3"><?php echo e(old('follow_up_plan', $consultation?->privateNote?->follow_up_plan)); ?></textarea></div>
                </div>
            </div>

            <div class="sibk-panel mb-4"><div class="sibk-panel__body p-4"><h2 class="fs-6 fw-bold">Dokumen Pendukung</h2><p class="text-muted mb-0">Unggahan dokumen belum tersedia sampai kebijakan format, akses, dan retensi DEP-06 disahkan.</p></div></div>

            <div class="d-flex justify-content-end gap-2 mb-5"><a href="<?php echo e($isEdit ? route('consultations.show', $consultation) : route('cases.index', ['tab' => 'konsultasi'])); ?>" class="btn btn-outline-secondary">Batal</a><button class="btn btn-primary" type="submit">Simpan Konsultasi</button></div>
        </form>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('extra-javascript'); ?>
    <?php if(!$isEdit): ?>
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const student = document.getElementById('student_id');
                const nisn = document.getElementById('temporary_nisn');
                const name = document.getElementById('temporary_name');
                student?.addEventListener('change', () => { if (student.value) { nisn.value = ''; name.value = ''; } });
                nisn?.addEventListener('input', () => { if (nisn.value.trim()) student.value = ''; });
            });
        </script>
    <?php endif; ?>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app-2', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Tugas\pepe g\smt 2\pk\sibk\sibk\resources\views\pages\consultations\create.blade.php ENDPATH**/ ?>