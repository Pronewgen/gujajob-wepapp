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

function initializeCreateInteractions() {
    const uploadBox = document.getElementById('uploadBox');
    const assetImages = document.getElementById('assetImages');
    const fileChipList = document.getElementById('fileChipList');
    const saveButton = document.getElementById('saveAssetRegistrationButton');
    const saveOverlay = document.getElementById('saveAssetRegistrationOverlay');
    const cancelSaveButton = document.getElementById('cancelSaveAssetRegistrationButton');
    const confirmSaveButton = document.getElementById('confirmSaveAssetRegistrationButton');

    if (fileChipList) {
        fileChipList.addEventListener('click', (event) => {
            if (event.target.tagName === 'BUTTON') {
                const chip = event.target.closest('.file-chip');

                if (chip) {
                    chip.remove();
                }
            }
        });
    }

    if (uploadBox && assetImages && fileChipList) {
        assetImages.addEventListener('change', () => {
            const files = Array.from(assetImages.files || []);

            files.forEach((file) => {
                const chip = document.createElement('span');
                chip.className = 'file-chip';
                chip.innerHTML = `${file.name} <button type="button">×</button>`;
                fileChipList.appendChild(chip);
            });

            assetImages.value = '';
        });
    }

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
            const redirectUrl = confirmSaveButton.dataset.redirectUrl;

            const isValid = validateRequiredFields();

            if (!isValid) {
                closeOverlay(saveOverlay);
                return;
            }

            closeOverlay(saveOverlay);

            if (redirectUrl) {
                window.location.href = redirectUrl;
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

document.addEventListener('DOMContentLoaded', () => {
    initializeSidebarGroups();
    preventDisabledMenuReload();
    initializeCreateInteractions();
});
