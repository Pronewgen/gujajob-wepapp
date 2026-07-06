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

function initializeAssetCategorySearch() {
    const searchType = document.getElementById('assetSearchType');
    const searchInput = document.getElementById('assetSearchInput');
    const searchButton = document.getElementById('assetSearchButton');
    const tableBody = document.getElementById('assetCategoryTableBody');
    const resultText = document.getElementById('assetCategoryResultText');

    if (!searchType || !searchInput || !searchButton || !tableBody || !resultText) {
        return;
    }

    const originalRows = Array.from(tableBody.querySelectorAll('tr'));

    function updatePlaceholder() {
        if (searchType.value === 'asset_name') {
            searchInput.placeholder = 'กรอกชื่อครุภัณฑ์';
            return;
        }

        if (searchType.value === 'asset_type') {
            searchInput.placeholder = 'กรอกชนิดครุภัณฑ์';
            return;
        }

        if (searchType.value === 'asset_group') {
            searchInput.placeholder = 'กรอกหมวดครุภัณฑ์';
            return;
        }

        searchInput.placeholder = 'กรอกรหัสประเภทครุภัณฑ์';
    }

    function getTargetText(row) {
        if (searchType.value === 'asset_name') {
            return row.dataset.assetName.toLowerCase();
        }

        if (searchType.value === 'asset_type') {
            return row.dataset.assetType.toLowerCase();
        }

        if (searchType.value === 'asset_group') {
            return row.dataset.assetGroup.toLowerCase();
        }

        return row.dataset.categoryCode.toLowerCase();
    }

    function updateResultText(visibleCount) {
        if (visibleCount === 0) {
            resultText.textContent = 'ไม่พบรายการครุภัณฑ์ในระบบ';
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

function initializeAssetCategoryCreatePopup() {
    const saveButton = document.getElementById('saveAssetCategoryButton');
    const overlay = document.getElementById('saveAssetCategoryOverlay');
    const cancelButton = document.getElementById('cancelSaveAssetCategoryButton');
    const confirmButton = document.getElementById('confirmSaveAssetCategoryButton');

    if (!saveButton || !overlay || !cancelButton || !confirmButton) {
        return;
    }

    saveButton.addEventListener('click', () => {
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
        window.location.href = '/asset/ASS-001-manage-asset-categories';
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
initializeAssetCategorySearch();
initializeAssetCategoryCreatePopup();


/* ===== Global save validation for create/edit pages ===== */

(function () {
    if (window.__gujajobSaveValidationInstalled) {
        return;
    }

    window.__gujajobSaveValidationInstalled = true;

    const currentPath = window.location.pathname;
    const isFormPage = currentPath.includes('/create') || currentPath.endsWith('/edit') || currentPath.includes('/edit/');

    if (!isFormPage) {
        return;
    }

    function isVisible(element) {
        if (!element) {
            return false;
        }

        const style = window.getComputedStyle(element);

        return style.display !== 'none'
            && style.visibility !== 'hidden'
            && element.offsetParent !== null;
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

    function isIgnoredField(field) {
        if (!field || field.disabled || field.readOnly) {
            return true;
        }

        if (!isVisible(field)) {
            return true;
        }

        const ignoredTypes = ['hidden', 'button', 'submit', 'reset', 'file'];

        if (ignoredTypes.includes(field.type)) {
            return true;
        }

        const id = field.id || '';
        const name = field.name || '';
        const ignoredKeyword = ['search', 'keyword', 'filter'];

        return ignoredKeyword.some((word) => {
            return id.toLowerCase().includes(word) || name.toLowerCase().includes(word);
        });
    }

    function isRequiredField(field) {
        if (field.required) {
            return true;
        }

        const label = getFieldLabel(field);

        if (!label) {
            return false;
        }

        return label.textContent.includes('*') || Boolean(label.querySelector('.required'));
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

    function hasAtLeastOneRealRow(selector) {
        const body = document.querySelector(selector);

        if (!body) {
            return true;
        }

        const rows = Array.from(body.querySelectorAll('tr')).filter((row) => {
            const text = row.textContent.trim();

            return isVisible(row)
                && !row.classList.contains('no-data-row')
                && !text.includes('ยังไม่มีรายการ')
                && !text.includes('ไม่พบข้อมูล')
                && row.querySelectorAll('td').length > 1;
        });

        return rows.length > 0;
    }

    function validateRequiredFields() {
        clearValidationState();

        const root = getContentRoot();
        let isValid = true;
        let firstInvalidField = null;

        const fields = Array.from(root.querySelectorAll('input, select, textarea'));

        fields.forEach((field) => {
            if (isIgnoredField(field)) {
                return;
            }

            const required = isRequiredField(field);
            const value = String(field.value || '').trim();

            if (required && value === '') {
                isValid = false;

                if (!firstInvalidField) {
                    firstInvalidField = field;
                }

                markInvalid(field, 'กรุณากรอกข้อมูลช่องนี้');

                return;
            }

            if (field.type === 'number' && value !== '' && Number(value) < 0) {
                isValid = false;

                if (!firstInvalidField) {
                    firstInvalidField = field;
                }

                markInvalid(field, 'ค่าตัวเลขต้องไม่ติดลบ');
            }
        });

        if (currentPath.includes('MAT-002-record-material-receiving')) {
            const hasRows = hasAtLeastOneRealRow('#receivingItemsBody')
                && hasAtLeastOneRealRow('#receiveItemsBody')
                && hasAtLeastOneRealRow('#materialReceivingItemsBody');

            if (!hasRows) {
                isValid = false;
                showValidationAlert('กรุณาเพิ่มรายการวัสดุที่รับเข้าคลังอย่างน้อย 1 รายการ');
            }
        }

        if (currentPath.includes('MAT-003-withdraw-material')) {
            const hasRows = hasAtLeastOneRealRow('#withdrawItemsBody');

            if (!hasRows) {
                isValid = false;
                showValidationAlert('กรุณาเพิ่มรายการวัสดุที่ต้องการเบิกอย่างน้อย 1 รายการ');
            }
        }

        if (!isValid) {
            if (!document.querySelector('.gujajob-validation-alert')) {
                showValidationAlert('กรุณากรอกข้อมูลที่จำเป็นให้ครบก่อนบันทึก');
            }

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

    function isMainSaveButton(button) {
        if (!button || button.tagName !== 'BUTTON') {
            return false;
        }

        const text = button.textContent.trim();

        if (!text.includes('บันทึก')) {
            return false;
        }

        if (text.includes('ยืนยัน')) {
            return false;
        }

        if (text.includes('พิมพ์') || text.includes('ดาวน์โหลด')) {
            return false;
        }

        return true;
    }

    function enableAllSaveButtons() {
        const root = getContentRoot();

        Array.from(root.querySelectorAll('button')).forEach((button) => {
            if (!isMainSaveButton(button)) {
                return;
            }

            button.disabled = false;
            button.classList.remove('gujajob-save-disabled');
        });
    }

    document.addEventListener('click', (event) => {
        const button = event.target.closest('button');

        if (!isMainSaveButton(button)) {
            return;
        }

        const isValid = validateRequiredFields();

        if (!isValid) {
            event.preventDefault();
            event.stopPropagation();
            event.stopImmediatePropagation();
        }
    }, true);

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

    setTimeout(enableAllSaveButtons, 50);
    setTimeout(enableAllSaveButtons, 300);
})();



/* ===== ASS-001 show/edit popup actions ===== */

function initializeAssetCategoryShowEditActions() {
    const deleteOverlay = document.getElementById('deleteAssetCategoryOverlay');
    const openDeleteButton = document.getElementById('openDeleteAssetCategoryPopup');
    const cancelDeleteButton = document.getElementById('cancelDeleteAssetCategoryButton');
    const confirmDeleteButton = document.getElementById('confirmDeleteAssetCategoryButton');

    const editOverlay = document.getElementById('editAssetCategoryOverlay');
    const openEditButton = document.getElementById('openEditAssetCategoryPopup');
    const cancelEditButton = document.getElementById('cancelEditAssetCategoryButton');
    const confirmEditButton = document.getElementById('confirmEditAssetCategoryButton');

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
            window.location.href = '/asset/ASS-001-manage-asset-categories';
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
            window.location.href = '/asset/ASS-001-manage-asset-categories';
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

initializeAssetCategoryShowEditActions();
