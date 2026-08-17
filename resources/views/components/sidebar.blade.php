<aside class="offcanvas-lg offcanvas-start sibk-sidebar" tabindex="-1" id="appSidebar"
    aria-labelledby="appSidebarLabel">
    <div class="offcanvas-header sibk-sidebar__mobile-header">
        <h2 class="offcanvas-title" id="appSidebarLabel">Navigasi Ruang BK</h2>
        <button class="btn-close btn-close-white" type="button" data-bs-dismiss="offcanvas"
            data-bs-target="#appSidebar" aria-label="Tutup menu"></button>
    </div>

    <div class="sibk-sidebar__content">
        <a class="sibk-sidebar__brand" href="{{ route('dashboard.preview', ['role' => $previewRole]) }}">
            <div class="sibk-sidebar__logo-wrapper">
                <x-logo class="sibk-sidebar__logo-img" />
            </div>
            <span><strong>RUANG BK</strong><small>SMK NEGERI 1 SURABAYA</small></span>
        </a>

        <nav class="sibk-sidebar__nav" aria-label="Navigasi utama">
            <a class="sibk-nav-link {{ request()->routeIs('dashboard.preview') ? 'is-active' : '' }}" href="{{ route('dashboard.preview') }}"
                aria-current="{{ request()->routeIs('dashboard.preview') ? 'page' : 'false' }}">
                <svg aria-hidden="true" viewBox="0 0 24 24"><rect x="3" y="3" width="7" height="7" rx="1"/><rect x="14" y="3" width="7" height="7" rx="1"/><rect x="3" y="14" width="7" height="7" rx="1"/><rect x="14" y="14" width="7" height="7" rx="1"/></svg>
                <span>Dashboard</span>
            </a>

            <a class="sibk-nav-link {{ request()->routeIs('cases.*') ? 'is-active' : '' }}" href="{{ route('cases.index') }}" 
                aria-current="{{ request()->routeIs('cases.*') ? 'page' : 'false' }}">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M6 4h12a2 2 0 0 1 2 2v14H4V6a2 2 0 0 1 2-2Z"/><path d="M8 4V2h8v2M8 9h8M8 13h5"/></svg>
                <span>Layanan BK</span>
            </a>

            <span class="sibk-nav-link is-planned" aria-disabled="true" title="Dikerjakan pada tahap halaman murid">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
                <span>Data Murid</span>
            </span>

            <span class="sibk-nav-link is-planned" aria-disabled="true" title="Dikerjakan pada tahap halaman laporan">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M6 3h12a2 2 0 0 1 2 2v16H4V5a2 2 0 0 1 2-2Z"/><path d="M8 8h8M8 12h8M8 16h5"/></svg>
                <span>Laporan</span>
            </span>

            <p class="sibk-sidebar__section">PENGELOLAAN</p>

            <span class="sibk-nav-link is-planned" aria-disabled="true" title="Dikerjakan pada tahap pengelolaan">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="8" cy="8" r="3"/><circle cx="17" cy="9" r="2"/><path d="M2 20c0-3.9 2.7-7 6-7s6 3.1 6 7M14 14c3.6 0 6 2.6 6 6"/></svg>
                <span>Penugasan Kelas</span>
            </span>

            <span class="sibk-nav-link is-planned" aria-disabled="true" title="Dikerjakan pada tahap pengelolaan">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m3 11 18-5v12L3 14v-3zM11.6 16.8a3 3 0 1 1-5.8-1.6"/></svg>
                <span>Pengalihan Kasus</span>
            </span>

            <span class="sibk-nav-link is-planned" aria-disabled="true" title="Dikerjakan pada tahap pengelolaan">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="m18 5-3-3H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V7.83A2 2 0 0 0 19.41 6l-1.41-1zM14 2.5V7h4.5M8 13h8M8 17h8M8 9h2"/></svg>
                <span>Koreksi Data</span>
            </span>

            <span class="sibk-nav-link is-planned" aria-disabled="true" title="Dikerjakan pada tahap pengelolaan">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M3 12a9 9 0 1 0 9-9 9.75 9.75 0 0 0-6.74 2.74L3 8"/><path d="M3 3v5h5"/><path d="M12 7v5l4 2"/></svg>
                <span>Riwayat Perubahan</span>
            </span>

            <span class="sibk-nav-link is-planned" aria-disabled="true" title="Dikerjakan pada tahap pengelolaan">
                <svg aria-hidden="true" viewBox="0 0 24 24"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M3 5V19A9 3 0 0 0 21 19V5"/><path d="M3 12A9 3 0 0 0 21 12"/></svg>
                <span>Data Master</span>
            </span>

            <p class="sibk-sidebar__section">UTILITAS</p>

            <a class="sibk-nav-link {{ request()->routeIs('notifications.preview') ? 'is-active' : '' }}" href="{{ route('notifications.preview') }}" 
                aria-current="{{ request()->routeIs('notifications.preview') ? 'page' : 'false' }}">
                <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 7-3 9h18c0-2-3-2-3-9ZM10 21h4"/></svg>
                <span>Notifikasi</span>
            </a>
            <a class="sibk-nav-link {{ request()->routeIs('account.preview') ? 'is-active' : '' }}" href="{{ route('account.preview') }}" 
                aria-current="{{ request()->routeIs('account.preview') ? 'page' : 'false' }}">
                <svg aria-hidden="true" viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
                <span>Akun Saya</span>
            </a>
        </nav>
    </div>
</aside>
