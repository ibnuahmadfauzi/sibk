import { displayStudentName } from './student-name.js';

const escapeHtml = (value) => {
    const element = document.createElement('span');
    element.textContent = String(value ?? '');

    return element.innerHTML;
};

export const initEtatibIdentityMapping = async (root = document, { Modal } = {}) => {
    const page = root.querySelector('[data-etatib-mapping-page]');
    const modalElement = root.getElementById('etatib-mapping-modal');

    if (!page || !modalElement) return;

    const modal = Modal.getOrCreateInstance(modalElement);
    const form = modalElement.querySelector('[data-etatib-map-form]');
    const method = modalElement.querySelector('[data-etatib-map-method]');
    const studentId = modalElement.querySelector('[data-etatib-student-id]');
    const search = modalElement.querySelector('[data-etatib-candidate-search]');
    const searchButton = modalElement.querySelector('[data-etatib-candidate-submit]');
    const results = modalElement.querySelector('[data-etatib-candidate-results]');
    const save = modalElement.querySelector('[data-etatib-map-save]');
    const confirmed = modalElement.querySelector('#etatib_mapping_confirmed');

    const resetSelection = () => {
        studentId.value = '';
        save.disabled = true;
    };

    const selectCandidate = (button) => {
        results.querySelectorAll('[data-etatib-candidate]').forEach((candidate) => {
            candidate.classList.remove('table-primary');
            candidate.querySelector('button').textContent = 'Pilih';
        });
        button.closest('[data-etatib-candidate]').classList.add('table-primary');
        button.textContent = 'Dipilih';
        studentId.value = button.dataset.studentId;
        save.disabled = false;
    };

    const renderCandidates = (candidates) => {
        if (!candidates.length) {
            results.innerHTML = '<p class="text-muted text-center small p-4 mb-0">Murid tidak ditemukan.</p>';
            return;
        }

        const rows = candidates.map((candidate) => `
            <tr data-etatib-candidate>
                <td>
                    <strong class="d-block">${escapeHtml(displayStudentName(candidate.name))}</strong>
                    <span class="text-muted small">NISN ${escapeHtml(candidate.nisn)}</span>
                </td>
                <td>
                    ${escapeHtml(candidate.classroom)}
                    <span class="text-muted small d-block">${escapeHtml(candidate.academic_year)}</span>
                </td>
                <td>
                    ${escapeHtml(candidate.status)}
                    <span class="text-muted small d-block">${escapeHtml(candidate.source)}</span>
                </td>
                <td class="text-end">
                    <button
                        class="btn btn-outline-primary btn-sm"
                        type="button"
                        data-student-id="${escapeHtml(candidate.id)}"
                    >Pilih</button>
                </td>
            </tr>
        `).join('');

        results.innerHTML = `
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Murid</th><th>Rombel/Tahun</th><th>Status/Sumber</th><th></th></tr></thead>
                    <tbody>${rows}</tbody>
                </table>
            </div>
        `;
        results.querySelectorAll('[data-etatib-candidate] button').forEach((button) => {
            button.addEventListener('click', () => selectCandidate(button));
        });
    };

    const findCandidates = async () => {
        const query = search.value.trim();
        resetSelection();

        if (query.length < 2) {
            results.innerHTML = '<p class="text-muted text-center small p-4 mb-0">Masukkan minimal dua karakter.</p>';
            return;
        }

        results.innerHTML = '<p class="text-muted text-center small p-4 mb-0">Mencari data murid...</p>';
        try {
            const url = new URL(page.dataset.candidateUrl, window.location.origin);
            url.searchParams.set('search', query);
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
                credentials: 'same-origin',
            });
            if (!response.ok) throw new Error('candidate_request_failed');

            const payload = await response.json();
            renderCandidates(payload.data ?? []);
        } catch {
            results.innerHTML = '<p class="text-danger text-center small p-4 mb-0">Pencarian murid gagal. Coba kembali.</p>';
        }
    };

    root.querySelectorAll('[data-etatib-map-open]').forEach((button) => {
        button.addEventListener('click', () => {
            form.action = button.dataset.action;
            method.disabled = button.dataset.method !== 'patch';
            modalElement.querySelector('[data-etatib-source-name]').textContent = displayStudentName(button.dataset.sourceName);
            modalElement.querySelector('[data-etatib-source-nisn]').textContent = `NISN ${button.dataset.sourceNisn}`;
            modalElement.querySelector('[data-etatib-source-classroom]').textContent = `Rombel ${button.dataset.sourceClassroom}`;
            search.value = displayStudentName(button.dataset.sourceName);
            confirmed.checked = false;
            resetSelection();
            results.innerHTML = '<p class="text-muted text-center small p-4 mb-0">Tekan Cari untuk melihat kandidat murid.</p>';
            modal.show();
        });
    });

    searchButton.addEventListener('click', findCandidates);
    search.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            findCandidates();
        }
    });

    const autoModalElement = root.getElementById('etatib-auto-modal');
    const autoModal = Modal.getOrCreateInstance(autoModalElement);
    const autoMode = autoModalElement.querySelector('[data-etatib-auto-mode]');
    const autoFingerprint = autoModalElement.querySelector('[data-etatib-auto-fingerprint]');
    const autoResults = autoModalElement.querySelector('[data-etatib-auto-results]');
    const autoApply = autoModalElement.querySelector('[data-etatib-auto-apply]');
    const autoConfirmed = autoModalElement.querySelector('[data-etatib-auto-confirmed]');

    root.querySelectorAll('[data-etatib-auto-open]').forEach((button) => {
        button.addEventListener('click', async () => {
            const mode = 'nisn';
            autoMode.value = mode;
            autoFingerprint.value = '';
            autoConfirmed.checked = false;
            autoApply.disabled = true;
            autoResults.innerHTML = '<p class="text-muted mb-0">Memeriksa konflik dan murid master...</p>';
            autoModal.show();

            try {
                const url = new URL(page.dataset.automaticPreviewUrl, window.location.origin);
                url.searchParams.set('mode', mode);
                const response = await fetch(url, { headers: { Accept: 'application/json' }, credentials: 'same-origin' });
                if (!response.ok) throw new Error('preview_failed');
                const preview = await response.json();
                if (autoMode.value !== mode) return;

                const method = 'NISN yang sama';
                const rows = preview.proposals.map((proposal) => `
                    <tr>
                        <td><strong class="d-block">${escapeHtml(displayStudentName(proposal.source_name))}</strong><span class="text-muted small">NISN ${escapeHtml(proposal.source_nisn)}</span></td>
                        <td><strong class="d-block">${escapeHtml(displayStudentName(proposal.student_name))}</strong><span class="text-muted small">NISN ${escapeHtml(proposal.student_nisn)}</span></td>
                    </tr>
                `).join('');
                const skipped = preview.skipped.map((item) => `<li>${escapeHtml(displayStudentName(item.source_name))} (NISN ${escapeHtml(item.source_nisn)}): ${escapeHtml(item.reason)}</li>`).join('');
                autoResults.innerHTML = `
                    <p class="small mb-2">Metode: ${method}. <strong>${preview.proposals.length}</strong> identitas cocok; <strong>${preview.skipped.length}</strong> tetap perlu ditinjau.</p>
                    ${rows ? `<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Data e-Tatib</th><th>Murid master</th></tr></thead><tbody>${rows}</tbody></table></div>` : '<p class="text-muted mb-0">Tidak ada kecocokan tunggal yang dapat diterapkan.</p>'}
                    ${skipped ? `<details class="mt-3"><summary>Identitas yang tetap perlu dicocokkan manual</summary><ul class="small mt-2 mb-0">${skipped}</ul></details>` : ''}
                `;
                autoFingerprint.value = preview.fingerprint;
                autoApply.disabled = preview.proposals.length === 0;
            } catch {
                autoResults.innerHTML = '<p class="text-danger mb-0">Pratinjau gagal dimuat. Coba kembali.</p>';
            }
        });
    });
};
