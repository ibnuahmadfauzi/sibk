<?php $__env->startSection('page-title', 'Kelola Akun - Ruang BK'); ?>

<?php $__env->startSection('body'); ?>
    <div class="sibk-dashboard" data-page-id="ADMIN-USERS">
        <div class="sibk-page-header mb-4">
            <div class="sibk-page-header__copy">
                <h1>Kelola Akun</h1>
                <p>Buat akun, tetapkan satu atau beberapa peran, serta aktifkan atau nonaktifkan akses pengguna.</p>
            </div>
        </div>

        <?php if(session('success')): ?>
            <div class="alert alert-success" role="status"><?php echo e(session('success')); ?></div>
        <?php endif; ?>

        <?php if($errors->any()): ?>
            <div class="alert alert-danger" role="alert">
                <strong>Periksa kembali data berikut:</strong>
                <ul class="mb-0 mt-2">
                    <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <li><?php echo e($error); ?></li>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="sibk-panel mb-4">
            <div class="sibk-panel__header">
                <div>
                    <h2 class="sibk-panel__title">Tambah Akun</h2>
                    <p class="sibk-panel__subtitle">Gunakan alamat email resmi dan pilih minimal satu peran.</p>
                </div>
            </div>
            <div class="sibk-panel__body p-4">
                <form action="<?php echo e(route('admin.users.store')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <div class="row g-3">
                        <div class="col-12 col-md-6">
                            <label for="new-name" class="form-label sibk-form-label">Nama <span class="text-danger">*</span></label>
                            <input id="new-name" class="form-control sibk-form-control" name="name" value="<?php echo e(old('name')); ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="new-email" class="form-label sibk-form-label">Email <span class="text-danger">*</span></label>
                            <input id="new-email" class="form-control sibk-form-control" type="email" name="email" value="<?php echo e(old('email')); ?>" required>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="new-password" class="form-label sibk-form-label">Kata Sandi <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input id="new-password" class="form-control sibk-form-control pe-5" type="password" name="password" minlength="8" required>
                                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y pe-3 text-muted p-0 border-0" data-toggle-password="new-password" aria-label="Tampilkan kata sandi">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="col-12 col-md-6">
                            <label for="new-password-confirmation" class="form-label sibk-form-label">Konfirmasi Kata Sandi <span class="text-danger">*</span></label>
                            <div class="position-relative">
                                <input id="new-password-confirmation" class="form-control sibk-form-control pe-5" type="password" name="password_confirmation" minlength="8" required>
                                <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y pe-3 text-muted p-0 border-0" data-toggle-password="new-password-confirmation" aria-label="Tampilkan konfirmasi kata sandi">
                                    <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                        <circle cx="12" cy="12" r="3"></circle>
                                    </svg>
                                </button>
                            </div>
                        </div>
                        <div class="col-12">
                            <span class="form-label sibk-form-label d-block">Peran <span class="text-danger">*</span></span>
                            <div class="d-flex flex-wrap gap-3">
                                <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" name="roles[]" value="<?php echo e($role->slug); ?>" id="new-role-<?php echo e($role->slug); ?>" <?php if(in_array($role->slug, old('roles', []), true)): echo 'checked'; endif; ?>>
                                        <label class="form-check-label" for="new-role-<?php echo e($role->slug); ?>"><?php echo e($role->name); ?></label>
                                    </div>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </div>
                        </div>
                        <div class="col-12 d-flex justify-content-end">
                            <button class="btn btn-primary px-4" type="submit">Buat Akun</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <div class="sibk-panel">
            <div class="sibk-panel__header">
                <div>
                    <h2 class="sibk-panel__title">Daftar Akun</h2>
                    <p class="sibk-panel__subtitle">Perubahan status menggantikan penghapusan akun permanen.</p>
                </div>
                <span class="sibk-badge sibk-badge--primary"><?php echo e($users->total()); ?> akun</span>
            </div>
            <div class="sibk-panel__body p-4">
                <div class="d-flex flex-column gap-3">
                    <?php $__empty_1 = true; $__currentLoopData = $users; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $managedUser): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); $__empty_1 = false; ?>
                        <form action="<?php echo e(route('admin.users.update', $managedUser)); ?>" method="POST" class="border rounded-3 p-3">
                            <?php echo csrf_field(); ?>
                            <?php echo method_field('PATCH'); ?>
                            <div class="d-flex flex-wrap justify-content-between gap-2 mb-3">
                                <div>
                                    <strong><?php echo e($managedUser->name); ?></strong>
                                    <div class="small text-muted">Login terakhir: <?php echo e($managedUser->last_login_at?->locale('id')->translatedFormat('d M Y H.i') ?? 'Belum tercatat'); ?></div>
                                </div>
                                <span class="sibk-badge <?php echo e($managedUser->is_active ? 'sibk-badge--success' : 'sibk-badge--warning'); ?>">
                                    <?php echo e($managedUser->is_active ? 'Aktif' : 'Nonaktif'); ?>

                                </span>
                            </div>
                            <div class="row g-3">
                                <div class="col-12 col-lg-4">
                                    <label for="name-<?php echo e($managedUser->id); ?>" class="form-label sibk-form-label">Nama</label>
                                    <input id="name-<?php echo e($managedUser->id); ?>" class="form-control sibk-form-control" name="name" value="<?php echo e($managedUser->name); ?>" required>
                                </div>
                                <div class="col-12 col-lg-4">
                                    <label for="email-<?php echo e($managedUser->id); ?>" class="form-label sibk-form-label">Email</label>
                                    <input id="email-<?php echo e($managedUser->id); ?>" class="form-control sibk-form-control" type="email" name="email" value="<?php echo e($managedUser->email); ?>" required>
                                </div>
                                <div class="col-12 col-lg-4">
                                    <label for="password-<?php echo e($managedUser->id); ?>" class="form-label sibk-form-label">Kata Sandi Baru</label>
                                    <div class="position-relative">
                                        <input id="password-<?php echo e($managedUser->id); ?>" class="form-control sibk-form-control pe-5" type="password" name="password" minlength="8" placeholder="Kosongkan jika tidak diubah">
                                        <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y pe-3 text-muted p-0 border-0" data-toggle-password="password-<?php echo e($managedUser->id); ?>" aria-label="Tampilkan kata sandi baru">
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </button>
                                    </div>
                                    <div class="position-relative mt-2">
                                        <input id="password_confirmation-<?php echo e($managedUser->id); ?>" type="password" name="password_confirmation" class="form-control sibk-form-control pe-5" minlength="8" aria-label="Konfirmasi kata sandi baru" placeholder="Konfirmasi kata sandi baru">
                                        <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y pe-3 text-muted p-0 border-0" data-toggle-password="password_confirmation-<?php echo e($managedUser->id); ?>" aria-label="Tampilkan konfirmasi kata sandi baru">
                                            <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
                                                <circle cx="12" cy="12" r="3"></circle>
                                            </svg>
                                        </button>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-8">
                                    <span class="form-label sibk-form-label d-block">Peran</span>
                                    <div class="d-flex flex-wrap gap-3">
                                        <?php $__currentLoopData = $roles; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $role): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="roles[]" value="<?php echo e($role->slug); ?>" id="role-<?php echo e($managedUser->id); ?>-<?php echo e($role->slug); ?>" <?php if($managedUser->roles->contains('slug', $role->slug)): echo 'checked'; endif; ?>>
                                                <label class="form-check-label" for="role-<?php echo e($managedUser->id); ?>-<?php echo e($role->slug); ?>"><?php echo e($role->name); ?></label>
                                            </div>
                                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                    </div>
                                </div>
                                <div class="col-12 col-lg-4 d-flex align-items-end justify-content-lg-end gap-3">
                                    <input type="hidden" name="is_active" value="0">
                                    <div class="form-check form-switch mb-2">
                                        <input class="form-check-input" type="checkbox" role="switch" name="is_active" value="1" id="active-<?php echo e($managedUser->id); ?>" <?php if($managedUser->is_active): echo 'checked'; endif; ?>>
                                        <label class="form-check-label" for="active-<?php echo e($managedUser->id); ?>">Akun aktif</label>
                                    </div>
                                    <button class="btn btn-primary" type="submit">Simpan</button>
                                </div>
                            </div>
                        </form>
                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); if ($__empty_1): ?>
                        <div class="text-center text-muted py-4">Belum ada akun.</div>
                    <?php endif; ?>
                </div>

                <?php if($users->hasPages()): ?><div class="mt-4"><?php echo e($users->links()); ?></div><?php endif; ?>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('extra-javascript'); ?>
    <script>
        document.querySelectorAll('[data-toggle-password]').forEach(function (button) {
            button.addEventListener('click', function () {
                const targetId = this.getAttribute('data-toggle-password');
                const input = document.getElementById(targetId);
                if (!input) return;

                const isPassword = input.type === 'password';
                input.type = isPassword ? 'text' : 'password';

                const svg = this.querySelector('svg');
                if (svg) {
                    svg.innerHTML = isPassword
                        ? '<path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"></path><line x1="1" y1="1" x2="23" y2="23"></line>'
                        : '<path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle>';
                }
            });
        });
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.app-2', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH D:\Projects\PPG\sibk\resources\views/pages/admin/users/index.blade.php ENDPATH**/ ?>