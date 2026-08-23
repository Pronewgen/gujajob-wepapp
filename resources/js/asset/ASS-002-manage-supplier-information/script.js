import { SearchAutocomplete } from '../../components/search-autocomplete.js';
import { initSearchableSelects, resetSearchableSelect } from '../../components/searchable-select.js';

/* ASS-002 — Manage Supplier Information */

document.addEventListener('DOMContentLoaded', () => {
    initializeDeleteModal();
    initializeSupplierCreatePopup();
    initializeSupplierEditPopup();
    initializeLocationSearchableSelects();
    initializeSupplierAutocomplete();
});

/* ---------- Delete modal (index page) ---------------------------------- */
function initializeDeleteModal() {
    const overlay   = document.getElementById('deleteSupplierOverlay');
    const form      = document.getElementById('deleteSupplierForm');
    const msgEl     = document.getElementById('deleteSupplierMessage');
    const cancelBtn = document.getElementById('cancelDeleteSupplierButton');
    const confirmBtn = document.getElementById('confirmDeleteSupplierButton');

    if (!overlay || !form) return;

    document.querySelectorAll('[data-delete-url]').forEach(btn => {
        btn.addEventListener('click', () => {
            const url  = btn.dataset.deleteUrl;
            const name = btn.dataset.supplierName ?? '';
            form.action = url;
            if (msgEl) {
                msgEl.innerHTML = `คุณแน่ใจหรือไม่ว่าต้องการลบข้อมูล<br><strong>${escapeHtml(name)}</strong><br>การดำเนินการนี้ไม่สามารถเรียกคืนได้`;
            }
            overlay.classList.add('is-visible');
            overlay.setAttribute('aria-hidden', 'false');
        });
    });

    cancelBtn?.addEventListener('click', () => closeOverlay(overlay));
    overlay.addEventListener('click', e => { if (e.target === overlay) closeOverlay(overlay); });

    confirmBtn?.addEventListener('click', () => form.submit());
}

/* ---------- Create modal (green) --------------------------------------- */
function initializeSupplierCreatePopup() {
    const saveBtn   = document.getElementById('saveSupplierButton');
    const overlay   = document.getElementById('saveSupplierOverlay');
    const cancelBtn = document.getElementById('cancelSaveSupplierButton');
    const confirmBtn = document.getElementById('confirmSaveSupplierButton');
    const form      = document.getElementById('supplierCreateForm');

    if (!saveBtn || !overlay || !form) return;

    saveBtn.addEventListener('click', () => {
        if (!form.checkValidity()) { form.reportValidity(); return; }
        overlay.classList.add('is-visible');
        overlay.setAttribute('aria-hidden', 'false');
    });

    cancelBtn?.addEventListener('click', () => closeOverlay(overlay));
    overlay.addEventListener('click', e => { if (e.target === overlay) closeOverlay(overlay); });

    confirmBtn?.addEventListener('click', () => form.submit());
}

/* ---------- Edit modal (yellow) ---------------------------------------- */
function initializeSupplierEditPopup() {
    const saveBtn   = document.getElementById('saveSupplierButton');
    const overlay   = document.getElementById('editSupplierOverlay');
    const cancelBtn = document.getElementById('cancelEditSupplierButton');
    const confirmBtn = document.getElementById('confirmEditSupplierButton');
    const form      = document.getElementById('supplierEditForm');

    if (!saveBtn || !overlay || !form) return;

    saveBtn.addEventListener('click', () => {
        if (!form.checkValidity()) { form.reportValidity(); return; }
        overlay.classList.add('is-visible');
        overlay.setAttribute('aria-hidden', 'false');
    });

    cancelBtn?.addEventListener('click', () => closeOverlay(overlay));
    overlay.addEventListener('click', e => { if (e.target === overlay) closeOverlay(overlay); });

    confirmBtn?.addEventListener('click', () => form.submit());
}

/* ---------- Searchable province / amphur / tambon ---------------------- */
function initializeLocationSearchableSelects() {
    const provHidden = document.getElementById('dealer_prov_id');
    const ampHidden  = document.getElementById('dealer_amp_id');
    const tamHidden  = document.getElementById('dealer_tam_id');
    const zipInput   = document.getElementById('postalCode');

    if (!provHidden) return; // not on create/edit page

    const urls = window._locationUrls ?? { amphurs: '/api/amphurs', tambons: '/api/tambons' };

    // Initialise all [data-searchable-select] wrappers on the page
    initSearchableSelects(document);

    function getWrapper(inputId) {
        return document.getElementById(inputId)?.closest('[data-searchable-select]');
    }

    function enableWrapper(inputId, placeholder) {
        const wrapper = getWrapper(inputId);
        if (!wrapper) return;
        wrapper.classList.remove('guja-autocomplete--disabled');
        const vis = wrapper.querySelector('.guja-autocomplete__input');
        if (vis) {
            vis.removeAttribute('disabled');
            if (placeholder) vis.placeholder = placeholder;
        }
    }

    function disableWrapper(inputId, placeholder) {
        const wrapper = getWrapper(inputId);
        if (!wrapper) return;
        wrapper.classList.add('guja-autocomplete--disabled');
        const vis = wrapper.querySelector('.guja-autocomplete__input');
        if (vis) {
            vis.setAttribute('disabled', '');
            if (placeholder) vis.placeholder = placeholder;
        }
    }

    // Province change → reload amphurs, reset tambon
    provHidden.addEventListener('change', () => {
        const provId = provHidden.value;

        resetSearchableSelect('dealer_amp_id', [], true);
        resetSearchableSelect('dealer_tam_id', [], true);
        disableWrapper('dealer_amp_id', 'เลือกจังหวัดก่อน');
        disableWrapper('dealer_tam_id', 'เลือกอำเภอก่อน');
        if (zipInput) zipInput.value = '';
        window._tambonZipcodes = {};

        if (!provId) return;

        fetch(`${urls.amphurs}?province_id=${encodeURIComponent(provId)}`)
            .then(r => r.json())
            .then(list => {
                const opts = list.map(a => ({ value: String(a.id), label: a.name }));
                resetSearchableSelect('dealer_amp_id', opts, true);
                if (opts.length > 0) {
                    enableWrapper('dealer_amp_id', 'พิมพ์ชื่ออำเภอเพื่อค้นหา');
                }
            })
            .catch(console.error);
    });

    // Amphur change → reload tambons
    ampHidden.addEventListener('change', () => {
        const ampId = ampHidden.value;

        resetSearchableSelect('dealer_tam_id', [], true);
        disableWrapper('dealer_tam_id', 'เลือกอำเภอก่อน');
        if (zipInput) zipInput.value = '';
        window._tambonZipcodes = {};

        if (!ampId) return;

        fetch(`${urls.tambons}?amphur_id=${encodeURIComponent(ampId)}`)
            .then(r => r.json())
            .then(list => {
                const opts = list.map(t => ({ value: String(t.id), label: t.name }));
                // Build zipcode lookup from AJAX response
                const zipcodes = {};
                list.forEach(t => { zipcodes[t.id] = t.zipcode ?? ''; });
                window._tambonZipcodes = zipcodes;

                resetSearchableSelect('dealer_tam_id', opts, true);
                if (opts.length > 0) {
                    enableWrapper('dealer_tam_id', 'พิมพ์ชื่อตำบลเพื่อค้นหา');
                }
            })
            .catch(console.error);
    });

    // Tambon change → auto-fill zipcode
    tamHidden.addEventListener('change', () => {
        const tamId = tamHidden.value;
        if (zipInput && tamId && window._tambonZipcodes) {
            const zip = window._tambonZipcodes[tamId] ?? window._tambonZipcodes[String(tamId)] ?? '';
            if (zip) zipInput.value = zip;
        }
    });
}

/* ---------- Helpers ----------------------------------------------------- */
function closeOverlay(overlay) {
    overlay.classList.remove('is-visible');
    overlay.setAttribute('aria-hidden', 'true');
}

function escapeHtml(str) {
    return str.replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

/* ---------- Autocomplete for ASS-002 search bar ------------------------ */
function initializeSupplierAutocomplete() {
    const searchInput = document.getElementById('supplierSearchInput');
    const searchType  = document.getElementById('supplierSearchType');
    const form        = document.getElementById('supplierSearchForm');

    if (!searchInput) return;

    const ac = new SearchAutocomplete({
        inputEl:      searchInput,
        searchTypeEl: searchType,
        endpoint:     '/search/suggestions',
        extraParams:  { entity: 'dealer' },
        minChars:     1,
        debounceMs:   300,
        maxResults:   15,
        onSelect: (item) => {
            searchInput.value = item.code;
            ac.close();
            if (form) form.submit();
        },
    });

    // Clear suggestions immediately when search type changes
    if (searchType) {
        searchType.addEventListener('change', () => {
            ac.close();
        });
    }

    const wrapper = searchInput.closest('.sac-wrapper') ?? searchInput.parentElement;
    if (wrapper && wrapper.classList.contains('sac-wrapper')) {
        wrapper.classList.add('sac-full');
    }
}

