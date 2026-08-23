import { initServerSearchableSelects } from '../../components/server-searchable-select.js';

const sidebarStorageKey = 'gujajob.sidebar.groupState';

function getSidebarState() {
    try {
        const storedState = localStorage.getItem(sidebarStorageKey);
        if (!storedState) return { material: true, asset: true };
        return { material: true, asset: true, ...JSON.parse(storedState) };
    } catch {
        return { material: true, asset: true };
    }
}

function saveSidebarState(state) {
    try { localStorage.setItem(sidebarStorageKey, JSON.stringify(state)); } catch {}
}

function applySidebarGroupState(groupElement, isOpen) {
    const toggleButton = groupElement.querySelector('.menu-group-toggle');
    groupElement.classList.toggle('is-collapsed', !isOpen);
    if (toggleButton) toggleButton.setAttribute('aria-expanded', String(isOpen));
}

function initializeSidebarGroups() {
    const state = getSidebarState();
    document.querySelectorAll('[data-sidebar-group]').forEach((groupElement) => {
        const groupName = groupElement.dataset.sidebarGroup;
        const toggleButton = groupElement.querySelector('.menu-group-toggle');
        const isOpen = state[groupName] !== false;
        applySidebarGroupState(groupElement, isOpen);
        if (!toggleButton) return;
        toggleButton.addEventListener('click', () => {
            const currentState = getSidebarState();
            const nextIsOpen = groupElement.classList.contains('is-collapsed');
            currentState[groupName] = nextIsOpen;
            applySidebarGroupState(groupElement, nextIsOpen);
            saveSidebarState(currentState);
        });
    });
}

function preventDisabledMenuReload() {
    document.querySelectorAll('.disabled-link').forEach((link) => {
        link.addEventListener('click', (e) => e.preventDefault());
    });
}

function initCancelModal() {
    const overlay   = document.getElementById('cancelAssignmentOverlay');
    const cancelBtn = document.getElementById('cancelCancelButton');
    const confirmBtn = document.getElementById('confirmCancelButton');
    if (!overlay) return;

    let pendingCancelUrl = null;

    const modalDesc = document.getElementById('cancelModalDesc');
    document.querySelectorAll('.js-cancel-btn').forEach((btn) => {
        btn.addEventListener('click', () => {
            pendingCancelUrl = btn.dataset.cancelUrl;
            if (modalDesc) {
                modalDesc.textContent = 'ต้องการยกเลิกการจัดสรรนี้ใช่หรือไม่';
            }
            overlay.classList.add('is-visible');
            overlay.setAttribute('aria-hidden', 'false');
        });
    });

    if (cancelBtn) cancelBtn.addEventListener('click', closeModal);
    overlay.addEventListener('click', (e) => { if (e.target === overlay) closeModal(); });
    document.addEventListener('keydown', (e) => { if (e.key === 'Escape') closeModal(); });

    function closeModal() {
        overlay.classList.remove('is-visible');
        overlay.setAttribute('aria-hidden', 'true');
        pendingCancelUrl = null;
    }

    if (confirmBtn) {
        confirmBtn.addEventListener('click', async () => {
            if (!pendingCancelUrl) return;
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'กำลังยกเลิก...';
            try {
                const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';
                const resp = await fetch(pendingCancelUrl, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                });
                if (resp.ok) {
                    window.location.reload();
                } else {
                    const json = await resp.json().catch(() => ({}));
                    alert(json.message ?? 'ไม่สามารถยกเลิกการจัดสรรได้');
                    confirmBtn.disabled = false;
                    confirmBtn.textContent = 'ยืนยันยกเลิกการจัดสรร';
                    closeModal();
                }
            } catch {
                alert('เกิดข้อผิดพลาด กรุณาลองใหม่');
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'ยืนยันยกเลิกการจัดสรร';
                closeModal();
            }
        });
    }
}

document.addEventListener('DOMContentLoaded', () => {
    initializeSidebarGroups();
    preventDisabledMenuReload();
    initServerSearchableSelects();
    initCancelModal();
});
