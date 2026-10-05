const initialisedForms = new WeakSet();

const normalise = (value) => String(value ?? '').trim().toLocaleLowerCase('id');

export const filterConsultationStudents = (students, query) => {
    const term = normalise(query);
    if (!term) return [];

    return students.filter((student) => normalise(student.nisn).includes(term)
        || normalise(student.name).includes(term)).slice(0, 8);
};

export const initConsultationCreateForm = (page) => {
    if (initialisedForms.has(page)) return;
    initialisedForms.add(page);

    const form = page.querySelector('#consultation-create-form');
    const nisn = page.querySelector('#student_nisn');
    const name = page.querySelector('#student_name');
    const classroom = page.querySelector('#manual_classroom_select');
    const studentId = page.querySelector('#hidden_student_id');
    const results = page.querySelector('#student_lookup_results');
    if (!form || !nisn || !name || !classroom || !studentId || !results) return;

    let students = [];
    try { students = JSON.parse(page.dataset.consultationStudents ?? '[]'); } catch { /* Empty lookup. */ }

    const hide = () => {
        results.classList.add('d-none');
        results.replaceChildren();
        nisn.setAttribute('aria-expanded', 'false');
    };

    const clearSelection = () => {
        studentId.value = '';
        classroom.querySelectorAll('[data-lookup-classroom]').forEach((option) => {
            const selected = classroom.value === option.value;
            option.remove();
            if (selected) classroom.value = '';
        });
    };

    const select = (student, notifyDraft = true) => {
        studentId.value = String(student.id);
        nisn.value = String(student.nisn ?? '');
        name.value = String(student.name ?? '');
        if (student.classroom_id && ![...classroom.options].some((option) => option.value === String(student.classroom_id))) {
            const option = new Option(student.classroom, String(student.classroom_id));
            option.dataset.lookupClassroom = 'true';
            classroom.add(option);
        }
        classroom.value = String(student.classroom_id ?? '');
        hide();
        if (notifyDraft) studentId.dispatchEvent(new Event('input', { bubbles: true }));
    };

    const render = () => {
        results.replaceChildren();
        if (!normalise(nisn.value)) return hide();

        const matches = filterConsultationStudents(students, nisn.value);
        if (matches.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'px-3 py-2 small text-secondary bg-white';
            empty.textContent = 'Murid tidak ditemukan. Isi NISN, nama, dan rombel secara manual.';
            results.append(empty);
        } else {
            matches.forEach((student) => {
                const option = document.createElement('button');
                option.type = 'button';
                option.className = 'list-group-item list-group-item-action px-3 py-2 text-start';
                option.setAttribute('role', 'option');
                const number = document.createElement('strong');
                number.className = 'd-block small text-primary';
                number.textContent = student.nisn;
                const identity = document.createElement('span');
                identity.className = 'd-block fw-semibold text-dark';
                identity.textContent = student.name;
                const className = document.createElement('span');
                className.className = 'd-block small text-secondary';
                className.textContent = student.classroom;
                option.append(number, identity, className);
                option.addEventListener('click', () => select(student));
                results.append(option);
            });
        }
        results.classList.remove('d-none');
        nisn.setAttribute('aria-expanded', 'true');
    };

    nisn.addEventListener('input', () => {
        clearSelection();
        render();
    });
    nisn.addEventListener('focus', render);
    name.addEventListener('input', clearSelection);
    classroom.addEventListener('change', clearSelection);
    nisn.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') hide();
        if (event.key === 'ArrowDown') {
            const first = results.querySelector('button');
            if (first) { event.preventDefault(); first.focus(); }
        }
    });
    results.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') { nisn.focus(); hide(); }
        if (!['ArrowUp', 'ArrowDown'].includes(event.key)) return;
        event.preventDefault();
        const options = [...results.querySelectorAll('button')];
        const next = options.indexOf(document.activeElement) + (event.key === 'ArrowDown' ? 1 : -1);
        if (next < 0) nisn.focus();
        else options[Math.min(next, options.length - 1)]?.focus();
    });
    page.addEventListener('click', (event) => {
        if (event.target !== nisn && !results.contains(event.target)) hide();
    });
    page.addEventListener('focusout', (event) => {
        if (!page.contains(event.relatedTarget)) hide();
    });
    page.querySelector('[data-clear-fields]')?.addEventListener('click', () => {
        classroom.querySelectorAll('[data-lookup-classroom]').forEach((option) => option.remove());
        hide();
        nisn.focus();
    });

    const selected = students.find((student) => String(student.id) === studentId.value);
    if (selected) select(selected, false);
};

export const initConsultationCreate = (root = document) => {
    root.querySelectorAll('[data-consultation-create]').forEach(initConsultationCreateForm);
    root.addEventListener('sibk:modal-loaded', (event) => {
        event.target.querySelectorAll('[data-consultation-create]').forEach(initConsultationCreateForm);
    });
};
