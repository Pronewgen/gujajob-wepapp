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

function initializeSupplierSearch() {
    const searchType = document.getElementById('supplierSearchType');
    const searchInput = document.getElementById('supplierSearchInput');
    const searchButton = document.getElementById('supplierSearchButton');
    const tableBody = document.getElementById('supplierTableBody');
    const resultText = document.getElementById('supplierResultText');

    if (!searchType || !searchInput || !searchButton || !tableBody || !resultText) {
        return;
    }

    const originalRows = Array.from(tableBody.querySelectorAll('tr'));

    function updatePlaceholder() {
        const placeholders = {
            supplier_name: 'กรอกชื่อผู้ประกอบการ',
            supplier_type: 'กรอกประเภทผู้ประกอบการ',
            contact_name: 'กรอกชื่อผู้ติดต่อ',
            phone: 'กรอกเบอร์โทรศัพท์',
            address: 'กรอกที่อยู่',
        };

        searchInput.placeholder = placeholders[searchType.value] || 'กรอกชื่อผู้ประกอบการ';
    }

    function getTargetText(row) {
        const keyMap = {
            supplier_name: 'supplierName',
            supplier_type: 'supplierType',
            contact_name: 'contactName',
            phone: 'phone',
            address: 'address',
        };

        const key = keyMap[searchType.value] || 'supplierName';

        return String(row.dataset[key] || '').toLowerCase();
    }

    function updateResultText(visibleCount) {
        if (visibleCount === 0) {
            resultText.textContent = 'ไม่พบข้อมูลผู้ประกอบการ';
            return;
        }

        resultText.textContent = `แสดง 1 จากทั้งหมด ${visibleCount} รายการ`;
    }

    function searchRecords() {
        const keyword = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;

        originalRows.forEach((row) => {
            const isVisible = getTargetText(row).includes(keyword);

            row.style.display = isVisible ? '' : 'none';

            if (isVisible) {
                visibleCount += 1;
            }
        });

        const oldNoDataRow = tableBody.querySelector('.no-data-row');

        if (oldNoDataRow) {
            oldNoDataRow.remove();
        }

        if (visibleCount === 0) {
            const noDataRow = document.createElement('tr');
            noDataRow.className = 'no-data-row';
            noDataRow.innerHTML = '<td class="no-data" colspan="7">ไม่พบข้อมูลที่ค้นหา</td>';
            tableBody.appendChild(noDataRow);
        }

        updateResultText(visibleCount);
    }

    searchType.addEventListener('change', () => {
        searchInput.value = '';
        updatePlaceholder();
        searchRecords();
    });

    searchButton.addEventListener('click', searchRecords);

    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            searchRecords();
        }
    });

    searchInput.addEventListener('input', () => {
        if (searchInput.value.trim() === '') {
            searchRecords();
        }
    });

    updatePlaceholder();
    searchRecords();
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

function markInvalid(field) {
    field.classList.add('gujajob-is-invalid');

    const wrapper = field.closest('.form-field') || field.parentElement;

    if (wrapper && !wrapper.querySelector('.gujajob-validation-message')) {
        const message = document.createElement('div');
        message.className = 'gujajob-validation-message';
        message.textContent = 'กรุณากรอกข้อมูลช่องนี้';
        wrapper.appendChild(message);
    }
}

function showValidationAlert() {
    const pageHeader = document.querySelector('.page-header');

    const alert = document.createElement('div');
    alert.className = 'gujajob-validation-alert';
    alert.textContent = 'กรุณากรอกข้อมูลที่จำเป็นให้ครบก่อนบันทึก';

    if (pageHeader) {
        pageHeader.insertAdjacentElement('afterend', alert);
    }
}

function validateSupplierForm() {
    clearValidationState();

    const requiredFields = Array.from(document.querySelectorAll('.supplier-form [required]'));
    let isValid = true;
    let firstInvalid = null;

    requiredFields.forEach((field) => {
        const value = String(field.value || '').trim();

        if (value === '') {
            isValid = false;

            if (!firstInvalid) {
                firstInvalid = field;
            }

            markInvalid(field);
        }
    });

    if (!isValid) {
        showValidationAlert();

        if (firstInvalid) {
            firstInvalid.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });

            setTimeout(() => firstInvalid.focus(), 250);
        }
    }

    return isValid;
}

function initializeSupplierCreatePopup() {
    const saveButton = document.getElementById('saveSupplierButton');
    const overlay = document.getElementById('saveSupplierOverlay');
    const cancelButton = document.getElementById('cancelSaveSupplierButton');
    const confirmButton = document.getElementById('confirmSaveSupplierButton');

    if (!saveButton || !overlay || !cancelButton || !confirmButton) {
        return;
    }

    saveButton.addEventListener('click', () => {
        if (!validateSupplierForm()) {
            return;
        }

        openOverlay(overlay);
    });

    cancelButton.addEventListener('click', () => {
        closeOverlay(overlay);
    });

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            closeOverlay(overlay);
        }
    });

    confirmButton.addEventListener('click', () => {
        closeOverlay(overlay);
        window.location.href = '/asset/ASS-002-manage-supplier-information';
    });

    document.querySelectorAll('.supplier-form input, .supplier-form select').forEach((field) => {
        field.addEventListener('input', () => {
            if (String(field.value || '').trim() !== '') {
                field.classList.remove('gujajob-is-invalid');

                const wrapper = field.closest('.form-field');
                const message = wrapper ? wrapper.querySelector('.gujajob-validation-message') : null;

                if (message) {
                    message.remove();
                }
            }
        });

        field.addEventListener('change', () => {
            if (String(field.value || '').trim() !== '') {
                field.classList.remove('gujajob-is-invalid');

                const wrapper = field.closest('.form-field');
                const message = wrapper ? wrapper.querySelector('.gujajob-validation-message') : null;

                if (message) {
                    message.remove();
                }
            }
        });
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

initializeSidebarGroups();
preventDisabledMenuReload();
initializeSupplierSearch();
initializeSupplierCreatePopup();

/* ===== ASS-002 show/edit popup actions ===== */

function validateSupplierRequiredForm() {
    clearValidationState();

    const form = document.querySelector('.supplier-form');

    if (!form) {
        return true;
    }

    const fields = Array.from(form.querySelectorAll('[required]'));
    let isValid = true;
    let firstInvalid = null;

    fields.forEach((field) => {
        if (String(field.value || '').trim() === '') {
            isValid = false;

            if (!firstInvalid) {
                firstInvalid = field;
            }

            markInvalid(field);
        }
    });

    if (!isValid) {
        showValidationAlert();

        if (firstInvalid) {
            firstInvalid.scrollIntoView({
                behavior: 'smooth',
                block: 'center',
            });

            setTimeout(() => firstInvalid.focus(), 250);
        }
    }

    return isValid;
}

function initializeSupplierShowEditActions() {
    const deleteOverlay = document.getElementById('deleteSupplierOverlay');
    const openDeleteButton = document.getElementById('openDeleteSupplierPopup');
    const cancelDeleteButton = document.getElementById('cancelDeleteSupplierButton');
    const confirmDeleteButton = document.getElementById('confirmDeleteSupplierButton');

    const editOverlay = document.getElementById('editSupplierOverlay');
    const openEditButton = document.getElementById('openEditSupplierPopup');
    const cancelEditButton = document.getElementById('cancelEditSupplierButton');
    const confirmEditButton = document.getElementById('confirmEditSupplierButton');

    if (openDeleteButton && deleteOverlay) {
        openDeleteButton.addEventListener('click', () => {
            openOverlay(deleteOverlay);
        });
    }

    if (cancelDeleteButton && deleteOverlay) {
        cancelDeleteButton.addEventListener('click', () => {
            closeOverlay(deleteOverlay);
        });
    }

    if (confirmDeleteButton && deleteOverlay) {
        confirmDeleteButton.addEventListener('click', () => {
            closeOverlay(deleteOverlay);
            window.location.href = '/asset/ASS-002-manage-supplier-information';
        });
    }

    if (deleteOverlay) {
        deleteOverlay.addEventListener('click', (event) => {
            if (event.target === deleteOverlay) {
                closeOverlay(deleteOverlay);
            }
        });
    }

    if (openEditButton && editOverlay) {
        openEditButton.addEventListener('click', () => {
            if (!validateSupplierRequiredForm()) {
                return;
            }

            openOverlay(editOverlay);
        });
    }

    if (cancelEditButton && editOverlay) {
        cancelEditButton.addEventListener('click', () => {
            closeOverlay(editOverlay);
        });
    }

    if (confirmEditButton && editOverlay) {
        confirmEditButton.addEventListener('click', () => {
            closeOverlay(editOverlay);
            window.location.href = '/asset/ASS-002-manage-supplier-information';
        });
    }

    if (editOverlay) {
        editOverlay.addEventListener('click', (event) => {
            if (event.target === editOverlay) {
                closeOverlay(editOverlay);
            }
        });
    }
}

initializeSupplierShowEditActions();
