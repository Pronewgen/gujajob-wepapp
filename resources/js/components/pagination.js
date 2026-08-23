/**
 * Shared pagination jump-form validation.
 * Handles every .app-page-jump-form element on the page automatically.
 */
function initializeAppPaginationJumps() {
    document.querySelectorAll('.app-page-jump-form').forEach(function (form) {
        const input = form.querySelector('.app-page-jump-input');
        if (!input) {
            return;
        }

        const lastPage = parseInt(input.dataset.lastPage, 10);

        form.addEventListener('submit', function (event) {
            input.setCustomValidity('');

            const raw = input.value.trim();

            // Empty input: silently block, no navigation
            if (raw === '') {
                event.preventDefault();
                return;
            }

            const page = Number(raw);

            if (!Number.isInteger(page) || page < 1) {
                event.preventDefault();
                input.setCustomValidity('เลขหน้าต้องเริ่มตั้งแต่ 1');
                input.reportValidity();
                return;
            }

            if (page > lastPage) {
                event.preventDefault();
                input.setCustomValidity(`กรุณาระบุเลขหน้าระหว่าง 1\u2013${lastPage}`);
                input.reportValidity();
                return;
            }

            // Valid — let the form submit naturally (GET request)
        });

        input.addEventListener('input', function () {
            input.setCustomValidity('');
        });
    });
}

document.addEventListener('DOMContentLoaded', initializeAppPaginationJumps);
