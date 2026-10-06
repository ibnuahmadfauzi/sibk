import { displayStudentName } from './student-name.js';

const element = (tag, className = '', text = '') => {
    const node = document.createElement(tag);
    node.className = className;
    node.textContent = text;

    return node;
};

const choiceLabel = (student) => `NISN ${student.nisn} · Kelas ${student.classroom}${student.academic_year ? ` (${student.academic_year})` : ''}`;

export const renderEtatibPreview = (container, preview, dapodikUrl, revokeUrl = '') => {
    container.replaceChildren();
    const groups = preview.duplicate_groups ?? [];
    container.append(element('p', 'fw-semibold mb-3', groups.length
        ? `${preview.received} baris diterima; ${preview.rows} pelanggaran akan disinkronkan jika seluruh duplikasi disetujui.`
        : `${preview.rows} pelanggaran diterima dari e-Tatib.`));
    if (groups.length) {
        container.append(element('div', 'alert alert-warning',
            'Baris identik belum membuktikan satu kejadian. Konfirmasi hanya setelah memeriksa sumber; pilih Batal untuk menunda sinkronisasi. Total poin sumber tidak diubah.'));
        groups.forEach((group) => {
            const card = element('article', 'border rounded-3 p-3 mb-3');
            card.append(
                element('h3', 'fs-6 fw-semibold', `Baris ${group.rows.join(', ')}: ${group.count} salinan identik`),
                element('strong', 'd-block', displayStudentName(group.name)),
                element('span', 'd-block small', `NISN ${group.nisn} · Kelas ${group.classroom}`),
                element('p', 'small mt-2 mb-2', 'Sembilan field sumber sama: NISN, nama, kelas, pelanggaran, kategori, poin, pencatat, tanggal, dan total poin.'),
            );
            if (group.approved) {
                card.append(element('p', 'small mb-2', 'Keputusan tersimpan: dianggap satu kejadian untuk isi dan jumlah salinan ini.'));
                if (revokeUrl && group.decision_id) {
                    const revoke = element('form');
                    revoke.method = 'POST';
                    revoke.action = revokeUrl.replace('__DECISION__', group.decision_id);
                    const review = element('details');
                    review.append(element('summary', 'fw-semibold', 'Tinjau pembatalan keputusan'));
                    revoke.append(element('p', 'small mt-2', 'Pembatalan menahan duplikasi pada sinkronisasi berikutnya sampai disetujui kembali. Riwayat yang sudah tersimpan tetap dipertahankan.'));
                    const revokeLabel = element('label', 'd-flex gap-2 align-items-start mb-2');
                    const revokeChoice = element('input', 'form-check-input mt-1');
                    revokeChoice.type = 'checkbox';
                    revokeChoice.required = true;
                    revokeLabel.append(revokeChoice, element('span', '', 'Saya ingin membatalkan keputusan ini.'));
                    revoke.append(revokeLabel);
                    [['_token', document.querySelector('meta[name="csrf-token"]')?.content ?? ''], ['_method', 'DELETE']].forEach(([name, value]) => {
                        const input = element('input');
                        input.type = 'hidden';
                        input.name = name;
                        input.value = value;
                        revoke.append(input);
                    });
                    const button = element('button', 'btn btn-outline-danger btn-sm', 'Batalkan Keputusan');
                    button.type = 'submit';
                    revoke.append(button);
                    review.append(revoke);
                    card.append(review);
                }
            } else {
                const label = element('label', 'd-flex gap-2 align-items-start');
                const choice = element('input', 'form-check-input mt-1');
                choice.type = 'checkbox';
                choice.value = group.key;
                choice.dataset.duplicateChoice = '';
                label.append(choice, element('span', '', 'Saya sudah memeriksa sumber: anggap sebagai satu kejadian.'));
                card.append(label);
            }
            container.append(card);
        });
    }
    const identities = element('div');
    identities.dataset.etatibIdentities = '';
    container.append(identities);
    container = identities;
    const identityCount = preview.identity_conflicts.length;
    if (preview.undated > 0) {
        container.append(element('div', 'alert alert-warning',
            `${preview.undated} pelanggaran akan disimpan tanpa tanggal karena tanggal dari API berubah saat dibaca.`));
    }
    if (preview.missing > 0) {
        container.append(element('div', 'alert alert-warning',
            `${preview.missing} pelanggaran lama tidak dikirim API. Riwayat tersebut tetap disimpan di BK.`));
    } else if (identityCount > 0) {
        container.append(element('div', 'alert alert-warning',
            `${identityCount} identitas belum cocok, terkait ${preview.conflicts} pelanggaran. Pilih murid yang benar jika sudah yakin.`));
    } else if (preview.undated === 0) {
        container.append(element('div', 'alert alert-success', 'Identitas pada data yang siap disinkronkan cocok.'));
    }

    if (preview.missing_students > 0) {
        const link = element('a', 'd-inline-block mb-3 fw-semibold', 'Periksa murid yang belum ada di Data Master');
        link.href = dapodikUrl;
        container.append(link);
    }

    if (preview.conflicts > 0) {
        [
            ['name_mismatch', preview.name_mismatches, 'nama berbeda'],
            ['nisn_not_found', preview.missing_students, 'NISN belum ada di master'],
        ].forEach(([kind, count, label]) => {
            if (!count) return;
            const details = element('details', 'mb-3');
            details.append(element('summary', 'fw-semibold', `Lihat ${count} identitas: ${label}`));
            const list = element('div', 'd-grid gap-3 mt-2');
            const bulkButtons = element('div', 'd-flex flex-wrap gap-2 mt-2');
            const bulkStatus = element('p', 'small text-muted mt-2 mb-0');
            (kind === 'name_mismatch'
                ? [['nisn', 'Pilih berdasarkan NISN'], ['name', 'Pilih berdasarkan nama']]
                : [['name', 'Pilih berdasarkan nama']]
            ).forEach(([basis, textLabel]) => {
                const button = element('button', 'btn btn-outline-primary btn-sm', textLabel);
                button.type = 'button';
                button.addEventListener('click', () => {
                    let selected = 0;
                    list.querySelectorAll('[data-etatib-conflict]').forEach((card) => {
                        const choices = card.querySelectorAll(`[data-identity-choice][data-choice-basis="${basis}"]`);
                        if (choices.length !== 1) return;
                        choices[0].checked = true;
                        selected++;
                    });
                    bulkStatus.textContent = `${selected} kartu diisi berdasarkan ${basis === 'nisn' ? 'NISN' : 'nama'}. Periksa pilihan pada setiap kartu sebelum sinkronisasi.`;
                });
                bulkButtons.append(button);
            });
            preview.identity_conflicts.filter((item) => item.kind === kind).forEach((item, index) => {
                const row = element('article', 'sibk-etatib-conflict rounded-3 p-3');
                row.dataset.etatibConflict = '';
                row.append(
                    element('span', 'text-muted small d-block', 'e-Tatib'),
                    element('strong', 'd-block', displayStudentName(item.name)),
                    element('span', 'd-block small', `NISN ${item.nisn} · Kelas ${item.classroom}`),
                );

                const groupName = `identity-${kind}-${index}`;
                const option = (student, labelText, basis) => {
                    const label = element('label', 'sibk-etatib-choice d-flex gap-2 align-items-start border rounded-3 p-2 mt-2');
                    const radio = document.createElement('input');
                    radio.type = 'radio';
                    radio.name = groupName;
                    radio.value = student.id;
                    radio.className = 'form-check-input mt-1';
                    radio.dataset.identityChoice = '';
                    radio.dataset.choiceBasis = basis;
                    radio.dataset.nisn = item.nisn;
                    radio.dataset.name = item.name;
                    const copy = element('span', 'd-block');
                    copy.append(
                        element('span', 'd-block small text-muted', labelText),
                        element('strong', 'd-block', displayStudentName(student.name)),
                        element('span', 'd-block small', choiceLabel(student)),
                    );
                    label.append(radio, copy);
                    return label;
                };

                const columns = element('div', 'row g-3 mt-1');
                const byNisn = element('div', 'col-12 col-lg-6');
                byNisn.append(item.master
                    ? option(item.master, 'NISN cocok', 'nisn')
                    : element('p', 'text-muted small mt-2 mb-0', 'NISN tidak ditemukan di master.'));
                const byName = element('div', 'col-12 col-lg-6');
                const nameOptions = element('div');
                item.suggestions.forEach((candidate) => nameOptions.append(option(candidate, 'Nama cocok', 'name')));
                if (!item.suggestions.length) nameOptions.append(element('p', 'text-muted small mt-2 mb-0', 'Nama tidak ditemukan di master.'));
                byName.append(nameOptions);
                columns.append(byNisn, byName);
                row.append(columns);

                const unlinked = element('label', 'd-flex gap-2 align-items-center small mt-3');
                const unlinkedRadio = document.createElement('input');
                unlinkedRadio.type = 'radio';
                unlinkedRadio.name = groupName;
                unlinkedRadio.value = '';
                unlinkedRadio.checked = true;
                unlinkedRadio.className = 'form-check-input m-0';
                unlinked.append(unlinkedRadio, element('span', '', 'Lewati dulu, belum tertaut'));
                row.append(unlinked);
                list.append(row);
            });
            details.append(bulkButtons, bulkStatus, list);
            container.append(details);
        });
    }
};

export const updateEtatibReadiness = (body, missing, confirm, automaticConfirm) => {
    const pending = [...body.querySelectorAll('[data-duplicate-choice]')].some((choice) => !choice.checked);
    body.querySelector('[data-etatib-identities]').hidden = pending;
    confirm.disabled = automaticConfirm.disabled = pending;
};

export const writeEtatibDecisions = (form, body) => {
    form.querySelectorAll('[data-preview-decision]').forEach((input) => input.remove());
    body.querySelectorAll('[data-identity-choice]:checked').forEach((choice, index) => {
        [['nisn', choice.dataset.nisn], ['name', choice.dataset.name], ['student_id', choice.value]].forEach(([field, value]) => {
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = `identity_decisions[${index}][${field}]`;
            input.value = value;
            input.dataset.previewDecision = '';
            form.append(input);
        });
    });
    body.querySelectorAll('[data-duplicate-choice]:checked').forEach((choice) => {
        const input = element('input');
        input.type = 'hidden';
        input.name = 'duplicate_decisions[]';
        input.value = choice.value;
        input.dataset.previewDecision = '';
        form.append(input);
    });
};

export const initEtatibApiPreview = async (root = document) => {
    const form = root.querySelector('[data-etatib-api-form]');
    const modalNode = root.querySelector('[data-etatib-preview-modal]');
    if (!form || !modalNode) return;

    const { default: Modal } = await import('bootstrap/js/dist/modal.js');
    const modal = Modal.getOrCreateInstance(modalNode);
    const body = modalNode.querySelector('[data-etatib-preview-body]');
    const confirm = modalNode.querySelector('[data-etatib-confirm]');
    const automaticConfirm = modalNode.querySelector('[data-etatib-confirm-automatic]');
    const automaticFields = modalNode.querySelector('[data-etatib-automatic-fields]');
    const automaticPassword = modalNode.querySelector('[data-etatib-automatic-password]');
    const previewButton = form.querySelector('[data-etatib-preview-button]');
    const url = form.elements.namedItem('api_url');
    let previewedUrl = '';
    let missing = 0;
    let controller;


    body.addEventListener('change', (event) => {
        if (event.target.matches('[data-duplicate-choice]') && previewedUrl) {
            updateEtatibReadiness(body, missing, confirm, automaticConfirm);
        }
    });

    form.addEventListener('submit', async (event) => {
        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;
            return;
        }
        event.preventDefault();
        if (!form.reportValidity()) return;

        controller?.abort();
        form.querySelectorAll('[data-preview-decision]').forEach((input) => input.remove());
        controller = new AbortController();
        previewedUrl = '';
        confirm.disabled = true;
        automaticConfirm.disabled = true;
        automaticPassword.disabled = true;
        automaticPassword.required = false;
        automaticPassword.value = '';
        automaticFields.hidden = true;
        automaticConfirm.textContent = 'Aktifkan Otomatis';
        previewButton.disabled = true;
        body.replaceChildren(element('p', 'text-muted mb-0', 'Menghubungi API e-Tatib...'));
        modal.show();

        try {
            const response = await fetch(form.dataset.previewUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                },
                body: new FormData(form),
                signal: controller.signal,
            });
            const payload = await response.json();
            if (!response.ok) {
                const message = Object.values(payload.errors ?? {}).flat()[0]
                    ?? payload.message
                    ?? 'Pratinjau API e-Tatib gagal dimuat.';
                body.replaceChildren(element('div', 'alert alert-danger mb-0', message));
                return;
            }
            renderEtatibPreview(body, payload.data, form.dataset.dapodikUrl, form.dataset.duplicateRevokeUrl);
            previewedUrl = url.value;
            missing = payload.data.missing;
            updateEtatibReadiness(body, missing, confirm, automaticConfirm);
        } catch (error) {
            if (error.name !== 'AbortError') {
                body.replaceChildren(element('div', 'alert alert-danger mb-0', 'Pratinjau tidak dapat dimuat.'));
            }
        } finally {
            previewButton.disabled = false;
        }
    });

    confirm.addEventListener('click', () => {
        if (!previewedUrl || previewedUrl !== url.value) {
            body.replaceChildren(element('div', 'alert alert-danger mb-0', 'Link berubah. Jalankan pratinjau kembali.'));
            confirm.disabled = true;
            return;
        }
        form.dataset.confirmed = 'true';
        writeEtatibDecisions(form, body);
        form.action = form.dataset.manualSyncUrl;
        automaticPassword.required = false;
        automaticPassword.disabled = true;
        confirm.disabled = true;
        automaticConfirm.disabled = true;
        modal.hide();
        form.requestSubmit();
    });

    automaticConfirm.addEventListener('click', () => {
        if (!previewedUrl || previewedUrl !== url.value) {
            body.replaceChildren(element('div', 'alert alert-danger mb-0', 'Link berubah. Jalankan pratinjau kembali.'));
            confirm.disabled = true;
            automaticConfirm.disabled = true;
            return;
        }

        if (automaticFields.hidden) {
            automaticFields.hidden = false;
            automaticPassword.disabled = false;
            automaticPassword.required = true;
            automaticConfirm.textContent = 'Simpan & Aktifkan Otomatis';
            automaticPassword.focus();
            return;
        }
        if (!form.reportValidity()) {
            automaticPassword.focus();
            return;
        }

        form.dataset.confirmed = 'true';
        writeEtatibDecisions(form, body);
        form.action = form.dataset.automaticSyncUrl;
        confirm.disabled = true;
        automaticConfirm.disabled = true;
        modal.hide();
        form.requestSubmit();
    });

    modalNode.addEventListener('hidden.bs.modal', () => {
        controller?.abort();
        if (form.dataset.confirmed !== 'true') {
            automaticFields.hidden = true;
            automaticConfirm.textContent = 'Aktifkan Otomatis';
            automaticPassword.value = '';
            automaticPassword.disabled = true;
            automaticPassword.required = false;
        }
        previewButton.focus();
    });
};
