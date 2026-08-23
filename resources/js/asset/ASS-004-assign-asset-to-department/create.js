import { initServerSearchableSelects } from '../../components/server-searchable-select.js';
import { SearchAutocomplete } from '../../components/search-autocomplete.js';
import '../../components/date-picker.js';

const sidebarStorageKey = 'gujajob.sidebar.groupState';

function getSidebarState() {
    try {
        const storedState = localStorage.getItem(sidebarStorageKey);
        if (!storedState) return { material: true, asset: true };
        return { material: true, asset: true, ...JSON.parse(storedState) };
    } catch { return { material: true, asset: true }; }
}

function saveSidebarState(state) {
    try { localStorage.setItem(sidebarStorageKey, JSON.stringify(state)); } catch {}
}

function applySidebarGroupState(groupElement, isOpen) {
    const btn = groupElement.querySelector('.menu-group-toggle');
    groupElement.classList.toggle('is-collapsed', !isOpen);
    if (btn) btn.setAttribute('aria-expanded', String(isOpen));
}

function initializeSidebarGroups() {
    const state = getSidebarState();
    document.querySelectorAll('[data-sidebar-group]').forEach((g) => {
        const name = g.dataset.sidebarGroup;
        const btn  = g.querySelector('.menu-group-toggle');
        applySidebarGroupState(g, state[name] !== false);
        if (!btn) return;
        btn.addEventListener('click', () => {
            const cur = getSidebarState();
            const next = g.classList.contains('is-collapsed');
            cur[name] = next;
            applySidebarGroupState(g, next);
            saveSidebarState(cur);
        });
    });
}

function preventDisabledMenuReload() {
    document.querySelectorAll('.disabled-link').forEach((l) => {
        l.addEventListener('click', (e) => e.preventDefault());
    });
}

/* ── Asset selection logic ── */
function initAssetSelection() {
    const availableBody  = document.getElementById('availableAssetsBody');
    const selectedBody   = document.getElementById('selectedAssetsBody');
    const noSelectionRow = document.getElementById('noSelectionRow');
    const selectedCount  = document.getElementById('selectedCount');
    const selectAll      = document.getElementById('selectAllAvailable');

    if (!availableBody) return;

    function updateSelectedCount() {
        const n = selectedBody ? selectedBody.querySelectorAll('tr[data-asset-id]').length : 0;
        if (selectedCount) selectedCount.textContent = n;
        if (noSelectionRow) noSelectionRow.style.display = n === 0 ? '' : 'none';
    }

    function addToSelected(checkbox) {
        if (!selectedBody) return;
        const id   = checkbox.value;
        const code = checkbox.dataset.assetCode ?? '';
        const name = checkbox.dataset.assetName ?? '';
        const cat  = checkbox.dataset.category ?? '';
        const val  = checkbox.dataset.assetValue ?? '-';
        if (selectedBody.querySelector(`tr[data-asset-id="${id}"]`)) return;
        const tr = document.createElement('tr');
        tr.dataset.assetId = id;
        tr.innerHTML = `
            <td class="asset-code">${code}</td>
            <td>${name}</td>
            <td>${cat}</td>
            <td class="right">${val}</td>
            <td class="center">
                <button class="table-action-icon table-action-delete" type="button" data-id="${id}"
                        aria-label="\u0e19\u0e33\u0e2d\u0e2d\u0e01" data-tooltip="\u0e19\u0e33\u0e2d\u0e2d\u0e01">
                    <svg aria-hidden="true"><use href="#icon-trash"></use></svg>
                </button>
            </td>`;
        tr.querySelector('.table-action-delete').addEventListener('click', () => removeFromSelected(id));
        selectedBody.appendChild(tr);
    }

    function removeFromSelected(id) {
        const row = selectedBody ? selectedBody.querySelector(`tr[data-asset-id="${id}"]`) : null;
        if (row) row.remove();
        const cb = availableBody.querySelector(`.asset-checkbox[value="${id}"]`);
        if (cb) cb.checked = false;
        if (selectAll) selectAll.checked = false;
        updateSelectedCount();
    }

    availableBody.addEventListener('change', (e) => {
        if (!e.target.classList.contains('asset-checkbox')) return;
        if (e.target.checked) addToSelected(e.target);
        else removeFromSelected(e.target.value);
        updateSelectedCount();
    });

    if (selectAll) {
        selectAll.addEventListener('change', () => {
            availableBody.querySelectorAll('.asset-checkbox').forEach((cb) => {
                if (cb.style.display !== 'none' && !cb.closest('tr').hidden) {
                    cb.checked = selectAll.checked;
                    if (selectAll.checked) addToSelected(cb);
                    else removeFromSelected(cb.value);
                }
            });
            updateSelectedCount();
        });
    }

    // Pre-tick already-checked checkboxes (for edit page)
    availableBody.querySelectorAll('.asset-checkbox:checked').forEach((cb) => addToSelected(cb));
    updateSelectedCount();
}

/* ── Asset AJAX search ── */
function initAssetSearch() {
    const searchBtn    = document.getElementById('assetSearchBtn');
    const searchInput  = document.getElementById('assetSearchInput');
    const searchField  = document.getElementById('assetSearchField');
    const availableBody = document.getElementById('availableAssetsBody');
    if (!searchBtn || !availableBody) return;

    const form      = document.getElementById('createAssignmentForm') || document.getElementById('editAssignmentForm');
    const searchUrl = form ? (form.dataset.assetSearchUrl ?? '') : '';
    if (!searchUrl) return;

    // Split out any pre-existing query params (e.g. exclude_assignment on edit page)
    const [baseSearchUrl, existingQuery] = searchUrl.split('?');
    const baseExtraParams = {};
    if (existingQuery) {
        new URLSearchParams(existingQuery).forEach((v, k) => { baseExtraParams[k] = v; });
    }

    searchBtn.addEventListener('click', doSearch);
    if (searchInput) {
        searchInput.addEventListener('keydown', (e) => { if (e.key === 'Enter') { e.preventDefault(); doSearch(); } });
        // Live table update as user types — debounced 300ms
        let liveTimer = null;
        let liveCtrl  = null;
        searchInput.addEventListener('input', () => {
            clearTimeout(liveTimer);
            liveTimer = setTimeout(() => {
                if (liveCtrl) liveCtrl.abort();
                liveCtrl = new AbortController();
                const q     = searchInput.value.trim();
                const field = searchField ? searchField.value : '';
                const params = new URLSearchParams({ q, field, ...baseExtraParams });
                fetch(`${baseSearchUrl}?${params}`, {
                    headers: { 'Accept': 'application/json' },
                    signal: liveCtrl.signal,
                })
                    .then((r) => r.json())
                    .then((json) => renderRows(json.data ?? []))
                    .catch(() => {});
            }, 300);
        });
    }
    // Re-search when search type changes
    if (searchField) {
        searchField.addEventListener('change', () => doSearch());
    }

    function doSearch() {
        const q = searchInput ? searchInput.value.trim() : '';
        const field = searchField ? searchField.value : '';
        const params = new URLSearchParams({ q, field, ...baseExtraParams });
        fetch(`${baseSearchUrl}?${params}`, { headers: { 'Accept': 'application/json' } })
            .then((r) => r.json())
            .then((json) => renderRows(json.data ?? []))
            .catch(() => {});
    }

    /* ── Autocomplete suggestion dropdown (SearchAutocomplete) ── */
    if (searchInput) {
        const esc = (s) => String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
        const ac = new SearchAutocomplete({
            inputEl:      searchInput,
            searchTypeEl: searchField,
            endpoint:     baseSearchUrl,
            extraParams:  baseExtraParams,
            minChars:     1,
            debounceMs:   300,
            maxResults:   15,
            renderItem: (item) =>
                `<span class="sac-code">${esc(item.asset_code ?? '')}</span>`
                + `<span class="sac-name">${esc(item.asset_name ?? '')}</span>`,
            onSelect: (item) => {
                const fld = searchField ? searchField.value : '';
                searchInput.value = fld === 'code'
                    ? (item.asset_code ?? '')
                    : (item.asset_name ?? item.asset_code ?? '');
                ac.close();
                doSearch();
            },
        });
        // Expand the sac-wrapper to fill the form-group
        const sacWrapper = searchInput.closest('.sac-wrapper');
        if (sacWrapper) sacWrapper.classList.add('sac-full');
    }

    function renderRows(rows) {
        availableBody.innerHTML = '';
        if (!rows.length) {
            availableBody.innerHTML = '<tr><td colspan="6" class="no-data">ไม่พบครุภัณฑ์</td></tr>';
            return;
        }
        // Collect currently selected IDs
        const selectedBody = document.getElementById('selectedAssetsBody');
        const selectedIds  = new Set(
            [...(selectedBody ? selectedBody.querySelectorAll('tr[data-asset-id]') : [])]
                .map((r) => r.dataset.assetId)
        );
        rows.forEach((a) => {
            const id       = String(a.id);
            const checked  = selectedIds.has(id) ? 'checked' : '';
            const valueFormatted = a.asset_value
                ? Number(a.asset_value).toLocaleString('th-TH', { minimumFractionDigits: 2 })
                : '-';
            const inspectDate = a.inspect_date_th ?? '-';
            const tr = document.createElement('tr');
            tr.dataset.assetId   = id;
            tr.dataset.assetCode = a.asset_code ?? '';
            tr.dataset.assetName = a.asset_name ?? '';
            tr.dataset.category  = a.category_name ?? '';
            tr.innerHTML = `
                <td class="col-check">
                    <input class="asset-checkbox" type="checkbox" value="${id}"
                        data-asset-code="${a.asset_code ?? ''}"
                        data-asset-name="${a.asset_name ?? ''}"
                        data-category="${a.category_name ?? ''}"
                        data-asset-value="${valueFormatted}"
                        data-inspect-date="${inspectDate}"
                        ${checked}>
                </td>
                <td>${a.asset_code ?? ''}</td>
                <td>${a.asset_name ?? ''}</td>
                <td>${a.category_name ?? ''}</td>
                <td class="right">${valueFormatted}</td>
                <td>${inspectDate}</td>`;
            availableBody.appendChild(tr);
        });
    }
}

/* ── Save confirmation modal ── */
function initSaveModal() {
    const overlay    = document.getElementById('saveConfirmOverlay');
    const saveBtn    = document.getElementById('saveAssignmentBtn');
    const cancelBtn  = document.getElementById('cancelSaveBtn');
    const confirmBtn = document.getElementById('confirmSaveBtn');
    const form       = document.getElementById('createAssignmentForm') || document.getElementById('editAssignmentForm');

    if (!overlay || !saveBtn || !form) return;

    saveBtn.addEventListener('click', () => {
        overlay.classList.add('is-visible');
        overlay.setAttribute('aria-hidden', 'false');
    });

    function closeModal() {
        overlay.classList.remove('is-visible');
        overlay.setAttribute('aria-hidden', 'true');
    }

    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

    if (confirmBtn) {
        confirmBtn.addEventListener('click', () => {
            // Collect selected asset IDs and inject hidden inputs before submit
            const selectedBody = document.getElementById('selectedAssetsBody');
            form.querySelectorAll('input[name="asset_ids[]"]').forEach((i) => i.remove());
            if (selectedBody) {
                selectedBody.querySelectorAll('tr[data-asset-id]').forEach((tr) => {
                    const input = document.createElement('input');
                    input.type  = 'hidden';
                    input.name  = 'asset_ids[]';
                    input.value = tr.dataset.assetId;
                    form.appendChild(input);
                });
            }
            form.submit();
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initializeSidebarGroups();
    preventDisabledMenuReload();
    initServerSearchableSelects();
    initAssetSelection();
    initAssetSearch();
    initSaveModal();
});
