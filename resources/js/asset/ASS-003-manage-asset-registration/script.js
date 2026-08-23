import { SearchAutocomplete } from '../../components/search-autocomplete.js';
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

function initializeAssetRegistrationSearch() {
    const searchTypeEl  = document.getElementById('assetSearchType');
    const searchInputEl = document.getElementById('assetSearchInput');

    if (!searchTypeEl || !searchInputEl) {
        return;
    }

    const ac = new SearchAutocomplete({
        inputEl:      searchInputEl,
        searchTypeEl: searchTypeEl,
        endpoint:     '/search/suggestions',
        extraParams:  { entity: 'asset' },
        minChars:     1,
        debounceMs:   300,
        maxResults:   15,
        onSelect: (item) => {
            searchInputEl.value = item.code;
            searchInputEl.closest('form')?.submit();
        },
    });

    searchTypeEl.addEventListener('change', () => {
        searchInputEl.value = '';
        ac.clear?.();
    });
}

function initializeDeleteModal() {
    const overlay       = document.getElementById('deleteAssetOverlay');
    const cancelButton  = document.getElementById('cancelDeleteAssetButton');
    const confirmButton = document.getElementById('confirmDeleteAssetButton');
    const deleteForm    = document.getElementById('deleteAssetForm');
    const messageEl     = document.getElementById('deleteAssetMessage');

    if (!overlay || !deleteForm) {
        return;
    }

    document.querySelectorAll('[data-delete-url]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const code = btn.dataset.deleteCode || '';
            deleteForm.action = btn.dataset.deleteUrl;
            if (messageEl && code) {
                messageEl.innerHTML = `คุณแน่ใจหรือไม่ว่าต้องการลบครุภัณฑ์ : '${code}'<br>การดำเนินการนี้ไม่สามารถเรียกคืนได้`;
            }
            overlay.classList.add('is-visible');
            overlay.setAttribute('aria-hidden', 'false');
        });
    });

    function closeModal() {
        overlay.classList.remove('is-visible');
        overlay.setAttribute('aria-hidden', 'true');
    }

    cancelButton?.addEventListener('click', closeModal);
    confirmButton?.addEventListener('click', () => {
        deleteForm.submit();
    });

    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            closeModal();
        }
    });
}

function initializeForecastToggle() {
    const toggleBtn       = document.getElementById('forecastToggleBtn');
    const closeBtn        = document.getElementById('forecastCloseBtn');
    const forecastSection = document.getElementById('forecastSection');
    const calcBtn         = document.getElementById('forecastCalcBtn');
    const resultEl        = document.getElementById('forecastResult');
    const yearsEl         = document.getElementById('forecastYears');
    // forecastCatId and forecastOrgId are now hidden inputs managed by x-searchable-select
    const catIdEl  = document.getElementById('forecastCatId');
    const orgIdEl  = document.getElementById('forecastOrgId');

    if (!forecastSection) return;

    function showForecast() {
        forecastSection.style.display = '';
        forecastSection.setAttribute('aria-hidden', 'false');
    }

    function hideForecast() {
        forecastSection.style.display = 'none';
        forecastSection.setAttribute('aria-hidden', 'true');
    }

    toggleBtn?.addEventListener('click', () => {
        if (forecastSection.style.display === 'none' || forecastSection.style.display === '') {
            showForecast();
        } else {
            hideForecast();
        }
    });

    closeBtn?.addEventListener('click', hideForecast);

    let lastForecastData = [];

    calcBtn?.addEventListener('click', async () => {
        if (!resultEl) return;
        resultEl.innerHTML = '<p style="color:#6d28d9;font-size:12px;font-weight:700;">กำลังคำนวณ...</p>';

        const years = yearsEl?.value || '1';
        const catId = catIdEl?.value || '';
        const orgId = orgIdEl?.value || '';
        const url   = new URL('/asset/ASS-003-manage-asset-registration/forecast-data', window.location.origin);
        url.searchParams.set('years_ahead', years);
        if (catId) url.searchParams.set('filter_cat_id', catId);
        if (orgId) url.searchParams.set('filter_org_id', orgId);

        try {
            const res  = await fetch(url.toString(), { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const json = await res.json();
            lastForecastData = json.data ?? [];
            renderForecast(json, resultEl, lastForecastData);
        } catch {
            resultEl.innerHTML = '<p style="color:#dc2626;font-size:12px;font-weight:700;">เกิดข้อผิดพลาด กรุณาลองอีกครั้ง</p>';
        }
    });

    document.getElementById('forecastPrintBtn')?.addEventListener('click', () => window.print());
}

function renderForecast(res, container, allRows) {
    const rows = allRows ?? res.data ?? [];
    if (!rows.length) {
        container.innerHTML = '<p style="color:#6d28d9;font-size:12px;font-weight:700;">ไม่พบครุภัณฑ์ที่คาดว่าจะหมดอายุในช่วงเวลาที่เลือก</p>';
        return;
    }

    const totalBudget = (res.summary?.total_budget ?? 0).toLocaleString('th-TH', { minimumFractionDigits: 2 });
    const totalCount  = res.summary?.total_count ?? 0;

    const searchBarHtml = `<div class="forecast-search-bar">
        <div class="forecast-search-field"><label>ค้นหาจาก</label><select id="forecastTableSearchType"><option value="code">รหัสครุภัณฑ์</option><option value="name">ชื่อครุภัณฑ์</option><option value="org">หน่วยงาน</option></select></div>
        <div class="forecast-search-field"><label>คำค้นหา</label><input id="forecastTableSearchInput" type="search" autocomplete="off" placeholder="ค้นหา..."></div>
        <button class="forecast-search-btn" type="button" id="forecastTableSearchBtn">ค้นหา</button>
    </div>`;

    const summaryHtml = `<div class="forecast-summary">
        <div class="summary-box"><span>ครุภัณฑ์ที่คาดจะหมดอายุ</span><strong>${totalCount} รายการ</strong></div>
        <div class="summary-box"><span>งบประมาณรวม</span><strong>${totalBudget} บาท</strong></div>
    </div>`;

    container.innerHTML = summaryHtml + `<div class="forecast-table-card">
        ${searchBarHtml}
        <div id="forecastTableBody"></div>
    </div>`;

    renderForecastRows(rows);

    document.getElementById('forecastTableSearchBtn')?.addEventListener('click', () => {
        const type    = document.getElementById('forecastTableSearchType')?.value || 'code';
        const keyword = (document.getElementById('forecastTableSearchInput')?.value || '').trim().toUpperCase();
        if (!keyword) {
            renderForecastRows(allRows);
            return;
        }
        const keyMap = { code: 'code', name: 'name', org: 'org' };
        const key    = keyMap[type] || 'code';
        const filtered = allRows.filter((r) => String(r[key] ?? '').toUpperCase().includes(keyword));
        renderForecastRows(filtered);
    });
}

function renderForecastRows(rows) {
    const tbody = document.getElementById('forecastTableBody');
    if (!tbody) return;

    if (!rows.length) {
        tbody.innerHTML = '<table class="forecast-table"><tbody><tr><td class="no-data" colspan="8">ไม่พบข้อมูล</td></tr></tbody></table>';
        return;
    }

    let html = `<table class="forecast-table">
        <thead><tr>
            <th>รหัสครุภัณฑ์</th>
            <th>ชื่อครุภัณฑ์</th>
            <th>หน่วยงาน</th>
            <th>อายุ (ปี)</th>
            <th>วันที่ตรวจรับ</th>
            <th>วันหมดอายุ</th>
            <th>หมดใน</th>
            <th style="text-align:right;">ราคาทดแทน</th>
        </tr></thead>
        <tbody>`;

    for (const r of rows) {
        const yrs     = (r.years_remaining ?? 0);
        const yrsText = yrs <= 0 ? 'หมดอายุแล้ว' : `${yrs.toFixed(1)} ปี`;
        const price   = (r.price ?? 0).toLocaleString('th-TH', { minimumFractionDigits: 2 });
        html += `<tr>
            <td class="asset-code">${r.code ?? '-'}</td>
            <td>${r.name ?? '-'}</td>
            <td>${r.org ?? '-'}</td>
            <td style="text-align:center;">${r.lifetime ?? '-'}</td>
            <td>${r.inspect_date ?? '-'}</td>
            <td class="expire-date">${r.end_date ?? '-'}</td>
            <td style="text-align:center;">${yrsText}</td>
            <td style="text-align:right;">${price}</td>
        </tr>`;
    }

    html += '</tbody></table>';
    tbody.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', () => {
    initializeSidebarGroups();
    preventDisabledMenuReload();
    initSearchableSelects();
    initializeAssetRegistrationSearch();
    initializeDeleteModal();
    initializeForecastToggle();
});