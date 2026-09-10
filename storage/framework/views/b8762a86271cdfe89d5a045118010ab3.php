<?php $__env->startSection('page-title', 'Pusat Laporan - Ruang BK'); ?>

<?php $__env->startSection('body'); ?>
<div class="sibk-dashboard" data-page-id="PG-301">
    <div class="sibk-page-header mb-4"><div class="sibk-page-header__copy"><h1>Pusat Laporan</h1><p>Pilih laporan yang tersedia sesuai kewenangan Anda.</p></div></div>
    <div class="row g-4 sibk-report-grid">
        <?php $__currentLoopData = $reports; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $report): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
            <div class="<?php echo e($loop->last && $loop->count % 2 !== 0 ? 'col-12 col-md-6 mx-auto' : 'col-12 col-md-6'); ?>">
                <article class="sibk-report-card sibk-report-card--<?php echo e($report['tone']); ?> h-100">
                    <div class="sibk-report-card__body">
                        <div class="sibk-report-card__icon-wrapper sibk-icon-tone--<?php echo e($report['tone']); ?>" aria-hidden="true">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                                <?php switch($report['icon']):
                                    case ('student'): ?><circle cx="12" cy="7" r="3"/><path d="M5.5 19c.7-3.1 2.6-4.5 6.5-4.5s5.8 1.4 6.5 4.5"/><?php break; ?>
                                    <?php case ('classroom'): ?><path d="M4 6h16v12H4V6zM8 3v5M16 3v5M4 10h16"/><?php break; ?>
                                    <?php case ('points'): ?><path d="M7 4v16M17 4v16M4 9h16M4 15h16"/><?php break; ?>
                                    <?php case ('consultation'): ?><path d="M4 6.5A1.5 1.5 0 0 1 5.5 5h13A1.5 1.5 0 0 1 20 6.5v8a1.5 1.5 0 0 1-1.5 1.5H10l-5 4v-4H5.5A1.5 1.5 0 0 1 4 14.5v-8z"/><?php break; ?>
                                    <?php case ('follow-up'): ?><rect x="4" y="5.5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16M8 15l2.5 2.5 5.5-5"/><?php break; ?>
                                    <?php case ('recap'): ?><path d="M6 3.5h7.5L18 8v12.5H6V3.5zM13.5 3.5V8H18M9 12h5M9 16h5"/><?php break; ?>
                                    <?php default: ?><path d="M8 4h8v4.5a4 4 0 0 1-8 0V4zM8 5.5H5a1.5 1.5 0 0 0 1.5 2.5H8M16 5.5h3a1.5 1.5 0 0 1-1.5 2.5H16M12 12.5v4.5M8.5 19.5h7"/>
                                <?php endswitch; ?>
                            </svg>
                        </div>
                        <div class="sibk-report-card__content"><div class="d-flex align-items-center gap-2 mb-1"><h2 class="sibk-report-card__title mb-0"><?php echo e($report['title']); ?></h2><span class="badge bg-light text-dark"><?php echo e($report['badge']); ?></span></div><p class="sibk-report-card__desc"><?php echo e($report['description']); ?></p></div>
                    </div>
                    <div class="sibk-report-card__footer"><a href="<?php echo e(route('reports.preview', ['type' => $report['id']])); ?>" class="sibk-report-card__action" aria-label="Buka <?php echo e($report['title']); ?>"><span><?php echo e(($report['pending'] ?? false) ? 'Lihat status' : 'Buka'); ?></span><svg viewBox="0 0 24 24" width="16" height="16"><path d="M5 12h14M12 5l7 7-7 7"/></svg></a></div>
                </article>
            </div>
        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app-2', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Tugas\pepe g\smt 2\pk\sibk\sibk\resources\views\pages\reports\index.blade.php ENDPATH**/ ?>