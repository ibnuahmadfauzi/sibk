<div class="sibk-dashboard" data-page-id="PG-002" data-dashboard-role="<?php echo e($dashboard['role_key']); ?>">

    <header class="sibk-page-header d-flex flex-wrap justify-content-between gap-3">
        <div class="sibk-page-header__copy">
            <h1 id="dashboard-title">Dashboard</h1>
            <p><?php echo e($dashboard['description']); ?></p>
            <small class="text-muted"><?php echo e($dashboard['scope']); ?></small>
        </div>
        <?php if($years->isNotEmpty()): ?>
            <form method="GET" action="<?php echo e(route('dashboard.preview')); ?>" class="d-flex align-items-end gap-2 sibk-header-filter">
                <div class="flex-grow-1"><label for="academic_year_id" class="form-label small">Tahun Ajaran</label><select class="form-select" id="academic_year_id" name="academic_year_id"><?php $__currentLoopData = $years; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $year): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><option value="<?php echo e($year->id); ?>" <?php if($activeYear?->id === $year->id): echo 'selected'; endif; ?>><?php echo e($year->name); ?></option><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></select></div>
                <button class="btn btn-outline-primary text-nowrap">Terapkan</button>
            </form>
        <?php endif; ?>
        
        <?php if($dashboard['read_only']): ?>
            <div class="alert sibk-read-only-notice" role="status">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3 5 6v5c0 4.6 2.9 8.5 7 10 4.1-1.5 7-5.4 7-10V6l-7-3Z"/><path d="M12 8v4M12 16h.01"/></svg>
                <div><strong>Tampilan koordinasi hanya-baca</strong><p>Anda hanya melihat agregat yang diizinkan dan kasus yang secara eksplisit dikoordinasikan. Isi konsultasi sensitif serta aksi perubahan tidak ditampilkan.</p></div>
            </div>
        <?php endif; ?>
    </header>

        <section aria-label="Statistik utama">
            <div class="row g-3 sibk-stat-row">
                <?php $__currentLoopData = $dashboard['stats']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $stat): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                    <div class="col-12 col-sm-6 col-xl-3">
                        <article class="sibk-stat-card sibk-tone--<?php echo e($stat['tone']); ?>">
                            <div class="sibk-stat-card__inner">
                                <div class="sibk-stat-card__icon-col">
                                    <div class="sibk-stat-card__icon" aria-hidden="true">
                                        <?php switch($stat['kind']):
                                            case ('students'): ?>
                                                <svg viewBox="0 0 24 24"><circle cx="9" cy="8" r="3"/><path d="M3 20c0-4 2.7-7 6-7s6 3 6 7"/><circle cx="17" cy="9" r="2"/><path d="M15 15c3.6 0 6 2.2 6 5"/></svg>
                                                <?php break; ?>
                                            <?php case ('etatib'): ?>
                                                <svg viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/><path d="M8 14h.01"/><path d="M12 14h.01"/><path d="M16 14h.01"/><path d="M8 18h.01"/><path d="M12 18h.01"/><path d="M16 18h.01"/></svg>
                                                <?php break; ?>
                                            <?php case ('cases'): ?>
                                                <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                                <?php break; ?>
                                            <?php default: ?>
                                                <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                        <?php endswitch; ?>
                                    </div>
                                </div>
                                <div class="sibk-stat-card__content-col">
                                    <h2 class="sibk-stat-card__label"><?php echo e($stat['label']); ?></h2>
                                    <strong class="sibk-stat-card__value"><?php echo e($stat['value']); ?></strong>
                                    
                                    <?php if(isset($stat['delta'])): ?>
                                        <span class="sibk-stat-delta sibk-stat-delta--<?php echo e($stat['delta_tone'] ?? 'neutral'); ?>">
                                            <?php if(($stat['delta_tone'] ?? '') === 'up'): ?>
                                                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 19V5M5 12l7-7 7 7"/></svg>
                                            <?php elseif(($stat['delta_tone'] ?? '') === 'down'): ?>
                                                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 5v14M19 12l-7 7-7-7"/></svg>
                                            <?php endif; ?>
                                            <?php echo e($stat['delta']); ?>

                                        </span>
                                    <?php else: ?>
                                        <span class="sibk-stat-meta"><?php echo e($stat['meta']); ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </article>
                    </div>
                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
            </div>
        </section>

        <div class="row g-3 mt-1">
            
            <div class="col-12 col-xl-7">
                <section class="sibk-panel sibk-panel--inset sibk-tindak-lanjut-panel" aria-labelledby="tindak-lanjut-title">
                    <header class="sibk-panel__header">
                        <div class="sibk-panel__title-group">
                            <svg class="sibk-panel__icon" aria-hidden="true" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
                            <h2 id="tindak-lanjut-title"><?php echo e($dashboard['schedule_title']); ?></h2>
                        </div>
                        <a href="<?php echo e($dashboard['schedule_url']); ?>" class="btn btn-sm btn-outline-primary sibk-panel__action">
                            Lihat semua <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    </header>

                    <?php if(empty($dashboard['tindak_lanjut'])): ?>
                        <?php if (isset($component)) { $__componentOriginal074a021b9d42f490272b5eefda63257c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal074a021b9d42f490272b5eefda63257c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty-state','data' => ['title' => 'Tidak ada tindak lanjut','description' => 'Tidak ada jadwal tindak lanjut dalam waktu dekat.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Tidak ada tindak lanjut','description' => 'Tidak ada jadwal tindak lanjut dalam waktu dekat.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal074a021b9d42f490272b5eefda63257c)): ?>
<?php $attributes = $__attributesOriginal074a021b9d42f490272b5eefda63257c; ?>
<?php unset($__attributesOriginal074a021b9d42f490272b5eefda63257c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal074a021b9d42f490272b5eefda63257c)): ?>
<?php $component = $__componentOriginal074a021b9d42f490272b5eefda63257c; ?>
<?php unset($__componentOriginal074a021b9d42f490272b5eefda63257c); ?>
<?php endif; ?>
                    <?php else: ?>
                        <div class="sibk-list-group">
                            <?php $__currentLoopData = $dashboard['tindak_lanjut']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $item): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <a href="<?php echo e($item['url']); ?>" class="sibk-list-item text-decoration-none">
                                    <div class="sibk-list-item__date-box">
                                        <strong><?php echo e($item['date']); ?></strong>
                                        <span><?php echo e($item['month']); ?><br><?php echo e($item['year']); ?></span>
                                    </div>
                                    <div class="sibk-list-item__content">
                                        <strong><?php echo e($item['code']); ?></strong>
                                        <span><?php echo e($item['title']); ?></span>
                                        <small><?php echo e($item['context_label']); ?></small>
                                    </div>
                                    <div class="sibk-list-item__trailing">
                                        <span class="badge sibk-icon-tone--<?php echo e($item['status_tone']); ?>"><?php echo e($item['status']); ?></span>
                                        <svg class="sibk-list-item__chevron" aria-hidden="true" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                                    </div>
                                </a>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>

            
            <div class="col-12 col-xl-5">
                <section class="sibk-panel sibk-panel--raised sibk-activity-panel" aria-labelledby="activity-title">
                    <header class="sibk-panel__header">
                        <div class="sibk-panel__title-group">
                            <svg class="sibk-panel__icon" aria-hidden="true" viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                            <h2 id="activity-title">Aktivitas terbaru</h2>
                        </div>
                        <a href="<?php echo e(route('history.index')); ?>" class="btn btn-sm btn-outline-primary sibk-panel__action">
                            Lihat semua <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m9 18 6-6-6-6"/></svg>
                        </a>
                    </header>
                    
                    <?php if(empty($dashboard['activities'])): ?>
                        <?php if (isset($component)) { $__componentOriginal074a021b9d42f490272b5eefda63257c = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal074a021b9d42f490272b5eefda63257c = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.empty-state','data' => ['title' => 'Belum ada aktivitas','description' => 'Aktivitas sistem yang relevan dengan Anda akan tampil di sini.']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('empty-state'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['title' => 'Belum ada aktivitas','description' => 'Aktivitas sistem yang relevan dengan Anda akan tampil di sini.']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal074a021b9d42f490272b5eefda63257c)): ?>
<?php $attributes = $__attributesOriginal074a021b9d42f490272b5eefda63257c; ?>
<?php unset($__attributesOriginal074a021b9d42f490272b5eefda63257c); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal074a021b9d42f490272b5eefda63257c)): ?>
<?php $component = $__componentOriginal074a021b9d42f490272b5eefda63257c; ?>
<?php unset($__componentOriginal074a021b9d42f490272b5eefda63257c); ?>
<?php endif; ?>
                    <?php else: ?>
                        <div class="sibk-activity-list">
                            <?php $__currentLoopData = $dashboard['activities']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $activity): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <article class="sibk-activity-row">
                                    <div class="sibk-activity-row__icon-circle sibk-icon-tone--<?php echo e($activity['tone']); ?>" aria-hidden="true">
                                        <?php if($activity['icon'] === 'case-new'): ?>
                                            <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
                                        <?php elseif($activity['icon'] === 'followup'): ?>
                                            <svg viewBox="0 0 24 24"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                                        <?php elseif($activity['icon'] === 'etatib'): ?>
                                            <svg viewBox="0 0 24 24"><path d="M12 20h9"/><path d="M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                                        <?php else: ?>
                                            <svg viewBox="0 0 24 24"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                                        <?php endif; ?>
                                    </div>
                                    <div class="sibk-activity-row__content">
                                        <strong><?php echo e($activity['title']); ?></strong>
                                        <span><?php echo e($activity['context']); ?></span>
                                    </div>
                                    <time class="sibk-activity-row__time"><?php echo e($activity['time']); ?></time>
                                </article>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    <?php endif; ?>
                </section>
            </div>
        </div>

        
        <div class="sibk-dashboard-footer">
            <div class="sibk-quick-actions"><?php $__currentLoopData = $dashboard['quick_actions']; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $action): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?><a class="btn <?php echo e($action['primary'] ? 'btn-primary' : 'btn-outline-primary'); ?>" href="<?php echo e($action['url']); ?>"><?php echo e($action['label']); ?></a><?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?></div>
            
            <div class="sibk-dashboard-deco" aria-hidden="true">
                <p>Konseling Hari Ini,<br>Masa Depan yang Lebih Baik</p>
                <svg class="sibk-dashboard-deco__leaf" viewBox="0 0 120 160" fill="none">
                    <path d="M60 155 C60 155 5 120 8 60 C10 20 35 5 50 18 C57 24 60 60 60 155Z" fill="currentColor" opacity="0.18"/>
                    <path d="M60 155 C60 155 115 120 112 60 C110 20 85 5 70 18 C63 24 60 60 60 155Z" fill="currentColor" opacity="0.11"/>
                    <path d="M60 155 C60 155 30 110 35 70 C38 48 52 42 60 52 C68 42 82 48 85 70 C90 110 60 155 60 155Z" fill="currentColor" opacity="0.14"/>
                </svg>
            </div>
        </div>
</div>
<?php /**PATH D:\cadangan\Tugas Kuliah\PGG\Sesmter 2\PK\Website BK\sibk\resources\views/pages/dashboard/html.blade.php ENDPATH**/ ?>