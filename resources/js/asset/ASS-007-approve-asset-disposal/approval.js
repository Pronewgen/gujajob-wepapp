import '../../components/date-picker.js';

/**
 * ASS-007 Approval review page.
 *
 * Single #approveForm holds the approval_date input, per-item selling_min_price
 * inputs, and a hidden reject_reason field. The "อนุมัติ"/"ไม่อนุมัติ" buttons
 * only open a shared confirm modal; on confirm, the form action is switched to
 * the matching route and submitted (no separate AJAX call, backend validates).
 */

const approveForm = document.getElementById('approveForm');
if (approveForm) {
    const approveTriggerBtn  = document.getElementById('approveTriggerBtn');
    const rejectTriggerBtn   = document.getElementById('rejectTriggerBtn');
    const overlay            = document.getElementById('approvalConfirmOverlay');
    const iconWrapper        = document.getElementById('approvalConfirmIcon');
    const iconUse            = document.getElementById('approvalConfirmIconUse');
    const titleEl            = document.getElementById('approvalConfirmTitle');
    const messageEl          = document.getElementById('approvalConfirmMessage');
    const reasonField        = document.getElementById('rejectReasonField');
    const reasonInput        = document.getElementById('rejectReasonInput');
    const reasonError        = document.getElementById('rejectReasonError');
    const reasonHidden       = document.getElementById('rejectReasonHidden');
    const cancelBtn          = document.getElementById('cancelApprovalButton');
    const confirmBtn         = document.getElementById('confirmApprovalButton');
    const validationAlert    = document.getElementById('approvalValidationAlert');
    const approvalDateInput  = document.getElementById('approvalDate');
    const priceInputs        = Array.from(document.querySelectorAll('.price-input'));

    const approveUrl = approveForm.action;
    const rejectUrl  = approveForm.dataset.rejectUrl || '';

    let pendingMode = null;

    function showAlert(message) {
        if (!validationAlert) return;
        validationAlert.textContent = message;
        validationAlert.hidden = false;
    }

    function hideAlert() {
        if (!validationAlert) return;
        validationAlert.hidden = true;
    }

    function clearPriceErrors() {
        priceInputs.forEach((input) => input.classList.remove('is-invalid'));
    }

    function validateApprovalDate() {
        if (!approvalDateInput || approvalDateInput.value.trim() === '') {
            showAlert('กรุณาระบุวันที่พิจารณา');
            return false;
        }
        return true;
    }

    function validatePrices() {
        clearPriceErrors();
        let valid = true;
        priceInputs.forEach((input) => {
            const value = parseFloat(input.value);
            if (input.value.trim() === '' || Number.isNaN(value) || value < 0) {
                input.classList.add('is-invalid');
                valid = false;
            }
        });
        if (!valid) {
            showAlert('กรุณากรอกราคาขายของทุกรายการให้ถูกต้อง (ต้องไม่ติดลบ)');
        }
        return valid;
    }

    function openModal(mode) {
        pendingMode = mode;
        if (mode === 'approve') {
            iconWrapper.className = 'confirm-icon success-confirm-icon';
            iconUse.setAttribute('href', '#icon-success');
            titleEl.textContent = 'ยืนยันการอนุมัติ';
            messageEl.textContent = 'คุณแน่ใจหรือไม่ว่าต้องการอนุมัติใบแจ้งจำหน่ายนี้';
            confirmBtn.className = 'modal-confirm-btn success-confirm-btn';
            reasonField.hidden = true;
        } else {
            iconWrapper.className = 'confirm-icon delete-confirm-icon';
            iconUse.setAttribute('href', '#icon-alert-triangle');
            titleEl.textContent = 'ยืนยันไม่อนุมัติ';
            messageEl.textContent = 'กรุณาระบุเหตุผลการไม่อนุมัติก่อนยืนยัน';
            confirmBtn.className = 'modal-confirm-btn delete-confirm-btn';
            reasonField.hidden = false;
            reasonInput.value = '';
            reasonError.hidden = true;
        }
        overlay.classList.add('is-visible');
        overlay.setAttribute('aria-hidden', 'false');
    }

    function closeModal() {
        overlay.classList.remove('is-visible');
        overlay.setAttribute('aria-hidden', 'true');
        pendingMode = null;
    }

    approveTriggerBtn?.addEventListener('click', () => {
        hideAlert();
        if (!validateApprovalDate() || !validatePrices()) return;
        openModal('approve');
    });

    rejectTriggerBtn?.addEventListener('click', () => {
        hideAlert();
        if (!validateApprovalDate()) return;
        openModal('reject');
    });

    cancelBtn?.addEventListener('click', closeModal);
    overlay?.addEventListener('click', (event) => {
        if (event.target === overlay) closeModal();
    });
    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape' && overlay?.classList.contains('is-visible')) closeModal();
    });

    confirmBtn?.addEventListener('click', () => {
        if (pendingMode === 'reject') {
            const reason = reasonInput.value.trim();
            if (reason === '') {
                reasonError.hidden = false;
                return;
            }
            reasonHidden.value = reason;
            approveForm.action = rejectUrl;
        } else {
            reasonHidden.value = '';
            approveForm.action = approveUrl;
        }
        approveForm.submit();
    });
}

