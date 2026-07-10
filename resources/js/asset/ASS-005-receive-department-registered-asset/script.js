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

function initializeAssetReceivingSearch() {
    const searchType = document.getElementById('searchType');
    const searchInput = document.getElementById('searchInput');
    const categoryFilter = document.getElementById('categoryFilter');
    const searchButton = document.getElementById('searchButton');
    const tableBody = document.getElementById('receivingTableBody');
    const resultText = document.getElementById('resultText');

    if (!searchType || !searchInput || !categoryFilter || !searchButton || !tableBody || !resultText) {
        return;
    }

    const originalRows = Array.from(tableBody.querySelectorAll('tr'));

    function removeNoDataRow() {
        const oldNoDataRow = tableBody.querySelector('.no-data-row');

        if (oldNoDataRow) {
            oldNoDataRow.remove();
        }
    }

    function updateResult(visibleCount, pendingCount) {
        if (visibleCount === 0) {
            resultText.textContent = 'ไม่พบข้อมูลรับครุภัณฑ์ลงทะเบียนหน่วยงาน';
            return;
        }

        resultText.textContent = `แสดง ${visibleCount} รายการ - รอรับ ${pendingCount} รายการ`;
    }

    function filterRecords() {
        const selectedType = searchType.value;
        const keyword = searchInput.value.trim().toLowerCase();
        const selectedCategory = categoryFilter.value;
        let visibleCount = 0;
        let pendingCount = 0;

        removeNoDataRow();

        originalRows.forEach((row) => {
            const assetCode = row.dataset.assetCode || '';
            const assetName = row.dataset.assetName || '';
            const category = row.dataset.category || '';
            const searchTarget = selectedType === 'asset_name' ? assetName : assetCode;
            const keywordMatched = keyword === '' || searchTarget.includes(keyword);
            const categoryMatched = selectedCategory === 'all' || selectedCategory === category;
            const isVisible = keywordMatched && categoryMatched;

            row.style.display = isVisible ? '' : 'none';

            if (isVisible) {
                visibleCount += 1;

                const dateCell = row.querySelector('.date-text');
                const isPending = dateCell && dateCell.classList.contains('pending');

                if (isPending) {
                    pendingCount += 1;
                }
            }
        });

        if (visibleCount === 0) {
            const noDataRow = document.createElement('tr');
            noDataRow.className = 'no-data-row';
            noDataRow.innerHTML = '<td class="no-data" colspan="6">ไม่พบข้อมูลที่ค้นหา</td>';
            tableBody.appendChild(noDataRow);
        }

        updateResult(visibleCount, pendingCount);
    }

    searchButton.addEventListener('click', filterRecords);
    searchInput.addEventListener('input', filterRecords);
    searchType.addEventListener('change', filterRecords);
    categoryFilter.addEventListener('change', filterRecords);

    filterRecords();
}

function initializeReceiveConfirmation() {
    const openButton = document.getElementById('openReceiveConfirmButton');
    const overlay = document.getElementById('receiveConfirmOverlay');
    const cancelButton = document.getElementById('cancelReceiveConfirmButton');
    const confirmButton = document.getElementById('confirmReceiveButton');

    if (!openButton || !overlay || !cancelButton || !confirmButton) {
        return;
    }

    const requiredFields = Array.from(document.querySelectorAll('[data-required="true"]'));

    function clearInlineError() {
        const oldError = document.querySelector('.receive-inline-validation-error');

        if (oldError) {
            oldError.remove();
        }
    }

    function showInlineError(message) {
        clearInlineError();

        const form = document.querySelector('.receive-form');
        const container = document.querySelector('.receiving-confirm-page .receive-card') || document.querySelector('.receiving-confirm-page');
        const error = document.createElement('div');

        error.className = 'receive-inline-validation-error';
        error.setAttribute('role', 'alert');
        error.textContent = message;

        if (form) {
            form.insertAdjacentElement('beforebegin', error);
            return;
        }

        if (container) {
            container.prepend(error);
        }
    }

    function isFieldFilled(field) {
        return field && String(field.value || '').trim() !== '';
    }

    function hasRequiredValues() {
        return requiredFields.every(isFieldFilled);
    }

    function openModal() {
        if (!hasRequiredValues()) {
            showInlineError('กรุณากรอกข้อมูลที่มีเครื่องหมาย * ให้ครบก่อนยืนยันรับครุภัณฑ์');
            return;
        }

        clearInlineError();

        overlay.classList.add('is-open');
        overlay.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        overlay.classList.remove('is-open');
        overlay.setAttribute('aria-hidden', 'true');
    }

    requiredFields.forEach((field) => {
        field.addEventListener('input', clearInlineError);
        field.addEventListener('change', clearInlineError);
    });

    openButton.addEventListener('click', openModal);
    cancelButton.addEventListener('click', closeModal);

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            closeModal();
        }
    });

    confirmButton.addEventListener('click', () => {
        const redirectUrl = openButton.dataset.redirectUrl;

        if (redirectUrl) {
            window.location.href = redirectUrl;
            return;
        }

        closeModal();
    });
}

function initializeReceiveDatePicker() {
    const receiveDate = document.getElementById('receiveDate');

    if (!receiveDate) {
        return;
    }

    // Allow selection from the native date picker only.
    receiveDate.addEventListener('keydown', (event) => {
        event.preventDefault();
    });

    receiveDate.addEventListener('paste', (event) => {
        event.preventDefault();
    });

    const openNativePicker = () => {
        if (typeof receiveDate.showPicker === 'function') {
            receiveDate.showPicker();
        }
    };

    receiveDate.addEventListener('focus', openNativePicker);
    receiveDate.addEventListener('click', openNativePicker);
}

function initializeReceiveDetailEdit() {
    const openButton = document.getElementById('openReceiveEditOverlay');
    const overlay = document.getElementById('saveReceiveDetailOverlay');
    const cancelButton = document.getElementById('cancelReceiveDetailButton');
    const confirmButton = document.getElementById('confirmReceiveDetailButton');

    if (!openButton || !overlay || !cancelButton || !confirmButton) {
        return;
    }

    function openOverlay() {
        overlay.classList.add('is-visible');
        overlay.setAttribute('aria-hidden', 'false');
    }

    function closeOverlay() {
        overlay.classList.remove('is-visible');
        overlay.setAttribute('aria-hidden', 'true');
    }

    openButton.addEventListener('click', (event) => {
        event.preventDefault();
        openOverlay();
    });
    cancelButton.addEventListener('click', closeOverlay);

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            closeOverlay();
        }
    });

    confirmButton.addEventListener('click', () => {
        const redirectUrl = openButton.dataset.redirectUrl;

        if (redirectUrl) {
            window.location.href = redirectUrl;
            return;
        }

        closeOverlay();
    });

    openButton.onclick = (event) => {
        event.preventDefault();
        openOverlay();
    };
}

document.addEventListener('DOMContentLoaded', () => {
    initializeSidebarGroups();
    preventDisabledMenuReload();
    initializeAssetReceivingSearch();
    initializeReceiveDatePicker();
    initializeReceiveConfirmation();
    initializeReceiveDetailEdit();
});
