import flatpickr from 'flatpickr';
import 'flatpickr/dist/flatpickr.min.css';
import { Thai } from 'flatpickr/dist/l10n/th.js';

/**
 * Central date picker initializer.
 *
 * Any <input class="js-date-picker"> is turned into a Flatpickr instance:
 * - The user sees DD-MM-BBBB (Buddhist Era year, altInput).
 * - The value actually submitted to Laravel stays Y-m-d (Gregorian CE ISO),
 *   which is safe to bind to Oracle DATE/TIMESTAMP columns.
 *
 * Position:
 * - Defaults to "auto" (Flatpickr decides up/down based on viewport space).
 * - Add data-picker-position="below" to force the calendar to always open
 *   below the input, never flip upward.
 */

/** Convert a JS Date to "DD-MM-BBBB" Buddhist Era string. */
function formatBuddhistDate(date) {
    const dd = String(date.getDate()).padStart(2, '0');
    const mm = String(date.getMonth() + 1).padStart(2, '0');
    const be = date.getFullYear() + 543;
    return `${dd}-${mm}-${be}`;
}

/**
 * Override the altInput display to show DD-MM-BBBB.
 * Called after Flatpickr sets its own altInput value.
 */
function applyBuddhistAlt(selectedDates, fp) {
    if (selectedDates.length === 0 || !fp.altInput) return;
    fp.altInput.value = formatBuddhistDate(selectedDates[0]);
}

/**
 * Patch the calendar popup year input to display Buddhist Era year.
 * - Attaches a capture-phase listener ONCE per container so that when the
 *   user triggers a year change (typing or spinner arrows), we subtract 543
 *   BEFORE Flatpickr reads the value and sets its internal year.
 * - After each call, updates the displayed year to CE + 543.
 */
function patchCalendarYear(fp) {
    const container = fp.calendarContainer;
    if (!container) return;

    // Attach the intercept listener only once
    if (!container._bePatch) {
        container._bePatch = true;
        container.addEventListener(
            'change',
            function interceptYearChange(e) {
                const el = e.target;
                if (!el.classList.contains('cur-year')) return;
                const be = parseInt(el.value, 10);
                if (!isNaN(be) && be > 2400) {
                    // Convert back to CE so Flatpickr sees the right year
                    el.value = be - 543;
                }
            },
            true, // capture: fires BEFORE Flatpickr's own listener
        );
    }

    // Always update the displayed value to Buddhist Era
    container.querySelectorAll('.cur-year').forEach((el) => {
        const v = parseInt(el.value, 10);
        if (!isNaN(v) && v < 2400) {
            el.value = v + 543;
        }
    });
}

function initDatePickers(root = document) {
    const inputs = root.querySelectorAll('.js-date-picker');

    inputs.forEach((input) => {
        if (input._flatpickr) {
            return;
        }

        flatpickr(input, {
            locale: Thai,
            altInput: true,
            altFormat: 'd-m-Y',       // Dash-separated; overridden by applyBuddhistAlt for display
            dateFormat: 'Y-m-d',      // CE ISO date stored in the hidden value / submitted
            allowInput: true,
            position: input.dataset.pickerPosition || 'auto',

            // Accept "DD-MM-BBBB" (Buddhist Era), "DD-MM-YYYY" (Gregorian), or "YYYY-MM-DD" (DB ISO)
            parseDate(dateStr) {
                const s = (dateStr || '').trim();
                // DD-MM-YYYY or DD-MM-BBBB (day ≤ 2 digits first)
                const mDash = /^(\d{1,2})-(\d{1,2})-(\d{4})$/.exec(s);
                if (mDash) {
                    const day     = parseInt(mDash[1], 10);
                    const month   = parseInt(mDash[2], 10) - 1;
                    const rawYear = parseInt(mDash[3], 10);
                    const ceYear  = rawYear > 2400 ? rawYear - 543 : rawYear;
                    const d = new Date(ceYear, month, day);
                    if (!isNaN(d.getTime())) return d;
                }
                // YYYY-MM-DD — ISO format stored in DB, used as input value on edit pages
                const mISO = /^(\d{4})-(\d{2})-(\d{2})$/.exec(s);
                if (mISO) {
                    const d = new Date(parseInt(mISO[1], 10), parseInt(mISO[2], 10) - 1, parseInt(mISO[3], 10));
                    if (!isNaN(d.getTime())) return d;
                }
                return null;
            },

            onReady(selectedDates, _, fp) {
                applyBuddhistAlt(selectedDates, fp);
                patchCalendarYear(fp);
                if (fp.altInput) fp.altInput.placeholder = 'วว-ดด-ปปปป';
            },
            onChange(selectedDates, _, fp) {
                applyBuddhistAlt(selectedDates, fp);
                // Re-dispatch native events on the original (name-bound) input so
                // page-level validation scripts listening for input/change keep working.
                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            },
            onOpen(_, __, fp) {
                patchCalendarYear(fp);
            },
            onMonthChange(_, __, fp) {
                patchCalendarYear(fp);
            },
            onYearChange(_, __, fp) {
                // After Flatpickr processes the year, update the display back to BE
                patchCalendarYear(fp);
            },
        });
    });
}

document.addEventListener('DOMContentLoaded', () => {
    initDatePickers();
});

export { initDatePickers };
