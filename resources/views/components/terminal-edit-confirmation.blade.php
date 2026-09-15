@props(['continueUrl', 'cancelUrl'])

<div class="sibk-panel">
    <div class="sibk-panel__body p-4 text-center">
        <p class="mb-4">Data ini telah dinyatakan selesai. Apakah Anda setuju melanjutkan pengeditan?</p>
        <div class="d-flex justify-content-center gap-2">
            <a href="{{ $cancelUrl }}" class="btn btn-outline-secondary">Kembali</a>
            <a href="{{ $continueUrl }}" class="btn btn-primary">Lanjutkan Edit</a>
        </div>
    </div>
</div>
