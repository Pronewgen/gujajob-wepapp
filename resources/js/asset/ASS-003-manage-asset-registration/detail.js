const sidebarStorageKey = 'gujajob.sidebar.groupState';

function getSidebarState() {
    try {
        const storedState = localStorage.getItem(sidebarStorageKey);

        if (!storedState) {
            return {
                material: true,
                asset: true,
            };
        }

        return {
            material: true,
            asset: true,
            ...JSON.parse(storedState),
        };
    } catch (error) {
        return {
            material: true,
            asset: true,
        };
    }
}

function saveSidebarState(state) {
    try {
        localStorage.setItem(sidebarStorageKey, JSON.stringify(state));
    } catch (error) {
        console.warn('Cannot save sidebar state.');
    }
}

function applySidebarGroupState(groupElement, isOpen) {
    const toggleButton = groupElement.querySelector('.menu-group-toggle');

    groupElement.classList.toggle('is-collapsed', !isOpen);

    if (toggleButton) {
        toggleButton.setAttribute('aria-expanded', String(isOpen));
    }
}

function initializeSidebarGroups() {
    const state = getSidebarState();
    const groups = document.querySelectorAll('[data-sidebar-group]');

    groups.forEach((groupElement) => {
        const groupName = groupElement.dataset.sidebarGroup;
        const toggleButton = groupElement.querySelector('.menu-group-toggle');
        const isOpen = state[groupName] !== false;

        applySidebarGroupState(groupElement, isOpen);

        if (!toggleButton) {
            return;
        }

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
        link.addEventListener('click', (event) => {
            event.preventDefault();
        });
    });
}

function openOverlay(overlay) {
    if (!overlay) return;

    overlay.classList.add('is-visible');
    overlay.setAttribute('aria-hidden', 'false');
}

function closeOverlay(overlay) {
    if (!overlay) return;

    overlay.classList.remove('is-visible');
    overlay.setAttribute('aria-hidden', 'true');
}

function bindOverlay(openButtonId, overlayId, cancelButtonId, confirmButtonId) {
    const openButton = document.getElementById(openButtonId);
    const overlay = document.getElementById(overlayId);
    const cancelButton = document.getElementById(cancelButtonId);
    const confirmButton = document.getElementById(confirmButtonId);

    if (!openButton || !overlay || !cancelButton || !confirmButton) {
        return;
    }

    openButton.addEventListener('click', () => openOverlay(overlay));
    cancelButton.addEventListener('click', () => closeOverlay(overlay));

    overlay.addEventListener('click', (event) => {
        if (event.target === overlay) {
            closeOverlay(overlay);
        }
    });

    confirmButton.addEventListener('click', () => {
        const redirectUrl = confirmButton.dataset.redirectUrl;

        closeOverlay(overlay);

        if (redirectUrl) {
            window.location.href = redirectUrl;
        }
    });
}

function initializeDetailPopups() {
    bindOverlay(
        'openEditAssetRegistrationPopup',
        'editAssetRegistrationOverlay',
        'cancelEditAssetRegistrationButton',
        'confirmEditAssetRegistrationButton'
    );

    bindOverlay(
        'openDeleteAssetRegistrationPopup',
        'deleteAssetRegistrationOverlay',
        'cancelDeleteAssetRegistrationButton',
        'confirmDeleteAssetRegistrationButton'
    );
}

document.addEventListener('keydown', (event) => {
    if (event.key !== 'Escape') {
        return;
    }

    document.querySelectorAll('.confirm-overlay.is-visible').forEach((overlay) => {
        closeOverlay(overlay);
    });
});

document.addEventListener('DOMContentLoaded', () => {
    initializeSidebarGroups();
    preventDisabledMenuReload();
    initializeDetailPopups();
});
