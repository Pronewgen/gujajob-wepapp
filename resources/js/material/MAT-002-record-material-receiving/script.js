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

function initializeReceivingSearch() {
    const searchType = document.getElementById('searchType');
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    const tableBody = document.getElementById('receivingTableBody');
    const resultText = document.getElementById('resultText');

    if (!searchType || !searchInput || !searchButton || !tableBody || !resultText) {
        return;
    }

    const originalRows = Array.from(tableBody.querySelectorAll('tr'));

    function updatePlaceholder() {
        if (searchType.value === 'received_date') {
            searchInput.placeholder = 'กรอกวันที่รับ';
            return;
        }

        if (searchType.value === 'procurement_method') {
            searchInput.placeholder = 'กรอกวิธีการจัดซื้อจัดจ้าง';
            return;
        }

        searchInput.placeholder = 'กรอกเลขที่ใบรับวัสดุ';
    }

    function updateResultText(visibleCount) {
        if (visibleCount === 0) {
            resultText.textContent = 'ไม่พบประวัติการรับวัสดุเข้าคลัง';
            return;
        }

        resultText.textContent = `แสดง 1–${visibleCount} จากทั้งหมด ${visibleCount} รายการ`;
    }

    function searchRecords() {
        const keyword = searchInput.value.trim().toLowerCase();
        const type = searchType.value;
        let visibleCount = 0;

        originalRows.forEach((row) => {
            let targetText = '';

            if (type === 'received_date') {
                targetText = row.dataset.receivedDate.toLowerCase();
            } else if (type === 'procurement_method') {
                targetText = row.dataset.procurementMethod.toLowerCase();
            } else {
                targetText = row.dataset.receiptNo.toLowerCase();
            }

            const isVisible = targetText.includes(keyword);
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
            noDataRow.innerHTML = '<td class="no-data" colspan="5">ไม่พบข้อมูลที่ค้นหา</td>';
            tableBody.appendChild(noDataRow);
        }

        updateResultText(visibleCount);
    }

    searchType.addEventListener('change', () => {
        updatePlaceholder();
        searchInput.value = '';
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
}

function initializeReceivingItemEditor() {
    const materialSearchType = document.getElementById('materialSearchType');
    const materialSearchInput = document.getElementById('materialSearchInput');
    const findMaterialButton = document.getElementById('findMaterialButton');

    const materialCode = document.getElementById('materialCode');
    const materialName = document.getElementById('materialName');
    const materialUnit = document.getElementById('materialUnit');
    const receiveQty = document.getElementById('receiveQty');
    const unitPrice = document.getElementById('unitPrice');
    const addMaterialButton = document.getElementById('addMaterialButton');

    const tableBody = document.getElementById('receivingItemsBody');
    const totalQty = document.getElementById('totalQty');

    if (!materialSearchType || !materialSearchInput || !findMaterialButton || !materialCode || !materialName || !materialUnit || !tableBody || !addMaterialButton) {
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

    function setAddMode() {
        editingRow = null;
        addMaterialButton.textContent = 'เพิ่ม';
        addMaterialButton.classList.remove('is-editing');
    }

    function setEditMode(row) {
        editingRow = row;
        addMaterialButton.textContent = 'เพิ่ม';
        addMaterialButton.classList.add('is-editing');
    }

    function clearMaterialInputForm() {
        materialCode.value = '';
        materialName.value = '';
        receiveQty.value = '';
        materialUnit.value = '';
        unitPrice.value = '';
        setAddMode();
    }

    function updateSearchPlaceholder() {
        if (materialSearchType.value === 'name') {
            materialSearchInput.placeholder = 'กรอกชื่อวัสดุ';
            return;
        }

        materialSearchInput.placeholder = 'กรอกรหัสวัสดุ';
    }

    function fillMaterialInputForm(material, qty = '', price = '') {
        materialCode.value = material.code;
        materialName.value = material.name;
        materialUnit.value = material.unit;
        receiveQty.value = qty;
        unitPrice.value = price;
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
            clearMaterialInputForm();
            alert('ไม่พบข้อมูลวัสดุที่ค้นหา');
            return;
        }

        fillMaterialInputForm(material);
        setAddMode();
    }

    function recalculateTotal() {
        let total = 0;

        tableBody.querySelectorAll('tr').forEach((row) => {
            total += Number(row.dataset.qty || 0);
        });

        if (totalQty) {
            totalQty.textContent = total;
        }
    }

    function reorderRows() {
        tableBody.querySelectorAll('tr').forEach((row, index) => {
            row.children[0].textContent = index + 1;
        });
    }

    function showNoItemRowIfEmpty() {
        const rows = tableBody.querySelectorAll('tr:not(.empty-row)');

        if (rows.length > 0) {
            const oldEmpty = tableBody.querySelector('.empty-row');

            if (oldEmpty) {
                oldEmpty.remove();
            }

            return;
        }

        if (!tableBody.querySelector('.empty-row')) {
            const emptyRow = document.createElement('tr');
            emptyRow.className = 'empty-row';
            emptyRow.innerHTML = '<td colspan="7" style="text-align:center;color:#6b7280;">ยังไม่มีรายการ</td>';
            tableBody.appendChild(emptyRow);
        }
    }

    function buildActionButtons() {
        return `
            <button class="small-edit-btn" type="button">แก้ไข</button>
            <button class="small-delete-btn" type="button">ลบ</button>
        `;
    }

    function updateRow(row, code, name, qty, unit, price) {
        row.dataset.code = code;
        row.dataset.name = name;
        row.dataset.qty = qty;
        row.dataset.unit = unit;
        row.dataset.price = price.toFixed(2);

        row.children[1].innerHTML = `<span class="material-code">${code}</span>`;
        row.children[2].textContent = name;
        row.children[3].textContent = qty;
        row.children[4].textContent = unit;
        row.children[5].textContent = price.toFixed(2);
        row.children[6].innerHTML = buildActionButtons();
    }

    function addNewRow(code, name, qty, unit, price) {
        const emptyRow = tableBody.querySelector('.empty-row');

        if (emptyRow) {
            emptyRow.remove();
        }

        const rowCount = tableBody.querySelectorAll('tr').length + 1;
        const row = document.createElement('tr');

        row.dataset.code = code;
        row.dataset.name = name;
        row.dataset.qty = qty;
        row.dataset.unit = unit;
        row.dataset.price = price.toFixed(2);

        row.innerHTML = `
            <td>${rowCount}</td>
            <td><span class="material-code">${code}</span></td>
            <td>${name}</td>
            <td>${qty}</td>
            <td>${unit}</td>
            <td>${price.toFixed(2)}</td>
            <td>${buildActionButtons()}</td>
        `;

        tableBody.appendChild(row);
    }

    materialSearchType.addEventListener('change', () => {
        materialSearchInput.value = '';
        updateSearchPlaceholder();
    });

    findMaterialButton.addEventListener('click', findMaterial);

    materialSearchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            findMaterial();
        }
    });

    addMaterialButton.addEventListener('click', () => {
        if (!materialCode.value || !materialName.value || !materialUnit.value) {
            alert('กรุณาค้นหาและเลือกวัสดุก่อน');
            return;
        }

        const qty = Number(receiveQty.value);
        const price = Number(unitPrice.value);

        if (qty <= 0 || price < 0) {
            alert('กรุณาระบุจำนวนและราคาให้ถูกต้อง');
            return;
        }

        if (editingRow) {
            updateRow(editingRow, materialCode.value, materialName.value, qty, materialUnit.value, price);
        } else {
            addNewRow(materialCode.value, materialName.value, qty, materialUnit.value, price);
        }

        reorderRows();
        recalculateTotal();
        clearMaterialInputForm();
        materialSearchInput.value = '';
        showNoItemRowIfEmpty();
    });

    tableBody.addEventListener('click', (event) => {
        const row = event.target.closest('tr');

        if (!row || row.classList.contains('empty-row')) {
            return;
        }

        if (event.target.classList.contains('small-delete-btn')) {
            if (editingRow === row) {
                clearMaterialInputForm();
            }

            row.remove();
            reorderRows();
            recalculateTotal();
            showNoItemRowIfEmpty();
            return;
        }

        if (event.target.classList.contains('small-edit-btn')) {
            fillMaterialInputForm(
                {
                    code: row.dataset.code,
                    name: row.dataset.name,
                    unit: row.dataset.unit,
                },
                row.dataset.qty,
                row.dataset.price
            );

            materialSearchInput.value = row.dataset.code;
            materialSearchType.value = 'code';
            updateSearchPlaceholder();
            setEditMode(row);
        }
    });

    updateSearchPlaceholder();
    clearMaterialInputForm();
    recalculateTotal();
    showNoItemRowIfEmpty();
}

function initializeCreateSavePopup() {
    const saveButton = document.getElementById('saveReceivingButton');
    const overlay = document.getElementById('saveReceivingOverlay');
    const cancelButton = document.getElementById('cancelSaveReceivingButton');
    const confirmButton = document.getElementById('confirmSaveReceivingButton');

    if (!saveButton || !overlay || !cancelButton || !confirmButton) {
        return;
    }

    saveButton.addEventListener('click', () => openOverlay(overlay));
    cancelButton.addEventListener('click', () => closeOverlay(overlay));

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            closeOverlay(overlay);
        }
    });

    confirmButton.addEventListener('click', () => {
        closeOverlay(overlay);
        window.location.href = '/material/MAT-002-record-material-receiving';
    });
}

function initializeEditSavePopup() {
    const saveButton = document.getElementById('saveReceivingEditButton');
    const overlay = document.getElementById('editReceivingOverlay');
    const cancelButton = document.getElementById('cancelEditReceivingButton');
    const confirmButton = document.getElementById('confirmEditReceivingButton');

    if (!saveButton || !overlay || !cancelButton || !confirmButton) {
        return;
    }

    saveButton.addEventListener('click', () => openOverlay(overlay));
    cancelButton.addEventListener('click', () => closeOverlay(overlay));

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            closeOverlay(overlay);
        }
    });

    confirmButton.addEventListener('click', () => {
        closeOverlay(overlay);
        window.location.href = '/material/MAT-002-record-material-receiving';
    });
}

function initializeReceivingDeletePopup() {
    const openButton = document.getElementById('openDeleteReceivingModal');
    const overlay = document.getElementById('deleteReceivingOverlay');
    const cancelButton = document.getElementById('cancelDeleteReceivingButton');
    const confirmButton = document.getElementById('confirmDeleteReceivingButton');

    if (!openButton || !overlay || !cancelButton || !confirmButton) {
        return;
    }

    openButton.addEventListener('click', () => openOverlay(overlay));
    cancelButton.addEventListener('click', () => closeOverlay(overlay));

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            closeOverlay(overlay);
        }
    });

    confirmButton.addEventListener('click', () => {
        closeOverlay(overlay);
        window.location.href = '/material/MAT-002-record-material-receiving';
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
initializeReceivingSearch();
initializeReceivingItemEditor();
initializeCreateSavePopup();
initializeEditSavePopup();
initializeReceivingDeletePopup();


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
