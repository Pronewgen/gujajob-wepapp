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

document.addEventListener('DOMContentLoaded', () => {
    initializeSidebarGroups();
    preventDisabledMenuReload();
});
