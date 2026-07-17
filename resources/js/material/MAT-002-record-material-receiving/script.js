import '../../components/date-picker.js';
import { initSearchableSelects } from '../../components/searchable-select.js';

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

function initializeReceivingSearch() {
    const searchType = document.getElementById('searchType');
    const searchInput = document.getElementById('searchInput');
    const searchButton = document.getElementById('searchButton');
    const tableBody = document.getElementById('receivingTableBody');
    const resultText = document.getElementById('resultText');

    if (!searchType || !searchInput || !searchButton || !tableBody || !resultText) {
        return;
    }

    const originalRows = Array.from(tableBody.querySelectorAll('tr')).filter((row) => !row.classList.contains('no-data-row'));

    function updatePlaceholder() {
        if (searchType.value === 'received_date') {
            searchInput.placeholder = 'กรอกวันที่รับ';
            return;
        }

        if (searchType.value === 'organization') {
            searchInput.placeholder = 'กรอกหน่วยงาน';
            return;
        }

        searchInput.placeholder = 'กรอกเลขที่ใบรับวัสดุ';
    }

    function updateResultText(visibleCount) {
        if (visibleCount === 0) {
            resultText.textContent = 'ไม่พบประวัติการรับวัสดุเข้าคลัง';
            return;
        }

        resultText.textContent = `แสดงทั้งหมด ${visibleCount} รายการ`;
    }

    function searchRecords() {
        const keyword = searchInput.value.trim().toLowerCase();
        const type = searchType.value;
        let visibleCount = 0;

        originalRows.forEach((row) => {
            let targetText = row.dataset.receiptNo || '';

            if (type === 'received_date') {
                targetText = row.dataset.receivedDate || '';
            } else if (type === 'organization') {
                targetText = row.dataset.organization || '';
            }

            const isVisible = targetText.toLowerCase().includes(keyword);
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
            noDataRow.innerHTML = '<td class="no-data" colspan="6">ยังไม่มีข้อมูลวัสดุ</td>';
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
    searchInput.addEventListener('input', searchRecords);

    updatePlaceholder();
}

/* ===== Shared helpers used by both the header form and the item editor ===== */

function getDisplayField(field) {
    if (field && field._flatpickr && field._flatpickr.altInput) return field._flatpickr.altInput;
    if (field && field._saTextInput) return field._saTextInput;
    return field;
}

function getErrorContainer(field) {
    if (!field || !field.id) {
        return null;
    }

    return document.getElementById(`${field.id}Error`);
}

function markFieldInvalid(field, message) {
    if (!field) {
        return;
    }

    getDisplayField(field).classList.add('gujajob-is-invalid');

    const container = getErrorContainer(field);

    if (container) {
        container.textContent = message;
    }
}

function clearFieldInvalid(field) {
    if (!field) {
        return;
    }

    getDisplayField(field).classList.remove('gujajob-is-invalid');

    const container = getErrorContainer(field);

    if (container) {
        container.textContent = '';
    }
}

function attachLiveClear(field, isValidNow) {
    if (!field) {
        return;
    }

    const handler = () => {
        if (isValidNow()) {
            clearFieldInvalid(field);
        }
    };

    field.addEventListener('input', handler);
    field.addEventListener('change', handler);
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

function getCsrfToken() {
    const tokenInput = document.querySelector('#receivingHeaderForm input[name="_token"]');
    return tokenInput ? tokenInput.value : '';
}

/* ===== Step 1: Header form only (MATERIAL_PROCUREMENT). Never touches items. ===== */

function initializeHeaderForm() {
    const headerForm = document.getElementById('receivingHeaderForm');
    const saveButton = document.getElementById('saveHeaderButton');

    if (!headerForm || !saveButton) {
        return;
    }

    const receivedDateInput = document.getElementById('receivedDate');
    const receiverDepartmentSelect = document.getElementById('receiverDepartment');
    const dealerSelect = document.getElementById('dealerSelect');

    const saveConfirmOverlay = document.getElementById('saveReceivingOverlay');
    const cancelSaveButton = document.getElementById('cancelSaveReceivingButton');
    const confirmSaveButton = document.getElementById('confirmSaveReceivingButton');

    function validateHeaderFields() {
        const requiredFields = [
            { field: receivedDateInput, message: 'กรุณาระบุวันที่รับวัสดุ' },
            { field: receiverDepartmentSelect, message: 'กรุณาเลือกหน่วยงานที่รับเข้า' },
            { field: dealerSelect, message: 'กรุณาเลือกผู้ประกอบการ' },
        ];

        let isValid = true;
        let firstInvalidField = null;

        requiredFields.forEach(({ field, message }) => {
            if (!field) {
                return;
            }

            const value = String(field.value || '').trim();

            if (value === '') {
                markFieldInvalid(field, message);
                isValid = false;

                if (!firstInvalidField) {
                    firstInvalidField = field;
                }
            } else {
                clearFieldInvalid(field);
            }
        });

        return { isValid, firstInvalidField };
    }

    attachLiveClear(receivedDateInput, () => String(receivedDateInput?.value || '').trim() !== '');
    attachLiveClear(receiverDepartmentSelect, () => String(receiverDepartmentSelect?.value || '').trim() !== '');
    attachLiveClear(dealerSelect, () => String(dealerSelect?.value || '').trim() !== '');

    saveButton.addEventListener('click', () => {
        const result = validateHeaderFields();

        if (!result.isValid) {
            if (result.firstInvalidField) {
                const displayField = getDisplayField(result.firstInvalidField);
                displayField.scrollIntoView({ behavior: 'smooth', block: 'center' });
                setTimeout(() => displayField.focus(), 250);
            }

            return;
        }

        openOverlay(saveConfirmOverlay);
    });

    if (cancelSaveButton) {
        cancelSaveButton.addEventListener('click', () => closeOverlay(saveConfirmOverlay));
    }

    if (confirmSaveButton) {
        confirmSaveButton.addEventListener('click', () => {
            closeOverlay(saveConfirmOverlay);
            headerForm.submit();
        });
    }

    if (saveConfirmOverlay) {
        saveConfirmOverlay.addEventListener('click', (event) => {
            if (event.target === saveConfirmOverlay) {
                closeOverlay(saveConfirmOverlay);
            }
        });
    }
}

/* ===== Step 2: Items (MATERIAL_PROCUREMENT_LIST), enabled only once the header exists. ===== */

function initializeReceivingItemEditor() {
    const materialSearchType = document.getElementById('materialSearchType');
    const materialSearchInput = document.getElementById('materialSearchInput');
    const findMaterialButton = document.getElementById('findMaterialButton');
    const addMaterialButton = document.getElementById('addMaterialButton');
    const tableBody = document.getElementById('receivingItemsBody');
    const totalQtyElement = document.getElementById('totalQty');

    if (!materialSearchType || !materialSearchInput || !findMaterialButton || !addMaterialButton || !tableBody || !totalQtyElement) {
        return;
    }

    const materialIdInput = document.getElementById('materialId');
    const materialCodeInput = document.getElementById('materialCode');
    const materialNameInput = document.getElementById('materialName');
    const materialUnitInput = document.getElementById('materialUnit');
    const receiveQtyInput = document.getElementById('receiveQty');
    const unitPriceInput = document.getElementById('unitPrice');

    const finalizeButton = document.getElementById('finalizeReceivingButton');
    const finalizeForm = document.getElementById('finalizeForm');
    const noItemsOverlay = document.getElementById('noItemsOverlay');
    const noItemsMessage = document.getElementById('noItemsMessage');
    const closeNoItemsButton = document.getElementById('closeNoItemsButton');

    const materialMaster = Array.isArray(window.matReceivingMaterials) ? window.matReceivingMaterials : [];
    const isRecordSaved = Boolean(window.matReceivingRecordSaved);
    const itemsBaseUrl = window.matReceivingItemsBaseUrl || null;

    function updateSearchPlaceholder() {
        materialSearchInput.placeholder = materialSearchType.value === 'name'
            ? 'กรอกชื่อวัสดุ'
            : 'กรอกรหัสวัสดุ';
    }

    function clearMiniForm() {
        materialIdInput.value = '';
        materialCodeInput.value = '';
        materialNameInput.value = '';
        materialUnitInput.value = '';
        receiveQtyInput.value = '';
        unitPriceInput.value = '';
        clearFieldInvalid(materialCodeInput);
        clearFieldInvalid(receiveQtyInput);
        clearFieldInvalid(unitPriceInput);
    }

    function findMaterial() {
        const keyword = materialSearchInput.value.trim().toLowerCase();

        clearFieldInvalid(materialSearchInput);

        if (!keyword) {
            markFieldInvalid(materialSearchInput, 'กรุณากรอกคำค้นหา');
            return;
        }

        const material = materialMaster.find((item) => {
            if (materialSearchType.value === 'name') {
                return String(item.name || '').toLowerCase().includes(keyword);
            }

            return String(item.code || '').toLowerCase().includes(keyword);
        });

        if (!material) {
            markFieldInvalid(materialSearchInput, 'ไม่พบข้อมูลวัสดุที่ค้นหา');
            return;
        }

        materialIdInput.value = material.id;
        materialCodeInput.value = material.code;
        materialNameInput.value = material.name;
        materialUnitInput.value = material.unit;
        clearFieldInvalid(materialCodeInput);
    }

    function collectRows() {
        return Array.from(tableBody.querySelectorAll('tr')).filter((row) => !row.classList.contains('empty-row'));
    }

    function rebuildRowNumbers() {
        collectRows().forEach((row, index) => {
            row.children[0].textContent = index + 1;
        });
    }

    function showEmptyRowIfNeeded() {
        if (collectRows().length > 0) {
            const empty = tableBody.querySelector('.empty-row');
            if (empty) {
                empty.remove();
            }
            return;
        }

        if (!tableBody.querySelector('.empty-row')) {
            const row = document.createElement('tr');
            row.className = 'empty-row';
            row.innerHTML = '<td colspan="7" class="no-data">ยังไม่มีรายการ</td>';
            tableBody.appendChild(row);
        }
    }

    function updateTotalQty() {
        const totalQty = collectRows().reduce((sum, row) => sum + Number(row.dataset.qty || 0), 0);
        totalQtyElement.textContent = totalQty;
    }

    function buildRow(item) {
        const row = document.createElement('tr');
        row.dataset.itemId = String(item.id);
        row.dataset.matId = String(item.mat_id);
        row.dataset.code = item.code || '';
        row.dataset.name = item.name || '';
        row.dataset.unit = item.unit || '';
        row.dataset.qty = String(item.qty);
        row.dataset.price = String(item.price);

        row.innerHTML = `
            <td>0</td>
            <td><span class="material-code">${item.code || ''}</span></td>
            <td>${item.name || ''}</td>
            <td>${item.qty}</td>
            <td>${item.unit || ''}</td>
            <td>${Number(item.price).toFixed(2)}</td>
            <td><button class="small-delete-btn" type="button">ลบ</button></td>
        `;

        return row;
    }

    function findDuplicateRow(matId) {
        return collectRows().find((row) => Number(row.dataset.matId) === matId);
    }

    function addItem() {
        if (!isRecordSaved || !itemsBaseUrl) {
            return;
        }

        const matId = Number(materialIdInput.value || 0);
        const qtyRaw = receiveQtyInput.value;
        const priceRaw = unitPriceInput.value;
        const qty = Number(qtyRaw || 0);
        const price = Number(priceRaw || 0);

        let isValid = true;

        if (!matId) {
            markFieldInvalid(materialCodeInput, 'กรุณาเลือกวัสดุ');
            isValid = false;
        } else {
            clearFieldInvalid(materialCodeInput);
        }

        if (String(qtyRaw).trim() === '' || qty <= 0) {
            markFieldInvalid(receiveQtyInput, 'กรุณาระบุจำนวนมากกว่า 0');
            isValid = false;
        } else {
            clearFieldInvalid(receiveQtyInput);
        }

        if (String(priceRaw).trim() === '' || price < 0) {
            markFieldInvalid(unitPriceInput, 'กรุณาระบุราคา/หน่วยให้ถูกต้อง');
            isValid = false;
        } else {
            clearFieldInvalid(unitPriceInput);
        }

        if (!isValid) {
            return;
        }

        if (findDuplicateRow(matId)) {
            markFieldInvalid(materialCodeInput, 'วัสดุรายการนี้ถูกเพิ่มแล้ว กรุณาแก้ไขจำนวนในรายการเดิม');
            return;
        }

        addMaterialButton.disabled = true;

        fetch(itemsBaseUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
            body: JSON.stringify({
                material_id: matId,
                quantity: qty,
                unit_price: price,
            }),
        })
            .then(async (response) => {
                const data = await response.json().catch(() => ({}));

                if (!response.ok) {
                    const message = data?.errors?.material_id?.[0]
                        || data?.errors?.quantity?.[0]
                        || data?.errors?.unit_price?.[0]
                        || data?.message
                        || 'ไม่สามารถเพิ่มรายการวัสดุได้';

                    markFieldInvalid(materialCodeInput, message);
                    return;
                }

                const row = buildRow(data.item);
                tableBody.appendChild(row);
                rebuildRowNumbers();
                showEmptyRowIfNeeded();
                updateTotalQty();
                clearMiniForm();
                materialSearchInput.value = '';
            })
            .catch(() => {
                markFieldInvalid(materialCodeInput, 'ไม่สามารถเพิ่มรายการวัสดุได้ กรุณาลองใหม่อีกครั้ง');
            })
            .finally(() => {
                addMaterialButton.disabled = false;
            });
    }

    function deleteItem(row) {
        if (!itemsBaseUrl) {
            return;
        }

        const itemId = row.dataset.itemId;

        if (!itemId) {
            return;
        }

        fetch(`${itemsBaseUrl}/${itemId}`, {
            method: 'DELETE',
            headers: {
                Accept: 'application/json',
                'X-CSRF-TOKEN': getCsrfToken(),
            },
        })
            .then((response) => {
                if (!response.ok) {
                    throw new Error('delete failed');
                }

                row.remove();
                rebuildRowNumbers();
                showEmptyRowIfNeeded();
                updateTotalQty();
            })
            .catch(() => {
                // Silently ignore; row stays as-is if the delete failed.
            });
    }

    tableBody.addEventListener('click', (event) => {
        if (!event.target.classList.contains('small-delete-btn')) {
            return;
        }

        const row = event.target.closest('tr');

        if (row && !row.classList.contains('empty-row')) {
            deleteItem(row);
        }
    });

    materialSearchType.addEventListener('change', () => {
        materialSearchInput.value = '';
        clearFieldInvalid(materialSearchInput);
        updateSearchPlaceholder();
    });

    findMaterialButton.addEventListener('click', findMaterial);
    addMaterialButton.addEventListener('click', addItem);

    attachLiveClear(materialSearchInput, () => materialSearchInput.value.trim() !== '');
    attachLiveClear(receiveQtyInput, () => Number(receiveQtyInput.value || 0) > 0);
    attachLiveClear(unitPriceInput, () => String(unitPriceInput.value).trim() !== '' && Number(unitPriceInput.value) >= 0);

    /* ===== Finalize: the ONLY place that requires at least one item ===== */

    if (finalizeButton) {
        finalizeButton.addEventListener('click', () => {
            if (!isRecordSaved || !finalizeForm) {
                return;
            }

            if (collectRows().length === 0) {
                if (noItemsMessage) {
                    noItemsMessage.textContent = 'กรุณาเพิ่มรายการวัสดุอย่างน้อย 1 รายการ';
                }
                openOverlay(noItemsOverlay);
                return;
            }

            finalizeForm.submit();
        });
    }

    if (closeNoItemsButton) {
        closeNoItemsButton.addEventListener('click', () => closeOverlay(noItemsOverlay));
    }

    if (noItemsOverlay) {
        noItemsOverlay.addEventListener('click', (event) => {
            if (event.target === noItemsOverlay) {
                closeOverlay(noItemsOverlay);
            }
        });
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && noItemsOverlay && noItemsOverlay.classList.contains('is-visible')) {
            closeOverlay(noItemsOverlay);
        }
    });

    // Server redirected back after a failed finalize() (items were removed by
    // someone else between page load and clicking finalize) - show the same popup.
    if (window.matReceivingFinalizeError) {
        if (noItemsMessage) {
            noItemsMessage.textContent = window.matReceivingFinalizeError;
        }
        openOverlay(noItemsOverlay);
    }

    updateSearchPlaceholder();
    showEmptyRowIfNeeded();
    updateTotalQty();
}

/* ===== VAT type toggle: enable/disable the vat_rate input ===== */

function initializeVatToggle() {
    const vatRateInput = document.getElementById('vatRate');
    const vatTypeRadios = document.querySelectorAll('input[name="vat_type"]');

    if (!vatRateInput || !vatTypeRadios.length) {
        return;
    }

    function applyVatState() {
        const selected = document.querySelector('input[name="vat_type"]:checked');
        const isIncluded = selected && String(selected.value) === '1';

        if (isIncluded) {
            vatRateInput.disabled = false;

            if (String(vatRateInput.value).trim() === '') {
                vatRateInput.value = '7';
            }
        } else {
            vatRateInput.value = '';
            vatRateInput.disabled = true;
        }
    }

    vatTypeRadios.forEach((radio) => {
        radio.addEventListener('change', applyVatState);
    });

    // Apply on page load
    applyVatState();
}

initializeSidebarGroups();
preventDisabledMenuReload();
initializeReceivingSearch();
initSearchableSelects();
initializeHeaderForm();
initializeReceivingItemEditor();
initializeVatToggle();

