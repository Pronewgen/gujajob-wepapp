const form = document.getElementById('resultRecordForm');
const saveButton = document.getElementById('saveResultButton');
const overlay = document.getElementById('saveResultOverlay');
const cancelButton = document.getElementById('cancelSaveResultButton');
const confirmButton = document.getElementById('confirmSaveResultButton');
const validationAlert = document.getElementById('resultValidationAlert');

const setOverlay = (open) => {
    if (!overlay) return;
    overlay.classList.toggle('is-visible', open);
    overlay.setAttribute('aria-hidden', open ? 'false' : 'true');
};

const hideValidationAlert = () => {
    if (!validationAlert) return;
    validationAlert.hidden = true;
    validationAlert.textContent = '';
};

const showValidationAlert = (message) => {
    if (!validationAlert) return;
    validationAlert.textContent = message;
    validationAlert.hidden = false;
};

const validateForm = () => {
    if (!form) return false;

    if (form.dataset.resultMode === 'lost') {
        hideValidationAlert();
        return true;
    }

    const buyerInput = form.querySelector('#buyer');
    const rawBuyer = buyerInput ? buyerInput.value.trim() : '';

    if (!rawBuyer) {
        showValidationAlert('กรุณากรอกผู้รับซื้อก่อนบันทึกผลการจำหน่าย');
        if (buyerInput) buyerInput.focus();
        return false;
    }

    const priceInputs = [...form.querySelectorAll('.js-real-price-input')];
    if (priceInputs.length === 0) {
        showValidationAlert('ไม่พบรายการครุภัณฑ์สำหรับบันทึกผลการจำหน่าย');
        return false;
    }

    for (const input of priceInputs) {
        const value = String(input.value ?? '').trim();
        if (value === '') {
            showValidationAlert('กรุณากรอกราคาที่ขายได้จริงให้ครบทุกรายการ');
            input.focus();
            return false;
        }

        const parsed = Number(value);
        if (Number.isNaN(parsed) || parsed < 0) {
            showValidationAlert('ราคาที่ขายได้จริงต้องเป็นตัวเลขตั้งแต่ 0 ขึ้นไป');
            input.focus();
            return false;
        }

        if (!/^\d+(\.\d{1,2})?$/.test(value)) {
            showValidationAlert('ราคาที่ขายได้จริงรองรับทศนิยมไม่เกิน 2 ตำแหน่ง');
            input.focus();
            return false;
        }
    }

    hideValidationAlert();
    return true;
};

if (saveButton) {
    saveButton.addEventListener('click', () => {
        if (!validateForm()) return;
        setOverlay(true);
    });
}

if (cancelButton) {
    cancelButton.addEventListener('click', () => setOverlay(false));
}

if (confirmButton) {
    confirmButton.addEventListener('click', () => {
        if (!form) return;
        confirmButton.disabled = true;
        form.submit();
    });
}

if (overlay) {
    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            setOverlay(false);
        }
    });
}

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape') {
        setOverlay(false);
    }
});

hideValidationAlert();
