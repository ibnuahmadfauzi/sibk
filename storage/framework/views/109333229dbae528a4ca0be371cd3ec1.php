<?php $__env->startSection('page-title', 'Detail Konsultasi - Ruang BK'); ?>

<?php $__env->startSection('body'); ?>
    <div class="sibk-dashboard">
        <?php if(session('success')): ?><div class="alert alert-success"><?php echo e(session('success')); ?></div><?php endif; ?>
        <div class="sibk-page-header d-flex flex-wrap justify-content-between gap-3 mb-4">
            <div class="sibk-page-header__copy"><a href="<?php echo e(route('cases.index', ['tab' => 'konsultasi'])); ?>" class="text-decoration-none small">&larr; Kembali ke daftar</a><h1>Detail Sesi Bimbingan</h1><p>Nomor Sesi: <?php echo e($consultation->registration_number); ?></p></div>
            <div class="d-flex gap-2"><?php if(auth()->user()?->hasRole('guru_bk')): ?><a href="<?php echo e(route('corrections.create', ['target_type' => 'consultation', 'target_id' => $consultation->id])); ?>" class="btn btn-outline-secondary">Ajukan Koreksi</a><?php endif; ?><button type="button" data-print-report class="btn btn-outline-secondary">Cetak Riwayat</button><?php if($canUpdateConsultation): ?><a href="<?php echo e(route('consultations.edit', $consultation)); ?>" class="btn btn-primary">Edit Data Sesi</a><?php endif; ?></div>
        </div>

        <div class="row g-4">
            <div class="col-12 col-lg-4">
                <div class="sibk-panel h-100"><div class="sibk-panel__header p-4 border-bottom"><h2 class="fs-5 m-0">Informasi Murid & Jadwal</h2></div><div class="sibk-panel__body p-4">
                    <div class="p-3 bg-light rounded mb-4"><strong class="d-block"><?php echo e($consultation->identityName()); ?></strong><span class="small text-muted">NISN <?php echo e($consultation->identityNisn()); ?><?php if($consultation->temporary_student_id): ?> &bull; Identitas sementara <?php endif; ?></span></div>
                    <p><span class="text-muted small d-block">Status Sesi</span><span class="sibk-badge sibk-badge--primary"><?php echo e($consultation->status->label); ?></span></p>
                    <p><span class="text-muted small d-block">Jadwal Pelaksanaan</span><strong><?php echo e($consultation->session_date->locale('id')->translatedFormat('d F Y')); ?></strong><?php if($consultation->starts_at): ?><span class="small text-muted d-block"><?php echo e(substr($consultation->starts_at, 0, 5)); ?><?php if($consultation->ends_at): ?>–<?php echo e(substr($consultation->ends_at, 0, 5)); ?><?php endif; ?> WIB</span><?php endif; ?></p>
                    <p><span class="text-muted small d-block">Guru BK Pencatat</span><?php echo e($consultation->counselor->name); ?></p>
                    <p><span class="text-muted small d-block">Jenis Layanan</span><?php echo e($consultation->serviceField->label); ?></p>
                    <p><span class="text-muted small d-block">Sumber Rujukan</span><?php echo e($consultation->referral_source ?: '—'); ?></p>
                    <p><span class="text-muted small d-block">Kasus Terkait</span><?php if($consultation->case): ?><a href="<?php echo e(route('cases.show', $consultation->case)); ?>"><?php echo e($consultation->case->registration_number); ?></a><?php else: ?> — <?php endif; ?></p>
                    <p class="mb-0"><span class="text-muted small d-block">Tindak Lanjut</span><?php echo e($consultation->follow_up_date?->locale('id')->translatedFormat('d F Y') ?? '—'); ?></p>
                </div></div>
            </div>
            <div class="col-12 col-lg-8">
                <div class="sibk-panel mb-4"><div class="sibk-panel__header p-4 border-bottom"><h2 class="fs-5 m-0">Ringkasan Umum</h2></div><div class="sibk-panel__body p-4">
                    <h3 class="fs-6 fw-bold">Topik / Permasalahan Awal</h3><p><?php echo e($consultation->topic); ?></p>
                    <h3 class="fs-6 fw-bold">Ringkasan yang Diizinkan</h3><p class="mb-0"><?php echo e($consultation->general_summary ?: 'Belum ada ringkasan umum.'); ?></p>
                </div></div>

                <?php if($canViewSensitive): ?>
                    <div class="sibk-panel"><div class="sibk-panel__header p-4 border-bottom"><h2 class="fs-5 m-0">Catatan Profesional Privat</h2></div><div class="sibk-panel__body p-4">
                        <h3 class="fs-6 fw-bold">Uraian / Proses Bimbingan</h3><p><?php echo e($consultation->privateNote?->sensitive_content ?: '—'); ?></p>
                        <h3 class="fs-6 fw-bold">Catatan Internal</h3><p><?php echo e($consultation->privateNote?->internal_note ?: '—'); ?></p>
                        <h3 class="fs-6 fw-bold">Kesimpulan / Solusi</h3><p><?php echo e($consultation->privateNote?->conclusion ?: '—'); ?></p>
                        <h3 class="fs-6 fw-bold">Rencana Lanjutan</h3><p class="mb-0"><?php echo e($consultation->privateNote?->follow_up_plan ?: '—'); ?></p>
                    </div></div>
                <?php else: ?>
                    <div class="alert alert-info">Catatan profesional privat hanya tersedia bagi Guru BK dengan kewenangan atas murid.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app-2', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Tugas\pepe g\smt 2\pk\sibk\sibk\resources\views\pages\consultations\show.blade.php ENDPATH**/ ?>