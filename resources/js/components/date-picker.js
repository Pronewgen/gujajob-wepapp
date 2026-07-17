import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';

/**
 * Central date picker initializer.
 *
 * Any <input class="js-date-picker"> is turned into a Flatpickr instance:
 * - The user sees dd/mm/yyyy (altFormat).
 * - The value actually submitted to Laravel stays Y-m-d (dateFormat),
 *   which is safe to bind to Oracle DATE/TIMESTAMP columns.
 *
 * Position:
 * - Defaults to "auto" (Flatpickr decides up/down based on viewport space).
 * - Add data-picker-position="below" to force the calendar to always open
 *   below the input, never flip upward.
 *
 * Existing value/old()/validation-error markup on the input is left
 * untouched, so nothing else needs to change per page.
 */
function initDatePickers(root = document) {
    const inputs = root.querySelectorAll('.js-date-picker');

    inputs.forEach((input) => {
        if (input._flatpickr) {
            return;
        }

        flatpickr(input, {
            altInput: true,
            altFormat: 'd/m/Y',
            dateFormat: 'Y-m-d',
            allowInput: true,
            // data-picker-position="below" forces calendar below input (no auto-flip).
            // Omit the attribute to keep the default "auto" behaviour on other pages.
            position: input.dataset.pickerPosition || 'auto',
            onChange() {
                // Re-dispatch native events on the original (name-bound) input so
                // page-level validation scripts listening for input/change keep working.
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            },
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initDatePickers();
});

export { initDatePickers };
