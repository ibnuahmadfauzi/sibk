<?php $__env->startSection('page-title', 'Akun Saya - Ruang BK'); ?>

<?php $__env->startSection('body'); ?>
    <div class="sibk-dashboard">
        <!-- Header -->
        <div class="sibk-page-header d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
            <div class="sibk-page-header__copy">
                <h1>Akun Saya</h1>
                <p>Identitas akun yang digunakan untuk masuk ke Ruang BK.</p>
            </div>
            <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('manageDataMaster')): ?>
                <div class="d-flex gap-2">
                    <a href="<?php echo e(route('admin.users.index')); ?>" class="btn btn-primary d-inline-flex align-items-center gap-2">
                        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"></line>
                            <line x1="5" y1="12" x2="19" y2="12"></line>
                        </svg>
                        Kelola Akun
                    </a>
                </div>
            <?php endif; ?>
        </div>


        <div class="row g-4">
            <!-- Kolom Kiri: Profil & Info Personal -->
            <div class="col-lg-7 col-xl-8">
                <div class="sibk-panel h-100">
                    <div class="sibk-panel__header sibk-account-panel__header">
                        <h3 class="sibk-panel__title">Informasi Akun</h3>
                        <p class="sibk-panel__subtitle">Identitas akun yang digunakan untuk masuk ke Ruang BK.</p>
                    </div>
                    
                    <div class="sibk-panel__body p-4">
                        <div class="sibk-account-profile">
                            <div class="sibk-account-profile__avatar">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                                    <circle cx="12" cy="7" r="4"/>
                                </svg>
                            </div>
                            <div class="sibk-account-profile__info">
                                <h4 class="sibk-account-profile__name"><?php echo e($account['name']); ?></h4>
                                <p class="sibk-account-profile__email"><?php echo e($account['email']); ?></p>
                            </div>
                        </div>

                        <table class="table sibk-account-table mb-0">
                            <tbody>
                                <tr>
                                    <th scope="row">Peran</th>
                                    <td><?php echo e(implode(', ', $account['roles']) ?: 'Belum memiliki peran'); ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">Email</th>
                                    <td><?php echo e($account['email']); ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">Status akun</th>
                                    <td><span class="sibk-badge <?php echo e($account['status'] === 'Aktif' ? 'sibk-badge--success' : 'sibk-badge--warning'); ?>"><?php echo e($account['status']); ?></span></td>
                                </tr>
                                <tr>
                                    <th scope="row">Login terakhir</th>
                                    <td><?php echo e($account['last_login_at']?->locale('id')->translatedFormat('d F Y H.i') ?? 'Belum tercatat'); ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">Tahun ajaran aktif</th>
                                    <td><?php echo e($account['academic_year']); ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Kolom Kanan: Keamanan & Info -->
            <div class="col-lg-5 col-xl-4 d-flex flex-column gap-4">
                <div class="sibk-panel">
                    <div class="sibk-panel__header sibk-account-panel__header">
                        <h3 class="sibk-panel__title">Keamanan Akun</h3>
                        <p class="sibk-panel__subtitle">Kelola keamanan akun yang sedang digunakan.</p>
                    </div>

                    <div class="sibk-panel__body p-4">
                        <div class="sibk-security-info">
                            <div class="sibk-security-info__icon sibk-icon-tone--primary">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="18" height="11" x="3" y="11" rx="2" ry="2"/>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"/>
                                </svg>
                            </div>
                            <div class="sibk-security-info__text">
                                <h5 class="sibk-security-info__title">Kata sandi</h5>
                                <p class="sibk-security-info__desc">Perubahan kata sandi dikelola oleh Admin IT.</p>
                            </div>
                        </div>

                        <form action="<?php echo e(route('logout')); ?>" method="POST">
                            <?php echo csrf_field(); ?>
                            <button type="submit" class="btn w-100 sibk-btn-logout">
                                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                                    <polyline points="16 17 21 12 16 7"/>
                                    <line x1="21" x2="9" y1="12" y2="12"/>
                                </svg>
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>

                <div class="sibk-account-info-box">
                    <h4 class="sibk-account-info-box__title">Akun dan akses</h4>
                    <p class="sibk-account-info-box__text">
                        Untuk keamanan akun dan pemutakhiran data yang tidak bisa Anda perbarui dari halaman ini,
                        silakan menghubungi administrator sistem terkait.
                    </p>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app-2', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Projects\PPG\sibk\resources\views/pages/account/index.blade.php ENDPATH**/ ?>