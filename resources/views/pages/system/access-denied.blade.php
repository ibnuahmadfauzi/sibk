<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - Ruang BK</title>
    @vite(['resources/scss/app-dashboard.scss', 'resources/js/app-dashboard.js'])
</head>
<body>
    <div class="sibk-access-denied-page" data-page-id="PG-901">
        <!-- Top Brand Header -->
        <div class="container-fluid mb-4">
            <a href="{{ route('dashboard.preview') }}" class="sibk-access-denied-brand">
                <div class="sibk-access-denied-brand__logo">
                    <x-logo class="sibk-access-denied-brand__image" />
                </div>
                <div>
                    <strong class="sibk-access-denied-brand__title">Ruang BK</strong>
                    <small class="sibk-access-denied-brand__subtitle">SMK Negeri 1 Surabaya</small>
                </div>
            </a>
        </div>

        <!-- Centered Error Card -->
        <div class="sibk-access-denied-card">
            <div class="sibk-access-denied-icon">
                <svg viewBox="0 0 24 24" width="56" height="56" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"></path>
                    <rect x="9" y="11" width="6" height="5" rx="1"></rect>
                    <path d="M10 11V9a2 2 0 1 1 4 0v2"></path>
                </svg>
            </div>
            
            <h1 class="sibk-access-denied-card__title">Akses Ditolak</h1>
            <p class="sibk-access-denied-card__message">Anda tidak memiliki akses ke halaman ini.</p>
            <p class="text-muted small mb-4">Kembali ke Dashboard untuk membuka menu yang tersedia.</p>

            <a href="{{ route('dashboard.preview') }}" class="btn btn-primary sibk-access-denied-card__action">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <rect x="3" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="3" width="7" height="7" rx="1"></rect>
                    <rect x="3" y="14" width="7" height="7" rx="1"></rect>
                    <rect x="14" y="14" width="7" height="7" rx="1"></rect>
                </svg>
                Kembali ke Dashboard
            </a>
        </div>
    </div>
</body>
</html>
