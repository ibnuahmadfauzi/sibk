export const initAutoFilters = (root = document, navigate = (url) => window.location.assign(url)) => {
    root.querySelectorAll('form[data-auto-filter]').forEach((form) => {
        const fields = [...form.querySelectorAll('[data-filter-field]')];
        const button = form.querySelector('[data-filter-action]');
        if (!button || !fields.length) return;
        let timer;
        let composing = false;
        const active = () => fields.some((field) => field.value !== (field.dataset.filterDefault ?? ''));
        const refresh = () => { button.textContent = active() ? 'Reset' : 'Filter'; };
        const apply = () => {
            clearTimeout(timer);
            if (!composing && form.reportValidity()) form.requestSubmit();
        };
        button.type = 'button';
        refresh();
        button.addEventListener('click', () => {
            clearTimeout(timer);
            if (active()) navigate(form.dataset.filterResetUrl || form.action);
            else apply();
        });
        form.addEventListener('submit', () => clearTimeout(timer));
        fields.forEach((field) => {
            const schedule = () => {
                refresh();
                clearTimeout(timer);
                if (!composing) timer = setTimeout(apply, 450);
            };
            if (field.tagName === 'INPUT' && ['text', 'search'].includes(field.type)) {
                field.addEventListener('input', schedule);
                field.addEventListener('compositionstart', () => { composing = true; clearTimeout(timer); });
                field.addEventListener('compositionend', () => { composing = false; schedule(); });
            } else {
                field.addEventListener('change', () => { refresh(); apply(); });
            }
        });
    });
};
