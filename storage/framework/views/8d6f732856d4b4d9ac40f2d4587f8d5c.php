<main class="sibk-auth" id="main-content" data-page-id="PG-001">

    
    <section class="sibk-auth-brand" aria-labelledby="auth-brand-title">
        <div class="sibk-auth-brand__inner">

            <a class="sibk-auth-brand__identity" href="<?php echo e(route('login')); ?>"
                aria-label="Aplikasi BK, kembali ke login">
                <?php if (isset($component)) { $__componentOriginal987d96ec78ed1cf75b349e2e5981978f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal987d96ec78ed1cf75b349e2e5981978f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.logo','data' => ['ariaHidden' => 'true','width' => '36','height' => '36']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['aria-hidden' => 'true','width' => '36','height' => '36']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal987d96ec78ed1cf75b349e2e5981978f)): ?>
<?php $attributes = $__attributesOriginal987d96ec78ed1cf75b349e2e5981978f; ?>
<?php unset($__attributesOriginal987d96ec78ed1cf75b349e2e5981978f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal987d96ec78ed1cf75b349e2e5981978f)): ?>
<?php $component = $__componentOriginal987d96ec78ed1cf75b349e2e5981978f; ?>
<?php unset($__componentOriginal987d96ec78ed1cf75b349e2e5981978f); ?>
<?php endif; ?>
                <span>Ruang BK</span>
            </a>

            <div class="sibk-auth-brand__copy">
                <h1 id="auth-brand-title">Pencatatan layanan BK yang terstruktur dan sesuai kewenangan.</h1>
                <div class="sibk-auth-brand__accent" aria-hidden="true"></div>
                <p>Kelola layanan bimbingan dan konseling dengan mudah, aman, dan terorganisir dalam satu sistem.</p>
            </div>

            <div class="sibk-auth-illustration" aria-hidden="true">
                <img src="<?php echo e(asset('assets/images/illustration-consultation.png')); ?>" alt=""
                    width="520" height="380" loading="lazy">
            </div>

            <p class="sibk-auth-brand__note">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M12 3 5 6v5c0 4.6 2.9 8.5 7 10 4.1-1.5 7-5.4 7-10V6l-7-3Z"/><path d="m9 12 2 2 4-4"/></svg>
                Gunakan akun yang telah ditetapkan oleh sekolah.
            </p>

        </div>
    </section>

    
    <section class="sibk-auth-form-area" aria-labelledby="login-title">
        <div class="sibk-auth-card card border-0">

            
            <div class="sibk-auth-card__logo" aria-hidden="true">
                <?php if (isset($component)) { $__componentOriginal987d96ec78ed1cf75b349e2e5981978f = $component; } ?>
<?php if (isset($attributes)) { $__attributesOriginal987d96ec78ed1cf75b349e2e5981978f = $attributes; } ?>
<?php $component = Illuminate\View\AnonymousComponent::resolve(['view' => 'components.logo','data' => ['width' => '56','height' => '56']] + (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag ? $attributes->all() : [])); ?>
<?php $component->withName('logo'); ?>
<?php if ($component->shouldRender()): ?>
<?php $__env->startComponent($component->resolveView(), $component->data()); ?>
<?php if (isset($attributes) && $attributes instanceof Illuminate\View\ComponentAttributeBag): ?>
<?php $attributes = $attributes->except(\Illuminate\View\AnonymousComponent::ignoredParameterNames()); ?>
<?php endif; ?>
<?php $component->withAttributes(['width' => '56','height' => '56']); ?>
<?php echo $__env->renderComponent(); ?>
<?php endif; ?>
<?php if (isset($__attributesOriginal987d96ec78ed1cf75b349e2e5981978f)): ?>
<?php $attributes = $__attributesOriginal987d96ec78ed1cf75b349e2e5981978f; ?>
<?php unset($__attributesOriginal987d96ec78ed1cf75b349e2e5981978f); ?>
<?php endif; ?>
<?php if (isset($__componentOriginal987d96ec78ed1cf75b349e2e5981978f)): ?>
<?php $component = $__componentOriginal987d96ec78ed1cf75b349e2e5981978f; ?>
<?php unset($__componentOriginal987d96ec78ed1cf75b349e2e5981978f); ?>
<?php endif; ?>
            </div>

            
            <div class="alert sibk-form-summary" id="loginSummary" role="alert" tabindex="-1" <?php if(! $errors->any()): ?> hidden <?php endif; ?>>
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M12 7v6M12 17h.01"/></svg>
                <div>
                    <strong id="loginSummaryTitle">Periksa kembali isian Anda</strong>
                    <p id="loginSummaryMessage"><?php echo e($errors->first()); ?></p>
                </div>
            </div>

            
            <div class="alert sibk-auth-success" id="loginSuccess" role="status" tabindex="-1" hidden>
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/></svg>
                <div>
                    <strong>Identitas Anda diterima.</strong>
                    <p>Sebentar lagi Anda akan diarahkan ke ruang kerja.</p>
                    <a class="btn btn-sm btn-outline-success mt-2"
                        href="<?php echo e(route('dashboard.preview', ['role' => 'guru'])); ?>">Lanjutkan ke Dashboard</a>
                </div>
            </div>

            <header class="sibk-auth-card__header">
                <h2 id="login-title">Masuk ke Ruang BK</h2>
            </header>

            <form id="loginForm" action="<?php echo e(route('login.store')); ?>" method="post" novalidate>
                <?php echo csrf_field(); ?>

                <div class="mb-3">
                    <label class="form-label" for="identifier">Email</label>
                    <div class="input-group <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php if($message !== 'Email atau kata sandi tidak sesuai.'): ?> is-invalid <?php endif; ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>">
                        <span class="input-group-text" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
                        </span>
                        <input class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> <?php if($message !== 'Email atau kata sandi tidak sesuai.'): ?> is-invalid <?php endif; ?> <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="identifier" name="email" type="email" required
                            autocomplete="username" spellcheck="false"
                            value="<?php echo e(old('email')); ?>" placeholder="Masukkan email akun"
                            aria-describedby="identifierError">
                    </div>
                    <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <?php if($message !== 'Email atau kata sandi tidak sesuai.'): ?>
                            <div class="invalid-feedback d-block" id="identifierError"><?php echo e($message); ?></div>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="invalid-feedback" id="identifierError">Email wajib diisi.</div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <div class="mb-4">
                    <label class="form-label" for="password">Kata sandi</label>
                    <div class="input-group <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php if($errors->first('email') === 'Email atau kata sandi tidak sesuai.'): ?> is-invalid <?php endif; ?>">
                        <span class="input-group-text" aria-hidden="true">
                            <svg viewBox="0 0 24 24"><rect x="5" y="10" width="14" height="11" rx="2"/><path d="M8 10V7a4 4 0 0 1 8 0v3"/></svg>
                        </span>
                        <input class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?> <?php if($errors->first('email') === 'Email atau kata sandi tidak sesuai.'): ?> is-invalid <?php endif; ?>" id="password" name="password" type="password" required
                            autocomplete="current-password" placeholder="Masukkan kata sandi"
                            aria-describedby="passwordError">
                        <button class="btn sibk-password-toggle" id="togglePassword" type="button"
                            aria-label="Tampilkan kata sandi" aria-pressed="false">
                            <svg id="passwordVisibilityIcon" aria-hidden="true" viewBox="0 0 24 24"><path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/></svg>
                        </button>
                    </div>
                    <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                        <div class="invalid-feedback d-block" id="passwordError"><?php echo e($message); ?></div>
                    <?php else: ?>
                        <div class="invalid-feedback" id="passwordError">Kata sandi wajib diisi.</div>
                    <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                </div>

                <button class="btn btn-primary sibk-auth-submit w-100" id="loginButton" type="submit">
                    <span class="spinner-border spinner-border-sm" id="loginSpinner" aria-hidden="true" hidden></span>
                    <span id="loginButtonText">Masuk</span>
                </button>
            </form>

            <div class="sibk-auth-help">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="12" r="9"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3M12 17h.01"/></svg>
                <span>Kesulitan mengakses akun? <strong>Hubungi Admin IT sekolah.</strong></span>
            </div>

        </div>
    </section>

</main>
<?php /**PATH D:\Tugas\pepe g\smt 2\pk\sibk\sibk\resources\views/pages/login/html.blade.php ENDPATH**/ ?>