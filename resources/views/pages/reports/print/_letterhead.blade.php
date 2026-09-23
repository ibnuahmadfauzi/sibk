<header class="sibk-document-letterhead">
    <div class="sibk-document-letterhead__identity">
        <div
            class="sibk-document-letterhead__logo-placeholder"
            aria-hidden="true"
        >
            Logo
        </div>
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
        <div
            class="sibk-document-letterhead__logo-placeholder"
            aria-hidden="true"
        >
            Logo
        </div>
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
