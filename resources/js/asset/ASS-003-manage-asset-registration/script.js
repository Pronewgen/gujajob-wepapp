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
    const printBtn        = document.getElementById('forecastPrintBtn');
    const resultEl        = document.getElementById('forecastResult');
    const yearsEl         = document.getElementById('forecastYears');
    const catIdEl         = document.getElementById('forecastCatId');
    const orgIdEl         = document.getElementById('forecastOrgId');

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

    calcBtn?.addEventListener('click', async () => {
        if (!resultEl || !calcBtn) return;

        calcBtn.disabled = true;
        calcBtn.textContent = 'กำลังคำนวณ...';
        if (printBtn) printBtn.disabled = true;
        resultEl.innerHTML = '<p class="forecast-loading-msg">กำลังวิเคราะห์ข้อมูลและพยากรณ์...</p>';

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
        const payload   = {
            forecast_years: parseInt(yearsEl?.value || '1', 10),
            filter_cat_id:  catIdEl?.value ? parseInt(catIdEl.value, 10) : null,
            filter_org_id:  orgIdEl?.value ? parseInt(orgIdEl.value, 10) : null,
        };

        try {
            const res  = await fetch('/asset/ASS-003-manage-asset-registration/ai-forecast', {
                method:  'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'Accept':           'application/json',
                    'X-CSRF-TOKEN':     csrfToken,
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify(payload),
            });

            const json = await res.json();

            if (!res.ok || !json.success) {
                const msg = json.message || 'เกิดข้อผิดพลาด กรุณาลองอีกครั้ง';
                resultEl.innerHTML = `<p class="forecast-error-msg">${msg}</p>`;
                return;
            }

            renderAiForecast(json, resultEl);
            if (printBtn && json.total_assets >= 0) {
                printBtn.disabled = false;
            }
        } catch {
            resultEl.innerHTML = '<p class="forecast-error-msg">ไม่สามารถเชื่อมต่อบริการได้ กรุณาลองอีกครั้ง</p>';
        } finally {
            calcBtn.disabled    = false;
            calcBtn.textContent = 'คำนวณ';
        }
    });

    printBtn?.addEventListener('click', () => {
        window.open('/asset/ASS-003-manage-asset-registration/forecast-print', '_blank');
    });
}

function renderAiForecast(json, container) {
    if (json.total_assets === 0) {
        container.innerHTML = '<p class="forecast-empty-msg">ไม่พบครุภัณฑ์ที่คาดว่าจะถึงกำหนดทดแทนในช่วงเวลาที่เลือก</p>';
        return;
    }

    const fmt      = (n) => Number(n).toLocaleString('th-TH', { minimumFractionDigits: 2 });
    const totalBud = fmt(json.total_forecast_budget);

    // ── Summary ───────────────────────────────────────────────────────────────
    let html = `
    <div class="forecast-ai-summary">
        <div class="ai-summary-row">
            <div class="ai-summary-item">
                <span>ระยะเวลาพยากรณ์</span>
                <strong>${json.forecast_years} ปี</strong>
            </div>
            <div class="ai-summary-item">
                <span>จำนวนครุภัณฑ์ที่คาดว่าจะทดแทน</span>
                <strong>${json.total_assets} รายการ</strong>
            </div>
            <div class="ai-summary-item highlight">
                <span>งบประมาณรวมที่คาดการณ์</span>
                <strong>${totalBud} บาท</strong>
            </div>
        </div>
    </div>`;

    // ── Year-by-year table ────────────────────────────────────────────────────
    html += `<div class="forecast-year-table-wrap">
        <div class="forecast-section-title">ผลการพยากรณ์รายปี</div>
        <table class="forecast-year-table">
            <thead><tr>
                <th>ปี</th>
                <th style="text-align:center;">จำนวนครุภัณฑ์</th>
                <th style="text-align:right;">งบประมาณที่คาดการณ์ (บาท)</th>
            </tr></thead>
            <tbody>`;

    for (const y of json.years) {
        html += `<tr>
            <td>พ.ศ. ${y.year + 543} <span class="year-ce">(ค.ศ. ${y.year})</span></td>
            <td style="text-align:center;">${y.asset_count}</td>
            <td style="text-align:right;">${fmt(y.forecast_budget)}</td>
        </tr>`;
    }

    html += `</tbody></table></div>`;

    // ── Asset details ─────────────────────────────────────────────────────────
    if (json.assets && json.assets.length > 0) {
        html += `<div class="forecast-asset-detail-wrap">
            <div class="forecast-section-title">รายละเอียดครุภัณฑ์</div>
            <div class="forecast-table-card">
                <table class="forecast-table">
                    <thead><tr>
                        <th>รหัสครุภัณฑ์</th>
                        <th>ชื่อครุภัณฑ์</th>
                        <th>หมวดครุภัณฑ์</th>
                        <th>หน่วยงาน</th>
                        <th>วันที่ตรวจรับ</th>
                        <th style="text-align:center;">ปีที่คาดว่าจะทดแทน</th>
                        <th style="text-align:right;">มูลค่าเดิม</th>
                        <th style="text-align:right;">มูลค่าทดแทน (AI)</th>
                    </tr></thead>
                    <tbody>`;

        for (const a of json.assets) {
            const yearTh   = (a.forecast_year + 543);
            const curVal   = a.current_value !== null && a.current_value !== undefined
                ? fmt(a.current_value) : '-';
            const predCost = fmt(a.predicted_replacement_cost);

            html += `<tr>
                <td><a class="ass-code-link asset-code" href="/asset/ASS-003-manage-asset-registration?search_by=code&keyword=${encodeURIComponent(a.asset_code ?? '')}">${a.asset_code ?? '-'}</a></td>
                <td>${a.asset_name ?? '-'}</td>
                <td>${a.category_name ?? '-'}</td>
                <td>${a.organization_name ?? '-'}</td>
                <td>${a.acceptance_date ?? '-'}</td>
                <td style="text-align:center;">พ.ศ. ${yearTh}</td>
                <td style="text-align:right;">${curVal}</td>
                <td style="text-align:right;" class="ai-cost-cell">${predCost}</td>
            </tr>`;
        }

        html += `</tbody></table></div></div>`;
    }

    // ── Model badge ───────────────────────────────────────────────────────────
    const m = json.model;
    if (m) {
        let modelInfo = `โมเดล: ${m.name ?? 'AI'} · ข้อมูลฝึกสอน: ${m.training_records ?? '-'} รายการ`;
        if (m.mae !== null && m.mae !== undefined) modelInfo += ` · MAE: ${Number(m.mae).toLocaleString('th-TH')}`;
        html += `<div class="forecast-model-badge">${modelInfo}</div>`;
    }

    container.innerHTML = html;
}

document.addEventListener('DOMContentLoaded', () => {
    initializeSidebarGroups();
    preventDisabledMenuReload();
    initSearchableSelects();
    initializeAssetRegistrationSearch();
    initializeDeleteModal();
    initializeForecastToggle();
});