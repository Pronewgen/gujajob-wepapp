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

function initializeWithdrawSearch() {
    const searchType = document.getElementById('withdrawSearchType');
    const searchInput = document.getElementById('withdrawSearchInput');
    const statusSelect = document.getElementById('withdrawStatus');
    const searchButton = document.getElementById('withdrawSearchButton');
    const tableBody = document.getElementById('withdrawTableBody');
    const resultText = document.getElementById('withdrawResultText');

    if (!searchType || !searchInput || !statusSelect || !searchButton || !tableBody || !resultText) {
        return;
    }

    const originalRows = Array.from(tableBody.querySelectorAll('tr'));

    function updatePlaceholder() {
        if (searchType.value === 'withdraw_date') {
            searchInput.placeholder = 'กรอกวันที่เบิก';
            return;
        }

        if (searchType.value === 'requester') {
            searchInput.placeholder = 'กรอกชื่อผู้เบิก';
            return;
        }

        if (searchType.value === 'department') {
            searchInput.placeholder = 'กรอกหน่วยงาน';
            return;
        }

        searchInput.placeholder = 'กรอกเลขที่ใบเบิกวัสดุ';
    }

    function getTargetText(row) {
        if (searchType.value === 'withdraw_date') {
            return row.dataset.withdrawDate.toLowerCase();
        }

        if (searchType.value === 'requester') {
            return row.dataset.requester.toLowerCase();
        }

        if (searchType.value === 'department') {
            return row.dataset.department.toLowerCase();
        }

        return row.dataset.withdrawNo.toLowerCase();
    }

    function updateResultText(visibleCount) {
        if (visibleCount === 0) {
            resultText.textContent = 'ไม่พบประวัติการเบิกวัสดุ';
            return;
        }

        resultText.textContent = `แสดง 1–${visibleCount} จากทั้งหมด ${visibleCount} รายการ`;
    }

    function searchWithdrawRecords() {
        const keyword = searchInput.value.trim().toLowerCase();
        const selectedStatus = statusSelect.value;
        let visibleCount = 0;

        originalRows.forEach((row) => {
            const targetText = getTargetText(row);
            const rowStatus = row.dataset.status;

            const keywordMatched = targetText.includes(keyword);
            const statusMatched = !selectedStatus || rowStatus === selectedStatus;
            const isVisible = keywordMatched && statusMatched;

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
            noDataRow.innerHTML = '<td class="no-data" colspan="6">ไม่พบข้อมูลที่ค้นหา</td>';
            tableBody.appendChild(noDataRow);
        }

        updateResultText(visibleCount);
    }

    searchType.addEventListener('change', () => {
        searchInput.value = '';
        updatePlaceholder();
        searchWithdrawRecords();
    });

    statusSelect.addEventListener('change', searchWithdrawRecords);
    searchButton.addEventListener('click', searchWithdrawRecords);

    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            searchWithdrawRecords();
        }
    });

    searchInput.addEventListener('input', () => {
        if (searchInput.value.trim() === '') {
            searchWithdrawRecords();
        }
    });

    updatePlaceholder();
    searchWithdrawRecords();
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

function initializeWithdrawCreatePage() {
    const withdrawDepartment = document.getElementById('withdrawDepartment');
    const statusBadge = document.getElementById('withdrawStatusBadge');
    const approveDepartment = document.getElementById('approveDepartment');

    const materialSearchType = document.getElementById('materialSearchType');
    const materialSearchInput = document.getElementById('materialSearchInput');
    const findButton = document.getElementById('findWithdrawMaterialButton');

    const materialCode = document.getElementById('materialCode');
    const materialName = document.getElementById('materialName');
    const withdrawQty = document.getElementById('withdrawQty');
    const materialUnit = document.getElementById('materialUnit');
    const addButton = document.getElementById('addWithdrawMaterialButton');
    const tableBody = document.getElementById('withdrawItemsBody');

    const saveButton = document.getElementById('saveWithdrawButton');
    const overlay = document.getElementById('saveWithdrawOverlay');
    const cancelButton = document.getElementById('cancelSaveWithdrawButton');
    const confirmButton = document.getElementById('confirmSaveWithdrawButton');
    const confirmText = document.getElementById('saveWithdrawConfirmText');

    if (!withdrawDepartment || !statusBadge || !approveDepartment) {
        return;
    }

    let editingRow = null;

    const materialMaster = [
        {
            code: 'MAT-1001',
            name: 'กระดาษถ่ายเอกสาร A4 80 แกรม',
            unit: 'รีม',
        },
        {
            code: 'MAT-3012',
            name: 'หมึกพิมพ์ Brother TN-2380',
            unit: 'กล่อง',
        },
    ];

    function updateStatusByDepartment() {
        if (withdrawDepartment.value === 'same') {
            statusBadge.textContent = 'อนุมัติ';
            statusBadge.classList.remove('pending');
            statusBadge.classList.add('approved');
            approveDepartment.value = 'Default จากหน่วยงานที่ทำการเบิก';
            return;
        }

        statusBadge.textContent = 'รอการอนุมัติ';
        statusBadge.classList.remove('approved');
        statusBadge.classList.add('pending');
        approveDepartment.value = 'Default จากหน่วยงานที่ทำการเบิก';
    }

    function updateSearchPlaceholder() {
        if (!materialSearchType || !materialSearchInput) return;

        if (materialSearchType.value === 'name') {
            materialSearchInput.placeholder = 'กรอกชื่อวัสดุ';
            return;
        }

        materialSearchInput.placeholder = 'กรอกรหัสวัสดุ';
    }

    function fillMaterialForm(material, qty = '') {
        materialCode.value = material.code;
        materialName.value = material.name;
        materialUnit.value = material.unit;
        withdrawQty.value = qty;
    }

    function clearMaterialForm() {
        materialCode.value = '';
        materialName.value = '';
        withdrawQty.value = '';
        materialUnit.value = '';
        editingRow = null;
    }

    function findMaterial() {
        const keyword = materialSearchInput.value.trim().toLowerCase();

        if (!keyword) {
            alert('กรุณากรอกคำค้นหาวัสดุ');
            return;
        }

        let material = null;

        if (materialSearchType.value === 'name') {
            material = materialMaster.find((item) => item.name.toLowerCase().includes(keyword));
        } else {
            material = materialMaster.find((item) => item.code.toLowerCase() === keyword);
        }

        if (!material) {
            clearMaterialForm();
            alert('ไม่พบข้อมูลวัสดุที่ค้นหา');
            return;
        }

        fillMaterialForm(material);
        editingRow = null;
    }

    function reorderRows() {
        tableBody.querySelectorAll('tr').forEach((row, index) => {
            row.children[0].textContent = index + 1;
        });
    }

    function buildButtons() {
        return `
            <button class="small-edit-btn" type="button">แก้ไข</button>
            <button class="small-delete-btn" type="button">ลบ</button>
        `;
    }

    function updateRow(row, code, name, qty, unit) {
        row.dataset.code = code;
        row.dataset.name = name;
        row.dataset.qty = qty;
        row.dataset.unit = unit;

        row.children[1].innerHTML = `<span class="material-code">${code}</span>`;
        row.children[2].textContent = name;
        row.children[3].innerHTML = `<input class="table-qty-input" type="number" value="${qty}" min="0">`;
        row.children[4].textContent = unit;
        row.children[5].innerHTML = buildButtons();
    }

    function addRow(code, name, qty, unit) {
        const row = document.createElement('tr');
        const rowCount = tableBody.querySelectorAll('tr').length + 1;

        row.dataset.code = code;
        row.dataset.name = name;
        row.dataset.qty = qty;
        row.dataset.unit = unit;

        row.innerHTML = `
            <td>${rowCount}</td>
            <td><span class="material-code">${code}</span></td>
            <td>${name}</td>
            <td><input class="table-qty-input" type="number" value="${qty}" min="0"></td>
            <td>${unit}</td>
            <td>${buildButtons()}</td>
        `;

        tableBody.appendChild(row);
    }

    withdrawDepartment.addEventListener('change', updateStatusByDepartment);

    if (materialSearchType) {
        materialSearchType.addEventListener('change', () => {
            materialSearchInput.value = '';
            updateSearchPlaceholder();
        });
    }

    if (findButton) {
        findButton.addEventListener('click', findMaterial);
    }

    if (materialSearchInput) {
        materialSearchInput.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                findMaterial();
            }
        });
    }

    if (addButton) {
        addButton.addEventListener('click', () => {
            if (!materialCode.value || !materialName.value || !materialUnit.value) {
                alert('กรุณาค้นหาและเลือกวัสดุก่อน');
                return;
            }

            const qty = Number(withdrawQty.value);

            if (qty <= 0) {
                alert('กรุณาระบุจำนวนที่เบิกให้ถูกต้อง');
                return;
            }

            if (editingRow) {
                updateRow(editingRow, materialCode.value, materialName.value, qty, materialUnit.value);
            } else {
                addRow(materialCode.value, materialName.value, qty, materialUnit.value);
            }

            reorderRows();
            clearMaterialForm();
            materialSearchInput.value = '';
        });
    }

    if (tableBody) {
        tableBody.addEventListener('click', (event) => {
            const row = event.target.closest('tr');

            if (!row) return;

            if (event.target.classList.contains('small-delete-btn')) {
                row.remove();
                reorderRows();
                clearMaterialForm();
                return;
            }

            if (event.target.classList.contains('small-edit-btn')) {
                editingRow = row;

                fillMaterialForm(
                    {
                        code: row.dataset.code,
                        name: row.dataset.name,
                        unit: row.dataset.unit,
                    },
                    row.dataset.qty
                );

                materialSearchType.value = 'code';
                materialSearchInput.value = row.dataset.code;
                updateSearchPlaceholder();
            }
        });

        tableBody.addEventListener('input', (event) => {
            if (!event.target.classList.contains('table-qty-input')) {
                return;
            }

            const row = event.target.closest('tr');

            if (row) {
                row.dataset.qty = event.target.value;
            }
        });
    }

    if (saveButton) {
        saveButton.addEventListener('click', () => {
            if (statusBadge.textContent.trim() === 'อนุมัติ') {
                confirmText.textContent = 'คุณแน่ใจหรือไม่ว่าต้องการบันทึกข้อมูลการเบิกวัสดุนี้ โดยสถานะจะเป็นอนุมัติ';
            } else {
                confirmText.textContent = 'คุณแน่ใจหรือไม่ว่าต้องการบันทึกข้อมูลการเบิกวัสดุนี้ โดยสถานะจะเป็นรอการอนุมัติ';
            }

            openOverlay(overlay);
        });
    }

    if (cancelButton) {
        cancelButton.addEventListener('click', () => closeOverlay(overlay));
    }

    if (overlay) {
        overlay.addEventListener('click', (event) => {
            if (event.target === overlay) {
                closeOverlay(overlay);
            }
        });
    }

    if (confirmButton) {
        confirmButton.addEventListener('click', () => {
            closeOverlay(overlay);
            window.location.href = '/material/MAT-003-withdraw-material';
        });
    }

    updateStatusByDepartment();
    updateSearchPlaceholder();
}

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') return;

    document.querySelectorAll('.confirm-overlay.is-visible').forEach((overlay) => {
        closeOverlay(overlay);
    });
});

initializeSidebarGroups();
preventDisabledMenuReload();
initializeWithdrawSearch();
initializeWithdrawCreatePage();


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
