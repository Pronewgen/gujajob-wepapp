/**
 * SearchAutocomplete – reusable live-search dropdown component.
 *
 * Usage:
 *   const ac = new SearchAutocomplete({
 *       inputEl:      document.getElementById('mySearchInput'),
 *       searchTypeEl: document.getElementById('mySearchType'),  // optional
 *       endpoint:     '/api/autocomplete',
 *       minChars:     1,
 *       debounceMs:   300,
 *       maxResults:   20,
 *       onSelect:     (item) => { fillForm(item); },
 *       renderItem:   (item) => `<span>${item.code}</span><span>${item.name}</span>`, // optional
 *   });
 *
 *   // Trigger a manual search (e.g. from a search button):
 *   findButton.addEventListener('click', () => ac.triggerSearch());
 */

export class SearchAutocomplete {
    constructor(config) {
        if (!config.inputEl) {
            throw new Error('SearchAutocomplete: inputEl is required');
        }

        this.inputEl      = config.inputEl;
        this.searchTypeEl = config.searchTypeEl ?? null;
        this.endpoint     = config.endpoint;
        this.extraParams  = config.extraParams  ?? {};
        this.minChars     = config.minChars     ?? 1;
        this.debounceMs   = config.debounceMs   ?? 300;
        this.maxResults   = config.maxResults   ?? 20;
        this.onSelect     = config.onSelect     ?? (() => {});
        this.renderItem   = config.renderItem   ?? this._defaultRenderItem.bind(this);

        this._timer      = null;
        this._activeIdx  = -1;
        this._items      = [];
        this._ctrl       = null;   // AbortController

        this._buildDOM();
        this._attachEvents();
    }

    /* ─────────────────────────────── DOM ─────────────────────────────── */

    _buildDOM() {
        const wrapper = document.createElement('div');
        wrapper.className = 'sac-wrapper';

        // Inject wrapper around the input (no layout disruption expected)
        this.inputEl.parentNode.insertBefore(wrapper, this.inputEl);
        wrapper.appendChild(this.inputEl);

        this._dropEl = document.createElement('div');
        this._dropEl.className   = 'sac-results';
        this._dropEl.setAttribute('role', 'listbox');
        this._dropEl.hidden = true;
        wrapper.appendChild(this._dropEl);

        this._wrapper = wrapper;
    }

    /* ─────────────────────────────── Events ──────────────────────────── */

    _attachEvents() {
        this.inputEl.setAttribute('autocomplete', 'off');

        this.inputEl.addEventListener('input', () => this._onInput());
        this.inputEl.addEventListener('keydown', (e) => this._onKeydown(e));

        // Close when clicking outside the component
        document.addEventListener('click', (e) => {
            if (!this._wrapper.contains(e.target)) {
                this.close();
            }
        });

        // Re-search when the type selector changes
        if (this.searchTypeEl) {
            this.searchTypeEl.addEventListener('change', () => {
                if (this.inputEl.value.trim().length >= this.minChars) {
                    this._schedule();
                } else {
                    this.close();
                }
            });
        }
    }

    _onInput() {
        const val = this.inputEl.value.trim();
        if (val.length < this.minChars) {
            this.close();
            return;
        }
        this._schedule();
    }

    _schedule() {
        clearTimeout(this._timer);
        this._timer = setTimeout(() => this._fetch(), this.debounceMs);
    }

    _onKeydown(e) {
        if (e.key === 'ArrowDown') {
            if (this._dropEl.hidden) {
                this._fetch();
            } else {
                e.preventDefault();
                this._move(1);
            }
            return;
        }

        if (e.key === 'ArrowUp') {
            e.preventDefault();
            this._move(-1);
            return;
        }

        if (e.key === 'Enter') {
            if (!this._dropEl.hidden && this._activeIdx >= 0) {
                e.preventDefault();
                this._select(this._activeIdx);
            } else if (!this._dropEl.hidden && this._items.length > 0) {
                e.preventDefault();
                this._select(0);
            } else {
                // Dropdown closed → trigger search on Enter
                e.preventDefault();
                clearTimeout(this._timer);
                this._fetch();
            }
            return;
        }

        if (e.key === 'Escape') {
            this.close();
        }
    }

    /* ─────────────────────────────── Fetch ───────────────────────────── */

    async _fetch() {
        const keyword = this.inputEl.value.trim();
        if (keyword.length < this.minChars) {
            this.close();
            return;
        }

        // Cancel previous in-flight request
        if (this._ctrl) {
            this._ctrl.abort();
        }
        this._ctrl = new AbortController();

        const type   = this.searchTypeEl ? this.searchTypeEl.value : 'code';
        const params = new URLSearchParams({
            type:  type,
            q:     keyword,
            limit: String(this.maxResults),
            ...this.extraParams,
        });

        this._showStatus('loading');

        try {
            const res  = await fetch(`${this.endpoint}?${params.toString()}`, {
                signal: this._ctrl.signal,
            });

            if (!res.ok) {
                throw new Error(`HTTP ${res.status}`);
            }

            const json  = await res.json();
            // Accept both flat-array and {data:[]} formats
            const items = Array.isArray(json) ? json : (json.data ?? []);

            this._items = items;
            this._render(items);
        } catch (err) {
            if (err.name === 'AbortError') return;
            this._showStatus('error');
        }
    }

    /* ─────────────────────────────── Render ──────────────────────────── */

    _render(items) {
        this._dropEl.innerHTML = '';
        this._activeIdx = -1;

        if (items.length === 0) {
            this._showStatus('empty');
            return;
        }

        items.forEach((item, idx) => {
            const el = document.createElement('div');
            el.className = 'sac-item';
            el.setAttribute('role', 'option');
            el.dataset.idx = String(idx);
            el.innerHTML = this.renderItem(item);

            el.addEventListener('mousedown', (e) => {
                e.preventDefault();   // keep input focused
                this._select(idx);
            });

            this._dropEl.appendChild(el);
        });

        this._dropEl.hidden = false;
    }

    _showStatus(type) {
        const messages = {
            loading: '<div class="sac-status">กำลังค้นหา...</div>',
            empty:   '<div class="sac-status sac-empty">ไม่พบข้อมูลที่ตรงกับคำค้นหา</div>',
            error:   '<div class="sac-status sac-error">ไม่สามารถค้นหาข้อมูลได้ กรุณาลองใหม่</div>',
        };
        this._dropEl.innerHTML = messages[type] ?? '';
        this._dropEl.hidden    = false;
    }

    _defaultRenderItem(item) {
        const code = this._esc(item.code ?? '');
        const name = this._esc(item.name ?? '');
        const unit = this._esc(item.unit ?? '');

        const balancePart = (item.balance !== null && item.balance !== undefined)
            ? ` · คงเหลือ ${item.balance}`
            : '';

        return `<span class="sac-code">${code}</span>`
             + `<span class="sac-name">${name}</span>`
             + `<span class="sac-meta">${unit}${balancePart}</span>`;
    }

    _esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;');
    }

    /* ─────────────────────────────── Navigation ──────────────────────── */

    _move(dir) {
        const items = this._dropEl.querySelectorAll('.sac-item');
        if (items.length === 0) return;

        this._activeIdx = Math.max(-1, Math.min(items.length - 1, this._activeIdx + dir));
        items.forEach((el, i) => el.classList.toggle('sac-active', i === this._activeIdx));

        if (this._activeIdx >= 0) {
            items[this._activeIdx].scrollIntoView({ block: 'nearest' });
        }
    }

    _select(idx) {
        const item = this._items[idx];
        if (!item) return;
        this.close();
        this.onSelect(item);
    }

    /* ─────────────────────────────── Public API ──────────────────────── */

    /** Trigger an immediate search (e.g. from a "ค้นหา" button). */
    triggerSearch() {
        clearTimeout(this._timer);
        this._fetch();
    }

    /** Close and clear the dropdown. */
    close() {
        this._dropEl.hidden = true;
        this._dropEl.innerHTML = '';
        this._activeIdx = -1;
        this._items     = [];
    }
}
