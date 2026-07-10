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

function initializeDisposalSearch() {
    const searchType = document.getElementById('searchType');
    const searchInput = document.getElementById('searchInput');
    const statusFilter = document.getElementById('statusFilter');
    const searchButton = document.getElementById('searchButton');
    const createButton = document.getElementById('createDisposalButton');
    const tableBody = document.getElementById('disposalTableBody');
    const resultText = document.getElementById('resultText');

    if (!searchType || !searchInput || !statusFilter || !searchButton || !tableBody || !resultText) {
        return;
    }

    const originalRows = Array.from(tableBody.querySelectorAll('tr'));

    function removeNoDataRow() {
        const oldNoDataRow = tableBody.querySelector('.no-data-row');

        if (oldNoDataRow) {
            oldNoDataRow.remove();
        }
    }

    function updateResult(visibleCount) {
        if (visibleCount === 0) {
            resultText.textContent = 'ไม่พบข้อมูลการแจ้งขอจำหน่ายครุภัณฑ์';
            return;
        }

        resultText.textContent = `แสดง 1-${visibleCount} จากทั้งหมด ${visibleCount} รายการ`;
    }

    function filterRecords() {
        const selectedType = searchType.value;
        const keyword = searchInput.value.trim().toLowerCase();
        const selectedStatus = statusFilter.value;
        let visibleCount = 0;

        removeNoDataRow();

        originalRows.forEach((row) => {
            const requestNo = row.dataset.requestNo || '';
            const requestDepartment = row.dataset.requestDepartment || '';
            const reason = row.dataset.reason || '';
            const status = row.dataset.status || '';
            const searchTarget = selectedType === 'request_department' ? requestDepartment : selectedType === 'reason' ? reason : requestNo;
            const keywordMatched = keyword === '' || searchTarget.includes(keyword);
            const statusMatched = selectedStatus === 'all' || selectedStatus === status;
            const isVisible = keywordMatched && statusMatched;

            row.style.display = isVisible ? '' : 'none';

            if (isVisible) {
                visibleCount += 1;
            }
        });

        if (visibleCount === 0) {
            const noDataRow = document.createElement('tr');
            noDataRow.className = 'no-data-row';
            noDataRow.innerHTML = '<td class="no-data" colspan="6">ไม่พบข้อมูลที่ค้นหา</td>';
            tableBody.appendChild(noDataRow);
        }

        updateResult(visibleCount);
    }

    searchButton.addEventListener('click', filterRecords);
    searchInput.addEventListener('input', filterRecords);
    searchType.addEventListener('change', filterRecords);
    statusFilter.addEventListener('change', filterRecords);

    if (createButton) {
        createButton.addEventListener('click', () => {
            const createUrl = createButton.dataset.createUrl;

            if (createUrl) {
                window.location.href = createUrl;
            }
        });
    }

    filterRecords();
}

function openOverlay(overlay) {
    if (!overlay) {
        return;
    }

    overlay.classList.add('is-visible');
    overlay.setAttribute('aria-hidden', 'false');
}

function closeOverlay(overlay) {
    if (!overlay) {
        return;
    }

    overlay.classList.remove('is-visible');
    overlay.setAttribute('aria-hidden', 'true');
}

function getFieldLabel(field) {
    if (field.id) {
        const label = document.querySelector(`label[for="${CSS.escape(field.id)}"]`);

        if (label) {
            return label;
        }
    }

    const wrapper = field.closest('.field-group');

    if (wrapper) {
        return wrapper.querySelector('label');
    }

    return null;
}

function validateRequiredFields() {
    const fields = Array.from(document.querySelectorAll('.disposal-create-page input, .disposal-create-page select, .disposal-create-page textarea'));
    const requiredFields = fields.filter((field) => {
        if (field.disabled || field.readOnly || field.type === 'hidden' || field.type === 'button') {
            return false;
        }

        const label = getFieldLabel(field);
        const isRequiredByStar = Boolean(label && label.textContent.includes('*'));
        const isRequiredByClass = field.classList.contains('required-filter');

        return isRequiredByStar || isRequiredByClass || field.required;
    });

    const firstInvalidField = requiredFields.find((field) => String(field.value || '').trim() === '');

    if (!firstInvalidField) {
        return {
            isValid: true,
            firstInvalidField: null,
        };
    }

    return {
        isValid: false,
        firstInvalidField,
    };
}

function getDisposalValidationHost() {
    return document.querySelector('.disposal-create-page .create-shell')
        || document.querySelector('.disposal-edit-page .create-shell')
        || document.querySelector('.disposal-page .page-container')
        || document.body;
}

function clearDisposalValidationAlert() {
    const oldAlert = document.querySelector('.disposal-inline-validation-error');

    if (oldAlert) {
        oldAlert.remove();
    }
}

function showDisposalValidationAlert(message) {
    clearDisposalValidationAlert();

    const host = getDisposalValidationHost();
    const alert = document.createElement('div');

    alert.className = 'disposal-inline-validation-error';
    alert.setAttribute('role', 'alert');
    alert.textContent = message;

    const firstSection = host.querySelector('.create-section');

    if (firstSection) {
        firstSection.insertAdjacentElement('beforebegin', alert);
        return;
    }

    host.prepend(alert);
}

function initializeDisposalItemManager() {
    const codeInput = document.getElementById('selectedAssetCode');
    const nameInput = document.getElementById('selectedAssetName');
    const addButton = document.getElementById('addAssetItemButton');
    const tableBody = document.getElementById('assetItemTableBody');
    const itemCountElement = document.getElementById('assetItemCount');

    if (!codeInput || !nameInput || !addButton || !tableBody || !itemCountElement) {
        return;
    }

    const items = [];
    const initialItemsRaw = tableBody.dataset.initialItems;

    if (initialItemsRaw) {
        try {
            const initialItems = JSON.parse(initialItemsRaw);

            if (Array.isArray(initialItems)) {
                initialItems.forEach((item) => {
                    if (!item || typeof item !== 'object') {
                        return;
                    }

                    const code = String(item.code || '').trim();
                    const name = String(item.name || '').trim();
                    const value = String(item.value || '').trim();

                    if (!code || !name) {
                        return;
                    }

                    items.push({
                        code,
                        name,
                        value,
                    });
                });
            }
        } catch (error) {
            console.warn('Cannot parse initial disposal items.');
        }
    }

    function renderItems() {
        tableBody.innerHTML = '';

        if (items.length === 0) {
            tableBody.innerHTML = `
                <tr>
                    <td class="no-data" colspan="7">ยังไม่มีรายการครุภัณฑ์ที่เลือก</td>
                </tr>
                <tr class="summary-row">
                    <td colspan="7">รวมจำนวนรายการทั้งสิ้น <strong id="assetItemCount">0</strong> รายการ</td>
                </tr>
            `;
            return;
        }

        items.forEach((item, index) => {
            const row = document.createElement('tr');
            const assetValue = String(item.value || '').trim() || '-';

            row.innerHTML = `
                <td class="center">${index + 1}</td>
                <td class="code-cell">${item.code}</td>
                <td>${item.name}</td>
                <td class="center">${assetValue}</td>
                <td class="center">-</td>
                <td class="center">-</td>
                <td class="center">
                    <div class="item-action-group">
                        <button type="button" class="edit-item-btn" data-index="${index}">แก้ไข</button>
                        <button type="button" class="remove-item-btn" data-index="${index}">ลบ</button>
                    </div>
                </td>
            `;

            tableBody.appendChild(row);
        });

        const summaryRow = document.createElement('tr');
        summaryRow.className = 'summary-row';
        summaryRow.innerHTML = `<td colspan="7">รวมจำนวนรายการทั้งสิ้น <strong id="assetItemCount">${items.length}</strong> รายการ</td>`;
        tableBody.appendChild(summaryRow);
    }

    function addItem() {
        const code = String(codeInput.value || '').trim();
        const name = String(nameInput.value || '').trim();

        if (!code || !name) {
            showDisposalValidationAlert('กรุณากรอกรหัสครุภัณฑ์และชื่อครุภัณฑ์ก่อนเพิ่มรายการ');
            return;
        }

        clearDisposalValidationAlert();

        items.push({
            code,
            name,
        });

        codeInput.value = '';
        nameInput.value = '';
        renderItems();
        codeInput.focus();
    }

    addButton.addEventListener('click', addItem);

    nameInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            event.preventDefault();
            addItem();
        }
    });

    tableBody.addEventListener('click', (event) => {
        const editButton = event.target.closest('.edit-item-btn');
        const removeButton = event.target.closest('.remove-item-btn');

        if (editButton) {
            const index = Number(editButton.dataset.index);

            if (Number.isInteger(index) && items[index]) {
                codeInput.value = items[index].code;
                nameInput.value = items[index].name;
                items.splice(index, 1);
                renderItems();
                codeInput.focus();
            }

            return;
        }

        if (removeButton) {
            const index = Number(removeButton.dataset.index);

            if (Number.isInteger(index) && items[index]) {
                items.splice(index, 1);
                renderItems();
            }
        }
    });

    renderItems();
}

function initializeDisposalCreateConfirm() {
    const saveButton = document.getElementById('saveDisposalButton');
    const overlay = document.getElementById('saveDisposalOverlay');
    const cancelButton = document.getElementById('cancelSaveDisposalButton');
    const confirmButton = document.getElementById('confirmSaveDisposalButton');

    if (!saveButton || !overlay || !cancelButton || !confirmButton) {
        return;
    }

    function showRequiredError(firstInvalidField) {
        showDisposalValidationAlert('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วนก่อนยืนยัน');

        if (firstInvalidField) {
            firstInvalidField.focus();
        }
    }

    document
        .querySelectorAll('.disposal-create-page input, .disposal-create-page select, .disposal-create-page textarea, .disposal-edit-page input, .disposal-edit-page select, .disposal-edit-page textarea')
        .forEach((field) => {
            field.addEventListener('input', clearDisposalValidationAlert);
            field.addEventListener('change', clearDisposalValidationAlert);
        });

    saveButton.addEventListener('click', () => {
        const validation = validateRequiredFields();

        if (!validation.isValid) {
            showRequiredError(validation.firstInvalidField);
            return;
        }

        clearDisposalValidationAlert();

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
        const validation = validateRequiredFields();

        if (!validation.isValid) {
            closeOverlay(overlay);
            showRequiredError(validation.firstInvalidField);
            return;
        }

        clearDisposalValidationAlert();

        const redirectUrl = saveButton.dataset.redirectUrl;

        if (redirectUrl) {
            window.location.href = redirectUrl;
            return;
        }

        closeOverlay(overlay);
    });
}

function initializeDisposalDeleteConfirm() {
    const openButton = document.getElementById('openDeleteDisposalButton');
    const overlay = document.getElementById('deleteDisposalOverlay');
    const cancelButton = document.getElementById('cancelDeleteDisposalButton');
    const confirmButton = document.getElementById('confirmDeleteDisposalButton');
    const requestNoText = document.getElementById('deleteDisposalRequestNo');

    if (!openButton || !overlay || !cancelButton || !confirmButton || !requestNoText) {
        return;
    }

    const requestNo = String(openButton.dataset.requestNo || '').trim();

    requestNoText.textContent = requestNo ? `'${requestNo}'` : "'-'";

    openButton.addEventListener('click', () => {
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
        const redirectUrl = openButton.dataset.redirectUrl;

        if (redirectUrl) {
            window.location.href = redirectUrl;
            return;
        }

        closeOverlay(overlay);
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initializeSidebarGroups();
    preventDisabledMenuReload();
    initializeDisposalSearch();
    initializeDisposalItemManager();
    initializeDisposalCreateConfirm();
    initializeDisposalDeleteConfirm();
});