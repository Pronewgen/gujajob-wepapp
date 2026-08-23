import '../../components/date-picker.js';
import { initSearchableSelects } from '../../components/searchable-select.js';
import { SearchAutocomplete } from '../../components/search-autocomplete.js';

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

function initializePlaceholder() {
    const searchType  = document.getElementById('searchType');
    const searchInput = document.getElementById('searchInput');

    if (!searchType || !searchInput) {
        return;
    }

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

    searchType.addEventListener('change', function () {
        updatePlaceholder();
        searchInput.value = '';
    });

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

    // Edit-mode confirmation overlay (only rendered when $isEdit)
    const updateConfirmOverlay = document.getElementById('updateReceivingOverlay');
    const cancelUpdateButton = document.getElementById('cancelUpdateReceivingButton');
    const confirmUpdateButton = document.getElementById('confirmUpdateReceivingButton');

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

        if (window.matReceivingIsEdit && updateConfirmOverlay) {
            openOverlay(updateConfirmOverlay);
        } else {
            openOverlay(saveConfirmOverlay);
        }
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

    if (cancelUpdateButton) {
        cancelUpdateButton.addEventListener('click', () => closeOverlay(updateConfirmOverlay));
    }

    if (confirmUpdateButton) {
        confirmUpdateButton.addEventListener('click', () => {
            closeOverlay(updateConfirmOverlay);
            headerForm.submit();
        });
    }

    if (updateConfirmOverlay) {
        updateConfirmOverlay.addEventListener('click', (event) => {
            if (event.target === updateConfirmOverlay) {
                closeOverlay(updateConfirmOverlay);
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

    if (!materialSearchType || !materialSearchInput || !findMaterialButton || !addMaterialButton || !tableBody) {
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
    const searchUrl = window.matReceivingSearchUrl || null;

    function selectMaterial(material) {
        materialIdInput.value = material.id;
        materialCodeInput.value = material.code;
        materialNameInput.value = material.name;
        materialUnitInput.value = material.unit || '';
        clearFieldInvalid(materialCodeInput);
        receiveQtyInput.disabled = false;
        receiveQtyInput.value = '';
        clearFieldInvalid(receiveQtyInput);
        unitPriceInput.disabled = false;
        unitPriceInput.value = '';
        clearFieldInvalid(unitPriceInput);
        addMaterialButton.disabled = false;
        receiveQtyInput.focus();
    }

    const ac = new SearchAutocomplete({
        inputEl:      materialSearchInput,
        searchTypeEl: materialSearchType,
        endpoint:     '/material/search-api',
        minChars:     1,
        debounceMs:   300,
        maxResults:   20,
        onSelect: (material) => {
            selectMaterial(material);
            materialSearchInput.value = '';
            clearFieldInvalid(materialSearchInput);
        },
    });

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
        receiveQtyInput.disabled = true;
        unitPriceInput.disabled = true;
        addMaterialButton.disabled = true;
        clearFieldInvalid(materialCodeInput);
        clearFieldInvalid(receiveQtyInput);
        clearFieldInvalid(unitPriceInput);
    }

    function collectRows() {        return Array.from(tableBody.querySelectorAll('tr')).filter((row) => !row.classList.contains('empty-row'));
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

        // In edit mode: show qty input + hidden input for form submission
        const isEditMode = Boolean(window.matReceivingIsEdit);
        const qtyCell = isEditMode
            ? `<td class="mat002-qty-cell">
                    <input class="mat002-qty-visible" type="number" value="${item.qty}" min="0.01" step="0.01" disabled>
                    <input class="mat002-qty-hidden" type="hidden" form="receivingHeaderForm" name="items[${item.id}][qty]" value="${item.qty}">
               </td>`
            : `<td>${item.qty}</td>`;

        const editBtnLabel = isEditMode ? 'แก้ไขจำนวน' : 'แก้ไข';

        row.innerHTML = `
            <td>0</td>
            <td><span class="material-code">${item.code || ''}</span></td>
            <td>${item.name || ''}</td>
            ${qtyCell}
            <td>${item.unit || ''}</td>
            <td>${Number(item.price).toFixed(2)}</td>
            <td>
                <div class="table-action-buttons">
                    <button class="table-action-icon table-action-edit" type="button" title="${editBtnLabel}" aria-label="${editBtnLabel}" data-tooltip="${editBtnLabel}">
                        <svg aria-hidden="true"><use href="#icon-square-pen"></use></svg>
                    </button>
                    <button class="table-action-icon table-action-delete" type="button" title="ลบ" aria-label="ลบ" data-tooltip="ลบ">
                        <svg aria-hidden="true"><use href="#icon-trash"></use></svg>
                    </button>
                </div>
            </td>
        `;

        return row;
    }

    /* ── Inline qty editing (EDIT mode only) ─────────────────────────── */

    function enterQtyEditMode(row) {
        const visibleInput      = row.querySelector('.mat002-qty-visible');
        const priceVisibleInput = row.querySelector('.mat002-price-visible');
        if (!visibleInput) return;

        // Save originals for Escape cancellation
        row.dataset.originalQty   = visibleInput.value;
        if (priceVisibleInput) row.dataset.originalPrice = priceVisibleInput.value;

        // Enable visible inputs
        visibleInput.disabled = false;
        if (priceVisibleInput) priceVisibleInput.disabled = false;
        row.dataset.editing = 'true';

        // Change pen → green check
        const editBtn = row.querySelector('.table-action-edit');
        if (editBtn) {
            editBtn.classList.remove('table-action-edit');
            editBtn.classList.add('qty-save-btn', 'table-action-save');
            editBtn.title = 'ยืนยันการแก้ไข';
            editBtn.setAttribute('aria-label', 'ยืนยันการแก้ไข');
            editBtn.setAttribute('data-tooltip', 'ยืนยันการแก้ไข');
            editBtn.querySelector('use').setAttribute('href', '#icon-success');
        }

        visibleInput.focus();
        visibleInput.select();
    }

    function confirmQtyEdit(row) {
        const visibleInput      = row.querySelector('.mat002-qty-visible');
        const hiddenInput       = row.querySelector('.mat002-qty-hidden');
        const priceVisibleInput = row.querySelector('.mat002-price-visible');
        const priceHiddenInput  = row.querySelector('.mat002-price-hidden');
        if (!visibleInput) return;

        const qty = parseFloat(visibleInput.value);
        if (isNaN(qty) || qty <= 0) {
            visibleInput.classList.add('gujajob-is-invalid');
            visibleInput.title = 'จำนวนต้องมากกว่า 0';
            visibleInput.focus();
            return;
        }
        visibleInput.classList.remove('gujajob-is-invalid');
        visibleInput.title = '';

        // Validate price if present
        if (priceVisibleInput) {
            const price = parseFloat(priceVisibleInput.value);
            if (isNaN(price) || price < 0) {
                priceVisibleInput.classList.add('gujajob-is-invalid');
                priceVisibleInput.title = 'ราคา/หน่วยต้องไม่ติดลบ';
                priceVisibleInput.focus();
                return;
            }
            priceVisibleInput.classList.remove('gujajob-is-invalid');
            priceVisibleInput.title = '';
            if (priceHiddenInput) priceHiddenInput.value = String(price);
            row.dataset.price = String(price);
        }

        // Write confirmed qty to hidden input and update dataset
        const qtyStr = String(qty);
        if (hiddenInput) hiddenInput.value = qtyStr;
        row.dataset.qty = qtyStr;

        // Disable visible inputs
        visibleInput.disabled = true;
        if (priceVisibleInput) priceVisibleInput.disabled = true;
        delete row.dataset.editing;

        // Change check → pen
        const saveBtn = row.querySelector('.qty-save-btn');
        if (saveBtn) {
            saveBtn.classList.remove('qty-save-btn', 'table-action-save');
            saveBtn.classList.add('table-action-edit');
            saveBtn.title = 'แก้ไขจำนวน/ราคา';
            saveBtn.setAttribute('aria-label', 'แก้ไขจำนวน/ราคา');
            saveBtn.setAttribute('data-tooltip', 'แก้ไขจำนวน/ราคา');
            saveBtn.querySelector('use').setAttribute('href', '#icon-square-pen');
        }

        updateTotalQty();
    }

    function cancelQtyEdit(row) {
        const visibleInput      = row.querySelector('.mat002-qty-visible');
        const priceVisibleInput = row.querySelector('.mat002-price-visible');
        if (!visibleInput) return;

        // Restore original values
        visibleInput.value = row.dataset.originalQty || row.dataset.qty || '';
        if (priceVisibleInput) priceVisibleInput.value = row.dataset.originalPrice || row.dataset.price || '';
        visibleInput.classList.remove('gujajob-is-invalid');
        if (priceVisibleInput) priceVisibleInput.classList.remove('gujajob-is-invalid');
        visibleInput.title = '';
        visibleInput.disabled = true;
        if (priceVisibleInput) priceVisibleInput.disabled = true;
        delete row.dataset.editing;

        // Change check → pen
        const saveBtn = row.querySelector('.qty-save-btn');
        if (saveBtn) {
            saveBtn.classList.remove('qty-save-btn', 'table-action-save');
            saveBtn.classList.add('table-action-edit');
            saveBtn.title = 'แก้ไขจำนวน/ราคา';
            saveBtn.setAttribute('aria-label', 'แก้ไขจำนวน/ราคา');
            saveBtn.setAttribute('data-tooltip', 'แก้ไขจำนวน/ราคา');
            saveBtn.querySelector('use').setAttribute('href', '#icon-square-pen');
        }
    }

    function findDuplicateRow(matId) {
        return collectRows().find((row) => Number(row.dataset.matId) === matId);
    }

    let editingRow = null;

    function fillMaterialForm(row) {
        materialIdInput.value = row.dataset.matId || '';
        materialCodeInput.value = row.dataset.code || '';
        materialNameInput.value = row.dataset.name || '';
        materialUnitInput.value = row.dataset.unit || '';
        clearFieldInvalid(materialCodeInput);

        // enable qty/price/button so user can enter values without saving the header first
        receiveQtyInput.disabled = false;
        receiveQtyInput.value = row.dataset.qty || '';
        clearFieldInvalid(receiveQtyInput);
        unitPriceInput.disabled = false;
        unitPriceInput.value = row.dataset.price || '';
        clearFieldInvalid(unitPriceInput);
        addMaterialButton.disabled = false;

        document.querySelector('.mat002-item-grid')?.scrollIntoView({ behavior: 'smooth', block: 'center' });
        receiveQtyInput.focus();
    }

    function startEditItem(row) {
        fillMaterialForm(row);
        receiveQtyInput.value = row.dataset.qty || '';
        unitPriceInput.value = row.dataset.price || '';
        clearFieldInvalid(receiveQtyInput);
        clearFieldInvalid(unitPriceInput);
        if (editingRow) editingRow.classList.remove('editing-row');
        editingRow = row;
        row.classList.add('editing-row');
        addMaterialButton.textContent = 'บันทึก';
    }

    /* ── Draft items state & helpers (create mode only) ──────────────────────────────────── */
    let draftItems = [];
    let draftIdCounter = 0;
    const draftJsonInput = document.getElementById('draftItemsJson');

    function syncDraftInput() {
        if (!draftJsonInput) return;
        draftJsonInput.value = JSON.stringify(
            draftItems.map((d) => ({
                material_id: d.matId,
                code:        d.code,
                name:        d.name,
                unit:        d.unit,
                quantity:    d.qty,
                unit_price:  d.price,
            }))
        );
    }

    function buildDraftRow(item) {
        const row = document.createElement('tr');
        row.dataset.draftId = String(item.id);
        row.dataset.matId   = String(item.matId);
        row.dataset.code    = item.code;
        row.dataset.name    = item.name;
        row.dataset.unit    = item.unit;
        row.dataset.qty     = String(item.qty);
        row.dataset.price   = String(item.price);

        row.innerHTML = `
            <td>0</td>
            <td><span class="material-code">${item.code}</span></td>
            <td>${item.name}</td>
            <td>${item.qty}</td>
            <td>${item.unit}</td>
            <td>${Number(item.price).toFixed(2)}</td>
            <td>
                <div class="table-action-buttons">
                    <button class="table-action-icon table-action-edit" type="button" title="แก้ไข" aria-label="แก้ไข" data-tooltip="แก้ไข">
                        <svg aria-hidden="true"><use href="#icon-square-pen"></use></svg>
                    </button>
                    <button class="table-action-icon table-action-delete" type="button" title="ลบ" aria-label="ลบ" data-tooltip="ลบ">
                        <svg aria-hidden="true"><use href="#icon-trash"></use></svg>
                    </button>
                </div>
            </td>
        `;
        return row;
    }

    function deleteDraftItem(row) {
        const draftId = parseInt(row.dataset.draftId);
        if (!isNaN(draftId)) {
            draftItems = draftItems.filter((d) => d.id !== draftId);
            syncDraftInput();
        }
        if (editingRow === row) {
            editingRow = null;
            addMaterialButton.textContent = 'เพิ่ม';
        }
        row.remove();
        rebuildRowNumbers();
        showEmptyRowIfNeeded();
        updateTotalQty();
    }
    /* ─────────────────────────────────────────────────────────────────────────────── */

    function addItem() {
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

        // Create mode: add item to draft state; save alongside header in a single transaction
        if (!window.matReceivingIsEdit) {
            const isDuplicate = findDuplicateRow(matId);
            if (isDuplicate && isDuplicate !== editingRow) {
                markFieldInvalid(materialCodeInput, 'วัสดุรายการนี้ถูกเพิ่มแล้ว กรุณาแก้ไขจำนวนในรายการเดิม');
                return;
            }
            if (editingRow) {
                deleteDraftItem(editingRow);
                editingRow = null;
                addMaterialButton.textContent = 'เพิ่ม';
            }
            const draftId = ++draftIdCounter;
            const item = { id: draftId, matId, code: materialCodeInput.value.trim(), name: materialNameInput.value.trim(), unit: materialUnitInput.value.trim(), qty, price };
            draftItems.push(item);
            syncDraftInput();
            const draftRow = buildDraftRow(item);
            tableBody.appendChild(draftRow);
            rebuildRowNumbers();
            showEmptyRowIfNeeded();
            updateTotalQty();
            clearMiniForm();
            materialSearchInput.value = '';
            return;
        }

        const isDuplicate = findDuplicateRow(matId);
        if (isDuplicate && isDuplicate !== editingRow) {
            markFieldInvalid(materialCodeInput, 'วัสดุรายการนี้ถูกเพิ่มแล้ว กรุณาแก้ไขจำนวนในรายการเดิม');
            return;
        }

        addMaterialButton.disabled = true;

        if (editingRow) {
            const oldItemId = editingRow.dataset.itemId;
            const rowRef = editingRow;

            fetch(`${itemsBaseUrl}/${oldItemId}`, {
                method: 'DELETE',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
            })
                .then((response) => {
                    if (!response.ok) throw new Error('delete failed');
                    return fetch(itemsBaseUrl, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': getCsrfToken() },
                        body: JSON.stringify({ material_id: matId, quantity: qty, unit_price: price }),
                    });
                })
                .then(async (response) => {
                    const data = await response.json().catch(() => ({}));
                    if (!response.ok) {
                        const message = data?.errors?.material_id?.[0] || data?.errors?.quantity?.[0] || data?.errors?.unit_price?.[0] || data?.message || 'ไม่สามารถแก้ไขรายการวัสดุได้';
                        markFieldInvalid(materialCodeInput, message);
                        return;
                    }
                    const newRow = buildRow(data.item);
                    rowRef.replaceWith(newRow);
                    editingRow = null;
                    addMaterialButton.textContent = 'เพิ่ม';
                    rebuildRowNumbers();
                    updateTotalQty();
                    clearMiniForm();
                    materialSearchInput.value = '';
                })
                .catch(() => {
                    markFieldInvalid(materialCodeInput, 'ไม่สามารถแก้ไขรายการวัสดุได้ กรุณาลองใหม่อีกครั้ง');
                })
                .finally(() => { addMaterialButton.disabled = false; });
            return;
        }

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
        const target = event.target;
        const row = target.closest('tr');
        if (!row || row.classList.contains('empty-row')) return;

        if (target.closest('.table-action-delete')) {
            if (window.matReceivingIsEdit) {
                deleteItem(row);
            } else {
                deleteDraftItem(row);
            }
        } else if (target.closest('.qty-save-btn')) {
            // EDIT mode: green check → confirm qty
            confirmQtyEdit(row);
        } else if (target.closest('.table-action-edit')) {
            if (window.matReceivingIsEdit) {
                // EDIT mode: pen → inline qty edit (never fill top form)
                enterQtyEditMode(row);
            } else {
                // CREATE mode: pen → fill top add-material form
                startEditItem(row);
            }
        }
    });

    // Keyboard support for inline qty editing
    tableBody.addEventListener('keydown', (event) => {
        if (!window.matReceivingIsEdit) return;
        const row = event.target.closest('tr[data-editing="true"]');
        if (!row) return;
        if (event.key === 'Enter') {
            event.preventDefault();
            confirmQtyEdit(row);
        } else if (event.key === 'Escape') {
            event.preventDefault();
            cancelQtyEdit(row);
        }
    });

    materialSearchType.addEventListener('change', () => {
        materialSearchInput.value = '';
        clearFieldInvalid(materialSearchInput);
        updateSearchPlaceholder();
    });

    findMaterialButton.addEventListener('click', () => ac.triggerSearch());
    addMaterialButton.addEventListener('click', addItem);

    attachLiveClear(materialSearchInput, () => materialSearchInput.value.trim() !== '');
    attachLiveClear(receiveQtyInput, () => Number(receiveQtyInput.value || 0) > 0);
    attachLiveClear(unitPriceInput, () => String(unitPriceInput.value).trim() !== '' && Number(unitPriceInput.value) >= 0);

    /* ===== Create-mode bottom save button: delegates to top save button ===== */

    const saveHeaderButtonBottom = document.getElementById('saveHeaderButtonBottom');
    if (saveHeaderButtonBottom) {
        saveHeaderButtonBottom.addEventListener('click', () => {
            document.getElementById('saveHeaderButton')?.click();
        });
    }

    /* ===== Finalize / Save (edit mode): check editing rows, then submit header form ===== */

    if (finalizeButton) {
        finalizeButton.addEventListener('click', () => {
            if (!isRecordSaved) {
                return;
            }

            // In edit mode: block submission if any row is still being edited
            if (window.matReceivingIsEdit && tableBody) {
                const editingRow = tableBody.querySelector('tr[data-editing="true"]');
                if (editingRow) {
                    if (noItemsMessage) noItemsMessage.textContent = 'กรุณายืนยันจำนวนที่แก้ไขก่อนบันทึก';
                    openOverlay(noItemsOverlay);
                    const visibleInput = editingRow.querySelector('.mat002-qty-visible');
                    visibleInput?.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    setTimeout(() => visibleInput?.focus(), 250);
                    return;
                }
            }

            if (collectRows().length === 0) {
                if (noItemsMessage) {
                    noItemsMessage.textContent = 'กรุณาเพิ่มรายการวัสดุอย่างน้อย 1 รายการ';
                }
                openOverlay(noItemsOverlay);
                return;
            }

            if (window.matReceivingIsEdit) {
                // EDIT mode: submit header form (includes item qty hidden inputs via form="receivingHeaderForm")
                document.getElementById('saveHeaderButton')?.click();
            } else if (finalizeForm) {
                finalizeForm.submit();
            }
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

    // Restore draft items after a backend validation error (create mode only)
    if (!window.matReceivingIsEdit && draftJsonInput && Array.isArray(window.mat002OldDraftItems)) {
        window.mat002OldDraftItems.forEach((item) => {
            const matId = parseInt(item.material_id);
            const qty   = parseFloat(item.quantity);
            const price = parseFloat(item.unit_price);
            if (matId > 0 && qty > 0 && price >= 0) {
                const draftId   = ++draftIdCounter;
                const draftItem = { id: draftId, matId, code: item.code || '', name: item.name || '', unit: item.unit || '', qty, price };
                draftItems.push(draftItem);
                tableBody.appendChild(buildDraftRow(draftItem));
            }
        });
        if (draftItems.length > 0) {
            rebuildRowNumbers();
            syncDraftInput();
        }
    }

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
        const selectedValue = selected ? String(selected.value) : '';

        if (selectedValue === '1' || selectedValue === '2') {
            // รวม VAT / ไม่รวม VAT: enable rate select, restore default 7
            vatRateInput.disabled = false;

            if (String(vatRateInput.value).trim() === '' || vatRateInput.value === '0') {
                vatRateInput.value = '7';
            }
        } else if (selectedValue === '3') {
            // ไม่มีภาษี: set 0, disabled (hidden input will carry 0)
            vatRateInput.value = '0';
            vatRateInput.disabled = true;
        } else {
            // unselected
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
initializePlaceholder();
initSearchableSelects();
initializeHeaderForm();
initializeReceivingItemEditor();
initializeVatToggle();
initializeReceiptCodePreview();
initializeMat002ListAutocomplete();

/* ===== Autocomplete for MAT-002 index search bar ===== */

function initializeMat002ListAutocomplete() {
    const searchInput = document.getElementById('searchInput');
    const searchType  = document.getElementById('searchType');

    if (!searchInput) return;

    const ac = new SearchAutocomplete({
        inputEl:      searchInput,
        searchTypeEl: searchType,
        endpoint:     window.searchSuggestionsUrl || '/search/suggestions',
        extraParams:  { entity: 'receipt' },
        minChars:     1,
        debounceMs:   300,
        maxResults:   15,
        onSelect: (item) => {
            searchInput.value = item.code;
            ac.close();
        },
    });
}

/* ===== Receipt code preview: update when date changes on create page ===== */

function initializeReceiptCodePreview() {
    const receivedDateInput = document.getElementById('receivedDate');
    const receiptNoInput    = document.getElementById('receiptNo');
    const previewUrl        = window.matReceivingPreviewCodeUrl;

    // Only run on create page (not edit)
    if (!receivedDateInput || !receiptNoInput || !previewUrl || window.matReceivingIsEdit) {
        return;
    }

    let lastFiscalYear = null;

    function fiscalYearFromDate(dateStr) {
        const date = new Date(dateStr);

        if (isNaN(date.getTime())) {
            return null;
        }

        const thaiYear = date.getFullYear() + 543;

        return date.getMonth() >= 9 ? thaiYear + 1 : thaiYear; // getMonth() is 0-indexed; Oct = 9
    }

    function updatePreview(dateStr) {
        if (!dateStr) {
            return;
        }

        const fiscalYear = fiscalYearFromDate(dateStr);

        if (!fiscalYear || fiscalYear === lastFiscalYear) {
            return;
        }

        lastFiscalYear = fiscalYear;

        fetch(`${previewUrl}?date=${encodeURIComponent(dateStr)}`)
            .then((res) => res.json())
            .then((data) => {
                if (data.code) {
                    receiptNoInput.value = data.code;
                }
            })
            .catch(() => {});
    }

    receivedDateInput.addEventListener('change', () => {
        updatePreview(receivedDateInput.value);
    });
}

