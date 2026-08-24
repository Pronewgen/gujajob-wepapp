/**
 * Server-side Searchable Select Component
 *
 * Shares the same HTML/CSS structure as searchable-select.js but fetches
 * suggestions from the server instead of filtering pre-loaded options.
 *
 * HTML structure expected:
 *   <div class="guja-autocomplete" data-server-select
 *        data-endpoint="/suggestions?entity=assign_org&q="
 *        data-initial-label="Optional pre-filled display label">
 *     <input type="text"   class="guja-autocomplete__input"  placeholder="...">
 *     <span                class="guja-autocomplete__arrow">▼</span>
 *     <div                 class="guja-autocomplete__items"></div>
 *     <input type="hidden" class="guja-autocomplete__value"  name="field_name" value="">
 *   </div>
 *
 * The hidden input stores the selected value (e.g. org_id).
 * The text input shows the display label.
 *
 * @module server-searchable-select
 */

let currentlyOpenServerSelect = null;

function closeAllServerSelects(except = null) {
    if (currentlyOpenServerSelect && currentlyOpenServerSelect !== except) {
        _close(currentlyOpenServerSelect);
    }
}

function _close(wrapper) {
    wrapper.classList.remove('is-open');
    const input = wrapper.querySelector('.guja-autocomplete__input');
    const list  = wrapper.querySelector('.guja-autocomplete__items');
    if (input) input.setAttribute('aria-expanded', 'false');
    if (list)  list.classList.remove('is-visible');
    if (currentlyOpenServerSelect === wrapper) currentlyOpenServerSelect = null;
}

function _open(wrapper) {
    closeAllServerSelects(wrapper);
    currentlyOpenServerSelect = wrapper;
    wrapper.classList.add('is-open');
    const input = wrapper.querySelector('.guja-autocomplete__input');
    const list  = wrapper.querySelector('.guja-autocomplete__items');
    if (input) input.setAttribute('aria-expanded', 'true');
    if (list)  list.classList.add('is-visible');
}

function initServerSearchableSelect(wrapper) {
    if (wrapper._ssInit) return;
    wrapper._ssInit = true;

    const input    = wrapper.querySelector('.guja-autocomplete__input');
    const hidden   = wrapper.querySelector('.guja-autocomplete__value');
    const list     = wrapper.querySelector('.guja-autocomplete__items');
    const endpoint = wrapper.dataset.endpoint || '';
    const debounce = parseInt(wrapper.dataset.debounce  || '300', 10);
    const minChars = parseInt(wrapper.dataset.minChars  || '1',   10);

    if (!input || !list) return;

    // Restore initial display label (set by Blade via data-initial-label)
    if (wrapper.dataset.initialLabel) {
        input.value = wrapper.dataset.initialLabel;
    }

    let timer      = null;
    let ctrl       = null;
    let activeIdx  = -1;
    const resultCache = new Map();
    let renderedQuery = null;

    /* ── Input handling ── */
    input.addEventListener('input', () => {
        clearTimeout(timer);
        const q = input.value.trim();
        if (q.length < minChars) { _close(wrapper); return; }
        timer = setTimeout(() => doFetch(q), debounce);
    });

    /* ── Click on input: re-show last results or trigger fetch ── */
    input.addEventListener('focus', () => {
        const q = input.value.trim();
        if (q.length >= minChars && !wrapper.classList.contains('is-open')) {
            doFetch(q);
        }
    });

    /* ── Keyboard navigation ── */
    input.addEventListener('keydown', (e) => {
        const rows = [...list.querySelectorAll('.guja-autocomplete__item')];
        if (e.key === 'Escape') { _close(wrapper); input.blur(); return; }
        if (!rows.length) return;
        if (e.key === 'ArrowDown') {
            e.preventDefault();
            activeIdx = Math.min(activeIdx + 1, rows.length - 1);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            activeIdx = Math.max(activeIdx - 1, 0);
        } else if (e.key === 'Enter' && activeIdx >= 0) {
            e.preventDefault();
            const row = rows[activeIdx];
            if (row) selectItem(row.dataset.value, row.dataset.label);
            return;
        } else { return; }
        rows.forEach((r, i) => r.classList.toggle('is-active', i === activeIdx));
        if (rows[activeIdx]) rows[activeIdx].scrollIntoView({ block: 'nearest' });
    });

    /* ── Close on outside click ── */
    document.addEventListener('click', (e) => {
        if (!wrapper.contains(e.target)) _close(wrapper);
    });

    /* ── Server fetch ── */
    function doFetch(q) {
        if (resultCache.has(q)) {
            if (renderedQuery === q && list.childElementCount > 0) {
                _open(wrapper);
            } else {
                render(resultCache.get(q));
            }
            return;
        }

        if (ctrl) ctrl.abort();
        ctrl = new AbortController();
        const url = endpoint + encodeURIComponent(q);
        fetch(url, { signal: ctrl.signal })
            .then((r) => r.json())
            .then((json) => {
                const items = json.data || [];
                resultCache.set(q, items);
                render(items, q);
            })
            .catch(() => {/* aborted or network error — silent */});
    }

    /* ── Render suggestion list ── */
    function render(items, query = input.value.trim()) {
        list.innerHTML = '';
        activeIdx = -1;
        renderedQuery = query;

        if (!items.length) {
            const empty = document.createElement('div');
            empty.className = 'guja-autocomplete__empty';
            empty.textContent = 'ไม่พบข้อมูลที่ค้นหา';
            list.appendChild(empty);
            _open(wrapper);
            return;
        }

        const currentValue = hidden ? hidden.value : '';

        items.forEach((item) => {
            const value = String(item.value ?? item.id   ?? item.code ?? '');
            const label = String(item.label ?? item.name ?? item.code ?? '');
            const div   = document.createElement('div');
            div.className          = 'guja-autocomplete__item';
            div.dataset.value      = value;
            div.dataset.label      = label;
            div.textContent        = label;
            if (currentValue && value === currentValue) {
                div.classList.add('is-selected');
            }
            div.addEventListener('mousedown', (e) => {
                // mousedown fires before blur — prevent dropdown close on blur
                e.preventDefault();
            });
            div.addEventListener('click', () => selectItem(value, label));
            list.appendChild(div);
        });

        _open(wrapper);

        // Scroll selected item into view
        const sel = list.querySelector('.is-selected');
        if (sel) sel.scrollIntoView({ block: 'nearest' });
    }

    /* ── Select an item ── */
    function selectItem(value, label) {
        input.value = label;
        if (hidden) {
            hidden.value = value;
            hidden.dispatchEvent(new Event('change', { bubbles: true }));
            hidden.dispatchEvent(new Event('input',  { bubbles: true }));
        }
        _close(wrapper);
    }
}

/**
 * Initialize all server-searchable-select wrappers on the page.
 * Call once after DOM ready.
 */
function initServerSearchableSelects() {
    document.querySelectorAll('[data-server-select]').forEach(initServerSearchableSelect);
}

export { initServerSearchableSelects, initServerSearchableSelect };
