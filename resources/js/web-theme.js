export const initWebTheme = (root = document) => {
    const form = root.querySelector('[data-web-theme-form]');
    if (!form) return;
    const custom = form.querySelector('[data-custom-palette-fields]');
    const update = () => {
        const enabled = form.querySelector('[name="theme"]:checked')?.value === 'custom';
        custom.hidden = !enabled;
        custom.disabled = !enabled;
    };
    form.querySelectorAll('[name="theme"]').forEach((input) => input.addEventListener('change', update));
    form.querySelectorAll('[data-theme-color-field]').forEach((field) => {
        const picker = field.querySelector('[data-theme-color-picker]');
        const hex = field.querySelector('[data-theme-color-hex]');
        picker.addEventListener('input', () => { hex.value = picker.value; });
        hex.addEventListener('input', () => {
            if (/^#[0-9a-f]{6}$/i.test(hex.value)) picker.value = hex.value;
        });
    });
    update();
};
