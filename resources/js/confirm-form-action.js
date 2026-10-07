export const confirmFormAction = (form, action, onConfirm) => {
    const clear = action === 'clear';
    document.dispatchEvent(new CustomEvent('sibk:confirm-form-action', {
        detail: {
            form,
            onConfirm,
            closeAfterConfirm: !clear,
            dataset: {
                confirmTitle: clear ? 'Kosongkan isian?' : 'Tutup form permasalahan?',
                confirmMessage: clear
                    ? 'Seluruh isian dan draft di perangkat akan dihapus. Form tetap terbuka.'
                    : 'Perubahan belum disimpan. Anda dapat melanjutkan pengisian sebelum menutup form.',
                confirmAction: clear ? 'Ya, kosongkan' : 'Ya, tutup',
                confirmCancel: 'Lanjutkan mengisi',
                confirmTone: clear ? 'danger' : 'warning',
            },
        },
    }));
};
