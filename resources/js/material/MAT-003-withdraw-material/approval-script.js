import { SearchAutocomplete } from '../../components/search-autocomplete.js';

/**
 * MAT-003 Approval page – JavaScript
 *
 * Flow:
 *  1. Live preview of "คงเหลือหลังอนุมัติ" while the user types.
 *  2. On "อนุมัติ" click:
 *       a. Basic local validation (NaN, negative, all-zero).
 *       b. POST to /validate-stock → Oracle re-checks current inventory.
 *       c. If valid → open approve popup.
 *       d. If invalid → show per-row error under the quantity input.
 *  3. Popup confirm → submit the approveForm to approve().
 *  4. "ไม่อนุมัติ" always opens the reject popup (no stock check needed).
 */

(function () {
    'use strict';

    // ─── Guard: only run on the approval show page ──────────────────────────
    const approveForm = document.getElementById('approveForm');
    if (! approveForm) return;

    // ─── Config ───────────────────────────────────────────────────────────────
    const validateStockUrl = approveForm.dataset.validateStockUrl ?? '';

    // ─── Elements ────────────────────────────────────────────────────────────
    const detailRows        = document.querySelectorAll('#approvalItemsBody tr[data-detail-id]');
    const approveBtn        = document.getElementById('approveBtnMain');
    const rejectBtn         = document.getElementById('rejectBtnMain');
    const validationAlert   = document.getElementById('approvalValidationAlert');

    // Approve popup
    const approveOverlay    = document.getElementById('approveOverlay');
    const approveCancelBtn  = document.getElementById('approveCancelBtn');
    const approveConfirmBtn = document.getElementById('approveConfirmBtn');

    // Reject popup
    const rejectOverlay     = document.getElementById('rejectOverlay');
    const rejectForm        = document.getElementById('rejectForm');
    const rejectCancelBtn   = document.getElementById('rejectCancelBtn');
    const rejectConfirmBtn  = document.getElementById('rejectConfirmBtn');

    // ─── Live preview: "คงเหลือหลังอนุมัติ" ────────────────────────────────────

    function updateRowPreview(row) {
        const visInput      = row.querySelector('.approval-qty-input');
        const hidInput      = row.querySelector('.approval-qty-hidden');
        const remainingCell = row.querySelector('.remaining-after-cell');
        if (! remainingCell) return;

        const rawBalance = row.dataset.currentBalance;

        // No inventory record → show en-dash, no colour
        if (! rawBalance) {
            remainingCell.textContent = '–';
            remainingCell.className   = 'remaining-after-cell';
            return;
        }

        // Live editing: use visible input; confirmed: use hidden input
        const activeInput    = (visInput && ! visInput.disabled) ? visInput : hidInput;
        const approvedQty    = parseInt(activeInput?.value ?? '0', 10);
        const currentBalance = parseInt(rawBalance, 10);

        if (isNaN(approvedQty)) {
            remainingCell.textContent = '–';
            remainingCell.className   = 'remaining-after-cell';
            return;
        }

        const remaining = currentBalance - approvedQty;
        remainingCell.textContent = remaining;
        remainingCell.className   = remaining < 0
            ? 'remaining-after-cell text-negative'
            : 'remaining-after-cell';
    }

    // ─── Row error helpers ─────────────────────────────────────────────────────

    function clearRowError(row) {
        const errorEl = row.querySelector('[data-quantity-error]');
        const input   = row.querySelector('.approval-qty-input');
        if (errorEl) { errorEl.textContent = ''; errorEl.hidden = true; }
        if (input)   input.classList.remove('is-invalid');
        row.classList.remove('row-over-balance');
    }

    function showRowError(row, message) {
        const errorEl = row.querySelector('[data-quantity-error]');
        const input   = row.querySelector('.approval-qty-input');
        if (errorEl) { errorEl.textContent = message; errorEl.hidden = false; }
        if (input)   input.classList.add('is-invalid');
        row.classList.add('row-over-balance');
    }

    function clearAllRowErrors() {
        detailRows.forEach(row => clearRowError(row));
    }

    // ─── Alert box helpers ────────────────────────────────────────────────────

    function showAlert(msg) {
        if (! validationAlert) return;
        validationAlert.textContent = msg;
        validationAlert.hidden = false;
    }

    function hideAlert() {
        if (! validationAlert) return;
        validationAlert.hidden = true;
    }

    // ─── Popup helpers ────────────────────────────────────────────────────────

    function openOverlay(overlay) {
        if (! overlay) return;
        overlay.classList.add('is-visible');
        overlay.setAttribute('aria-hidden', 'false');
    }

    function closeOverlay(overlay) {
        if (! overlay) return;
        overlay.classList.remove('is-visible');
        overlay.setAttribute('aria-hidden', 'true');
    }

    // ─── Inline quantity editor (pen → check) ───────────────────────────────────────────

    function isAnyRowEditing() {
        return document.querySelector('#approvalItemsBody .qty-action-btn[data-mode="save"]') !== null;
    }

    function openRowEditor(row) {
        const visInput = row.querySelector('.approval-qty-input');
        const hidInput = row.querySelector('.approval-qty-hidden');
        const actionBtn = row.querySelector('.qty-action-btn');
        if (! visInput || ! hidInput || ! actionBtn) return;

        visInput._editOriginal = visInput.value; // remember for Escape cancel
        visInput.disabled = false;
        visInput.focus();
        visInput.select();

        // Switch to “confirm” (green check) appearance
        actionBtn.querySelector('use').setAttribute('href', '#icon-success');
        actionBtn.setAttribute('aria-label',    'ยืนยันการแก้ไขจำนวน');
        actionBtn.setAttribute('data-tooltip',  'ยืนยันการแก้ไขจำนวน');
        actionBtn.classList.remove('table-action-edit');
        actionBtn.classList.add('table-action-save');
        actionBtn.dataset.mode = 'save';
    }

    function confirmRowEditor(row) {
        const visInput  = row.querySelector('.approval-qty-input');
        const hidInput  = row.querySelector('.approval-qty-hidden');
        const actionBtn = row.querySelector('.qty-action-btn');
        if (! visInput || ! hidInput) return;

        const val = parseInt(visInput.value, 10);
        if (isNaN(val) || val < 0) {
            showRowError(row, 'กรุณาระบุจำนวนที่ถูกต้อง');
            visInput.focus();
            return;
        }

        hidInput.value    = val;
        visInput.value    = val;
        visInput.disabled = true;

        // Switch back to pen (amber) appearance
        if (actionBtn) {
            actionBtn.querySelector('use').setAttribute('href', '#icon-square-pen');
            actionBtn.setAttribute('aria-label',   'แก้ไขจำนวน');
            actionBtn.setAttribute('data-tooltip', 'แก้ไขจำนวน');
            actionBtn.classList.remove('table-action-save');
            actionBtn.classList.add('table-action-edit');
            actionBtn.dataset.mode = 'edit';
        }

        clearRowError(row);
        updateRowPreview(row);
    }

    function cancelRowEditor(row) {
        const visInput  = row.querySelector('.approval-qty-input');
        const hidInput  = row.querySelector('.approval-qty-hidden');
        const actionBtn = row.querySelector('.qty-action-btn');
        if (! visInput || ! hidInput) return;

        visInput.value    = visInput._editOriginal ?? hidInput.value;
        visInput.disabled = true;

        // Switch back to pen (amber) appearance
        if (actionBtn) {
            actionBtn.querySelector('use').setAttribute('href', '#icon-square-pen');
            actionBtn.setAttribute('aria-label',   'แก้ไขจำนวน');
            actionBtn.setAttribute('data-tooltip', 'แก้ไขจำนวน');
            actionBtn.classList.remove('table-action-save');
            actionBtn.classList.add('table-action-edit');
            actionBtn.dataset.mode = 'edit';
        }

        clearRowError(row);
        updateRowPreview(row);
    }

    // ─── Initialise rows ────────────────────────────────────────────────────────

    detailRows.forEach(row => {
        const visInput  = row.querySelector('.approval-qty-input');
        const actionBtn = row.querySelector('.qty-action-btn');

        updateRowPreview(row);

        if (visInput) {
            visInput.addEventListener('input', () => {
                updateRowPreview(row);
                clearRowError(row);
                hideAlert();
            });

            visInput.addEventListener('keydown', e => {
                if (e.key === 'Enter') {
                    e.preventDefault();
                    confirmRowEditor(row);
                }
                if (e.key === 'Escape') {
                    e.preventDefault();
                    cancelRowEditor(row);
                }
            });
        }

        if (actionBtn) {
            actionBtn.addEventListener('click', () => {
                if (actionBtn.dataset.mode === 'save') {
                    confirmRowEditor(row);
                } else {
                    openRowEditor(row);
                }
            });
        }
    });

    // ─── Basic client-side validation (runs before AJAX) ─────────────────────

    function validateBasic() {
        // Block approval if any row is still in edit mode (pending check-confirmation)
        if (isAnyRowEditing()) {
            showAlert('กรุณายืนยันจำนวน (กดปุ่ม ✓) ให้ครบทุกแถวก่อนกดอนุมัติ');
            return false;
        }

        let hasError = false;
        let allZero  = true;

        detailRows.forEach(row => {
            const input = row.querySelector('.approval-qty-hidden');
            if (! input) return;

            const val = input.value.trim();
            const qty = Number(val);

            if (val === '' || isNaN(qty)) {
                showRowError(row, 'กรุณาระบุจำนวนที่อนุมัติให้ถูกต้อง');
                hasError = true;
                return;
            }

            if (qty < 0) {
                showRowError(row, 'จำนวนที่อนุมัติต้องไม่น้อยกว่า 0');
                hasError = true;
                return;
            }

            if (qty > 0) allZero = false;
        });

        if (! hasError && allZero) {
            showAlert('กรุณาระบุจำนวนที่อนุมัติอย่างน้อย 1 รายการ');
            return false;
        }

        return ! hasError;
    }

    // ─── Collect quantities for AJAX ──────────────────────────────────────────

    function collectDetails() {
        const details = {};
        detailRows.forEach(row => {
            const detailId = row.dataset.detailId;
            const input    = row.querySelector('.approval-qty-hidden');
            if (detailId && input) {
                details[detailId] = { approved_quantity: input.value };
            }
        });
        return details;
    }

    // ─── Approve button ───────────────────────────────────────────────────────

    if (approveBtn) {
        approveBtn.addEventListener('click', async () => {
            clearAllRowErrors();
            hideAlert();

            // Step 1: basic local validation
            if (! validateBasic()) {
                const firstError = document.querySelector('#approvalItemsBody tr.row-over-balance');
                if (firstError) firstError.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                return;
            }

            // Step 2: disable button while Oracle check is in flight
            approveBtn.disabled    = true;
            approveBtn.textContent = 'กำลังตรวจสอบ...';

            const csrfToken = approveForm.querySelector('input[name="_token"]')?.value ?? '';

            try {
                // Step 3: POST to validate-stock – Oracle reads live inventory
                const resp = await fetch(validateStockUrl, {
                    method:  'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept':       'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                    },
                    body: JSON.stringify({ details: collectDetails() }),
                });

                if (! resp.ok && resp.status !== 422 && resp.status !== 404) {
                    showAlert('ไม่สามารถตรวจสอบจำนวนคงเหลือได้ กรุณาลองใหม่');
                    return;
                }

                const data = await resp.json();

                // Step 4a: all quantities are within stock → open confirm popup
                if (data.valid) {
                    openOverlay(approveOverlay);
                    return;
                }

                // Step 4b: one or more quantities exceed available stock
                if (Array.isArray(data.items) && data.items.length > 0) {
                    data.items.forEach(item => {
                        const row = document.querySelector(
                            `#approvalItemsBody tr[data-detail-id="${item.detail_id}"]`
                        );
                        if (row) showRowError(row, item.message);
                    });

                    const firstError = document.querySelector('#approvalItemsBody tr.row-over-balance');
                    if (firstError) {
                        firstError.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                        firstError.querySelector('.approval-qty-input')?.focus();
                    }
                    return;
                }

                // Step 4c: generic server-side error
                showAlert(data.error || 'ตรวจสอบจำนวนไม่สำเร็จ กรุณาลองใหม่');

            } catch (_) {
                showAlert('ไม่สามารถตรวจสอบจำนวนคงเหลือได้ กรุณาลองใหม่');
            } finally {
                approveBtn.disabled    = false;
                approveBtn.textContent = 'อนุมัติ';
            }
        });
    }

    // ─── Reject button (no stock check – reject never touches stock) ─────────

    if (rejectBtn) {
        rejectBtn.addEventListener('click', () => {
            hideAlert();
            openOverlay(rejectOverlay);
        });
    }

    // ─── Approve overlay events ───────────────────────────────────────────────

    if (approveCancelBtn) {
        approveCancelBtn.addEventListener('click', () => closeOverlay(approveOverlay));
    }

    if (approveOverlay) {
        approveOverlay.addEventListener('click', e => {
            if (e.target === approveOverlay) closeOverlay(approveOverlay);
        });
    }

    if (approveConfirmBtn) {
        approveConfirmBtn.addEventListener('click', () => {
            approveConfirmBtn.disabled    = true;
            approveConfirmBtn.textContent = 'กำลังอนุมัติ...';
            approveForm.submit();
        });
    }

    // ─── Reject overlay events ────────────────────────────────────────────────

    if (rejectCancelBtn) {
        rejectCancelBtn.addEventListener('click', () => closeOverlay(rejectOverlay));
    }

    if (rejectOverlay) {
        rejectOverlay.addEventListener('click', e => {
            if (e.target === rejectOverlay) closeOverlay(rejectOverlay);
        });
    }

    if (rejectConfirmBtn) {
        rejectConfirmBtn.addEventListener('click', () => {
            rejectConfirmBtn.disabled    = true;
            rejectConfirmBtn.textContent = 'กำลังดำเนินการ...';
            if (rejectForm) rejectForm.submit();
        });
    }

    // ─── Escape key closes any open overlay ──────────────────────────────────

    document.addEventListener('keydown', e => {
        if (e.key === 'Escape') {
            closeOverlay(approveOverlay);
            closeOverlay(rejectOverlay);
        }
    });

}());

/* ===== Autocomplete for MAT-003 approval index search bar ===== */

function initializeApprovalListAutocomplete() {
    const searchInput = document.getElementById('approvalSearchInput');
    const searchType  = document.getElementById('approvalSearchType');

    if (!searchInput) return;

    const ac = new SearchAutocomplete({
        inputEl:      searchInput,
        searchTypeEl: searchType,
        endpoint:     window.searchSuggestionsUrl || '/search/suggestions',
        extraParams:  { entity: 'withdrawal' },
        minChars:     1,
        debounceMs:   300,
        maxResults:   15,
        onSelect: (item) => {
            searchInput.value = item.code;
            ac.close();
        },
    });
}

initializeApprovalListAutocomplete();
