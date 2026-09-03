<?php
$pageTitle = "Department Management";
$activeMenu = "departments";
?>

<style>
    @keyframes slideUp {
        from { opacity: 0; transform: translateY(16px); }
        to { opacity: 1; transform: translateY(0); }
    }
    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .toast-container {
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 9999;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .toast {
        animation: slideUp 0.3s ease-out;
        min-width: 280px;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 12px 16px;
        border-radius: 10px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        font-size: 14px;
        font-weight: 500;
        background: #fff;
    }

    .toast.success { background:#ecfdf5; border-left:4px solid #10b981; color:#065f46; }
    .toast.error { background:#fef2f2; border-left:4px solid #ef4444; color:#7f1d1d; }

    .modal-backdrop { animation: fadeIn 0.2s ease-out; }
    .modal-box { animation: slideUp 0.25s ease-out; }
</style>

<div class="w-full h-full">
    <div class="p-2 space-y-2">
        <?php
            $title = 'Department Management';
            $icon = 'mdi:office-building-outline text-emerald-500';
            ob_start();
        ?>
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-bold normal-case tracking-wider bg-emerald-50 text-emerald-700 px-2 py-0.5 rounded-md" id="departmentCount">0 Departments</span>
                <?php
                    $label = 'Add Department'; $type = 'success'; $size = 'xs'; $icon = 'mdi:plus'; $attr = 'onclick="openAddModal()"'; $id = null;
                    include 'component/button.php';
                    $label = null; $attr = null; $icon = null;
                ?>
            </div>
        <?php
            $headerRight = ob_get_clean();
            ob_start();
        ?>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 mb-3">
                <div class="bg-white rounded-lg p-2 border border-slate-100 shadow-sm">
                    <p class="text-[9px] font-bold text-slate-400 uppercase">Total Departments</p>
                    <p class="text-lg font-bold text-slate-900" id="statTotalDepartments">0</p>
                </div>
                <div class="bg-white rounded-lg p-2 border border-slate-100 shadow-sm">
                    <p class="text-[9px] font-bold text-slate-400 uppercase">Filtered</p>
                    <p class="text-lg font-bold text-slate-900" id="statFilteredDepartments">0</p>
                </div>
            </div>
            <div class="flex gap-2">
                <div class="flex-1">
                    <?php
                        $id = 'searchInput'; $placeholder = 'Search departments...'; $icon = 'mdi:magnify'; $label = null;
                        include 'component/input.php';
                        $id = null; $icon = null;
                    ?>
                </div>
            </div>
        <?php
            $content = ob_get_clean();
            include 'component/card.php';
            $title = null; $icon = null; $headerRight = null; $content = null;
        ?>

        <?php
            ob_start();
        ?>
            <div class="sticky-table-wrapper overflow-x-auto">
                <table class="w-full text-left text-[11px]">
                    <thead class="bg-slate-900 text-white sticky top-0 z-10">
                        <tr>
                            <th class="px-3 py-2 font-bold tracking-wider">ID</th>
                            <th class="px-3 py-2 font-bold tracking-wider">Name</th>
                            <th class="px-3 py-2 font-bold tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="departmentTableBody" class="divide-y divide-slate-100 bg-white">
                        <tr>
                            <td colspan="3" class="px-3 py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="iconify text-2xl animate-spin opacity-50" data-icon="mdi:loading"></span>
                                    <p class="text-[10px] font-semibold tracking-widest">Loading...</p>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        <?php
            $content = ob_get_clean();
            $padding = false;
            include 'component/card.php';
            $padding = true;
            $content = null;
        ?>
    </div>
</div>

<!-- Department Modal -->
<div id="departmentModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/40 backdrop-blur-[2px] p-4 modal-backdrop">
    <div class="w-full max-w-md">
        <?php
            ob_start();
        ?>
            <form id="departmentForm" class="space-y-3">
                <div class="flex flex-col">
                    <h2 id="departmentModalTitle" class="text-sm font-bold text-slate-800">Add Department</h2>
                    <p class="text-[10px] text-slate-500 font-medium">Create or update department names.</p>
                </div>
                <input type="hidden" id="departmentId">
                <div class="flex flex-col gap-1">
                    <label for="departmentName" class="text-[10px] font-black text-slate-500 uppercase tracking-wider">
                        Department Name <span class="text-rose-500">*</span>
                    </label>
                    <input
                        type="text"
                        id="departmentName"
                        class="w-full border border-slate-200 rounded-lg py-2 px-3 text-xs focus:outline-none focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all bg-white"
                        placeholder="IT Department"
                        maxlength="100"
                        required
                    >
                </div>
                <div class="flex justify-end gap-2">
                    <?php
                        $label = 'Cancel'; $type = 'secondary'; $size = 'sm'; $attr = 'type="button" onclick="closeDepartmentModal()"';
                        include 'component/button.php';
                        $label = 'Save Department'; $type = 'success'; $size = 'sm'; $icon = 'mdi:content-save-outline'; $attr = 'type="submit"'; $id = 'saveDepartmentBtn';
                        include 'component/button.php';
                        $label = null; $attr = null; $icon = null; $id = null;
                    ?>
                </div>
            </form>
        <?php
            $content = ob_get_clean();
            ob_start();
        ?>
            <button type="button" onclick="closeDepartmentModal()" class="w-6 h-6 flex items-center justify-center rounded-full bg-slate-50 text-slate-500 hover:text-slate-900 transition-colors text-xs">✕</button>
        <?php
            $headerRight = ob_get_clean();
            $title = null;
            $padding = true;
            include 'component/card.php';
            $title = null; $content = null; $headerRight = null;
        ?>
    </div>
</div>

<!-- Delete Modal -->
<div id="deleteModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/40 backdrop-blur-[2px] p-4 modal-backdrop">
    <div class="w-full max-w-sm">
        <?php
            $title = 'Delete Department';
            ob_start();
        ?>
            <div class="text-center py-2">
                <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-3 border border-rose-100">
                    <span class="iconify text-2xl" data-icon="mdi:alert-outline"></span>
                </div>
                <p class="text-[11px] text-slate-600 font-medium">
                    Are you sure you want to delete <strong id="deleteDepartmentName" class="text-slate-900"></strong>?
                </p>
            </div>
        <?php
            $content = ob_get_clean();
            ob_start();
        ?>
            <div class="grid grid-cols-2 gap-2">
                <?php
                    $label = 'Cancel'; $type = 'secondary'; $size = 'sm'; $attr = 'type="button" onclick="closeDeleteModal()"';
                    include 'component/button.php';
                    $label = 'Delete'; $type = 'danger'; $size = 'sm'; $icon = 'mdi:delete-outline'; $attr = 'type="button" onclick="confirmDelete()"';
                    include 'component/button.php';
                    $label = null; $attr = null; $icon = null;
                ?>
            </div>
        <?php
            $footer = ob_get_clean();
            $padding = true;
            include 'component/card.php';
            $title = null; $content = null; $footer = null;
        ?>
    </div>
</div>

<div id="toastContainer" class="toast-container"></div>

<script>
const departmentTableBody = document.getElementById('departmentTableBody');
const departmentCount = document.getElementById('departmentCount');
const statTotalDepartments = document.getElementById('statTotalDepartments');
const statFilteredDepartments = document.getElementById('statFilteredDepartments');
const searchInput = document.getElementById('searchInput');
const departmentModal = document.getElementById('departmentModal');
const departmentForm = document.getElementById('departmentForm');
const departmentId = document.getElementById('departmentId');
const departmentName = document.getElementById('departmentName');
const saveDepartmentBtn = document.getElementById('saveDepartmentBtn');
const deleteModal = document.getElementById('deleteModal');
const deleteDepartmentName = document.getElementById('deleteDepartmentName');

let departments = [];
let deleteDepartmentId = null;

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function encodeDataAttr(value) {
    return encodeURIComponent(String(value ?? ''));
}

function decodeDataAttr(value) {
    if (value === undefined || value === null || value === '') return '';
    try {
        return decodeURIComponent(value);
    } catch (err) {
        return String(value);
    }
}

function showToast(message, type = 'success') {
    const container = document.getElementById('toastContainer');
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(12px)';
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

function normalizeDepartment(raw) {
    return {
        id: raw?.id ?? '',
        name: raw?.name ?? '',
    };
}

function renderDepartments(list) {
    const rows = list || [];
    statFilteredDepartments.textContent = String(rows.length);

    if (!rows.length) {
        departmentTableBody.innerHTML = `
            <tr>
                <td colspan="3" class="px-3 py-12 text-center text-slate-400">
                    <div class="flex flex-col items-center justify-center gap-2">
                        <span class="iconify text-2xl opacity-30" data-icon="mdi:office-building-off"></span>
                        <p class="text-[10px] font-semibold tracking-widest">No departments found</p>
                    </div>
                </td>
            </tr>
        `;
        if (window.Iconify) Iconify.scan();
        return;
    }

    departmentTableBody.innerHTML = rows.map(department => `
        <tr class="hover:bg-slate-50 transition-colors group">
            <td class="px-3 py-2 text-[10px] font-semibold text-slate-500">${escapeHtml(department.id)}</td>
            <td class="px-3 py-2">
                <div class="font-semibold text-slate-800 group-hover:text-emerald-600 transition-colors">${escapeHtml(department.name)}</div>
            </td>
            <td class="px-3 py-2">
                <div class="flex justify-center gap-1">
                    <button
                        type="button"
                        class="js-edit-department inline-flex items-center gap-0.5 px-2 py-0.5 bg-white border border-slate-200 text-slate-600 rounded-md hover:bg-emerald-50 hover:border-emerald-200 hover:text-emerald-600 text-[9px] font-bold normal-case tracking-wider transition-all shadow-sm active:scale-95"
                        data-id="${encodeDataAttr(department.id)}"
                        data-name="${encodeDataAttr(department.name)}"
                    >
                        <span class="iconify" data-icon="mdi:pencil"></span> Edit
                    </button>
                    <button
                        type="button"
                        class="js-delete-department inline-flex items-center gap-0.5 px-2 py-0.5 bg-white border border-slate-200 text-slate-600 rounded-md hover:bg-rose-50 hover:border-rose-200 hover:text-rose-600 text-[9px] font-bold normal-case tracking-wider transition-all shadow-sm active:scale-95"
                        data-id="${encodeDataAttr(department.id)}"
                        data-name="${encodeDataAttr(department.name)}"
                    >
                        <span class="iconify" data-icon="mdi:trash-can-outline"></span> Del
                    </button>
                </div>
            </td>
        </tr>
    `).join('');

    if (window.Iconify) Iconify.scan();
}

function applyFilters() {
    const search = searchInput.value.trim().toLowerCase();
    const filtered = departments.filter(dept => !search || dept.name.toLowerCase().includes(search));
    renderDepartments(filtered);
}

async function loadDepartments() {
    try {
        departmentTableBody.innerHTML = `
            <tr>
                <td colspan="3" class="px-3 py-12 text-center text-slate-400">
                    <div class="flex flex-col items-center justify-center gap-2">
                        <span class="iconify text-2xl animate-spin opacity-50" data-icon="mdi:loading"></span>
                        <p class="text-[10px] font-semibold tracking-widest">Loading...</p>
                    </div>
                </td>
            </tr>
        `;

        const res = await fetch('/api/departments');
        const json = await res.json();

        if (!json.success) {
            throw new Error(json.message || 'Failed to load departments');
        }

        departments = Array.isArray(json.data) ? json.data.map(normalizeDepartment) : [];
        departmentCount.textContent = `${departments.length} Departments`;
        statTotalDepartments.textContent = String(departments.length);
        applyFilters();
    } catch (err) {
        departmentTableBody.innerHTML = `
            <tr>
                <td colspan="3" class="px-3 py-12 text-center text-rose-400">
                    Failed to load departments
                </td>
            </tr>
        `;
        showToast(err.message || 'Failed to load departments', 'error');
    }
}

function openAddModal() {
    departmentForm.reset();
    departmentId.value = '';
    document.getElementById('departmentModalTitle').textContent = 'Add Department';
    saveDepartmentBtn.innerHTML = '<span class="iconify" data-icon="mdi:content-save-outline"></span> Save Department';
    departmentModal.classList.remove('hidden');
    departmentModal.classList.add('flex');
    setTimeout(() => departmentName.focus(), 0);
}

function openEditModal(id, name) {
    departmentForm.reset();
    departmentId.value = id;
    departmentName.value = name;
    document.getElementById('departmentModalTitle').textContent = 'Edit Department';
    saveDepartmentBtn.innerHTML = '<span class="iconify" data-icon="mdi:content-save-outline"></span> Update Department';
    departmentModal.classList.remove('hidden');
    departmentModal.classList.add('flex');
    setTimeout(() => departmentName.focus(), 0);
}

function closeDepartmentModal() {
    departmentModal.classList.add('hidden');
    departmentModal.classList.remove('flex');
}

function openDeleteModal(id, name) {
    deleteDepartmentId = id;
    deleteDepartmentName.textContent = name;
    deleteModal.classList.remove('hidden');
    deleteModal.classList.add('flex');
}

function closeDeleteModal() {
    deleteDepartmentId = null;
    deleteModal.classList.add('hidden');
    deleteModal.classList.remove('flex');
}

async function saveDepartment() {
    const name = departmentName.value.trim();
    const id = departmentId.value.trim();

    if (!name) {
        showToast('Department name is required', 'error');
        return;
    }

    const originalButton = saveDepartmentBtn.innerHTML;
    saveDepartmentBtn.disabled = true;
    saveDepartmentBtn.innerHTML = '<span class="iconify animate-spin" data-icon="mdi:loading"></span> Saving...';

    try {
        const res = await fetch(id ? `/api/departments/${encodeURIComponent(id)}` : '/api/departments', {
            method: id ? 'PUT' : 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
            },
            body: JSON.stringify({ name }),
        });

        const json = await res.json();
        if (!json.success) {
            throw new Error(json.message || 'Save failed');
        }

        showToast(json.message || (id ? 'Department updated' : 'Department created'));
        closeDepartmentModal();
        await loadDepartments();
    } catch (err) {
        showToast(err.message || 'Save failed', 'error');
    } finally {
        saveDepartmentBtn.disabled = false;
        saveDepartmentBtn.innerHTML = originalButton;
    }
}

async function confirmDelete() {
    if (!deleteDepartmentId) return;

    try {
        const res = await fetch(`/api/departments/${encodeURIComponent(deleteDepartmentId)}`, {
            method: 'DELETE',
            headers: { 'Accept': 'application/json' },
        });

        const json = await res.json();
        if (!json.success) {
            throw new Error(json.message || 'Delete failed');
        }

        showToast(json.message || 'Department deleted');
        closeDeleteModal();
        await loadDepartments();
    } catch (err) {
        showToast(err.message || 'Delete failed', 'error');
    }
}

searchInput.addEventListener('input', applyFilters);
departmentModal.addEventListener('click', (e) => { if (e.target === departmentModal) closeDepartmentModal(); });
deleteModal.addEventListener('click', (e) => { if (e.target === deleteModal) closeDeleteModal(); });
departmentForm.addEventListener('submit', (e) => {
    e.preventDefault();
    saveDepartment();
});

departmentTableBody.addEventListener('click', (e) => {
    const editButton = e.target.closest('.js-edit-department');
    if (editButton) {
        openEditModal(
            decodeDataAttr(editButton.dataset.id),
            decodeDataAttr(editButton.dataset.name)
        );
        return;
    }

    const deleteButton = e.target.closest('.js-delete-department');
    if (deleteButton) {
        openDeleteModal(
            decodeDataAttr(deleteButton.dataset.id),
            decodeDataAttr(deleteButton.dataset.name)
        );
    }
});

loadDepartments();
</script>
