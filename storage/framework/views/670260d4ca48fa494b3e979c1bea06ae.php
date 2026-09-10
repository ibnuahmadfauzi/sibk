<header class="sibk-topbar">
    <button class="btn sibk-topbar__menu" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar"
        aria-controls="appSidebar" aria-label="Buka navigasi">
        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
    </button>

    <?php if (app(\Illuminate\Contracts\Auth\Access\Gate::class)->check('viewAny', App\Models\BkCase::class)): ?>
    <form class="sibk-topbar__search" role="search" action="<?php echo e(route('cases.index')); ?>" method="GET" aria-label="Cari murid atau kasus">
        <span class="sibk-topbar__search-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/></svg>
        </span>
        <input class="form-control sibk-topbar__search-input" id="topbarSearch" name="search" type="search"
            placeholder="Cari murid atau kasus..."
            autocomplete="off" aria-label="Cari murid atau kasus">
    </form>
    <?php endif; ?>

    <div class="dropdown sibk-topbar__user-dropdown">
        <button class="btn sibk-topbar__user" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Menu pengguna">
            <span class="sibk-topbar__avatar" aria-hidden="true">
                <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
            </span>
            <span class="sibk-topbar__user-name"><?php echo e(auth()->user()?->name ?? 'Akun'); ?></span>
            <svg class="sibk-topbar__chevron" aria-hidden="true" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
            <li><a class="dropdown-item" href="<?php echo e(route('account.index')); ?>">Akun Saya</a></li>
            <li><hr class="dropdown-divider"></li>
            <li>
                <form action="<?php echo e(route('logout')); ?>" method="POST">
                    <?php echo csrf_field(); ?>
                    <button class="dropdown-item text-danger d-flex align-items-center gap-2" type="submit">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" x2="9" y1="12" y2="12"/></svg>
                        Keluar
                    </button>
                </form>
            </li>
        </ul>
    </div>
</header>
<?php /**PATH D:\Tugas\pepe g\smt 2\pk\sibk\sibk\resources\views\components\topbar.blade.php ENDPATH**/ ?>