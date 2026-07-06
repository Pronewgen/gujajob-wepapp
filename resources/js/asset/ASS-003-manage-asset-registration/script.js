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

function initializeAssetRegistrationSearch() {
    const searchType = document.getElementById('assetSearchType');
    const searchInput = document.getElementById('assetSearchInput');
    const statusFilter = document.getElementById('assetStatusFilter');
    const searchButton = document.getElementById('assetSearchButton');
    const tableBody = document.getElementById('assetRegistrationTableBody');
    const resultText = document.getElementById('assetRegistrationResultText');

    if (!searchType || !searchInput || !statusFilter || !searchButton || !tableBody || !resultText) {
        return;
    }

    const originalRows = Array.from(tableBody.querySelectorAll('tr'));

    function updatePlaceholder() {
        const placeholders = {
            asset_code: 'กรอกรหัสครุภัณฑ์',
            asset_name: 'กรอกชื่อครุภัณฑ์',
            department: 'กรอกชื่อหน่วยงาน',
        };

        searchInput.placeholder = placeholders[searchType.value] || 'กรอกรหัสครุภัณฑ์';
    }

    function getTargetText(row) {
        const keyMap = {
            asset_code: 'assetCode',
            asset_name: 'assetName',
            department: 'department',
        };

        const key = keyMap[searchType.value] || 'assetCode';

        return String(row.dataset[key] || '').toLowerCase();
    }

    function updateResultText(visibleCount) {
        if (visibleCount === 0) {
            resultText.textContent = 'ไม่พบทะเบียนครุภัณฑ์';
            return;
        }

        resultText.textContent = `แสดง ${visibleCount} รายการ จากทั้งหมด 100 รายการ`;
    }

    function removeNoDataRow() {
        const oldNoDataRow = tableBody.querySelector('.no-data-row');

        if (oldNoDataRow) {
            oldNoDataRow.remove();
        }
    }

    function searchRecords() {
        const keyword = searchInput.value.trim().toLowerCase();
        const selectedStatus = statusFilter.value;
        let visibleCount = 0;

        removeNoDataRow();

        originalRows.forEach((row) => {
            const matchKeyword = getTargetText(row).includes(keyword);
            const matchStatus = selectedStatus === 'all' || row.dataset.status === selectedStatus;
            const isVisible = matchKeyword && matchStatus;

            row.style.display = isVisible ? '' : 'none';

            if (isVisible) {
                visibleCount += 1;
            }
        });

        if (visibleCount === 0) {
            const noDataRow = document.createElement('tr');
            noDataRow.className = 'no-data-row';
            noDataRow.innerHTML = '<td class="no-data" colspan="8">ไม่พบข้อมูลที่ค้นหา</td>';
            tableBody.appendChild(noDataRow);
        }

        updateResultText(visibleCount);
    }

    searchType.addEventListener('change', () => {
        searchInput.value = '';
        updatePlaceholder();
        searchRecords();
    });

    statusFilter.addEventListener('change', searchRecords);
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

    const createButton = document.getElementById('createAssetRegistrationButton');

    if (createButton) {
        createButton.addEventListener('click', () => {
            const createUrl = createButton.dataset.createUrl;

            if (createUrl) {
                window.location.href = createUrl;
                return;
            }

            alert('ไม่พบเส้นทางหน้าบันทึกทะเบียนใหม่');
        });
    }

    updatePlaceholder();
    searchRecords();
}

function initializeForecastPanel() {
    const openButton = document.getElementById('forecastOpenButton');
    const panel = document.getElementById('forecastPanel');
    const calculateButton = document.getElementById('calculateForecastButton');
    const printButton = document.getElementById('printForecastButton');

    if (!openButton || !panel) {
        return;
    }

    openButton.addEventListener('click', () => {
        panel.classList.toggle('is-hidden');

        if (!panel.classList.contains('is-hidden')) {
            panel.scrollIntoView({
                behavior: 'smooth',
                block: 'start',
            });
        }
    });

    if (calculateButton) {
        calculateButton.addEventListener('click', () => {
            alert('คำนวณพยากรณ์งบประมาณเรียบร้อย');
        });
    }

    if (printButton) {
        printButton.addEventListener('click', () => {
            alert('ปุ่มนี้เตรียมไว้สำหรับจัดพิมพ์รายงานพยากรณ์');
        });
    }
}

function initializeForecastSearch() {
    const searchType = document.getElementById('forecastSearchType');
    const searchInput = document.getElementById('forecastSearchInput');
    const searchButton = document.getElementById('forecastSearchButton');
    const tableBody = document.getElementById('forecastTableBody');

    if (!searchType || !searchInput || !searchButton || !tableBody) {
        return;
    }

    const originalRows = Array.from(tableBody.querySelectorAll('tr'));

    function updatePlaceholder() {
        const placeholders = {
            category: 'กรอกหมวดครุภัณฑ์',
            name: 'กรอกชื่อครุภัณฑ์',
            department: 'กรอกชื่อหน่วยงาน',
        };

        searchInput.placeholder = placeholders[searchType.value] || 'กรอกหมวดครุภัณฑ์';
    }

    function getForecastTarget(row) {
        const keyMap = {
            category: 'category',
            name: 'name',
            department: 'department',
        };

        const key = keyMap[searchType.value] || 'category';

        return String(row.dataset[key] || '').toLowerCase();
    }

    function removeNoDataRow() {
        const oldNoDataRow = tableBody.querySelector('.no-data-row');

        if (oldNoDataRow) {
            oldNoDataRow.remove();
        }
    }

    function searchForecast() {
        const keyword = searchInput.value.trim().toLowerCase();
        let visibleCount = 0;

        removeNoDataRow();

        originalRows.forEach((row) => {
            const target = getForecastTarget(row);
            const isVisible = target.includes(keyword);

            row.style.display = isVisible ? '' : 'none';

            if (isVisible) {
                visibleCount += 1;
            }
        });

        if (visibleCount === 0) {
            const noDataRow = document.createElement('tr');
            noDataRow.className = 'no-data-row';
            noDataRow.innerHTML = '<td class="no-data" colspan="8">ไม่พบข้อมูลที่ค้นหา</td>';
            tableBody.appendChild(noDataRow);
        }
    }

    searchType.addEventListener('change', () => {
        searchInput.value = '';
        updatePlaceholder();
        searchForecast();
    });

    searchButton.addEventListener('click', searchForecast);

    searchInput.addEventListener('keydown', (event) => {
        if (event.key === 'Enter') {
            searchForecast();
        }
    });

    searchInput.addEventListener('input', () => {
        if (searchInput.value.trim() === '') {
            searchForecast();
        }
    });

    updatePlaceholder();
}

initializeSidebarGroups();
preventDisabledMenuReload();
initializeAssetRegistrationSearch();
initializeForecastPanel();
initializeForecastSearch();
