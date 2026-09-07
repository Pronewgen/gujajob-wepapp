class SearchableDropdown {
    constructor(inputSelector, suggestionsSelector, hiddenInputSelector, apiUrl, options = {}) {
        this.input = document.querySelector(inputSelector);
        this.suggestionsContainer = document.querySelector(suggestionsSelector);
        this.hiddenInput = document.querySelector(hiddenInputSelector);
        this.apiUrl = apiUrl;
        this.options = options;
        
        if (!this.input) return;
        
        this.debounceTimer = null;
        this.requestId = 0;
        this.init();
    }
    
    init() {
        this.input.addEventListener('input', () => this.handleInput());
        this.input.addEventListener('focus', () => {
            if (this.options.isBlocked?.()) {
                return;
            }

            if (this.input.value.length > 0) {
                this.fetchSuggestions(this.input.value.trim());
            } else if (this.options.loadOnFocus) {
                this.fetchSuggestions('');
            }
        });
        
        document.addEventListener('click', (e) => {
            if (!e.target.closest('[class*="searchable"]')) {
                this.suggestionsContainer.style.display = 'none';
            }
        });
    }
    
    handleInput() {
        const query = this.input.value.trim();
        
        clearTimeout(this.debounceTimer);
        
        // Typing invalidates any previously picked item.
        this.hiddenInput.value = '';
        this.options.onChange?.();
        
        if (this.options.isBlocked?.()) {
            this.suggestionsContainer.style.display = 'none';
            return;
        }

        if (query.length === 0 && !this.options.loadOnFocus) {
            this.suggestionsContainer.style.display = 'none';
            return;
        }
        
        this.debounceTimer = setTimeout(() => {
            this.fetchSuggestions(query);
        }, 300);
    }
    
    fetchSuggestions(query) {
        const requestId = ++this.requestId;
        const params = new URLSearchParams({ q: query, ...(this.options.extraParams?.() ?? {}) });
        
        fetch(`${this.apiUrl}?${params.toString()}`)
            .then(response => response.json())
            .then(payload => {
                if (requestId === this.requestId) {
                    this.renderSuggestions(Array.isArray(payload) ? payload : payload?.data);
                }
            })
            .catch(error => {
                if (requestId !== this.requestId) return;
                console.error('Error fetching suggestions:', error);
                this.suggestionsContainer.style.display = 'none';
            });
    }
    
    renderSuggestions(data) {
        this.suggestionsContainer.innerHTML = '';
        
        if (!Array.isArray(data) || data.length === 0) {
            if (this.options.emptyMessage) {
                const emptyItem = document.createElement('div');
                emptyItem.className = 'suggestion-item suggestion-empty';
                emptyItem.textContent = this.options.emptyMessage;
                this.suggestionsContainer.appendChild(emptyItem);
                this.suggestionsContainer.style.display = 'block';
            } else {
                this.suggestionsContainer.style.display = 'none';
            }
            return;
        }
        
        data.forEach(item => {
            const suggestionItem = document.createElement('div');
            suggestionItem.className = 'suggestion-item';
            suggestionItem.textContent = this.formatSuggestion(item);
            suggestionItem.addEventListener('click', () => this.selectItem(item));
            this.suggestionsContainer.appendChild(suggestionItem);
        });
        
        this.suggestionsContainer.style.display = 'block';
    }
    
    formatSuggestion(item) {
        if (item.label) {
            return item.label;
        } else if (item.code && item.name) {
            return `${item.code} - ${item.name}`;
        } else if (item.name) {
            return item.name;
        }
        return JSON.stringify(item);
    }
    
    selectItem(item) {
        this.input.value = this.formatSuggestion(item);
        this.hiddenInput.value = item.id;
        this.suggestionsContainer.style.display = 'none';
        this.options.onSelect?.(item);
    }
    
    clear() {
        if (!this.input) return;
        clearTimeout(this.debounceTimer);
        this.requestId++;
        this.input.value = '';
        this.hiddenInput.value = '';
        this.suggestionsContainer.innerHTML = '';
        this.suggestionsContainer.style.display = 'none';
    }
}

// Report type selection
function initReportTypeSelection() {
    const registerBtn = document.querySelector('[data-report-type="register"]');
    const ledgerBtn = document.querySelector('[data-report-type="ledger"]');
    const registerConditions = document.getElementById('registerConditions');
    const ledgerConditions = document.getElementById('ledgerConditions');
    
    registerBtn?.addEventListener('click', () => {
        registerBtn.classList.add('active');
        ledgerBtn.classList.remove('active');
        registerConditions.style.display = 'block';
        ledgerConditions.style.display = 'none';
        clearLedgerFields();
    });
    
    ledgerBtn?.addEventListener('click', () => {
        ledgerBtn.classList.add('active');
        registerBtn.classList.remove('active');
        ledgerConditions.style.display = 'block';
        registerConditions.style.display = 'none';
        clearRegisterFields();
    });
}

// Clear field functions
function clearRegisterFields() {
    dropdowns.category?.clear();
    dropdowns.asset?.clear();
    syncAssetFieldState();
}

function clearLedgerFields() {
    document.getElementById('fiscalYear').value = '';
    dropdowns.org?.clear();
    dropdowns.subOrg?.clear();
}

// The asset-code field only searches once a category has been picked.
function syncAssetFieldState() {
    const hasCategory = Boolean(document.getElementById('categoryId')?.value);
    const assetInput = document.getElementById('assetSearch');
    const hint = document.getElementById('assetSearchHint');
    
    if (assetInput) assetInput.disabled = !hasCategory;
    if (hint) hint.style.display = hasCategory ? 'none' : '';
}

// Preview button
function initPreviewButton() {
    const previewBtn = document.getElementById('previewReportButton');
    
    previewBtn?.addEventListener('click', () => {
        const registerConditions = document.getElementById('registerConditions');
        const isRegisterType = registerConditions.style.display !== 'none';
        
        if (isRegisterType) {
            // Type 1: Asset Register
            const categoryId = document.getElementById('categoryId').value;
            const assetId = document.getElementById('assetId').value;
            
            let url = '/asset/ASS-009-print-asset-report/report-register';
            const params = new URLSearchParams();
            if (categoryId) params.append('category_id', categoryId);
            if (assetId) params.append('asset_id', assetId);
            if (params.toString()) url += '?' + params.toString();
            
            window.location.href = url;
        } else {
            // Type 2: Asset Ledger
            const fiscalYear = document.getElementById('fiscalYear').value;
            const orgId = document.getElementById('orgId').value;
            const subOrgId = document.getElementById('subOrgId').value;
            
            if (!fiscalYear) {
                alert('กรุณาระบุปีงบประมาณ');
                return;
            }
            
            let url = '/asset/ASS-009-print-asset-report/report-ledger';
            const params = new URLSearchParams();
            params.append('fiscal_year', fiscalYear);
            if (orgId) params.append('org_id', orgId);
            if (subOrgId) params.append('sub_org_id', subOrgId);
            
            window.location.href = url + '?' + params.toString();
        }
    });
}

// Initialize on page load
const dropdowns = {};

document.addEventListener('DOMContentLoaded', () => {
    initReportTypeSelection();
    
    // Initialize searchable dropdowns
    const resetAsset = () => {
        dropdowns.asset?.clear();
        syncAssetFieldState();
    };
    
    dropdowns.category = new SearchableDropdown('#categorySearch', '#categorySuggestions', '#categoryId', '/api/asset/categories/search', {
        onSelect: resetAsset,
        onChange: resetAsset,
    });
    dropdowns.asset = new SearchableDropdown('#assetSearch', '#assetSuggestions', '#assetId', '/api/asset/search', {
        isBlocked: () => !document.getElementById('categoryId').value,
        extraParams: () => ({ category_id: document.getElementById('categoryId').value }),
        emptyMessage: 'ไม่พบรหัสครุภัณฑ์ในประเภทที่เลือก',
        loadOnFocus: true,
    });
    dropdowns.org = new SearchableDropdown('#orgSearch', '#orgSuggestions', '#orgId', '/api/asset/organizations/search', {
        onSelect: () => dropdowns.subOrg?.clear(),
        onChange: () => dropdowns.subOrg?.clear(),
    });
    dropdowns.subOrg = new SearchableDropdown('#subOrgSearch', '#subOrgSuggestions', '#subOrgId', '/api/asset/sub-organizations/search', {
        extraParams: () => ({ parent_org_id: document.getElementById('orgId').value }),
    });
    
    syncAssetFieldState();
    
    // Initialize buttons
    initPreviewButton();
});
