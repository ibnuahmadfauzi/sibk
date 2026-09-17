const PREFIX = 'sibk:draft:v1:';
const TTL = 24 * 60 * 60 * 1000;
const PENDING_KEY = 'sibk:draft:pending';

const isUnsafe = (field) => {
    const name = (field.name ?? '').toLowerCase();

    return !name || field.disabled || field.type === 'file'
        || ['_token', '_method'].includes(name)
        || /password|token|credential|secret/.test(name);
};

export const draftKey = (userId, formKey, recordId = 'new') => `${PREFIX}${userId}:${formKey}:${recordId || 'new'}`;

export const collectSafeValues = (fields) => Object.fromEntries(Array.from(fields)
    .filter((field) => !isUnsafe(field) && (!['checkbox', 'radio'].includes(field.type) || field.checked))
    .map((field) => [field.name, field.value]));

export const saveDraft = (storage, key, values, clock = Date.now) => {
    storage.setItem(key, JSON.stringify({ version: 1, savedAt: clock(), values }));
};

export const removeDraft = (storage, key) => storage.removeItem(key);

export const loadDraft = (storage, key, clock = Date.now) => {
    try {
        const draft = JSON.parse(storage.getItem(key) ?? 'null');
        if (!draft || draft.version !== 1 || typeof draft.savedAt !== 'number' || clock() - draft.savedAt > TTL) {
            removeDraft(storage, key);
            return null;
        }

        return draft.values ?? null;
    } catch {
        removeDraft(storage, key);
        return null;
    }
};

export const removeUserDrafts = (storage, userId) => {
    const prefix = `${PREFIX}${userId}:`;
    [...Array(storage.length).keys()].map((index) => storage.key(index)).filter((key) => key?.startsWith(prefix))
        .forEach((key) => storage.removeItem(key));
};

export const purgeExpiredDrafts = (storage, clock = Date.now) => {
    [...Array(storage.length).keys()].map((index) => storage.key(index)).filter((key) => key?.startsWith(PREFIX))
        .forEach((key) => loadDraft(storage, key, clock));
};

const setStatus = (form, message, error = false) => {
    const status = form.querySelector('[data-draft-status]');
    if (status) {
        status.textContent = message;
        status.classList.toggle('text-danger', error);
    }
};

const restoreValues = (form, values) => Object.entries(values).forEach(([name, value]) => {
    const field = form.elements.namedItem(name);
    if (!field || isUnsafe(field)) return;
    if (field instanceof RadioNodeList) {
        [...field].forEach((item) => { item.checked = item.value === value; });
    } else if (field.type === 'checkbox') {
        field.checked = true;
    } else {
        field.value = value;
    }
});

const initialiseForm = (form, userId) => {
    const key = draftKey(userId, form.dataset.autosaveForm, form.dataset.autosaveRecord ?? 'new');
    const restore = loadDraft(window.localStorage, key);
    if (restore) {
        restoreValues(form, restore);
        setStatus(form, 'Draft dipulihkan');
    }

    let timer;
    form.addEventListener('input', () => {
        window.clearTimeout(timer);
        timer = window.setTimeout(() => {
            try {
                saveDraft(window.localStorage, key, collectSafeValues(form.elements));
                setStatus(form, 'Draft tersimpan di perangkat');
            } catch {
                setStatus(form, 'Draft gagal disimpan di perangkat', true);
            }
        }, 2500);
    });
    form.addEventListener('submit', () => window.sessionStorage.setItem(PENDING_KEY, key));
    form.querySelector('[data-clear-draft]')?.addEventListener('click', () => {
        removeDraft(window.localStorage, key);
        setStatus(form, 'Draft dihapus');
    });
};

export const initFormDrafts = () => {
    if (typeof document === 'undefined') return;
    const userId = document.body.dataset.draftUser;
    if (!userId) return;

    const pending = window.sessionStorage.getItem(PENDING_KEY);
    if (pending) {
        if (document.body.dataset.saveSucceeded === 'true') removeDraft(window.localStorage, pending);
        window.sessionStorage.removeItem(PENDING_KEY);
    }
    purgeExpiredDrafts(window.localStorage);
    document.querySelectorAll('[data-autosave-form]').forEach((form) => initialiseForm(form, userId));
    document.querySelectorAll('[data-clear-drafts-user]').forEach((form) => form.addEventListener('submit', () => {
        removeUserDrafts(window.localStorage, userId);
    }));
};
