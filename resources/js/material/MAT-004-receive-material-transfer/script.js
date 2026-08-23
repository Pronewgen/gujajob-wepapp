import { SearchAutocomplete } from '../../components/search-autocomplete.js';

/**
 * MAT-004 รับโอนวัสดุ — JavaScript v2
 * Real-time calculation + Modal confirmations
 */

document.addEventListener('DOMContentLoaded', () => {

    // ── Real-time "คงเหลือหลังรับโอน" calculation ───────────────────
    function updateBalanceAfter(row) {
        const currentBalance = Number(row.dataset.currentBalance ?? '');
        const increaseEl     = row.querySelector('[data-transfer-increase]');
        const resultCell     = row.querySelector('[data-balance-after-transfer]');

        if (!increaseEl || !resultCell) return;

        const increase = Number(increaseEl.value ?? increaseEl.textContent) || 0;

        if (isNaN(currentBalance) || row.dataset.currentBalance === '') {
            resultCell.textContent = '–';
            return;
        }

        resultCell.textContent = (currentBalance + increase).toLocaleString('th-TH');
    }

    document.querySelectorAll('[data-transfer-row]').forEach((row) => {
        updateBalanceAfter(row);
        const inp = row.querySelector('[data-transfer-increase]');
        inp?.addEventListener('input', () => updateBalanceAfter(row));
    });

    // ── Modal helpers ─────────────────────────────────────────────
    function openModal(overlay) {
        if (!overlay) return;
        overlay.classList.add('is-visible');
        overlay.setAttribute('aria-hidden', 'false');
    }

    function closeModal(overlay) {
        if (!overlay) return;
        overlay.classList.remove('is-visible');
        overlay.setAttribute('aria-hidden', 'true');
    }

    // Close on backdrop click
    document.querySelectorAll('.mat004-overlay').forEach((overlay) => {
        overlay.addEventListener('click', (e) => {
            if (e.target === overlay) closeModal(overlay);
        });
    });

    // Close on Escape
    document.addEventListener('keydown', (e) => {
        if (e.key !== 'Escape') return;
        document.querySelectorAll('.mat004-overlay.is-visible').forEach(closeModal);
    });

    // ── Show page: transfer confirm popup ────────────────────────
    const confirmOverlay   = document.getElementById('transferConfirmOverlay');
    const confirmBtn       = document.getElementById('transferConfirmBtn');
    const confirmModalBtn  = document.getElementById('transferConfirmModalBtn');
    const confirmCancelBtn = document.getElementById('transferConfirmCancelBtn');
    const confirmForm      = document.getElementById('transferConfirmForm');

    const showDateInput    = document.getElementById('showInspDate');
    const showPersonInput  = document.getElementById('showInspPerson');
    const showRemarkInput  = document.getElementById('showInspRemark');

    function syncHiddenFields(form) {
        if (!form) return;
        const set = (name, val) => {
            const el = form.querySelector(`[name="${name}"]`);
            if (el) el.value = val ?? '';
        };
        set('mat_insp_date',   showDateInput?.value   ?? '');
        set('mat_insp_person', showPersonInput?.value ?? '');
        set('mat_insp_remark', showRemarkInput?.value ?? '');
    }

    function validateHeader() {
        if (!showDateInput?.value?.trim()) {
            showDateInput?.focus();
            alert('กรุณาระบุวันที่รับโอน');
            return false;
        }
        if (!showPersonInput?.value?.trim()) {
            showPersonInput?.focus();
            alert('กรุณากรอกชื่อผู้รับโอนวัสดุ');
            return false;
        }
        return true;
    }

    confirmBtn?.addEventListener('click', () => {
        if (!validateHeader()) return;
        syncHiddenFields(confirmForm);
        openModal(confirmOverlay);
    });

    confirmCancelBtn?.addEventListener('click', () => closeModal(confirmOverlay));

    confirmModalBtn?.addEventListener('click', () => {
        confirmModalBtn.disabled = true;
        confirmModalBtn.textContent = 'กำลังบันทึก...';
        confirmForm?.submit();
    });

    // ── Show page: "แก้ไขข้อมูลรับโอน" is now an <a> link – no JS needed

    // ── Edit page: save popup ─────────────────────────────────────
    const editSaveOverlay = document.getElementById('editSaveOverlay');
    const saveBtn         = document.getElementById('transferSaveBtn');
    const saveModalBtn    = document.getElementById('editSaveModalBtn');
    const saveCancelBtn   = document.getElementById('editSaveCancelBtn');
    const editPageForm    = document.getElementById('transferEditPageForm');

    saveBtn?.addEventListener('click', () => openModal(editSaveOverlay));
    saveCancelBtn?.addEventListener('click', () => closeModal(editSaveOverlay));

    saveModalBtn?.addEventListener('click', () => {
        saveModalBtn.disabled = true;
        saveModalBtn.textContent = 'กำลังบันทึก...';
        editPageForm?.submit();
    });

});

/* ===== Autocomplete for MAT-004 index search bar ===== */

function initializeTransferListAutocomplete() {
    const searchInput = document.getElementById('transferSearchInput');
    const searchType  = document.getElementById('transferSearchType');

    if (!searchInput) return;

    const ac = new SearchAutocomplete({
        inputEl:      searchInput,
        searchTypeEl: searchType,
        endpoint:     window.searchSuggestionsUrl || '/search/suggestions',
        extraParams:  { entity: 'transfer' },
        minChars:     1,
        debounceMs:   300,
        maxResults:   15,
        onSelect: (item) => {
            searchInput.value = item.code;
            ac.close();
        },
    });
}

initializeTransferListAutocomplete();
