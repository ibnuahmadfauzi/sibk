import { initAccountInformation } from './account-information';
import 'bootstrap/js/dist/dropdown';
import 'bootstrap/js/dist/tab';
import Toast from 'bootstrap/js/dist/toast';
import 'bootstrap/js/dist/offcanvas';
import Modal from 'bootstrap/js/dist/modal';
import Collapse from 'bootstrap/js/dist/collapse';

window.bootstrap = window.bootstrap || {};
window.bootstrap.Modal = Modal;
import { initApiSiswaPreview } from './api-siswa-preview';
import { initEtatibApiPreview } from './etatib-api-preview';
import { initEtatibIdentityMapping } from './etatib-identity-mapping';
import { initFormDrafts } from './form-draft';
import { initServiceRecords } from './service-records';
import './case-create.js';
import './table-sort.js';
import { initConsultationCreate } from './consultation-create';
import { initWithdrawalProgress } from './withdrawal-progress';
import { initReportPreview } from './report-preview';
import { initAchievementForms } from './achievement-form';
import { displayStudentName } from './student-name';
import { initAutoFilters } from './auto-filter';

initFormDrafts();
initServiceRecords();
initConsultationCreate();
initApiSiswaPreview();
initEtatibApiPreview();
initEtatibIdentityMapping();
initWithdrawalProgress();
initReportPreview(Modal);
document.querySelectorAll('[data-password-visibility]').forEach((toggle) => {
    const input = document.getElementById(toggle.dataset.passwordVisibility);
    if (!input) return;
    toggle.addEventListener('change', () => {
        input.type = toggle.checked ? 'text' : 'password';
    });
    toggle.closest('.modal')?.addEventListener('hidden.bs.modal', () => {
        toggle.checked = false;
        input.type = 'password';
    });
});
initAchievementForms();
document.addEventListener('sibk:modal-loaded', (event) => initAchievementForms(event.target));

document.querySelectorAll('[data-notification-toast]').forEach((toast) => {
    if (toast.closest('[data-modal-submit-error]')) return;
    new Toast(toast, { delay: toast.classList.contains('sibk-notification-toast--error') ? 8000 : 4500 }).show();
});

const renameClassroomModal = document.getElementById('renameClassroomModal');
if (renameClassroomModal) {
    renameClassroomModal.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        const form = renameClassroomModal.querySelector('[data-classroom-rename-form]');
        form.action = button.dataset.classroomUpdateUrl;
        form.querySelector('[name="name"]').value = button.dataset.classroomName;
        form.querySelector('[name="is_active"]').value = button.dataset.classroomActive;
    });
}

document.querySelectorAll('[data-print-report]').forEach((button) => {
    button.addEventListener('click', () => window.print());
});

document.querySelectorAll('[data-report-year-filter]').forEach((select) => {
    select.addEventListener('change', () => {
        const form = select.closest('form');
        const classroom = form?.querySelector('[name="classroom_search"]');

        if (classroom) classroom.value = '';
    });
});

initAutoFilters();

document.querySelectorAll('[data-report-detail-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
        const detail = document.getElementById(button.getAttribute('aria-controls'));
        const expanded = button.getAttribute('aria-expanded') === 'true';

        detail.classList.toggle('d-none', expanded);
        button.setAttribute('aria-expanded', String(!expanded));
        button.title = expanded ? 'Tampilkan detail layanan' : 'Tutup detail layanan';
        button.setAttribute(
            'aria-label',
            `${button.title} ${displayStudentName(button.dataset.reportDetailName)}`,
        );
    });
});

const loadSyncHistory = async (button, panel) => {
    if (panel.dataset.loaded || panel.dataset.loading) return;
    panel.dataset.loading = 'true';
    panel.innerHTML = '<p class="sibk-panel__subtitle px-3 pt-3" role="status">Memuat riwayat...</p>';

    try {
        const url = new URL(window.location.href);
        url.searchParams.set('tab', 'sinkronisasi');
        url.searchParams.delete(button.dataset.historyParam === 'history_decisions' ? 'history_runs' : 'history_decisions');
        url.searchParams.set(button.dataset.historyParam, '1');
        const response = await fetch(url, { headers: { Accept: 'text/html' } });
        if (!response.ok) throw new Error('Gagal memuat riwayat.');
        const page = new DOMParser().parseFromString(await response.text(), 'text/html');
        const content = page.getElementById(panel.id);
        if (!content || !content.querySelector('table')) throw new Error('Riwayat tidak tersedia.');
        panel.innerHTML = content.innerHTML;
        panel.dataset.loaded = 'true';
    } catch {
        panel.innerHTML = '<div class="p-3" role="alert">Riwayat belum dapat dimuat. <button class="btn btn-link p-0" type="button" data-sync-history-retry>Coba lagi</button></div>';
    } finally {
        delete panel.dataset.loading;
    }
};

document.querySelectorAll('[data-sync-history-toggle]').forEach((button) => {
    const panel = document.getElementById(button.getAttribute('aria-controls'));
    const collapse = Collapse.getOrCreateInstance(panel, { toggle: false });
    button.addEventListener('click', () => {
        if (panel.classList.contains('collapsing')) return;
        const open = button.getAttribute('aria-expanded') !== 'true';
        button.setAttribute('aria-expanded', String(open));
        button.title = open ? 'Tutup Riwayat' : 'Tampilkan Riwayat';
        button.setAttribute('aria-label', button.title);
        button.querySelector('path').setAttribute('d', open ? 'm6 14 6-6 6 6' : 'm6 10 6 6 6-6');
        const url = new URL(window.location.href);
        if (open) url.searchParams.set(button.dataset.historyParam, '1');
        else url.searchParams.delete(button.dataset.historyParam);
        window.history.replaceState(window.history.state, '', url);
        if (open) {
            collapse.show();
            loadSyncHistory(button, panel);
        } else collapse.hide();
    });
});

document.addEventListener('click', (event) => {
    const retry = event.target.closest('[data-sync-history-retry]');
    if (retry) {
        const panel = retry.closest('[data-sync-history-content]');
        const button = document.querySelector(`[data-sync-history-toggle][aria-controls="${panel.id}"]`);
        loadSyncHistory(button, panel);
        return;
    }

    const button = event.target.closest('[data-sync-issue-toggle]');
    if (!button) return;
    const detail = document.getElementById(button.getAttribute('aria-controls'));
    const expanded = button.getAttribute('aria-expanded') === 'true';
    detail.classList.toggle('d-none', expanded);
    button.setAttribute('aria-expanded', String(!expanded));
    button.title = expanded ? 'Tinjau' : 'Tutup rincian';
    button.setAttribute('aria-label', `${button.title} ${button.dataset.syncIssueName}`);
    if (expanded || detail.dataset.loaded || detail.dataset.loading) return;
    detail.dataset.loading = 'true';
    fetch(button.dataset.detailUrl, { headers: { Accept: 'text/html' } })
        .then((response) => {
            if (!response.ok) throw new Error('Gagal memuat rincian.');
            return response.text();
        })
        .then((html) => {
            detail.querySelector('[data-sync-issue-content]').innerHTML = html;
            detail.dataset.loaded = 'true';
        })
        .catch(() => {
            detail.querySelector('[data-sync-issue-content]').textContent = 'Rincian belum dapat dimuat. Tutup lalu buka kembali untuk mencoba lagi.';
        })
        .finally(() => { delete detail.dataset.loading; });
});
document.querySelectorAll('[data-sync-issue-toggle][data-auto-open]').forEach((button) => button.click());

document.addEventListener('click', (event) => {
    const link = event.target.closest('a[href]');
    if (!link || !link.closest('[data-sync-history-content], [data-page-id="PG-501"]')) return;
    const url = new URL(link.href, window.location.href);
    if (url.origin !== window.location.origin || url.pathname !== window.location.pathname) return;
    document.querySelectorAll('[data-sync-history-toggle]').forEach((button) => {
        if (button.getAttribute('aria-expanded') === 'true') url.searchParams.set(button.dataset.historyParam, '1');
        else url.searchParams.delete(button.dataset.historyParam);
    });
    link.href = url;
}, true);

document.addEventListener('submit', (event) => {
    const input = event.target.querySelector('[data-sync-membership-input]');
    if (input) input.value = event.target.querySelector('[name="action"]:checked')?.dataset.syncMembership ?? '';
});

const appConfirmationElement = document.querySelector('[data-app-confirmation-modal]');
if (appConfirmationElement) {
    const appConfirmation = Modal.getOrCreateInstance(appConfirmationElement);
    const title = appConfirmationElement.querySelector('[data-app-confirmation-title]');
    const message = appConfirmationElement.querySelector('[data-app-confirmation-message]');
    const subject = appConfirmationElement.querySelector('[data-app-confirmation-subject]');
    const suffix = appConfirmationElement.querySelector('[data-app-confirmation-suffix]');
    const cancel = appConfirmationElement.querySelector('[data-app-confirmation-cancel]');
    const action = appConfirmationElement.querySelector('[data-app-confirmation-action]');
    let pendingForm;
    let pendingSubmitter;
    let pendingTrigger;
    let pendingFormAction;
    let formActionAccepted = false;
    let suspendedModal;

    const setupConfirmation = (target) => {
        title.textContent = target.dataset.confirmTitle || 'Konfirmasi';
        message.textContent = target.dataset.confirmMessage || 'Apakah Anda ingin melanjutkan?';
        subject.textContent = target.dataset.confirmSubject ? ` ${target.dataset.confirmSubject}` : '';
        suffix.textContent = target.dataset.confirmSuffix || '';
        action.textContent = target.dataset.confirmAction || 'Ya, lanjutkan';
        cancel.textContent = target.dataset.confirmCancel || 'Batal';
        const tone = target.dataset.confirmTone || 'primary';
        appConfirmationElement.dataset.tone = tone;
        action.classList.toggle('btn-danger', tone === 'danger');
        action.classList.toggle('btn-success', tone === 'success');
        action.classList.toggle('btn-warning', tone === 'warning');
        action.classList.toggle('btn-primary', !['danger', 'success', 'warning'].includes(tone));
        appConfirmation.show();
    };

    document.addEventListener('sibk:confirm-form-action', (event) => {
        if (pendingFormAction) return;
        pendingFormAction = event.detail;
        formActionAccepted = false;
        suspendedModal = event.detail.form.closest('.modal');
        if (suspendedModal) {
            suspendedModal.dataset.confirmationSuspended = 'true';
            suspendedModal.addEventListener('hidden.bs.modal', () => setupConfirmation(event.detail), { once: true });
            Modal.getOrCreateInstance(suspendedModal).hide();
        } else setupConfirmation(event.detail);
    });

    document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.matches('[data-app-confirm-submit]')) return;
        if (form.dataset.confirmed === 'true') {
            delete form.dataset.confirmed;
            return;
        }

        event.preventDefault();
        pendingForm = form;
        pendingSubmitter = event.submitter;
        setupConfirmation(form);
    }, true);

    document.addEventListener('click', (event) => {
        const trigger = event.target.closest?.('[data-app-confirm]');
        if (!trigger) return;
        if (trigger.dataset.confirmed === 'true') {
            delete trigger.dataset.confirmed;
            return;
        }

        event.preventDefault();
        event.stopImmediatePropagation();
        pendingTrigger = trigger;
        setupConfirmation(trigger);
    }, true);

    appConfirmationElement.addEventListener('shown.bs.modal', () => cancel.focus());
    appConfirmationElement.addEventListener('hidden.bs.modal', () => {
        if (pendingFormAction) {
            const pending = pendingFormAction;
            const accepted = formActionAccepted;
            pendingFormAction = undefined;
            if (suspendedModal) {
                delete suspendedModal.dataset.confirmationSuspended;
                if (!accepted || !pending.closeAfterConfirm) Modal.getOrCreateInstance(suspendedModal).show();
            }
            if (accepted) pending.onConfirm();
            suspendedModal = undefined;
        }
        pendingSubmitter?.focus();
        pendingTrigger?.focus();
        pendingForm = undefined;
        pendingSubmitter = undefined;
        pendingTrigger = undefined;
        action.disabled = false;
    });
    action.addEventListener('click', () => {
        if (pendingFormAction) {
            action.disabled = true;
            formActionAccepted = true;
            appConfirmation.hide();
            return;
        }
        if (pendingForm) {
            action.disabled = true;
            pendingForm.dataset.confirmed = 'true';
            appConfirmation.hide();
            pendingForm.requestSubmit(pendingSubmitter);
            return;
        }

        if (pendingTrigger) {
            const trigger = pendingTrigger;
            action.disabled = true;
            trigger.dataset.confirmed = 'true';
            appConfirmation.hide();
            trigger.click();
        }
    });
}

document.querySelectorAll('form[data-confirm-submit]').forEach((form) => {
    form.addEventListener('submit', (event) => {
        const message = form.dataset.confirmMessage;

        if (message && !window.confirm(message)) {
            event.preventDefault();
        }
    });
});

const assignmentScrollKey = 'sibk.assignmentScrollY';

const classUnassignModal = document.getElementById('classUnassignModal');
if (classUnassignModal) {
    const confirm = classUnassignModal.querySelector('[data-confirm-unassign]');
    let pendingForm = null;

    classUnassignModal.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        pendingForm = button.closest('form');
        classUnassignModal.querySelector('[data-unassign-class]').textContent = button.dataset.className;
        classUnassignModal.querySelector('[data-unassign-teacher]').textContent = button.dataset.teacherName;
    });
    classUnassignModal.addEventListener('hidden.bs.modal', () => {
        pendingForm = null;
        confirm.disabled = false;
    });
    confirm.addEventListener('click', () => {
        if (!pendingForm) return;

        confirm.disabled = true;
        sessionStorage.setItem(assignmentScrollKey, String(window.scrollY));
        pendingForm.requestSubmit();
    });
}

const classPicker = document.getElementById('classPickerModal');
if (classPicker) {
    window.addEventListener('load', () => {
        const savedScrollY = sessionStorage.getItem(assignmentScrollKey);
        if (savedScrollY === null) return;

        sessionStorage.removeItem(assignmentScrollKey);
        window.scrollTo(0, Number(savedScrollY));
    }, { once: true });

    const search = classPicker.querySelector('#classPickerSearch');
    const options = [...classPicker.querySelectorAll('[data-class-picker-option]')];
    const empty = classPicker.querySelector('[data-class-picker-empty]');
    const selectedItems = classPicker.querySelector('[data-class-picker-selected]');
    const selectedInputs = classPicker.querySelector('[data-class-picker-inputs]');
    const placeholder = classPicker.querySelector('[data-class-picker-placeholder]');
    const submit = classPicker.querySelector('[data-class-picker-submit]');
    const selected = new Map();

    classPicker.querySelector('form').addEventListener('submit', () => {
        sessionStorage.setItem(assignmentScrollKey, String(window.scrollY));
    });

    const render = () => {
        selectedItems.replaceChildren();
        selectedInputs.replaceChildren();

        selected.forEach((name, id) => {
            const chip = document.createElement('button');
            chip.type = 'button';
            chip.className = 'sibk-class-picker-selected-chip';
            chip.textContent = `${name} ×`;
            chip.setAttribute('aria-label', `Batalkan pilihan ${name}`);
            chip.addEventListener('click', () => {
                selected.delete(id);
                render();
                search.focus();
            });
            selectedItems.append(chip);

            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'classroom_ids[]';
            input.value = id;
            selectedInputs.append(input);
        });

        const query = search.value.trim().toLocaleLowerCase('id');
        options.forEach((option) => {
            option.hidden = selected.has(option.dataset.classId)
                || !option.dataset.classPickerOption.includes(query);
        });
        empty.textContent = selected.size === options.length
            ? 'Semua kelas tersedia sudah dipilih.'
            : 'Tidak ada kelas yang cocok.';
        empty.hidden = options.length === 0 || options.some((option) => !option.hidden);
        placeholder.hidden = selected.size > 0;
        submit.disabled = selected.size === 0;
        submit.textContent = selected.size > 0 ? `Tambah ${selected.size} kelas` : 'Tambah kelas';
    };

    classPicker.addEventListener('show.bs.modal', (event) => {
        const button = event.relatedTarget;
        classPicker.querySelector('#classPickerTitle').textContent = `Tambah kelas untuk ${button.dataset.teacherName}`;
        classPicker.querySelector('[name="user_id"]').value = button.dataset.teacherId;
        selected.clear();
        search.value = '';
        render();
    });
    classPicker.addEventListener('shown.bs.modal', () => search.focus());
    search.addEventListener('input', render);
    search.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') event.preventDefault();
    });
    options.forEach((option) => {
        option.addEventListener('click', () => {
            selected.set(option.dataset.classId, option.dataset.className);
            render();
            search.focus();
        });
    });
}

const accountPage = document.querySelector('[data-page-id="ADMIN-USERS"]');
if (accountPage) {
    const rolePicker = (root) => {
        const selected = new Map();
        let locked = false;
        const options = [...root.querySelectorAll('[data-account-role-option]')];
        const selectedItems = root.querySelector('[data-account-selected]');
        const inputs = root.querySelector('[data-account-inputs]');
        const placeholder = root.querySelector('[data-account-placeholder]');
        const submit = root.closest('form').querySelector('[data-account-submit]');
        const lockedMessage = root.querySelector('[data-account-role-locked]');
        const invalidMessage = root.querySelector('[data-account-role-invalid]');

        const render = () => {
            selectedItems.replaceChildren();
            inputs.replaceChildren();
            const slugs = [...selected.keys()].sort();
            const valid = slugs.length === 1
                || (slugs.length === 2 && slugs[0] === 'guru_bk' && slugs[1] === 'koordinator_bk');
            const exclusive = selected.has('admin_it') || selected.has('waka_kesiswaan');
            selected.forEach((name, slug) => {
                const chip = document.createElement(locked ? 'span' : 'button');
                if (!locked) chip.type = 'button';
                chip.className = 'sibk-class-picker-selected-chip';
                chip.textContent = locked ? name : `${name} ×`;
                if (!locked) {
                    chip.setAttribute('aria-label', `Batalkan pilihan ${name}`);
                    chip.addEventListener('click', () => {
                        selected.delete(slug);
                        render();
                    });
                }
                selectedItems.append(chip);

                if (!locked) {
                    const input = document.createElement('input');
                    input.type = 'hidden';
                    input.name = 'roles[]';
                    input.value = slug;
                    inputs.append(input);
                }
            });
            options.forEach((option) => {
                const slug = option.dataset.roleSlug;
                option.hidden = locked || selected.has(slug);
                option.disabled = exclusive
                    || (selected.size > 0 && ['admin_it', 'waka_kesiswaan'].includes(slug));
            });
            placeholder.hidden = selected.size > 0;
            if (lockedMessage) lockedMessage.classList.toggle('d-none', !locked);
            if (invalidMessage) invalidMessage.classList.toggle('d-none', valid || selected.size === 0);
            submit.disabled = !locked && !valid;
        };

        options.forEach((option) => {
            option.addEventListener('click', () => {
                if (option.disabled || locked) return;
                selected.set(option.dataset.roleSlug, option.dataset.roleName);
                render();
            });
        });

        return (slugs, readOnly = false) => {
            locked = readOnly;
            selected.clear();
            slugs.filter(Boolean).forEach((slug) => {
                const option = options.find((item) => item.dataset.roleSlug === slug);
                if (option) selected.set(slug, option.dataset.roleName);
            });
            render();
        };
    };

    const accountModal = document.getElementById('accountModal');
    const accountForm = document.getElementById('accountForm');
    const accountRolesModal = document.getElementById('accountRolesModal');
    const rolesForm = document.getElementById('accountRolesForm');
    const setAccountRoles = rolePicker(accountModal.querySelector('[data-account-role-picker]'));
    const setQuickRoles = rolePicker(accountRolesModal.querySelector('[data-account-role-picker]'));

    const setupAccount = (button, keepInput = false) => {
        const edit = button?.matches('[data-account-edit]') ?? false;
        accountForm.action = edit ? button.dataset.accountUrl : accountPage.querySelector('[data-account-create]').dataset.storeUrl;
        accountForm.querySelector('[name="_method"]').disabled = !edit;
        accountForm.querySelector('[name="_account_action"]').value = edit ? 'edit' : 'create';
        accountForm.querySelector('[name="_account_target"]').value = edit ? button.dataset.accountId : '';
        accountModal.querySelector('#accountModalTitle').textContent = edit ? `Edit ${button.dataset.accountName}` : 'Tambah akun';
        accountForm.querySelector('[data-account-submit]').textContent = edit ? 'Simpan akun' : 'Buat akun';
        const status = accountForm.querySelector('[name="is_active"]');
        accountForm.querySelector('[data-account-status-field]').classList.toggle('d-none', !edit);
        status.disabled = !edit || button.dataset.accountId === accountPage.dataset.currentUserId;
        if (!keepInput) status.value = edit ? button.dataset.accountActive : '1';
        if (!keepInput) {
            accountForm.querySelector('[name="name"]').value = edit ? button.dataset.accountName : '';
            accountForm.querySelector('[name="email"]').value = edit ? button.dataset.accountEmail : '';
            setAccountRoles(
                edit ? button.dataset.accountRoles.split(',') : [],
                edit && button.dataset.accountId === accountPage.dataset.currentUserId,
            );
        }
    };

    accountModal.addEventListener('show.bs.modal', (event) => {
        if (event.relatedTarget) setupAccount(event.relatedTarget);
    });
    accountModal.addEventListener('shown.bs.modal', () => accountForm.querySelector('#accountName').focus());
    accountRolesModal.addEventListener('show.bs.modal', (event) => {
        if (!event.relatedTarget) return;
        const button = event.relatedTarget;
        rolesForm.action = button.dataset.accountUrl;
        rolesForm.querySelector('[name="_account_target"]').value = button.dataset.accountId;
        accountRolesModal.querySelector('#accountRolesTitle').textContent = `Peran ${button.dataset.accountName}`;
        setQuickRoles(button.dataset.accountRoles.split(','));
    });
    accountRolesModal.addEventListener('shown.bs.modal', () => {
        accountRolesModal.querySelector('[data-account-role-option]:not([hidden]):not(:disabled)')?.focus();
    });

    initAccountInformation(accountPage);

    if (accountPage.dataset.accountOldAction) {
        const target = accountPage.dataset.accountOldTarget;
        const oldRoles = accountPage.dataset.accountOldRoles.split(',');
        if (accountPage.dataset.accountOldAction === 'roles') {
            const button = [...accountPage.querySelectorAll('[data-account-role-edit]')]
                .find((item) => item.dataset.accountId === target);
            if (button) {
                rolesForm.action = button.dataset.accountUrl;
                rolesForm.querySelector('[name="_account_target"]').value = target;
                accountRolesModal.querySelector('#accountRolesTitle').textContent = `Peran ${button.dataset.accountName}`;
                setQuickRoles(oldRoles);
                Modal.getOrCreateInstance(accountRolesModal).show();
            }
        } else {
            const button = accountPage.dataset.accountOldAction === 'edit'
                ? [...accountPage.querySelectorAll('[data-account-edit]')]
                    .find((item) => item.dataset.accountId === target)
                : null;
            setupAccount(button, true);
            setAccountRoles(oldRoles, button?.dataset.accountId === accountPage.dataset.currentUserId);
            Modal.getOrCreateInstance(accountModal).show();
        }
    } else if (new URLSearchParams(window.location.search).get('action') === 'create') {
        setupAccount(accountPage.querySelector('[data-account-create]'));
        Modal.getOrCreateInstance(accountModal).show();
    }
}

// ── Magic Sidebar Indicator ──────────────────────────────────────────────────
// Menciptakan indikator yang meluncur mulus di antara item menu aktif.
// Efek "cutout": indikator berwarna halaman sehingga terlihat berlubang.
(function initSidebarIndicator() {
    const nav = document.querySelector('.sibk-sidebar__nav');
    if (!nav) return;

    // Buat elemen indikator
    const indicator = document.createElement('div');
    indicator.className = 'sibk-nav-indicator';
    nav.insertBefore(indicator, nav.firstChild); // taruh sebelum link pertama

    function moveIndicator(target) {
        if (!target) return;

        // Hitung posisi target relatif terhadap nav container
        const navRect = nav.getBoundingClientRect();
        const targetRect = target.getBoundingClientRect();

        const top    = targetRect.top - navRect.top;
        const height = targetRect.height;

        indicator.style.top    = top + 'px';
        indicator.style.height = height + 'px';
        indicator.style.opacity = '1';
    }

    // Posisi awal (tanpa animasi)
    const activeLink = nav.querySelector('.sibk-nav-link.is-active');
    if (activeLink) {
        // Set langsung tanpa transition untuk posisi awal
        indicator.style.transition = 'none';
        moveIndicator(activeLink);
        // Aktifkan transition setelah frame pertama
        requestAnimationFrame(() => {
            requestAnimationFrame(() => {
                indicator.style.transition = '';
            });
        });
    } else {
        indicator.style.opacity = '0';
    }
})();
