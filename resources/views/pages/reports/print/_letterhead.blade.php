@php($hasAgencyLogo = app(\App\Services\WebAppearanceService::class)->agencyLogoPath() !== null)
<header class="sibk-document-letterhead">
    <div class="sibk-document-letterhead__identity {{ $hasAgencyLogo ? 'sibk-document-letterhead__identity--two-logos' : '' }}">
        <img src="{{ $hasAgencyLogo ? route('branding.agency-logo') : route('branding.logo') }}" class="sibk-document-letterhead__logo" alt="{{ $hasAgencyLogo ? 'Logo Dinas Pendidikan Jawa Timur' : 'Logo sekolah' }}">
        <div>
            <p class="sibk-document-letterhead__agency">
                Pagelaran Karya · Demonstrasi Aplikasi
            </p>
            <h1 class="sibk-document-letterhead__school">
                Sekolah Demo Ruang BK
            </h1>
            <p class="sibk-document-letterhead__address">
                Lingkungan demo · Seluruh data merupakan data sintetis
            </p>
        </div>
        @if($hasAgencyLogo)
            <img src="{{ route('branding.logo') }}" class="sibk-document-letterhead__logo" alt="Logo sekolah">
        @endif
    </div>
    <h2 class="sibk-document-letterhead__title">
        {{ $title }}
    </h2>
    @if(filled($subtitle ?? null))
        <p class="sibk-document-letterhead__subtitle">
            {{ $subtitle }}
        </p>
    @endif
</header>
