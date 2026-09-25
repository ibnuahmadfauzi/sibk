<aside class="offcanvas-lg offcanvas-start sibk-sidebar" tabindex="-1" id="appSidebar"
    aria-labelledby="appSidebarLabel">
    <div class="offcanvas-header sibk-sidebar__mobile-header">
        <h2 class="offcanvas-title" id="appSidebarLabel">Navigasi Ruang BK</h2>
        <button class="btn-close btn-close-white" type="button" data-bs-dismiss="offcanvas"
            data-bs-target="#appSidebar" aria-label="Tutup menu"></button>
    </div>

    <div class="sibk-sidebar__content">
        <a class="sibk-sidebar__brand" href="{{ route('dashboard.preview') }}">
            <div class="sibk-sidebar__logo-wrapper">
                <x-logo class="sibk-sidebar__logo-img" />
            </div>
            <span><strong>RUANG BK</strong><small>SMK NEGERI 1 SURABAYA</small></span>
        </a>

        @php
            $sidebarUser = auth()->user();
            $isWakaOnly = $sidebarUser?->hasRole('waka_kesiswaan')
                && ! $sidebarUser?->hasAnyRole(['guru_bk', 'koordinator_bk', 'admin_it']);
        @endphp

        <nav class="sibk-sidebar__nav" aria-label="Navigasi utama">
            <a class="sibk-nav-link {{ request()->routeIs('dashboard.preview') ? 'is-active' : '' }}" href="{{ route('dashboard.preview') }}"
                aria-current="{{ request()->routeIs('dashboard.preview') ? 'page' : 'false' }}">
                <svg aria-hidden="true" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                <span>Dashboard</span>
            </a>

            @if($isWakaOnly)
                <p class="sibk-sidebar__section">PEMANTAUAN WAKA</p>
                <a class="sibk-nav-link {{ request()->routeIs('waka.monitoring.students') ? 'is-active' : '' }}" href="{{ route('waka.monitoring.students') }}"
                    aria-current="{{ request()->routeIs('waka.monitoring.students') ? 'page' : 'false' }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
                    <span>Murid dengan Permasalahan</span>
                </a>
                <a
                    class="sibk-nav-link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}"
                    href="{{ route('reports.index') }}"
                    aria-current="{{ request()->routeIs('reports.*') ? 'page' : 'false' }}"
                >
                    <svg
                        aria-hidden="true"
                        viewBox="0 0 24 24"
                    >
                        <path d="M6 3h12a2 2 0 0 1 2 2v16H4V5a2 2 0 0 1 2-2Z" />
                        <path d="M8 8h8M8 12h8M8 16h5" />
                    </svg>
                    <span>Laporan</span>
                </a>
            @else
            @can('viewAny', App\Models\BkCase::class)
            <a class="sibk-nav-link {{ request()->routeIs('cases.*') || request()->routeIs('consultations.*') ? 'is-active' : '' }}" href="{{ route('cases.index') }}"
                aria-current="{{ request()->routeIs('cases.*') || request()->routeIs('consultations.*') ? 'page' : 'false' }}">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M6 4h12a2 2 0 0 1 2 2v14H4V6a2 2 0 0 1 2-2Z"/><path d="M8 4V2h8v2M8 9h8M8 13h5"/></svg>
                <span>Layanan BK</span>
            </a>
            @endcan

            @can('viewAny', App\Models\Student::class)
            <a class="sibk-nav-link {{ request()->routeIs('students.*') ? 'is-active' : '' }}" href="{{ route('students.index') }}"
                aria-current="{{ request()->routeIs('students.*') ? 'page' : 'false' }}">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
                <span>Data Murid</span>
            </a>
            @endcan

            @can('viewReports')
                <a class="sibk-nav-link {{ request()->routeIs('reports.*') ? 'is-active' : '' }}" href="{{ route('reports.index') }}"
                    aria-current="{{ request()->routeIs('reports.*') ? 'page' : 'false' }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M6 3h12a2 2 0 0 1 2 2v16H4V5a2 2 0 0 1 2-2Z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
                    <span>Laporan</span>
                </a>
            @endcan

            <p class="sibk-sidebar__section">PENGELOLAAN</p>

            @can('viewAny', App\Models\TeacherAssignment::class)
            @unless($sidebarUser?->hasRole('admin_it'))
            <a class="sibk-nav-link {{ request()->routeIs('assignments.classes.*') ? 'is-active' : '' }}" href="{{ route('assignments.classes.index') }}"
                aria-current="{{ request()->routeIs('assignments.classes.*') ? 'page' : 'false' }}">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2"/><path d="M2 20c0-3.9 2.7-7 6-7s6 3.1 6 7M14 14c3.6 0 6 2.6 6 6"/></svg>
                <span>Penugasan Kelas</span>
            </a>
            @endunless
            @endcan

            @can('manageDataMaster')
                <a class="sibk-nav-link {{ request()->routeIs('admin.users.*') ? 'is-active' : '' }}" href="{{ route('admin.users.index') }}"
                    aria-current="{{ request()->routeIs('admin.users.*') ? 'page' : 'false' }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2"/><path d="M2 20c0-3.9 2.7-7 6-7s6 3.1 6 7M14 14c3.6 0 6 2.6 6 6"/></svg>
                    <span>Kelola Akun</span>
                </a>
                <a class="sibk-nav-link {{ request()->routeIs('data-master.*') ? 'is-active' : '' }}" href="{{ route('data-master.index') }}"
                    aria-current="{{ request()->routeIs('data-master.*') ? 'page' : 'false' }}">
                    <svg aria-hidden="true" viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg>
                    <span>Data Master</span>
                </a>
                <a
                    class="sibk-nav-link {{ request()->routeIs('admin.api.*') ? 'is-active' : '' }}"
                    href="{{ route('admin.api.index') }}"
                    aria-current="{{ request()->routeIs('admin.api.*') ? 'page' : 'false' }}"
                >
                    <svg aria-hidden="true" viewBox="0 0 24 24">
                        <circle cx="6" cy="12" r="2" />
                        <circle cx="18" cy="6" r="2" />
                        <circle cx="18" cy="18" r="2" />
                        <path d="m8 11 8-4M8 13l8 4" />
                    </svg>
                    <span>Kelola API</span>
                </a>
            @endcan

            @can('viewWakaMonitoring')
            <p class="sibk-sidebar__section">PEMANTAUAN WAKA</p>
            <a class="sibk-nav-link {{ request()->routeIs('waka.monitoring.students') ? 'is-active' : '' }}" href="{{ route('waka.monitoring.students') }}"
                aria-current="{{ request()->routeIs('waka.monitoring.students') ? 'page' : 'false' }}">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
                <span>Murid dengan Permasalahan</span>
            </a>
            @endcan
            @endif

            <p class="sibk-sidebar__section">UTILITAS</p>

            <a class="sibk-nav-link {{ request()->routeIs('account.index') ? 'is-active' : '' }}" href="{{ route('account.index') }}"
                aria-current="{{ request()->routeIs('account.index') ? 'page' : 'false' }}">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
                <span>Akun Saya</span>
            </a>
        </nav>
    </div>
</aside>
