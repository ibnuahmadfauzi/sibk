import { confirmFormAction } from './confirm-form-action.js';

const json = (value, fallback) => {
    try { return JSON.parse(value); } catch { return fallback; }
};

export const caseSourceLayout = (code) => ({
    etatib: code === 'e_tatib',
    referrer: code === 'rujukan',
    wideBasicFields: code !== 'e_tatib' && code !== 'rujukan',
});

export const matchCaseStudents = (students, query) => {
    const term = query.trim().toLocaleLowerCase('id');
    if (!term) return [];
    return students.filter((student) =>
        String(student.nisn ?? '').toLocaleLowerCase('id').includes(term)
        || String(student.name ?? '').toLocaleLowerCase('id').includes(term)).slice(0, 8);
};

export const initCaseCreate = (form, request = fetch) => {
    if (!form || form.dataset.caseInitialised === 'true') return;
    form.dataset.caseInitialised = 'true';
    const find = (selector) => form.querySelector(selector);
    const source = find('#sumber');
    const studentId = find('#hidden_student_id');
    const recordId = find('#hidden_etatib_record_id');
    const search = find('#etatib_search_input');
    const studentNisn = find('#student_nisn');
    const studentName = find('#student_name');
    const classroom = find('#manual_classroom_select');
    const classroomValue = find('[data-case-classroom-value]');
    const etatibClassroom = find('#etatib_classroom_select');
    const suggestions = find('#student_lookup_results');
    const students = json(form.dataset.caseStudents, []);
    let records = json(form.dataset.caseRecords, []);
    let pagination = json(form.dataset.casePagination, { current_page: 1, last_page: 1, total: records.length });
    let selected = records.find((item) => String(item.id) === recordId.value) ?? null;
    let controller;
    let timer;

    const sourceCode = () => source.selectedOptions[0]?.dataset.code;
    const isEtatib = () => sourceCode() === 'e_tatib';
    const hideSuggestions = () => {
        suggestions.classList.add('d-none');
        suggestions.replaceChildren();
        studentNisn.setAttribute('aria-expanded', 'false');
    };
    const showSuggestions = (query) => {
        if (isEtatib() || !query.trim()) { hideSuggestions(); return; }
        const matches = matchCaseStudents(students, query);
        suggestions.replaceChildren();
        if (matches.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'list-group-item small text-secondary';
            empty.textContent = 'Murid tidak ditemukan. Lanjutkan isi data secara manual.';
            suggestions.append(empty);
        }
        for (const student of matches) {
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'list-group-item list-group-item-action text-start';
            option.setAttribute('role', 'option');
            const name = document.createElement('span');
            name.className = 'd-block fw-semibold sibk-case-suggestion-name';
            name.textContent = student.name ?? '-';
            const detail = document.createElement('span');
            detail.className = 'd-block small text-secondary';
            detail.textContent = `${student.nisn ?? '-'} · ${student.classroom_name ?? '-'}`;
            option.append(name, detail);
            option.addEventListener('click', () => {
                studentId.value = student.id ?? '';
                studentNisn.value = student.nisn ?? '';
                studentName.value = student.name ?? '';
                classroom.value = student.classroom_id ?? '';
                classroomValue.value = classroom.value;
                hideSuggestions();
                form.dispatchEvent(new Event('input', { bubbles: true }));
            });
            suggestions.append(option);
        }
        suggestions.classList.remove('d-none');
        studentNisn.setAttribute('aria-expanded', 'true');
    };

    const renderSelection = () => {
        const active = isEtatib() && !!selected;
        find('[data-case-identity-label]').textContent = active ? 'Murid terpilih' : 'Cari NISN atau nama';
        find('[data-case-etatib-query]').classList.toggle('d-none', active);
        find('[data-case-etatib-selected]').classList.toggle('d-none', !active);
        find('[data-case-etatib-results]').classList.toggle('d-none', active);
        find('[data-case-etatib-summary]').classList.toggle('d-none', !active);
        etatibClassroom.required = false;
        etatibClassroom.disabled = !active;
        find('[data-case-etatib-classroom-choice]').classList.add('d-none');
        if (!active) return;
        recordId.value = selected.id;
        studentId.value = selected.student_id ?? '';
        const needsClassroom = !selected.student_id && !selected.classroom_id;
        find('[data-case-etatib-classroom-choice]').classList.toggle('d-none', !needsClassroom);
        etatibClassroom.required = needsClassroom;
        etatibClassroom.disabled = !needsClassroom;
        if (selected.classroom_id) classroomValue.value = selected.classroom_id;
        etatibClassroom.value = needsClassroom ? classroomValue.value : '';
        find('[data-case-selected-identity]').textContent = selected.student_name ?? '-';
        find('[data-case-selected-details]').textContent = `${selected.nisn ?? '-'} · ${selected.classroom_name ?? '-'}`;
        find('[data-case-etatib-summary]').querySelectorAll('[data-case-field]').forEach((field) => {
            field.textContent = selected[field.dataset.caseField] ?? '-';
        });
    };
    const renderRecords = () => {
        const list = find('#etatib_results_container');
        list.replaceChildren();
        for (const record of records) {
            const option = document.createElement('button');
            option.type = 'button';
            option.className = 'list-group-item list-group-item-action text-start py-2';
            const identity = document.createElement('span');
            identity.className = 'd-block fw-semibold sibk-case-suggestion-name';
            identity.textContent = record.student_name ?? '-';
            const studentDetail = document.createElement('span');
            studentDetail.className = 'd-block small text-secondary';
            studentDetail.textContent = `${record.nisn ?? '-'} · ${record.classroom_name ?? '-'}`;
            const violation = document.createElement('span');
            violation.className = 'd-block small text-secondary';
            violation.textContent = `${record.violation_type ?? '-'} · ${record.occurred_at ?? '-'} · ${record.points ?? '-'} poin · ${record.category ?? '-'}`;
            option.append(identity, studentDetail, violation);
            option.addEventListener('click', () => {
                clearTimeout(timer);
                controller?.abort();
                classroomValue.value = '';
                selected = record;
                search.setCustomValidity('');
                renderSelection();
                form.dispatchEvent(new Event('input', { bubbles: true }));
            });
            list.append(option);
        }
        find('[data-case-etatib-empty]').classList.toggle('d-none', records.length !== 0);
        const total = Number(pagination.total ?? records.length);
        find('[data-case-etatib-count]').textContent = `${total} data`;
        const pages = find('[data-case-etatib-pages]');
        pages.replaceChildren();
        for (const [label, page, disabled] of [
            ['‹', Number(pagination.current_page) - 1, Number(pagination.current_page) <= 1],
            [`${pagination.current_page} / ${pagination.last_page}`, null, true],
            ['›', Number(pagination.current_page) + 1, Number(pagination.current_page) >= Number(pagination.last_page)],
        ]) {
            const button = document.createElement('button');
            button.type = 'button';
            button.className = 'btn btn-sm btn-outline-secondary';
            button.textContent = label;
            button.disabled = disabled;
            if (page) button.addEventListener('click', () => { void loadRecords(page); });
            pages.append(button);
        }
    };
    const loadRecords = async (page = 1, requestedId = null) => {
        controller?.abort();
        const currentController = new AbortController();
        controller = currentController;
        const url = new URL(form.dataset.caseSearchUrl, window.location.origin);
        url.searchParams.set('modal', '1');
        url.searchParams.set('etatib_search', '1');
        url.searchParams.set('q', search.value.trim());
        url.searchParams.set('page', String(page));
        if (requestedId) url.searchParams.set('record_id', String(requestedId));
        const count = find('[data-case-etatib-count]');
        if (!requestedId) count.textContent = 'Memuat data…';
        try {
            const response = await request(url, { headers: { Accept: 'application/json' }, signal: currentController.signal });
            if (!response.ok) throw new Error('Pencarian gagal. Coba lagi.');
            const payload = await response.json();
            if (currentController.signal.aborted || !isEtatib()) return;
            if (requestedId) {
                selected = payload.data?.find((item) => String(item.id) === String(requestedId)) ?? null;
                if (!selected) recordId.value = '';
                renderSelection();
                return;
            }
            records = payload.data ?? [];
            pagination = payload;
            renderRecords();
        } catch (error) {
            if (error.name === 'AbortError') return;
            count.textContent = error.message;
        }
    };
    const syncSource = () => {
        const layout = caseSourceLayout(sourceCode());
        const etatib = layout.etatib;
        find('[data-case-etatib-search]').classList.toggle('d-none', !etatib);
        find('[data-case-etatib-section]').classList.toggle('d-none', !etatib);
        find('[data-case-student-section]').classList.toggle('d-none', etatib);
        find('[data-case-referrer]').classList.toggle('d-none', !layout.referrer);
        find('#referrer').disabled = !layout.referrer;
        const wide = layout.wideBasicFields;
        for (const column of [find('[data-case-date-column]'), find('[data-case-field-column]')]) {
            column.classList.toggle('col-md-5', wide);
            column.classList.toggle('col-md-3', !wide);
        }
        for (const field of [studentNisn, studentName, classroom]) {
            field.disabled = etatib;
        }
        etatibClassroom.disabled = !etatib;
        studentNisn.required = !etatib;
        studentName.required = !etatib;
        if (etatib) {
            hideSuggestions();
            renderSelection();
        } else {
            find('[data-case-etatib-classroom-choice]').classList.add('d-none');
            etatibClassroom.required = false;
            selected = null;
            recordId.value = '';
            classroom.value = classroomValue.value;
            renderSelection();
        }
    };

    source.addEventListener('change', () => {
        clearTimeout(timer);
        controller?.abort();
        selected = null;
        recordId.value = '';
        studentId.value = '';
        etatibClassroom.value = '';
        classroomValue.value = '';
        search.setCustomValidity('');
        syncSource();
        if (isEtatib()) void loadRecords();
    });
    search.addEventListener('input', () => {
        search.setCustomValidity('');
        clearTimeout(timer);
        controller?.abort();
        timer = setTimeout(() => { void loadRecords(); }, 250);
    });
    find('[data-case-change-etatib]').addEventListener('click', () => {
        selected = null;
        recordId.value = '';
        studentId.value = '';
        etatibClassroom.value = '';
        classroomValue.value = '';
        search.setCustomValidity('');
        renderSelection();
        search.focus();
    });
    studentNisn.addEventListener('input', () => {
        studentId.value = '';
        showSuggestions(studentNisn.value);
    });
    studentNisn.addEventListener('focus', () => showSuggestions(studentNisn.value));
    studentNisn.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') hideSuggestions();
        if (event.key === 'ArrowDown') {
            const first = suggestions.querySelector('button');
            if (first) { event.preventDefault(); first.focus(); }
        }
    });
    studentName.addEventListener('input', () => { studentId.value = ''; });
    classroom.addEventListener('change', () => { classroomValue.value = classroom.value; });
    etatibClassroom.addEventListener('change', () => { classroomValue.value = etatibClassroom.value; });
    form.addEventListener('click', (event) => {
        if (!suggestions.contains(event.target) && event.target !== studentNisn) hideSuggestions();
    });
    find('[data-case-clear]').addEventListener('click', (event) => {
        const button = event.currentTarget;
        if (button.dataset.clearConfirmed === 'true') {
            delete button.dataset.clearConfirmed;
            return;
        }
        if (form.dataset.caseDirty === 'true') {
            event.stopImmediatePropagation();
            event.preventDefault();
            confirmFormAction(form, 'clear', () => {
                button.dataset.clearConfirmed = 'true';
                button.click();
            });
        }
    }, true);
    find('[data-case-clear]').addEventListener('click', () => {
        clearTimeout(timer);
        controller?.abort();
        form.reset();
        search.setCustomValidity('');
        [...form.elements].forEach((field) => {
            if (field.name === '_token' || field.type === 'submit' || field.type === 'button') return;
            if (field.tagName === 'SELECT') field.selectedIndex = 0;
            else field.value = '';
        });
        selected = null;
        recordId.value = '';
        studentId.value = '';
        etatibClassroom.value = '';
        classroomValue.value = '';
        search.value = '';
        hideSuggestions();
        syncSource();
        void loadRecords();
        form.dataset.caseDirty = 'false';
        find('[data-draft-restored]')?.classList.add('d-none');
        find('[data-draft-status]').textContent = 'Isian dikosongkan';
        form.dispatchEvent(new CustomEvent('sibk:form-cleared', { bubbles: true }));
    });
    form.addEventListener('input', () => { form.dataset.caseDirty = 'true'; });
    form.addEventListener('change', () => { form.dataset.caseDirty = 'true'; });
    form.addEventListener('submit', (event) => {
        if (!isEtatib() || selected) return;
        event.preventDefault();
        event.stopPropagation();
        search.setCustomValidity('Pilih data e-Tatib terlebih dahulu.');
        search.reportValidity();
    });

    syncSource();
    renderRecords();
    form.dataset.caseDirty = String(!find('[data-draft-restored]')?.classList.contains('d-none'));
    if (recordId.value && !selected) void loadRecords(1, recordId.value);
};

if (typeof document !== 'undefined') {
    document.addEventListener('sibk:modal-loaded', (event) => {
        initCaseCreate(event.target.querySelector('[data-case-create-form]'));
    });
}
