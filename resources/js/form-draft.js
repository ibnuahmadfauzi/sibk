const PREFIX = 'sibk:draft:v1:';
const TTL = 24 * 60 * 60 * 1000;
const PENDING_KEY = 'sibk:draft:pending';
const initialisedForms = new WeakSet();
const initialisedLogoutForms = new WeakSet();

const isUnsafe = (field) => {
    const name = (field.name ?? '').toLowerCase();

    return !name || field.disabled || field.type === 'file'
        || ['_token', '_method'].includes(name)
        || /password|token|credential|secret/.test(name);
};

export const draftKey = (userId, formKey, recordId = 'new') => `${PREFIX}${userId}:${formKey}:${recordId || 'new'}`;

export const collectSafeValues = (fields) => Array.from(fields)
    .filter((field) => !isUnsafe(field) && (!['checkbox', 'radio'].includes(field.type) || field.checked))
    .reduce((values, field) => {
        const current = values[field.name];
        values[field.name] = current === undefined
            ? field.value
            : [...(Array.isArray(current) ? current : [current]), field.value];

        return values;
    }, {});

export const saveDraft = (storage, key, values, clock = Date.now) => {
    storage.setItem(key, JSON.stringify({ version: 1, savedAt: clock(), values }));
};

export const removeDraft = (storage, key) => storage.removeItem(key);

export const loadDraft = (storage, key, clock = Date.now) => {
    try {
        const draft = JSON.parse(storage.getItem(key) ?? 'null');
        if (!draft || draft.version !== 1 || typeof draft.savedAt !== 'number' || clock() - draft.savedAt >= TTL) {
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
    if (!field) return;
    const isList = typeof RadioNodeList !== 'undefined' && field instanceof RadioNodeList;
    if (isUnsafe(isList ? field[0] : field)) return;
    const selected = (Array.isArray(value) ? value : [value]).map(String);
    if (isList) {
        [...field].forEach((item) => { item.checked = selected.includes(item.value); });
    } else if (field.type === 'checkbox') {
        field.checked = selected.includes(field.value);
    } else {
        field.value = value;
    }
});

export const initFormDraft = (form, environment) => {
    if (initialisedForms.has(form)) return false;
    initialisedForms.add(form);

    const {
        storage, sessionStorage, userId, clock = Date.now,
        setTimer = window.setTimeout.bind(window), clearTimer = window.clearTimeout.bind(window),
    } = environment;
    const key = draftKey(userId, form.dataset.autosaveForm, form.dataset.autosaveRecord ?? 'new');
    const restore = loadDraft(storage, key, clock);
    if (restore) {
        restoreValues(form, restore);
        setStatus(form, 'Draft dipulihkan');
    }

    let timer;
    const persist = () => {
        try {
            saveDraft(storage, key, collectSafeValues(form.elements), clock);
            setStatus(form, 'Draft tersimpan di perangkat');
        } catch {
            setStatus(form, 'Draft gagal disimpan di perangkat', true);
        }
    };
    form.addEventListener('input', () => {
        clearTimer(timer);
        timer = setTimer(persist, 2500);
    });
    form.addEventListener('submit', () => {
        clearTimer(timer);
        persist();
        sessionStorage.setItem(PENDING_KEY, key);
    });
    form.querySelector('[data-clear-draft]')?.addEventListener('click', () => {
        removeDraft(storage, key);
        setStatus(form, 'Draft dihapus');
    });

    return true;
};

export const initFormDrafts = (root = document) => {
    if (typeof document === 'undefined') return;
    const userId = document.body.dataset.draftUser;
    if (!userId) return;

    if (root === document) {
        const pending = window.sessionStorage.getItem(PENDING_KEY);
        if (pending) {
            if (document.body.dataset.saveSucceeded === 'true') removeDraft(window.localStorage, pending);
            window.sessionStorage.removeItem(PENDING_KEY);
        }
        purgeExpiredDrafts(window.localStorage);
    }
    root.querySelectorAll('[data-autosave-form]').forEach((form) => initFormDraft(form, {
        storage: window.localStorage,
        sessionStorage: window.sessionStorage,
        userId,
    }));
    root.querySelectorAll('[data-clear-drafts-user]').forEach((form) => {
        if (initialisedLogoutForms.has(form)) return;
        initialisedLogoutForms.add(form);
        form.addEventListener('submit', () => removeUserDrafts(window.localStorage, userId));
    });
};
