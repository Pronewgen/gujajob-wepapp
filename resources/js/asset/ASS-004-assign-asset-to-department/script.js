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

function initializeAssignmentSearch() {
    const departmentSelect = document.getElementById('assignDepartment');
    const groupSelect = document.getElementById('assignGroup');
    const assignerSelect = document.getElementById('assigner');
    const searchButton = document.getElementById('searchAssignButton');
    const createButton = document.getElementById('createAssignButton');
    const tableBody = document.getElementById('assignmentTableBody');
    const resultText = document.getElementById('resultText');

    if (!departmentSelect || !groupSelect || !assignerSelect || !searchButton || !tableBody || !resultText) {
        return;
    }

    const originalRows = Array.from(tableBody.querySelectorAll('tr'));

    function removeNoDataRow() {
        const oldNoDataRow = tableBody.querySelector('.no-data-row');

        if (oldNoDataRow) {
            oldNoDataRow.remove();
        }
    }

    function updateResult(visibleCount) {
        if (visibleCount === 0) {
            resultText.textContent = 'ไม่พบข้อมูลการจัดสรรครุภัณฑ์';
            return;
        }

        resultText.textContent = `แสดง 0 ถึง ${visibleCount} รายการจัดสรร : ครุภัณฑ์ที่จัดสรรแล้วรวม ${visibleCount} รายการ`;
    }

    function searchRecords() {
        const selectedDepartment = departmentSelect.value;
        const selectedGroup = groupSelect.value;
        const selectedAssigner = assignerSelect.value;
        let visibleCount = 0;

        removeNoDataRow();

        originalRows.forEach((row) => {
            const department = row.dataset.department || '';
            const group = row.dataset.group || '';
            const assigner = row.dataset.assigner || '';

            const matchDepartment = selectedDepartment === 'all' || selectedDepartment === department;
            const matchGroup = selectedGroup === 'all' || selectedGroup === group;
            const matchAssigner = selectedAssigner === 'all' || selectedAssigner === assigner;
            const isVisible = matchDepartment && matchGroup && matchAssigner;

            row.style.display = isVisible ? '' : 'none';

            if (isVisible) {
                visibleCount += 1;
            }
        });

        if (visibleCount === 0) {
            const noDataRow = document.createElement('tr');
            noDataRow.className = 'no-data-row';
            noDataRow.innerHTML = '<td class="no-data" colspan="7">ไม่พบข้อมูลที่ค้นหา</td>';
            tableBody.appendChild(noDataRow);
        }

        updateResult(visibleCount);
    }

    searchButton.addEventListener('click', searchRecords);

    departmentSelect.addEventListener('change', searchRecords);
    groupSelect.addEventListener('change', searchRecords);
    assignerSelect.addEventListener('change', searchRecords);

    if (createButton) {
        createButton.addEventListener('click', () => {
            const createUrl = createButton.dataset.createUrl;

            if (createUrl) {
                window.location.href = createUrl;
                return;
            }

            alert('ไม่พบเส้นทางหน้าบันทึกการจัดสรรใหม่');
        });
    }

    searchRecords();
}

document.addEventListener('DOMContentLoaded', () => {
    initializeSidebarGroups();
    preventDisabledMenuReload();
    initializeAssignmentSearch();
});
