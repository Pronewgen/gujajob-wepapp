import '../../components/date-picker.js';

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

function initializeDisposalCreatePage() {
    const form           = document.getElementById('disposalCreateForm');
    const searchTypeEl   = document.getElementById('assetSearchType');
    const searchInputEl  = document.getElementById('assetSearchKeyword');
    const searchBtn      = document.getElementById('assetSearchBtn');
    const resultBox      = document.getElementById('assetSearchResultBox');
    const resultBody     = document.getElementById('assetResultBody');
    const tableBody      = document.getElementById('assetItemTableBody');
    const saveButton     = document.getElementById('saveDisposalButton');

    if (!form || !tableBody || !saveButton) {
        return;
    }

    const searchUrl = form.dataset.assetSearchUrl || '';

    // Draft list stored by asset id to enable duplicate detection
    const draftItems = [];

    // ── render draft table ────────────────────────────────────────────────
    function renderDraft() {
        tableBody.innerHTML = '';

        if (draftItems.length === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.innerHTML = '<td class="no-data" colspan="7">ยังไม่มีรายการครุภัณฑ์ที่เลือก</td>';
            tableBody.appendChild(emptyRow);
        } else {
            draftItems.forEach((item, index) => {
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="center">${index + 1}</td>
                    <td class="code-cell">${escapeHtml(item.ass_code)}</td>
                    <td>${escapeHtml(item.asset_name)}</td>
                    <td class="center">${escapeHtml(item.ass_price)}</td>
                    <td class="center">-</td>
                    <td class="center">-</td>
                    <td class="center">
                        <div class="table-action-buttons">
                            <button type="button"
                                    class="table-action-icon table-action-delete remove-draft-btn"
                                    data-index="${index}"
                                    aria-label="ลบ" title="ลบ" data-tooltip="ลบ">
                                <svg aria-hidden="true"><use href="#icon-trash"></use></svg>
                            </button>
                        </div>
                    </td>
                `;
                tableBody.appendChild(row);
            });
        }

        const summaryRow = document.createElement('tr');
        summaryRow.className = 'summary-row';
        summaryRow.innerHTML = `<td colspan="7">รวมจำนวนรายการทั้งสิ้น <strong>${draftItems.length}</strong> รายการ</td>`;
        tableBody.appendChild(summaryRow);
    }

    renderDraft();

    // ── remove from draft ─────────────────────────────────────────────────
    tableBody.addEventListener('click', (event) => {
        const btn = event.target.closest('.remove-draft-btn');
        if (!btn) return;
        const index = Number(btn.dataset.index);
        if (Number.isInteger(index) && draftItems[index]) {
            draftItems.splice(index, 1);
            renderDraft();
        }
    });

    // ── asset search ──────────────────────────────────────────────────────
    function runSearch() {
        const field   = searchTypeEl ? searchTypeEl.value : '';
        const keyword = searchInputEl ? searchInputEl.value.trim() : '';

        if (!resultBox || !resultBody) return;

        resultBody.innerHTML = '<tr><td colspan="3" class="no-data">กำลังค้นหา…</td></tr>';
        resultBox.style.display = 'block';

        const params = new URLSearchParams({ q: keyword, field, limit: 20 });

        fetch(`${searchUrl}?${params.toString()}`, {
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
        })
            .then((res) => res.json())
            .then((json) => {
                resultBody.innerHTML = '';
                const rows = json.data || [];

                if (rows.length === 0) {
                    resultBody.innerHTML = '<tr><td colspan="3" class="no-data">ไม่พบรายการครุภัณฑ์</td></tr>';
                    return;
                }

                rows.forEach((asset) => {
                    const tr = document.createElement('tr');
                    tr.innerHTML = `
                        <td class="code-cell">${escapeHtml(asset.ass_code)}</td>
                        <td>${escapeHtml(asset.asset_name)}</td>
                        <td class="col-act">
                            <button type="button" class="add-item-btn add-search-result-btn">เพิ่ม</button>
                        </td>
                    `;
                    tr.querySelector('.add-search-result-btn').addEventListener('click', () => {
                        addToDraft(asset);
                    });
                    resultBody.appendChild(tr);
                });
            })
            .catch(() => {
                resultBody.innerHTML = '<tr><td colspan="3" class="no-data">เกิดข้อผิดพลาดในการค้นหา</td></tr>';
            });
    }

    if (searchBtn) {
        searchBtn.addEventListener('click', runSearch);
    }
    if (searchInputEl) {
        searchInputEl.addEventListener('keydown', (e) => {
            if (e.key === 'Enter') {
                e.preventDefault();
                runSearch();
            }
        });
    }

    // ── add to draft ──────────────────────────────────────────────────────
    function addToDraft(asset) {
        const id = Number(asset.id);
        if (draftItems.some((item) => item.id === id)) {
            showDisposalValidationAlert(`ครุภัณฑ์ "${asset.ass_code}" อยู่ในรายการแล้ว`);
            return;
        }
        clearDisposalValidationAlert();
        draftItems.push({
            id,
            ass_code:   asset.ass_code   ?? '',
            asset_name: asset.asset_name ?? '',
            ass_price:  asset.ass_price  ?? '-',
        });
        renderDraft();
    }

    // ── save with confirmation ─────────────────────────────────────────────
    const overlay       = document.getElementById('saveDisposalOverlay');
    const cancelBtn     = document.getElementById('cancelSaveDisposalButton');
    const confirmBtn    = document.getElementById('confirmSaveDisposalButton');

    if (!overlay || !cancelBtn || !confirmBtn) return;

    // Clear validation alert on any field change
    document.querySelectorAll('.disposal-create-page input, .disposal-create-page select')
        .forEach((field) => {
            field.addEventListener('input',  clearDisposalValidationAlert);
            field.addEventListener('change', clearDisposalValidationAlert);
        });

    saveButton.addEventListener('click', () => {
        const validation = validateRequiredFields();
        if (!validation.isValid) {
            showDisposalValidationAlert('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน');
            if (validation.firstInvalidField) validation.firstInvalidField.focus();
            return;
        }
        if (draftItems.length === 0) {
            showDisposalValidationAlert('กรุณาเพิ่มรายการครุภัณฑ์อย่างน้อย 1 รายการ');
            return;
        }
        clearDisposalValidationAlert();
        openOverlay(overlay);
    });

    cancelBtn.addEventListener('click', () => closeOverlay(overlay));
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeOverlay(overlay); });

    confirmBtn.addEventListener('click', () => {
        // Re-validate before actual submit
        const validation = validateRequiredFields();
        if (!validation.isValid) {
            closeOverlay(overlay);
            showDisposalValidationAlert('กรุณากรอกข้อมูลที่จำเป็นให้ครบถ้วน');
            return;
        }
        if (draftItems.length === 0) {
            closeOverlay(overlay);
            showDisposalValidationAlert('กรุณาเพิ่มรายการครุภัณฑ์อย่างน้อย 1 รายการ');
            return;
        }

        // Inject hidden inputs for each asset id, then submit
        form.querySelectorAll('input[name="asset_ids[]"]').forEach((el) => el.remove());
        draftItems.forEach((item) => {
            const input = document.createElement('input');
            input.type  = 'hidden';
            input.name  = 'asset_ids[]';
            input.value = String(item.id);
            form.appendChild(input);
        });
        form.submit();
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

function escapeHtml(str) {
    return String(str ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;');
}

document.addEventListener('DOMContentLoaded', () => {
    initializeSidebarGroups();
    preventDisabledMenuReload();
    initializeDisposalCreatePage();
    initializeDisposalDeleteConfirm();
});