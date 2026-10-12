import { buildStudentOption, filterStudents } from './withdrawal-progress.js';
import { displayStudentName } from './student-name.js';

const initialisedForms = new WeakSet();
const hideResults = (state) => {
    state.results.classList.add('d-none');
    state.lookup.setAttribute('aria-expanded', 'false');
};

export const invalidateStudentSelection = (state) => {
    state.studentId.value = '';
    state.lookup.setCustomValidity('');
    state.selected.textContent = '';
    state.selected.classList.add('d-none');
};

export const selectAchievementStudent = (state, student) => {
    state.studentId.value = String(student.id);
    state.lookup.value = displayStudentName(student.name);
    state.lookup.setCustomValidity('');
    state.selected.textContent = `${displayStudentName(student.name)} (${student.nisn}) · ${student.classroom}`;
    state.selected.classList.remove('d-none');
    hideResults(state);
};

export const initAchievementForms = (root = document) => {
    root.querySelectorAll('[data-achievement-form]').forEach((form) => {
        if (initialisedForms.has(form)) return;
        initialisedForms.add(form);
        const lookup = form.querySelector('[data-achievement-lookup]');
        if (!lookup) return;
        const students = JSON.parse(form.dataset.achievementStudents);
        const state = {
            lookup,
            studentId: form.elements.namedItem('student_id'),
            results: form.querySelector('[data-achievement-options]'),
            selected: form.querySelector('[data-achievement-selected]'),
        };
        const render = () => {
            state.results.replaceChildren();
            if (!lookup.value.trim()) return hideResults(state);
            const matches = filterStudents(students, lookup.value);
            matches.forEach((student) => state.results.append(buildStudentOption(student, (selected) => {
                selectAchievementStudent(state, selected);
                lookup.focus();
                hideResults(state);
                state.studentId.dispatchEvent(new Event('input', { bubbles: true }));
            })));
            if (!matches.length) {
                const empty = document.createElement('div');
                empty.className = 'px-3 py-2 small text-secondary bg-white';
                empty.textContent = 'Murid tidak ditemukan. Periksa kembali NISN atau nama murid.';
                state.results.append(empty);
            }
            state.results.classList.remove('d-none');
            lookup.setAttribute('aria-expanded', 'true');
        };
        lookup.addEventListener('input', () => { invalidateStudentSelection(state); render(); });
        lookup.addEventListener('focus', render);
        form.addEventListener('keydown', (event) => {
            if (event.key === 'Escape' && !state.results.classList.contains('d-none')) {
                event.preventDefault();
                event.stopPropagation();
                hideResults(state);
                lookup.focus();
                hideResults(state);
            }
            if (event.key === 'ArrowDown' || event.key === 'ArrowUp') {
                const options = [...state.results.querySelectorAll('button')];
                const current = options.indexOf(event.target);
                const next = options[current + (event.key === 'ArrowDown' ? 1 : -1)];
                if (next) { event.preventDefault(); next.focus(); }
                else if (current === 0 && event.key === 'ArrowUp') { event.preventDefault(); lookup.focus(); }
            }
        });
        form.addEventListener('focusout', (event) => {
            if (event.relatedTarget !== lookup && !state.results.contains(event.relatedTarget)) hideResults(state);
        });
        form.addEventListener('submit', (event) => {
            if (state.studentId.value) return;
            event.preventDefault();
            event.stopImmediatePropagation();
            lookup.setCustomValidity('Pilih murid dari saran yang tersedia.');
            lookup.reportValidity();
        });
        form.querySelector('[data-clear-draft]')?.addEventListener('click', () => {
            invalidateStudentSelection(state);
            lookup.value = '';
            hideResults(state);
        });
        const selected = students.find((student) => String(student.id) === state.studentId.value);
        if (selected) selectAchievementStudent(state, selected);
        else invalidateStudentSelection(state);
    });
};
