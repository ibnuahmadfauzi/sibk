{{-- Page Header --}}
<div class="page-header">
    <div>
        <h1 class="page-title">Dashboard</h1>
        <p class="page-description">Ringkasan kasus dan tindak lanjut dalam cakupan Anda.</p>
    </div>
</div>


{{-- STAT CARDS --}}
<div class="row g-3 mb-3">

    {{-- Card 1: Murid dalam cakupan --}}
    <div class="col-6 col-xl-3">
        <div class="stat-card stat-card--blue">
            <div class="stat-icon-wrap stat-icon-wrap--blue">
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none"><circle cx="8" cy="7" r="3" stroke="#4A6FA5" stroke-width="1.5"/><path d="M2 17c0-3.314 2.686-6 6-6" stroke="#4A6FA5" stroke-width="1.5" stroke-linecap="round"/><circle cx="15" cy="8" r="2.5" stroke="#4A6FA5" stroke-width="1.5"/><path d="M10.5 17c0-2.485 2.015-4.5 4.5-4.5s4.5 2.015 4.5 4.5" stroke="#4A6FA5" stroke-width="1.5" stroke-linecap="round"/></svg>
            </div>
            <div class="stat-label">Murid dalam cakupan</div>
            <div class="stat-value">72</div>
            <div class="stat-trend stat-trend--up">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M6 2v8M2.5 5.5L6 2l3.5 3.5" stroke="#22c55e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                +3 dari bulan lalu
            </div>
        </div>
    </div>

    {{-- Card 2: Kasus aktif --}}
    <div class="col-6 col-xl-3">
        <div class="stat-card stat-card--orange">
            <div class="stat-icon-wrap stat-icon-wrap--orange">
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none"><rect x="3" y="4" width="16" height="15" rx="2" stroke="#E8734A" stroke-width="1.5"/><path d="M7 4V2M15 4V2" stroke="#E8734A" stroke-width="1.5" stroke-linecap="round"/><path d="M7 10h8M7 14h5" stroke="#E8734A" stroke-width="1.5" stroke-linecap="round"/></svg>
            </div>
            <div class="stat-label">Kasus aktif</div>
            <div class="stat-value">7</div>
            <div class="stat-trend stat-trend--down">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M6 2v8M2.5 6.5L6 10l3.5-3.5" stroke="#ef4444" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                -2 dari bulan lalu
            </div>
        </div>
    </div>

    {{-- Card 3: Tindak lanjut terdekat --}}
    <div class="col-6 col-xl-3">
        <div class="stat-card stat-card--green">
            <div class="stat-icon-wrap stat-icon-wrap--green">
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none"><circle cx="11" cy="11" r="8" stroke="#22c55e" stroke-width="1.5"/><path d="M7 11l3 3 5-5" stroke="#22c55e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </div>
            <div class="stat-label">Tindak lanjut terdekat</div>
            <div class="stat-value">5</div>
            <div class="stat-trend stat-trend--up">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M6 2v8M2.5 5.5L6 2l3.5 3.5" stroke="#22c55e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                +1 dari bulan lalu
            </div>
        </div>
    </div>

    {{-- Card 4: Data e-Tatib terkait --}}
    <div class="col-6 col-xl-3">
        <div class="stat-card stat-card--purple">
            <div class="stat-icon-wrap stat-icon-wrap--purple">
                <svg width="22" height="22" viewBox="0 0 22 22" fill="none"><rect x="3" y="4" width="16" height="15" rx="2" stroke="#8B5CF6" stroke-width="1.5"/><path d="M7 4V2M15 4V2" stroke="#8B5CF6" stroke-width="1.5" stroke-linecap="round"/><path d="M3 9h16" stroke="#8B5CF6" stroke-width="1.5"/><circle cx="8" cy="14" r="1" fill="#8B5CF6"/><circle cx="11" cy="14" r="1" fill="#8B5CF6"/><circle cx="14" cy="14" r="1" fill="#8B5CF6"/></svg>
            </div>
            <div class="stat-label">Data e-Tatib terkait</div>
            <div class="stat-value">24</div>
            <div class="stat-trend stat-trend--up">
                <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><path d="M6 2v8M2.5 5.5L6 2l3.5 3.5" stroke="#22c55e" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                +4 dari bulan lalu
            </div>
        </div>
    </div>

</div>


{{-- MAIN PANELS ROW --}}
<div class="row g-3 mb-3">

    {{-- Panel Kiri: Tindak Lanjut Terdekat --}}
    <div class="col-lg-6">
        <div class="dash-panel">
            <div class="dash-panel-header">
                <div class="dash-panel-title-wrap">
                    <span class="dash-panel-icon">
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><rect x="2" y="3" width="14" height="13" rx="1.5" stroke="#4A6FA5" stroke-width="1.3"/><path d="M5 3V1M13 3V1" stroke="#4A6FA5" stroke-width="1.3" stroke-linecap="round"/><path d="M2 7h14" stroke="#4A6FA5" stroke-width="1.3"/></svg>
                    </span>
                    <span class="dash-panel-title">Tindak lanjut terdekat</span>
                </div>
                <a href="#" class="dash-panel-action">
                    Lihat semua
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M5 3l4 4-4 4" stroke="#4A6FA5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            </div>

            <div class="dash-panel-body">

                {{-- Item 1 --}}
                <div class="followup-item">
                    <div class="followup-date">
                        <div class="followup-day">11</div>
                        <div class="followup-month">Agu<br>2026</div>
                    </div>
                    <div class="followup-info">
                        <div class="followup-code">K-001</div>
                        <div class="followup-type">Konsultasi lanjutan</div>
                        <div class="followup-student">Siswa: Rina Aulia (XI IPS 2)</div>
                    </div>
                    <div class="followup-status status--processing">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><circle cx="6" cy="6" r="5" stroke="#4A6FA5" stroke-width="1.2"/><path d="M6 3.5V6l1.5 1.5" stroke="#4A6FA5" stroke-width="1.2" stroke-linecap="round"/></svg>
                        Dalam penanganan
                    </div>
                    <button class="followup-arrow">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M5 3l4 4-4 4" stroke="#aaa" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </button>
                </div>

                {{-- Item 2 --}}
                <div class="followup-item">
                    <div class="followup-date">
                        <div class="followup-day">12</div>
                        <div class="followup-month">Agu<br>2026</div>
                    </div>
                    <div class="followup-info">
                        <div class="followup-code">K-002</div>
                        <div class="followup-type">Home visit</div>
                        <div class="followup-student">Siswa: Andi Pratama (X IPA 1)</div>
                    </div>
                    <div class="followup-status status--waiting">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><circle cx="6" cy="6" r="5" stroke="#E8734A" stroke-width="1.2"/><path d="M6 3.5V6.5" stroke="#E8734A" stroke-width="1.2" stroke-linecap="round"/><circle cx="6" cy="8.5" r="0.6" fill="#E8734A"/></svg>
                        Menunggu
                    </div>
                    <button class="followup-arrow">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M5 3l4 4-4 4" stroke="#aaa" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </button>
                </div>

                {{-- Item 3 --}}
                <div class="followup-item followup-item--last">
                    <div class="followup-date">
                        <div class="followup-day">14</div>
                        <div class="followup-month">Agu<br>2026</div>
                    </div>
                    <div class="followup-info">
                        <div class="followup-code">K-006</div>
                        <div class="followup-type">Verifikasi hasil</div>
                        <div class="followup-student">Siswa: Siti Nurhaliza (XI IPS 1)</div>
                    </div>
                    <div class="followup-status status--done">
                        <svg width="12" height="12" viewBox="0 0 12 12" fill="none"><circle cx="6" cy="6" r="5" stroke="#22c55e" stroke-width="1.2"/><path d="M3.5 6l2 2 3-3" stroke="#22c55e" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        Selesai
                    </div>
                    <button class="followup-arrow">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M5 3l4 4-4 4" stroke="#aaa" stroke-width="1.5" stroke-linecap="round"/></svg>
                    </button>
                </div>

            </div>
        </div>
    </div>

    {{-- Panel Kanan: Aktivitas Terbaru --}}
    <div class="col-lg-6">
        <div class="dash-panel">
            <div class="dash-panel-header">
                <div class="dash-panel-title-wrap">
                    <span class="dash-panel-icon">
                        <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><rect x="2" y="2" width="14" height="14" rx="2" stroke="#4A6FA5" stroke-width="1.3"/><path d="M5 7h8M5 10h8M5 13h5" stroke="#4A6FA5" stroke-width="1.3" stroke-linecap="round"/></svg>
                    </span>
                    <span class="dash-panel-title">Aktivitas terbaru</span>
                </div>
                <a href="#" class="dash-panel-action">
                    Lihat semua
                    <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><path d="M5 3l4 4-4 4" stroke="#4A6FA5" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"/></svg>
                </a>
            </div>

            <div class="dash-panel-body">

                {{-- Activity 1 --}}
                <div class="activity-item">
                    <div class="activity-icon activity-icon--blue">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6" stroke="#4A6FA5" stroke-width="1.2"/><path d="M7 4v3.5L9 9" stroke="#4A6FA5" stroke-width="1.2" stroke-linecap="round"/></svg>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Kasus baru ditambahkan</div>
                        <div class="activity-sub">K-006 - Verifikasi hasil</div>
                    </div>
                    <div class="activity-time">10:24</div>
                </div>

                {{-- Activity 2 --}}
                <div class="activity-item">
                    <div class="activity-icon activity-icon--green">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><circle cx="7" cy="7" r="6" stroke="#22c55e" stroke-width="1.2"/><path d="M4 7l2.5 2.5 4-4" stroke="#22c55e" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Tindak lanjut diperbarui</div>
                        <div class="activity-sub">K-005 - Sesi konseling</div>
                    </div>
                    <div class="activity-time">09:15</div>
                </div>

                {{-- Activity 3 --}}
                <div class="activity-item">
                    <div class="activity-icon activity-icon--orange">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><rect x="2" y="2" width="10" height="10" rx="1.5" stroke="#E8734A" stroke-width="1.2"/><path d="M4.5 7h5M4.5 5h5M4.5 9h3" stroke="#E8734A" stroke-width="1.2" stroke-linecap="round"/></svg>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Data e-Tatib diperbarui</div>
                        <div class="activity-sub">Pelanggaran kelas IX</div>
                    </div>
                    <div class="activity-time">08:47</div>
                </div>

                {{-- Activity 4 --}}
                <div class="activity-item activity-item--last">
                    <div class="activity-icon activity-icon--purple">
                        <svg width="14" height="14" viewBox="0 0 14 14" fill="none"><rect x="2" y="1" width="10" height="12" rx="1.5" stroke="#8B5CF6" stroke-width="1.2"/><path d="M4 5h6M4 7.5h6M4 10h4" stroke="#8B5CF6" stroke-width="1.2" stroke-linecap="round"/></svg>
                    </div>
                    <div class="activity-content">
                        <div class="activity-title">Laporan baru dibuat</div>
                        <div class="activity-sub">Laporan bulanan Juli 2026</div>
                    </div>
                    <div class="activity-time">08:20</div>
                </div>

            </div>
        </div>
    </div>

</div>


{{-- QUICK ACTION BUTTONS --}}
<div class="quick-actions">
    <a href="#" class="quick-action-btn quick-action-btn--outline">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><rect x="2" y="2" width="14" height="14" rx="2" stroke="currentColor" stroke-width="1.5"/><path d="M5 7h8M5 10h5" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        Lihat daftar kasus
    </a>
    <a href="#" class="quick-action-btn quick-action-btn--primary">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><circle cx="9" cy="6" r="3" stroke="currentColor" stroke-width="1.5"/><path d="M3 15c0-3.314 2.686-6 6-6s6 2.686 6 6" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        Cari profil murid
    </a>
    <a href="#" class="quick-action-btn quick-action-btn--outline">
        <svg width="18" height="18" viewBox="0 0 18 18" fill="none"><rect x="3" y="2" width="12" height="14" rx="1.5" stroke="currentColor" stroke-width="1.5"/><path d="M6 6h6M6 9h6M6 12h4" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/></svg>
        Buka laporan
    </a>
</div>

{{-- Bottom Right Decoration --}}
<div class="dashboard-deco">
    <p class="dashboard-deco-text">Konseling Hari Ini,<br><em>Masa Depan yang Lebih Baik</em></p>
    <div class="dashboard-deco-leaf">
        <svg width="80" height="90" viewBox="0 0 80 90" fill="none" opacity="0.55"><ellipse cx="40" cy="45" rx="30" ry="42" fill="#E8D5C4" transform="rotate(-15 40 45)"/><ellipse cx="40" cy="45" rx="14" ry="36" fill="#D4BFA8" opacity="0.6" transform="rotate(-15 40 45)"/><path d="M40 10 Q42 45 38 80" stroke="#C4A882" stroke-width="2" opacity="0.5"/></svg>
    </div>
</div>
