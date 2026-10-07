import { displayStudentName } from './student-name.js';

const initialisedRoots = new WeakSet();

const normalise = (value) => String(value ?? '').trim().toLocaleLowerCase('id');
const safeJson = (value, fallback = []) => {
    try {
        return JSON.parse(value ?? '');
    } catch {
        return fallback;
    }
};

const hideResults = (state) => {
    state.results?.classList.add('d-none');
    state.lookup?.setAttribute('aria-expanded', 'false');
    return false;
};

export const filterStudents = (students, term, limit = 6) => {
    const query = normalise(term);
    if (!query) return [];

    return students.filter((student) =>
        normalise(student.nisn).includes(query)
        || normalise(student.name).includes(query)
        || normalise(student.classroom).includes(query),
    ).slice(0, limit);
};

export const applyStudentSelection = (state, student) => {
    if (state.studentId) state.studentId.value = String(student.id);
    if (state.lookup) {
        state.lookup.value = `${student.nisn} — ${displayStudentName(student.name)}`;
        state.lookup.setAttribute('aria-expanded', 'false');
        state.lookup.setCustomValidity?.('');
    }
    if (state.studentName) state.studentName.value = displayStudentName(student.name);
    if (state.studentClassroom) state.studentClassroom.value = student.classroom;
    if (state.selected) {
        state.selected.querySelector('[data-withdrawal-selected-name]').textContent = `${displayStudentName(student.name)} (${student.nisn})`;
        state.selected.querySelector('[data-withdrawal-selected-classroom]').textContent = student.classroom;
        state.selected.classList.remove('d-none');
    }
    hideResults(state);
    return student;
};

export const validateLookupSelection = (state) => {
    if (state.studentId?.value) return true;
    state.lookup?.setCustomValidity?.('Pilih murid dari saran yang tersedia.');
    state.lookup?.reportValidity?.();
    return false;
};

export const buildStudentOption = (student, onSelect, create = document.createElement.bind(document)) => {
    const option = create('button');
    option.type = 'button';
    option.className = 'list-group-item list-group-item-action px-3 py-2 text-start';
    option.setAttribute('role', 'option');
    const nisn = create('strong');
    nisn.className = 'd-block small text-primary';
    nisn.textContent = student.nisn;
    const name = create('span');
    name.className = 'd-block fw-semibold text-dark';
    name.textContent = displayStudentName(student.name);
    const classroom = create('span');
    classroom.className = 'd-block small text-secondary';
    classroom.textContent = student.classroom;
    option.append(nisn, name, classroom);
    option.addEventListener('click', () => onSelect(student));
    return option;
};

export const renderLookupResults = (state, students, environment = {}) => {
    if (!state.results || !state.lookup || state.lookup.disabled) return false;
    const create = environment.createElement ?? ((tag) => document.createElement(tag));
    state.results.replaceChildren();
    if (!normalise(state.lookup.value)) return hideResults(state);

    const matches = filterStudents(students, state.lookup.value);
    if (matches.length === 0) {
        const empty = create('div');
        empty.className = 'px-3 py-2 small text-secondary bg-white';
        empty.textContent = 'Murid tidak ditemukan. Periksa kembali NISN atau nama murid.';
        state.results.append(empty);
    } else {
        matches.forEach((student) => state.results.append(
            buildStudentOption(student, (selected) => applyStudentSelection(state, selected), create),
        ));
    }
    state.results.classList.remove('d-none');
    state.lookup.setAttribute('aria-expanded', 'true');
    return true;
};

const createLookupState = (page) => ({
    form: page.querySelector('[data-withdrawal-create-form]'),
    lookup: page.querySelector('#withdrawal-student-lookup'),
    results: page.querySelector('#withdrawal-student-options'),
    studentId: page.querySelector('#withdrawal-student-id'),
    studentName: page.querySelector('#withdrawal-student-name'),
    studentClassroom: page.querySelector('#withdrawal-student-classroom'),
    selected: page.querySelector('[data-withdrawal-selected-student]'),
});

const wireCreatePage = (page, root, environment) => {
    const state = createLookupState(page);
    if (!state.form || !state.lookup) return;
    const students = safeJson(page.dataset.withdrawalStudents);

    state.lookup.addEventListener('input', () => {
        state.studentId.value = '';
        if (state.studentName) state.studentName.value = '';
        if (state.studentClassroom) state.studentClassroom.value = '';
        state.selected?.classList.add('d-none');
        renderLookupResults(state, students, environment);
    });
    state.lookup.addEventListener('focus', () => renderLookupResults(state, students, environment));
    state.lookup.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') hideResults(state);
        if (event.key === 'ArrowDown') {
            const first = state.results?.querySelector('button');
            if (first) { event.preventDefault(); first.focus(); }
        }
    });
    page.addEventListener('click', (event) => {
        if (!state.results?.contains(event.target) && event.target !== state.lookup) hideResults(state);
    });
    state.form.addEventListener('submit', (event) => {
        if (validateLookupSelection(state)) return;
        event.preventDefault();
        event.stopPropagation();
    });

    const selected = students.find((student) => String(student.id) === String(state.studentId.value));
    if (selected) applyStudentSelection(state, selected);
};

export const positionPopover = (popover, button, environment = {}) => {
    const windowObject = environment.window ?? globalThis.window;
    const rect = button.getBoundingClientRect?.() ?? { left: 0, top: 0, bottom: 0, right: 0 };
    const vw = windowObject?.innerWidth ?? 1024;
    const vh = windowObject?.innerHeight ?? 768;
    const width = Math.min(320, vw - 16);
    const pw = popover.offsetWidth || width;
    const ph = popover.offsetHeight || 180;

    let left = rect.left;
    if (left + pw > vw - 8) left = vw - pw - 8;
    if (left < 8) left = 8;

    let top = rect.bottom + 8;
    if (top + ph > vh - 8) top = rect.top - ph - 8;
    if (top < 8) top = 8;

    popover.style.width = `${width}px`;
    popover.style.left = `${left}px`;
    popover.style.top = `${top}px`;
};

export const renderPopoverHistory = (container, items, create = (tag) => document.createElement(tag)) => {
    container.replaceChildren();
    items.forEach((item, index) => {
        const entry = create('div');
        entry.className = `sibk-follow-up-entry${index === 0 ? ' sibk-follow-up-entry--latest' : ''}`;
        const date = create('div');
        date.className = 'sibk-follow-up-entry__date';
        date.textContent = item.follow_up_date_formatted ?? item.followUpDateFormatted ?? '—';
        const type = create('div');
        type.className = 'sibk-follow-up-entry__type';
        const dot = create('span');
        dot.className = 'sibk-follow-up-entry__dot';
        const label = create('span');
        label.className = 'badge rounded-pill withdrawal-progress-badge';
        label.setAttribute('data-withdrawal-progress', item.progress ?? 'in_progress');
        label.textContent = item.progress_label ?? item.progressLabel ?? '—';
        type.append(dot, label);
        entry.append(date, type);
        if (item.notes) {
            const notes = create('div');
            notes.className = 'small text-muted mt-1';
            notes.textContent = item.notes;
            entry.append(notes);
        }
        container.append(entry);
    });
};

const validationMessage = (payload) => {
    const errors = payload?.errors ?? {};
    return Object.values(errors).flat()[0] ?? payload?.message ?? 'Progres penanganan belum dapat disimpan.';
};

export const initWithdrawalProgress = async (root = document, environment = {}) => {
    if (initialisedRoots.has(root)) return false;
    initialisedRoots.add(root);

    const createPage = root.querySelector?.('[data-withdrawal-create-page]');
    if (createPage) wireCreatePage(createPage, root, environment);
    root.addEventListener('sibk:modal-loaded', (event) => {
        const page = event.target.querySelector?.('[data-withdrawal-create-page]');
        if (page) wireCreatePage(page, root, environment);
    });

    const followUpModal = root.querySelector?.('[data-withdrawal-follow-up-modal]');
    const Modal = environment.Modal;
    const followUpModalInstance = followUpModal ? Modal.getOrCreateInstance(followUpModal) : null;
    const followUpForm = followUpModal?.querySelector('[data-withdrawal-follow-up-form]');
    let followUpTarget = null;
    let activePopover = null;
    let activePopoverButton = null;

    const closePopover = () => {
        if (activePopover) activePopover.style.display = 'none';
        activePopoverButton?.setAttribute('aria-expanded', 'false');
        activePopover = null;
        activePopoverButton = null;
    };

    root.addEventListener('click', (event) => {
        const noteTrigger = event.target.closest?.('[data-withdrawal-note-toggle]');
        if (noteTrigger) {
            const detail = root.getElementById?.(noteTrigger.getAttribute('aria-controls'))
                ?? root.querySelector?.(`#${noteTrigger.getAttribute('aria-controls')}`);
            if (!detail) return;
            const expanded = noteTrigger.getAttribute('aria-expanded') === 'true';
            detail.classList.toggle('d-none', expanded);
            noteTrigger.setAttribute('aria-expanded', String(!expanded));
            noteTrigger.title = expanded ? 'Tampilkan catatan' : 'Tutup catatan';
            noteTrigger.setAttribute('aria-label', `${noteTrigger.title} pengunduran diri ${displayStudentName(noteTrigger.dataset.studentName)}`);
            return;
        }

        const popoverTrigger = event.target.closest?.('[data-withdrawal-popover-trigger]');
        if (popoverTrigger) {
            event.preventDefault();
            const popover = popoverTrigger.parentElement?.querySelector('[data-withdrawal-popover]');
            const opening = popover && popover !== activePopover;
            closePopover();
            if (opening) {
                popover.style.display = 'block';
                positionPopover(popover, popoverTrigger, environment);
                popoverTrigger.setAttribute('aria-expanded', 'true');
                activePopover = popover;
                activePopoverButton = popoverTrigger;
            }
            return;
        }

        const followUpTrigger = event.target.closest?.('[data-withdrawal-follow-up-open]');
        if (followUpTrigger && followUpForm) {
            event.preventDefault();
            closePopover();
            followUpTarget = {
                id: followUpTrigger.dataset.withdrawalId,
                url: followUpTrigger.dataset.storeUrl,
                trigger: followUpTrigger,
            };
            followUpForm.reset();
            followUpForm.action = followUpTarget.url;
            followUpModal.querySelector('[data-follow-up-student]').textContent = displayStudentName(followUpTrigger.dataset.studentName);
            const date = followUpForm.querySelector('[name="follow_up_date"]');
            date.min = followUpTrigger.dataset.minDate ?? '';
            followUpForm.querySelector('[name="progress"]').value = followUpTrigger.dataset.currentProgress;
            followUpModal.querySelector('[data-follow-up-error]').classList.add('d-none');
            followUpModalInstance.show();
            return;
        }

        if (activePopover && !activePopover.contains(event.target)) closePopover();
    });

    root.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') closePopover();
    });
    const windowObject = environment.window ?? globalThis.window;
    windowObject?.addEventListener?.('resize', closePopover);
    windowObject?.addEventListener?.('scroll', closePopover, true);

    followUpForm?.addEventListener('submit', async (event) => {
        event.preventDefault();
        if (!followUpTarget || followUpForm.dataset.busy === 'true') return;
        const submit = followUpForm.querySelector('[data-follow-up-submit]');
        const error = followUpModal.querySelector('[data-follow-up-error]');
        followUpForm.dataset.busy = 'true';
        submit.disabled = true;
        error.classList.add('d-none');

        try {
            const fetcher = environment.fetch ?? window.fetch.bind(window);
            const response = await fetcher(followUpTarget.url, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: new FormData(followUpForm),
            });
            const payload = await response.json();
            if (!response.ok) throw new Error(validationMessage(payload));

            const data = payload.data;
            const wrapper = root.querySelector(`[data-withdrawal-id="${followUpTarget.id}"]`);
            const history = wrapper?.querySelector('[data-withdrawal-history]');
            if (history) renderPopoverHistory(history, data.follow_ups, environment.createElement);
            const count = wrapper?.querySelector('[data-withdrawal-follow-up-label]');
            if (count) count.textContent = data.follow_ups[0]?.progress_label ?? data.current_progress_label;
            const badge = wrapper?.querySelector('[data-withdrawal-popover-trigger]');
            if (badge) badge.dataset.withdrawalProgress = data.follow_ups[0]?.progress ?? data.current_progress;
            followUpTarget.trigger.dataset.currentProgress = data.current_progress;
            followUpModalInstance.hide();
        } catch (caught) {
            error.textContent = caught.message;
            error.classList.remove('d-none');
        } finally {
            followUpForm.dataset.busy = 'false';
            submit.disabled = false;
        }
    });

    return true;
};
