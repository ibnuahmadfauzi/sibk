@php($hasAgencyLogo = app(\App\Services\WebAppearanceService::class)->agencyLogoPath() !== null)
<header class="sibk-document-letterhead">
    <div class="sibk-document-letterhead__identity {{ $hasAgencyLogo ? 'sibk-document-letterhead__identity--two-logos' : '' }}">
        <img src="{{ $hasAgencyLogo ? app(\App\Services\WebAppearanceService::class)->agencyLogoUrl() : app(\App\Services\WebAppearanceService::class)->logoUrl() }}" class="sibk-document-letterhead__logo" alt="{{ $hasAgencyLogo ? 'Logo Dinas Pendidikan Jawa Timur' : 'Logo sekolah' }}">
        <div>
            <p class="sibk-document-letterhead__agency">
                Pemerintah Provinsi Jawa Timur · Dinas Pendidikan
            </p>
            <h1 class="sibk-document-letterhead__school">
                SMK Negeri 1 Surabaya
            </h1>
            <p class="sibk-document-letterhead__address">
                Jl. SMEA No. 4, Wonokromo, Surabaya, Jawa Timur 60243
            </p>
        </div>
        @if($hasAgencyLogo)
            <img src="{{ app(\App\Services\WebAppearanceService::class)->logoUrl() }}" class="sibk-document-letterhead__logo" alt="Logo sekolah">
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
