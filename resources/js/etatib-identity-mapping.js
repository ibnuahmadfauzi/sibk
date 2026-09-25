const escapeHtml = (value) => {
    const element = document.createElement('span');
    element.textContent = String(value ?? '');

    return element.innerHTML;
};

export const initEtatibIdentityMapping = async () => {
    const page = document.querySelector('[data-etatib-mapping-page]');
    const modalElement = document.getElementById('etatib-mapping-modal');

    if (!page || !modalElement) return;

    const { default: Modal } = await import('bootstrap/js/dist/modal.js');
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
                    <strong class="d-block">${escapeHtml(candidate.name)}</strong>
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

    document.querySelectorAll('[data-etatib-map-open]').forEach((button) => {
        button.addEventListener('click', () => {
            form.action = button.dataset.action;
            method.disabled = button.dataset.method !== 'patch';
            modalElement.querySelector('[data-etatib-source-name]').textContent = button.dataset.sourceName;
            modalElement.querySelector('[data-etatib-source-nisn]').textContent = `NISN ${button.dataset.sourceNisn}`;
            modalElement.querySelector('[data-etatib-source-classroom]').textContent = `Kelas ${button.dataset.sourceClassroom}`;
            search.value = button.dataset.sourceName;
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
};
