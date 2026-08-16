<header class="sibk-topbar">
    <button class="btn sibk-topbar__menu" type="button" data-bs-toggle="offcanvas" data-bs-target="#appSidebar"
        aria-controls="appSidebar" aria-label="Buka navigasi">
        <svg aria-hidden="true" viewBox="0 0 24 24"><path d="M4 7h16M4 12h16M4 17h16"/></svg>
    </button>

    <form class="sibk-topbar__search" role="search" action="#" aria-label="Cari murid atau kasus">
        <span class="sibk-topbar__search-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><path d="m21 21-4.35-4.35"/></svg>
        </span>
        <input class="form-control sibk-topbar__search-input" id="topbarSearch" type="search"
            placeholder="Cari murid atau kasus..."
            autocomplete="off" aria-label="Cari murid atau kasus">
    </form>

    <div class="sibk-topbar__user" aria-label="Identitas pengguna pratinjau">
        <span class="sibk-topbar__avatar" aria-hidden="true">
            <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 21c0-4.4 3.6-8 8-8s8 3.6 8 8"/></svg>
        </span>
        <span class="sibk-topbar__user-name">{{ $dashboard['label'] }}</span>
        <svg class="sibk-topbar__chevron" aria-hidden="true" viewBox="0 0 24 24"><path d="m6 9 6 6 6-6"/></svg>
    </div>
</header>
