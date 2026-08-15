<header class="sibk-topbar">

    {{-- Mobile Toggle --}}
    <button class="sibk-mobile-menu-btn" id="mobileMenuBtn" onclick="toggleSidebar()" aria-label="Buka menu">
        <svg width="20" height="20" viewBox="0 0 20 20" fill="none"><path d="M3 5h14M3 10h14M3 15h14" stroke="#555" stroke-width="1.8" stroke-linecap="round"/></svg>
    </button>

    {{-- Search --}}
    <div class="sibk-search-box">
        <span class="sibk-search-icon">
            <svg width="15" height="15" viewBox="0 0 15 15" fill="none"><circle cx="6.5" cy="6.5" r="5" stroke="#aaa" stroke-width="1.5"/><path d="M11 11l2.5 2.5" stroke="#aaa" stroke-width="1.5" stroke-linecap="round"/></svg>
        </span>
        <input type="text" id="searchInput" placeholder="Cari murid atau kasus..." autocomplete="off">
    </div>

    {{-- Right Side: User --}}
    <div class="sibk-topbar-right">
        <div class="sibk-user-info">
            <div class="sibk-user-avatar">
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none"><circle cx="11" cy="7" r="4" stroke="#4A6FA5" stroke-width="1.5"/><path d="M3 19c0-4.418 3.582-8 8-8s8 3.582 8 8" stroke="#4A6FA5" stroke-width="1.5" stroke-linecap="round"/></svg>
            </div>
            <span class="sibk-user-name">Guru BK</span>
            <span class="sibk-user-chevron">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M3 4.5l3 3 3-3" stroke="#777" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
        </div>
    </div>

</header>
