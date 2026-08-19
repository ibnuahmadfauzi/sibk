<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Akses Ditolak - Ruang BK</title>
    @vite(['resources/scss/app-dashboard.scss', 'resources/js/app-dashboard.js'])
    <style>
        .sibk-access-denied-page {
            min-height: 100vh;
            background-color: var(--sibk-color-page, #f8f7f4);
            display: flex;
            flex-direction: column;
            padding: 2rem 1.5rem;
        }
        .sibk-access-denied-brand {
            display: inline-flex;
            align-items: center;
            gap: 0.85rem;
            text-decoration: none;
            color: inherit;
        }
        .sibk-access-denied-brand__logo {
            width: 3.5rem;
            height: 3.5rem;
            border-radius: 50%;
            background-color: #f9f7f2;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .sibk-access-denied-card {
            max-width: 640px;
            width: 100%;
            background: var(--sibk-color-surface-raised, #ffffff);
            border-radius: 24px;
            padding: 3.5rem 2rem;
            box-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.04);
            text-align: center;
            margin: auto;
        }
        .sibk-access-denied-icon {
            width: 8rem;
            height: 8rem;
            border-radius: 50%;
            background-color: #f9e7e7;
            color: #dc2626;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 2rem;
        }
    </style>
</head>
<body>
    <div class="sibk-access-denied-page">
        <!-- Top Brand Header -->
        <div class="container-fluid mb-4">
            <a href="{{ route('dashboard.preview') }}" class="sibk-access-denied-brand">
                <div class="sibk-access-denied-brand__logo">
                    <x-logo style="width: 2.2rem; height: 2.2rem;" />
                </div>
                <div>
                    <strong class="d-block text-dark fw-bold" style="font-size: 1.25rem; line-height: 1.2;">Ruang BK</strong>
                    <small class="text-muted fw-medium" style="font-size: 0.8rem;">SMK Negeri 1 Surabaya</small>
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
            
            <h1 class="fw-bold mb-2" style="font-size: 1.85rem; color: #0d2447;">Akses Ditolak</h1>
            <p class="fw-semibold mb-1" style="font-size: 0.95rem; color: #40536c;">Anda tidak memiliki akses ke halaman ini.</p>
            <p class="text-muted small mb-4">Kembali ke Dashboard untuk membuka menu yang tersedia.</p>

            <a href="{{ route('dashboard.preview') }}" class="btn btn-primary fw-bold px-4 py-2 d-inline-flex align-items-center gap-2" style="border-radius: 14px; background-color: #2f6fc6; border-color: #2f6fc6; font-size: 0.9rem;">
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
