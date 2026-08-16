const form = document.querySelector('#loginForm');
const identifier = document.querySelector('#identifier');
const password = document.querySelector('#password');
const togglePassword = document.querySelector('#togglePassword');
const visibilityIcon = document.querySelector('#passwordVisibilityIcon');
const summary = document.querySelector('#loginSummary');
const summaryTitle = document.querySelector('#loginSummaryTitle');
const summaryMessage = document.querySelector('#loginSummaryMessage');
const success = document.querySelector('#loginSuccess');
const submitButton = document.querySelector('#loginButton');
const submitText = document.querySelector('#loginButtonText');
const spinner = document.querySelector('#loginSpinner');

const eyeOpen = '<path d="M2.5 12s3.5-6 9.5-6 9.5 6 9.5 6-3.5 6-9.5 6-9.5-6-9.5-6Z"/><circle cx="12" cy="12" r="2.5"/>';
const eyeClosed = '<path d="M4 4l16 16M9.5 6.4A9.7 9.7 0 0 1 12 6c6 0 9.5 6 9.5 6a16 16 0 0 1-2.5 3.2M14.3 14.3A3.2 3.2 0 0 1 9.7 9.7M6.2 8.2A15.6 15.6 0 0 0 2.5 12s3.5 6 9.5 6c1 0 2-.2 2.8-.5"/>';

function setFieldValidity(field, valid) {
    field.classList.toggle('is-invalid', !valid);
    field.setAttribute('aria-invalid', String(!valid));
}

function hideMessages() {
    summary.hidden = true;
    success.hidden = true;
}

function showSummary(title, message) {
    summaryTitle.textContent = title;
    summaryMessage.textContent = message;
    summary.hidden = false;
    success.hidden = true;
    summary.focus();
}

function setLoading(loading) {
    submitButton.disabled = loading;
    form.setAttribute('aria-busy', String(loading));
    spinner.hidden = !loading;
    submitText.textContent = loading ? 'Memeriksa akun…' : 'Masuk';
}

togglePassword?.addEventListener('click', () => {
    const reveal = password.type === 'password';
    password.type = reveal ? 'text' : 'password';
    togglePassword.setAttribute('aria-pressed', String(reveal));
    togglePassword.setAttribute('aria-label', reveal ? 'Sembunyikan kata sandi' : 'Tampilkan kata sandi');
    visibilityIcon.innerHTML = reveal ? eyeClosed : eyeOpen;
});

[identifier, password].forEach((field) => {
    field?.addEventListener('input', () => {
        if (field.value.length > 0) setFieldValidity(field, true);
        hideMessages();
    });
});

form?.addEventListener('submit', (event) => {
    event.preventDefault();
    hideMessages();

    const identifierValid = identifier.value.trim().length > 0;
    const passwordValid = password.value.length > 0;
    setFieldValidity(identifier, identifierValid);
    setFieldValidity(password, passwordValid);

    if (!identifierValid || !passwordValid) {
        showSummary('Periksa kembali isian Anda', 'Lengkapi semua field wajib sebelum melanjutkan.');
        (identifierValid ? password : identifier).focus();
        return;
    }

    setLoading(true);

    window.setTimeout(() => {
        setLoading(false);
        const result = form.dataset.previewResult;

        if (result === 'success') {
            success.hidden = false;
            success.focus();
            return;
        }

        if (result === 'system-error') {
            showSummary('Layanan belum dapat diakses', 'Coba kembali beberapa saat lagi atau hubungi Admin IT sekolah.');
            return;
        }

        showSummary('Belum dapat masuk', 'Nama pengguna/email atau kata sandi belum sesuai. Periksa kembali tanpa membagikan kredensial Anda.');
    }, 650);
});
