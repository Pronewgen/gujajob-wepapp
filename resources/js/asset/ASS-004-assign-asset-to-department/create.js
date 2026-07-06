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

function initializeAssignmentCreate() {
    const sourceTableBody = document.getElementById('sourceAssetTableBody');
    const selectedTableBody = document.getElementById('selectedAssetTableBody');
    const selectedCountText = document.getElementById('selectedCountText');
    const saveButton = document.getElementById('saveAssignmentButton');
    const saveOverlay = document.getElementById('saveAssignmentOverlay');
    const cancelSaveButton = document.getElementById('cancelSaveAssignmentButton');
    const confirmSaveButton = document.getElementById('confirmSaveAssignmentButton');
    const confirmMessage = document.getElementById('saveAssignmentConfirmMessage');
    const searchType = document.getElementById('sourceSearchType');
    const searchKeyword = document.getElementById('sourceSearchKeyword');
    const searchButton = document.getElementById('sourceSearchButton');

    if (!sourceTableBody || !selectedTableBody || !selectedCountText || !saveButton) {
        return;
    }

    const sourceRows = Array.from(sourceTableBody.querySelectorAll('.js-source-row'));
    const saveLabel = saveButton.dataset.saveLabel || 'บันทึกการจัดสรร';
    const confirmMessageText = saveButton.dataset.confirmMessage || '';

    function getCheckedAssets() {
        return sourceRows
            .filter((row) => {
                const checkbox = row.querySelector('.js-source-checkbox');

                return checkbox && checkbox.checked;
            })
            .map((row) => ({
                code: row.dataset.assetCode || '',
                name: row.dataset.assetName || '',
                category: row.dataset.category || '',
                value: row.dataset.value || '',
            }));
    }

    function updateSaveButton(count) {
        saveButton.textContent = `${saveLabel} (${count} รายการ)`;
    }

    function renderSelectedAssets() {
        const checkedAssets = sourceRows
            .filter((row) => {
                const checkbox = row.querySelector('.js-source-checkbox');
                return checkbox && checkbox.checked;
            })
            .map((row) => ({
                code: row.dataset.assetCode || '',
                name: row.dataset.assetName || '',
                category: row.dataset.category || '',
                value: row.dataset.value || '',
            }));

        selectedTableBody.innerHTML = '';

        if (checkedAssets.length === 0) {
            selectedTableBody.innerHTML = '<tr class="no-data-row"><td class="no-data" colspan="6">ยังไม่มีรายการที่เลือกจัดสรร</td></tr>';
            selectedCountText.textContent = 'เลือกแล้ว 0 รายการ';
            updateSaveButton(0);
            return;
        }

        checkedAssets.forEach((asset, index) => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="center">${index + 1}</td>
                <td class="asset-code">${asset.code}</td>
                <td>${asset.name}</td>
                <td>${asset.category}</td>
                <td class="number">${asset.value}</td>
                <td class="center">
                    <button class="remove-btn" type="button" data-asset-code="${asset.code}">ลบ</button>
                </td>
            `;

            selectedTableBody.appendChild(row);
        });

        selectedCountText.textContent = `เลือกแล้ว ${checkedAssets.length} รายการ`;
        updateSaveButton(checkedAssets.length);
    }

    function applySourceFilter() {
        const filterKey = searchType ? searchType.value : 'asset_code';
        const keyword = (searchKeyword ? searchKeyword.value : '').trim().toLowerCase();

        sourceRows.forEach((row) => {
            const valueMap = {
                asset_code: String(row.dataset.assetCode || '').toLowerCase(),
                asset_name: String(row.dataset.assetName || '').toLowerCase(),
                category: String(row.dataset.category || '').toLowerCase(),
            };

            const targetValue = valueMap[filterKey] || valueMap.asset_code;
            const isVisible = keyword === '' || targetValue.includes(keyword);

            row.style.display = isVisible ? '' : 'none';
        });
    }

    sourceRows.forEach((row) => {
        const checkbox = row.querySelector('.js-source-checkbox');

        if (!checkbox) {
            return;
        }

        checkbox.addEventListener('change', () => {
            row.classList.toggle('is-selected', checkbox.checked);
            renderSelectedAssets();
        });
    });

    selectedTableBody.addEventListener('click', (event) => {
        const button = event.target.closest('.remove-btn');

        if (!button) {
            return;
        }

        const code = button.dataset.assetCode;
        const sourceRow = sourceRows.find((row) => row.dataset.assetCode === code);

        if (!sourceRow) {
            return;
        }

        const checkbox = sourceRow.querySelector('.js-source-checkbox');

        if (checkbox) {
            checkbox.checked = false;
            sourceRow.classList.remove('is-selected');
            renderSelectedAssets();
        }
    });

    if (searchButton) {
        searchButton.addEventListener('click', applySourceFilter);
    }

    if (searchType) {
        searchType.addEventListener('change', applySourceFilter);
    }

    if (searchKeyword) {
        searchKeyword.addEventListener('keydown', (event) => {
            if (event.key === 'Enter') {
                applySourceFilter();
            }
        });
    }

    saveButton.addEventListener('click', () => {
        const selectedAssets = getCheckedAssets();

        if (selectedAssets.length === 0) {
            alert('กรุณาเลือกรายการครุภัณฑ์ก่อนบันทึกการจัดสรร');
            return;
        }

        if (confirmMessage) {
            if (confirmMessageText) {
                confirmMessage.textContent = confirmMessageText;
            } else {
                confirmMessage.textContent = `คุณแน่ใจหรือไม่ว่าต้องการบันทึกการจัดสรรนี้ (${selectedAssets.length} รายการ)`;
            }
        }

        openOverlay(saveOverlay);
    });

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
            const selectedAssets = getCheckedAssets();

            if (selectedAssets.length === 0) {
                closeOverlay(saveOverlay);
                alert('กรุณาเลือกรายการครุภัณฑ์ก่อนบันทึกการจัดสรร');
                return;
            }

            closeOverlay(saveOverlay);

            const redirectUrl = saveButton.dataset.redirectUrl;

            if (redirectUrl) {
                window.location.href = redirectUrl;
            }
        });
    }

    renderSelectedAssets();
}

document.addEventListener('DOMContentLoaded', () => {
    initializeSidebarGroups();
    preventDisabledMenuReload();
    initializeAssignmentCreate();
});

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    document.querySelectorAll('.confirm-overlay.is-visible').forEach((overlay) => {
        closeOverlay(overlay);
    });
});
