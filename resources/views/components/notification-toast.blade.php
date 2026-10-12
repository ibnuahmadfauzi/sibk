@props(['tone' => 'success', 'title' => null])

<div class="sibk-notification-toast-region" aria-live="{{ $tone === 'error' ? 'assertive' : 'polite' }}" aria-atomic="true">
    <div class="toast sibk-notification-toast sibk-notification-toast--{{ $tone }}" role="{{ $tone === 'error' ? 'alert' : 'status' }}" data-notification-toast>
        <div class="toast-body d-flex align-items-start gap-2">
            <span class="sibk-notification-toast__icon" aria-hidden="true">
                @if($tone === 'success')
                    <svg viewBox="0 0 24 24"><path d="m5 12 4 4L19 6" /></svg>
                @else
                    <svg viewBox="0 0 24 24"><path d="M12 7v6m0 4h.01" /></svg>
                @endif
            </span>
            <div class="flex-grow-1">
                <strong class="d-block">{{ $title ?? match($tone) { 'error' => 'Gagal memproses data', 'warning' => 'Perlu perhatian', default => 'Perubahan berhasil' } }}</strong>
                <div>{{ $slot }}</div>
            </div>
            <button class="btn-close" type="button" data-bs-dismiss="toast" aria-label="Tutup pemberitahuan"></button>
        </div>
    </div>
</div>
