/**
 * Searchable Autocomplete Dropdown Component
 *
 * Custom pure-JS autocomplete. No <select>, no <datalist>, no external library.
 * Behaviour mirrors the thailand-provinces.html reference implementation.
 *
 * Integration with MAT-002 form validation:
 *   hiddenInput._saTextInput  — reference to visible text input, so that
 *                               getDisplayField(hiddenInput) returns the visible
 *                               input and markFieldInvalid() adds red border there.
 *
 * Usage:
 *   Call initSearchableSelects() BEFORE any page-level validation setup so that
 *   the _saTextInput links are in place when attachLiveClear() is called.
 *
 * Options format:
 *   [{ value: "PRIMARY_KEY", label: "00000000 - ชื่อหน่วยงาน", searchText: "00000000 ชื่อหน่วยงาน" }]
 *   searchText is optional; falls back to label when absent.
 *
 * @module searchable-select
 */

import '../../css/components/searchable-select.css';

/** Track which wrapper is currently open so we can close it when another opens. */
let currentlyOpenWrapper = null;

function closeAllExcept(exceptWrapper) {
    if (currentlyOpenWrapper && currentlyOpenWrapper !== exceptWrapper) {
        const input = currentlyOpenWrapper.querySelector('.guja-autocomplete__input');
        const items = currentlyOpenWrapper.querySelector('.guja-autocomplete__items');
        currentlyOpenWrapper.classList.remove('is-open');
        if (input) input.setAttribute('aria-expanded', 'false');
        if (items) items.classList.remove('is-visible');
        currentlyOpenWrapper = null;
    }
}

function initSearchableSelect(wrapper) {
    // Find the hidden input first — it carries the id and initial value
    const hiddenInput = wrapper.querySelector('.guja-autocomplete__value');

    if (!hiddenInput || !hiddenInput.id) {
        return;
    }

    const id = hiddenInput.id;
    // Lazy getter so window._saOptions[id] can be updated after init (cascade dropdowns)
    const getOptions = () => (window._saOptions && window._saOptions[id]) || [];

    const visibleInput = wrapper.querySelector('.guja-autocomplete__input');
    const itemsContainer = wrapper.querySelector('.guja-autocomplete__items');

    if (!visibleInput || !itemsContainer) {
        return;
    }

    // Prevent double-initialisation (e.g. if initSearchableSelects is called twice)
    if (visibleInput._saInitialized) {
        return;
    }

    visibleInput._saInitialized = true;

    // Allow getDisplayField(hiddenInput) in page scripts to reach the visible input
    // so that markFieldInvalid() shows the red border on the correct element.
    hiddenInput._saTextInput = visibleInput;

    // On edit pages, restore the displayed label from the persisted hidden value
    if (String(hiddenInput.value).trim() !== '') {
        const match = getOptions().find((o) => String(o.value) === String(hiddenInput.value));

        if (match) {
            visibleInput.value = match.label;
        }
    }

    let currentFocus = -1;

    /* ---- helpers ---- */

    function getFiltered(searchText) {
        if (!searchText) {
            return getOptions();
        }

        const lower = searchText.toLowerCase();

        return getOptions().filter((o) => {
            const haystack = (o.searchText || o.label).toLowerCase();
            return haystack.includes(lower);
        });
    }

    function renderItems(searchText) {
        itemsContainer.innerHTML = '';
        currentFocus = -1;

        const filtered = getFiltered(searchText === undefined ? '' : searchText);

        if (filtered.length === 0) {
            const div = document.createElement('div');
            div.className = 'guja-autocomplete__empty';
            div.textContent = 'ไม่พบข้อมูลที่ค้นหา';
            itemsContainer.appendChild(div);
            return;
        }

        const currentVal = String(hiddenInput.value);

        filtered.forEach((opt) => {
            const div = document.createElement('div');
            div.className = 'guja-autocomplete__item';

            if (currentVal && String(opt.value) === currentVal) {
                div.classList.add('is-selected');
            }

            div.dataset.value = String(opt.value);
            div.dataset.label = opt.label;
            div.textContent = opt.label;
            itemsContainer.appendChild(div);
        });
    }

    function openDropdown() {
        closeAllExcept(wrapper);

        currentlyOpenWrapper = wrapper;
        wrapper.classList.add('is-open');
        visibleInput.setAttribute('aria-expanded', 'true');

        // If the text exactly matches the selected label, show the full list;
        // otherwise filter by whatever the user has typed.
        const currentLabel = visibleInput.value;
        const isExactMatch =
            hiddenInput.value !== '' &&
            getOptions().some((o) => o.label === currentLabel);

        renderItems(isExactMatch ? '' : currentLabel);
        itemsContainer.classList.add('is-visible');

        // Scroll selected option into view
        const selected = itemsContainer.querySelector('.is-selected');

        if (selected) {
            selected.scrollIntoView({ block: 'nearest' });
        }
    }

    function closeDropdown() {
        if (!wrapper.classList.contains('is-open')) {
            return;
        }

        wrapper.classList.remove('is-open');
        visibleInput.setAttribute('aria-expanded', 'false');
        itemsContainer.classList.remove('is-visible');
        currentFocus = -1;

        if (currentlyOpenWrapper === wrapper) {
            currentlyOpenWrapper = null;
        }

        // Restore label if the hidden input still has a value;
        // clear both if the user typed something that doesn't match.
        if (String(hiddenInput.value).trim() !== '') {
            const match = getOptions().find((o) => String(o.value) === String(hiddenInput.value));
            visibleInput.value = match ? match.label : '';

            if (!match) {
                hiddenInput.value = '';
                hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
            }
        } else {
            visibleInput.value = '';
        }
    }

    function selectOption(value, label) {
        visibleInput.value = label;
        hiddenInput.value = value;

        // Close before dispatching events so validation handlers see a clean state
        closeDropdown();

        // Notify form validation and live-clear handlers
        hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
        hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
        visibleInput.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function getVisibleItems() {
        return Array.from(itemsContainer.querySelectorAll('.guja-autocomplete__item'));
    }

    function updateActiveItem(items) {
        items.forEach((item) => item.classList.remove('is-active'));

        if (currentFocus >= 0 && currentFocus < items.length) {
            items[currentFocus].classList.add('is-active');
            items[currentFocus].scrollIntoView({ block: 'nearest' });
        }
    }

    /* ---- event listeners ---- */

    visibleInput.addEventListener('click', () => {
        if (!wrapper.classList.contains('guja-autocomplete--disabled')) {
            openDropdown();
        }
    });

    visibleInput.addEventListener('input', () => {
        // Clear the hidden value immediately — user is typing, no selection yet
        hiddenInput.value = '';

        if (!wrapper.classList.contains('is-open')) {
            closeAllExcept(wrapper);
            currentlyOpenWrapper = wrapper;
            wrapper.classList.add('is-open');
            visibleInput.setAttribute('aria-expanded', 'true');
            itemsContainer.classList.add('is-visible');
        }

        renderItems(visibleInput.value);
    });

    visibleInput.addEventListener('keydown', (e) => {
        const items = getVisibleItems();

        if (e.key === 'ArrowDown') {
            e.preventDefault();

            if (!wrapper.classList.contains('is-open')) {
                openDropdown();
                return;
            }

            currentFocus = Math.min(currentFocus + 1, items.length - 1);
            updateActiveItem(items);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            currentFocus = Math.max(currentFocus - 1, 0);
            updateActiveItem(items);
        } else if (e.key === 'Enter') {
            e.preventDefault();

            if (wrapper.classList.contains('is-open')) {
                const active = currentFocus >= 0 ? items[currentFocus] : items[0];

                if (active && active.dataset.value) {
                    selectOption(active.dataset.value, active.dataset.label);
                }
            }
        } else if (e.key === 'Escape') {
            if (wrapper.classList.contains('is-open')) {
                closeDropdown();
                visibleInput.blur();
            }
        } else if (e.key === 'Tab') {
            if (wrapper.classList.contains('is-open')) {
                closeDropdown();
            }
        }
    });

    visibleInput.addEventListener('blur', () => {
        // Small delay so a mousedown on a dropdown item registers first
        setTimeout(() => {
            if (!wrapper.contains(document.activeElement)) {
                closeDropdown();
            }
        }, 160);
    });

    // Option selection via mouse click
    itemsContainer.addEventListener('mousedown', (e) => {
        const item = e.target.closest('.guja-autocomplete__item');

        if (!item || !item.dataset.value) {
            return;
        }

        // preventDefault keeps focus on the text input (prevents blur→close race)
        e.preventDefault();
        selectOption(item.dataset.value, item.dataset.label);
    });

    // Redirect label[for="id"] clicks to the visible text input
    // (label points to hidden input by default, which is not focusable)
    const label = document.querySelector(`label[for="${CSS.escape(id)}"]`);

    if (label) {
        label.addEventListener('click', (e) => {
            e.preventDefault();

            if (!wrapper.classList.contains('guja-autocomplete--disabled')) {
                visibleInput.focus();
                openDropdown();
            }
        });
    }
}

// Close the open dropdown when clicking anywhere outside it
document.addEventListener('click', (e) => {
    if (currentlyOpenWrapper && !currentlyOpenWrapper.contains(e.target)) {
        const input = currentlyOpenWrapper.querySelector('.guja-autocomplete__input');
        const items = currentlyOpenWrapper.querySelector('.guja-autocomplete__items');
        currentlyOpenWrapper.classList.remove('is-open');
        if (input) input.setAttribute('aria-expanded', 'false');
        if (items) items.classList.remove('is-visible');
        currentlyOpenWrapper = null;
    }
});

/**
 * Initialise all [data-searchable-select] elements inside `root`.
 *
 * Call BEFORE any page-level validation setup (e.g. initializeHeaderForm) so
 * that hiddenInput._saTextInput links are established before attachLiveClear runs.
 *
 * @param {Document|Element} root
 */
export function initSearchableSelects(root = document) {
    root.querySelectorAll('[data-searchable-select]').forEach(initSearchableSelect);
}

/**
 * Update options for an already-initialised searchable-select (cascade dropdowns).
 * Because the component uses a lazy getOptions() getter, updating window._saOptions[id]
 * is enough — no re-init or duplicate listeners needed.
 *
 * @param {string}  hiddenInputId - id attribute of the hidden input
 * @param {Array}   newOptions    - [{value, label, searchText?}]
 * @param {boolean} clearValue    - clear current selection (default true)
 */
export function resetSearchableSelect(hiddenInputId, newOptions, clearValue = true) {
    window._saOptions = window._saOptions || {};
    window._saOptions[hiddenInputId] = newOptions;

    const hiddenInput = document.getElementById(hiddenInputId);
    if (!hiddenInput) return;

    const wrapper = hiddenInput.closest('[data-searchable-select]');
    if (!wrapper) return;

    const visibleInput  = wrapper.querySelector('.guja-autocomplete__input');
    const itemsContainer = wrapper.querySelector('.guja-autocomplete__items');

    if (clearValue) {
        hiddenInput.value = '';
        if (visibleInput) visibleInput.value = '';
        // Close dropdown if it was open
        wrapper.classList.remove('is-open');
        if (visibleInput) visibleInput.setAttribute('aria-expanded', 'false');
        if (itemsContainer) itemsContainer.classList.remove('is-visible');
    } else if (hiddenInput.value && visibleInput) {
        // Restore label for pre-selected value using the new options
        const match = newOptions.find((o) => String(o.value) === String(hiddenInput.value));
        if (match) visibleInput.value = match.label;
    }
}

