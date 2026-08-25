import { SearchAutocomplete } from '../../components/search-autocomplete.js';
import { initDatePickers } from '../../components/date-picker.js';
import { initServerSearchableSelects } from '../../components/server-searchable-select.js';

const sidebarStorageKey = 'gujajob.sidebar.groupState';

function getSidebarState() {
    try {
        const storedState = localStorage.getItem(sidebarStorageKey);

        if (!storedState) {
            return {
                material: true,
                asset: true,
            };
        }

        return {
            material: true,
            asset: true,
            ...JSON.parse(storedState),
        };
    } catch (error) {
        return {
            material: true,
            asset: true,
        };
    }
}

function saveSidebarState(state) {
    try {
        localStorage.setItem(sidebarStorageKey, JSON.stringify(state));
    } catch (error) {
        console.warn('Cannot save sidebar state.');
    }
}

function applySidebarGroupState(groupElement, isOpen) {
    const toggleButton = groupElement.querySelector('.menu-group-toggle');

    groupElement.classList.toggle('is-collapsed', !isOpen);

    if (toggleButton) {
        toggleButton.setAttribute('aria-expanded', String(isOpen));
    }
}

function initializeSidebarGroups() {
    const state = getSidebarState();
    const groups = document.querySelectorAll('[data-sidebar-group]');

    groups.forEach((groupElement) => {
        const groupName = groupElement.dataset.sidebarGroup;
        const toggleButton = groupElement.querySelector('.menu-group-toggle');
        const isOpen = state[groupName] !== false;

        applySidebarGroupState(groupElement, isOpen);

        if (!toggleButton) {
            return;
        }

        toggleButton.addEventListener('click', () => {
            const currentState = getSidebarState();
            const nextIsOpen = groupElement.classList.contains('is-collapsed');

            currentState[groupName] = nextIsOpen;

            applySidebarGroupState(groupElement, nextIsOpen);
            saveSidebarState(currentState);
        });
    });
}

function preventDisabledMenuReload() {
    document.querySelectorAll('.disabled-link').forEach((link) => {
        link.addEventListener('click', (event) => {
            event.preventDefault();
        });
    });
}

function openOverlay(overlay) {
    if (!overlay) return;

    overlay.classList.add('is-visible');
    overlay.setAttribute('aria-hidden', 'false');
}

function closeOverlay(overlay) {
    if (!overlay) return;

    overlay.classList.remove('is-visible');
    overlay.setAttribute('aria-hidden', 'true');
}

function getContentRoot() {
    return document.querySelector('.content') || document.body;
}

function getFieldLabel(field) {
    if (field.id) {
        const label = document.querySelector(`label[for="${CSS.escape(field.id)}"]`);

        if (label) {
            return label;
        }
    }

    const wrapper = field.closest('.form-field, .field-group');

    if (wrapper) {
        return wrapper.querySelector('label');
    }

    return null;
}

function clearValidationState() {
    document.querySelectorAll('.gujajob-is-invalid').forEach((field) => {
        field.classList.remove('gujajob-is-invalid');
    });

    document.querySelectorAll('.gujajob-validation-message').forEach((message) => {
        message.remove();
    });

    const oldAlert = document.querySelector('.gujajob-validation-alert');

    if (oldAlert) {
        oldAlert.remove();
    }
}

function markInvalid(field, message) {
    field.classList.add('gujajob-is-invalid');

    const wrapper = field.closest('.form-field, .field-group') || field.parentElement;

    if (wrapper && !wrapper.querySelector('.gujajob-validation-message')) {
        const messageElement = document.createElement('div');
        messageElement.className = 'gujajob-validation-message';
        messageElement.textContent = message;
        wrapper.appendChild(messageElement);
    }
}

function showValidationAlert(message) {
    const root = getContentRoot();
    const pageHeader = root.querySelector('.page-header');

    const alert = document.createElement('div');
    alert.className = 'gujajob-validation-alert';
    alert.textContent = message;

    if (pageHeader && pageHeader.parentNode) {
        pageHeader.insertAdjacentElement('afterend', alert);
    } else {
        root.prepend(alert);
    }
}

function validateRequiredFields() {
    clearValidationState();

    const root = getContentRoot();
    const fields = Array.from(root.querySelectorAll('input, select, textarea'));
    let isValid = true;
    let firstInvalidField = null;

    fields.forEach((field) => {
        if (field.disabled || field.readOnly || field.type === 'hidden' || field.type === 'button' || field.type === 'file') {
            return;
        }

        const label = getFieldLabel(field);
        const isRequiredByStar = Boolean(label && label.textContent.includes('*'));
        const isRequiredByAttr = field.required;
        const isRequiredByFilter = field.classList.contains('required-filter');
        const isRequired = isRequiredByStar || isRequiredByAttr || isRequiredByFilter;

        if (!isRequired) {
            return;
        }

        const value = String(field.value || '').trim();

        if (value === '') {
            isValid = false;

            if (!firstInvalidField) {
                firstInvalidField = field;
            }

            markInvalid(field, 'กรุณากรอกข้อมูลช่องนี้');
        }
    });

    if (!isValid) {
        showValidationAlert('กรุณากรอกข้อมูลก่อน');

        if (firstInvalidField) {
            firstInvalidField.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });

            setTimeout(() => firstInvalidField.focus(), 250);
        }
    }

    return isValid;
}

const ALLOWED_IMAGE_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'];
const MAX_IMAGE_FILES = 3;
const MAX_IMAGE_BYTES = 1 * 1024 * 1024;

let selectedImageFiles = [];

function syncImageInput(input) {
    const dt = new DataTransfer();
    selectedImageFiles.forEach((f) => dt.items.add(f));
    input.files = dt.files;
}

function renderImageChips(list, input) {
    list.innerHTML = '';
    selectedImageFiles.forEach((file, index) => {
        const chip = document.createElement('span');
        chip.className = 'file-chip';
        chip.innerHTML = `${file.name} <button type="button" aria-label="ลบ">×</button>`;
        chip.querySelector('button').addEventListener('click', () => {
            selectedImageFiles.splice(index, 1);
            renderImageChips(list, input);
            syncImageInput(input);
        });
        list.appendChild(chip);
    });
}

function showUploadError(list, message) {
    const existing = list.parentElement?.querySelector('.upload-error-msg');
    if (existing) existing.remove();
    const msg = document.createElement('p');
    msg.className = 'upload-error-msg gujajob-validation-message';
    msg.style.color = '#dc2626';
    msg.style.fontSize = '11px';
    msg.style.marginTop = '4px';
    msg.textContent = message;
    list.parentElement?.insertBefore(msg, list);
    setTimeout(() => msg.remove(), 4000);
}

function initializeImageUpload() {
    const dropzone = document.getElementById('assetImageDropzone');
    const input    = document.getElementById('assetImageInput');
    const list     = document.getElementById('assetImageList');

    if (!input || !list) return;

    dropzone?.addEventListener('click', (e) => {
        if (e.target !== input) input.click();
    });
    dropzone?.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); input.click(); }
    });

    input.addEventListener('change', () => {
        const newFiles = Array.from(input.files);
        // Clear error messages from previous attempt
        list.parentElement?.querySelectorAll('.upload-error-msg').forEach((m) => m.remove());

        for (const file of newFiles) {
            if (selectedImageFiles.length >= MAX_IMAGE_FILES) {
                showUploadError(list, 'สามารถแนบรูปภาพได้สูงสุด 3 รูป');
                break;
            }
            if (!ALLOWED_IMAGE_TYPES.includes(file.type.toLowerCase())) {
                showUploadError(list, `"${file.name}" — รองรับเฉพาะไฟล์ JPG, PNG, GIF เท่านั้น`);
                continue;
            }
            if (file.size > MAX_IMAGE_BYTES) {
                showUploadError(list, `"${file.name}" — ไฟล์รูปภาพต้องมีขนาดไม่เกิน 1 MB ต่อไฟล์`);
                continue;
            }
            selectedImageFiles.push(file);
        }
        input.value = '';
        renderImageChips(list, input);
        syncImageInput(input);
    });
}

function initializeCategorySearch() {
    const searchTypeEl  = document.getElementById('catSearchType');
    const searchInputEl = document.getElementById('catSearchInput');
    const searchBtn     = document.getElementById('catSearchBtn');
    const asscatIdEl    = document.getElementById('asscatId');

    if (!searchInputEl || !asscatIdEl) return;

    function fillCategoryFields(item) {
        const set = (id, val) => { const el = document.getElementById(id); if (el) el.value = val ?? ''; };
        set('asscatCode',       item.asscat_code ?? '');
        set('asscatId',        item.id ?? '');
        set('asscatGroup',     item.asscat_group ?? '');
        set('asscatType',      item.asscat_type ?? '');
        set('asscatName',      item.asscat_name ?? '');
        set('asscatUnit',      item.asscat_unit ?? '');
        set('depreciationRate', item.depreciation_rate ?? '');
        if (asscatIdEl) asscatIdEl.value = item.id ?? '';
        // Clear invalid state on asscatName
        const nameEl = document.getElementById('asscatName');
        nameEl?.classList.remove('gujajob-is-invalid');
    }

    function clearCategoryFields() {
        ['asscatId','asscatCode','asscatGroup','asscatType','asscatName','asscatUnit','depreciationRate']
            .forEach(id => { const el = document.getElementById(id); if (el) el.value = ''; });
        searchInputEl.value = '';
    }

    const ac = new SearchAutocomplete({
        inputEl:     searchInputEl,
        searchTypeEl: searchTypeEl ?? undefined,
        endpoint:    '/search/suggestions',
        extraParams: { entity: 'create_asscat' },
        minChars:    1,
        debounceMs:  300,
        maxResults:  20,
        onSelect: (item) => {
            fillCategoryFields(item);
            searchInputEl.value = '';
        },
    });

    searchBtn?.addEventListener('click', () => ac.triggerSearch());
}

/** Force-sync any Flatpickr altInput values into their hidden ISO inputs. */
function flushDatePickers() {
    document.querySelectorAll('.js-date-picker').forEach((input) => {
        const fp = input._flatpickr;
        if (!fp || !fp.altInput) return;
        const display = fp.altInput.value.trim();
        if (!display) return;
        // setDate re-parses the visible Buddhist-Era string and updates input.value
        fp.setDate(display, false, fp.config.altFormat);
    });
}

function initializeCreateInteractions() {
    const saveButton = document.getElementById('saveAssetRegistrationButton');
    const saveOverlay = document.getElementById('saveAssetRegistrationOverlay');
    const cancelSaveButton = document.getElementById('cancelSaveAssetRegistrationButton');
    const confirmSaveButton = document.getElementById('confirmSaveAssetRegistrationButton');
    if (saveButton) {
        saveButton.addEventListener('click', () => {
            const isValid = validateRequiredFields();

            if (!isValid) {
                return;
            }

            openOverlay(saveOverlay);
        });
    }

    if (cancelSaveButton) {
        cancelSaveButton.addEventListener('click', () => {
            closeOverlay(saveOverlay);
        });
    }

    if (saveOverlay) {
        saveOverlay.addEventListener('click', (event) => {
            if (event.target === saveOverlay) {
                closeOverlay(saveOverlay);
            }
        });
    }

    if (confirmSaveButton) {
        confirmSaveButton.addEventListener('click', () => {
            const isValid = validateRequiredFields();

            if (!isValid) {
                closeOverlay(saveOverlay);
                return;
            }

            flushDatePickers();
            confirmSaveButton.disabled = true;
            const form = document.querySelector('.registration-form');
            if (form) {
                form.submit();
            }
        });
    }

    document.addEventListener('input', (event) => {
        const field = event.target;

        if (!field.classList || !field.classList.contains('gujajob-is-invalid')) {
            return;
        }

        if (String(field.value || '').trim() !== '') {
            field.classList.remove('gujajob-is-invalid');

            const wrapper = field.closest('.form-field, .field-group') || field.parentElement;
            const message = wrapper ? wrapper.querySelector('.gujajob-validation-message') : null;

            if (message) {
                message.remove();
            }
        }
    });

    document.addEventListener('change', (event) => {
        const field = event.target;

        if (!field.classList || !field.classList.contains('gujajob-is-invalid')) {
            return;
        }

        if (String(field.value || '').trim() !== '') {
            field.classList.remove('gujajob-is-invalid');

            const wrapper = field.closest('.form-field, .field-group') || field.parentElement;
            const message = wrapper ? wrapper.querySelector('.gujajob-validation-message') : null;

            if (message) {
                message.remove();
            }
        }
    });
}

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    document.querySelectorAll('.confirm-overlay.is-visible').forEach((overlay) => {
        closeOverlay(overlay);
    });
});

function initializeEditInteractions() {
    const saveEditBtn    = document.getElementById('saveEditAssetButton');
    const editOverlay    = document.getElementById('editAssetOverlay');
    const cancelEditBtn  = document.getElementById('cancelEditAssetButton');
    const confirmEditBtn = document.getElementById('confirmEditAssetButton');

    if (!saveEditBtn || !editOverlay) {
        return;
    }

    saveEditBtn.addEventListener('click', () => {
        const isValid = validateRequiredFields();
        if (!isValid) return;
        openOverlay(editOverlay);
    });

    cancelEditBtn?.addEventListener('click', () => closeOverlay(editOverlay));

    editOverlay.addEventListener('click', (e) => {
        if (e.target === editOverlay) closeOverlay(editOverlay);
    });

    confirmEditBtn?.addEventListener('click', () => {
        const isValid = validateRequiredFields();
        if (!isValid) {
            closeOverlay(editOverlay);
            return;
        }
        flushDatePickers();
        confirmEditBtn.disabled = true;
        const form = document.querySelector('.registration-form');
        if (form) form.submit();
    });
}

function initializeExistingImageRemoval() {
    document.querySelectorAll('.remove-existing-image-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            const imageId = btn.dataset.imageId;
            const item = document.getElementById(`existing-img-${imageId}`);
            const hidden = document.getElementById(`remove-img-${imageId}`);
            if (hidden) hidden.disabled = false;
            if (item) item.style.display = 'none';
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initializeSidebarGroups();
    preventDisabledMenuReload();
    initDatePickers();
    initServerSearchableSelects();
    initializeCategorySearch();
    initializeImageUpload();
    initializeCreateInteractions();
    initializeEditInteractions();
    initializeExistingImageRemoval();
});
