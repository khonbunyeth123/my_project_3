<?php
$pageTitle = "Role Management";
$activeMenu = "roles";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?> - Admin</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(16px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes fadeIn  { from { opacity: 0; } to { opacity: 1; } }
        @keyframes spin    { to { transform: rotate(360deg); } }
        @keyframes shimmer {
            0%   { background-position: 200% 0; }
            100% { background-position: -200% 0; }
        }

        .modal-backdrop { animation: fadeIn 0.2s ease-out; }
        .modal-box      { animation: slideUp 0.25s ease-out; }

        .spinner {
            display: inline-block; width: 14px; height: 14px;
            border: 2px solid rgba(255,255,255,0.3);
            border-radius: 50%; border-top-color: white;
            animation: spin 0.7s linear infinite;
        }
        .skeleton {
            background: linear-gradient(90deg,#f0f0f0 25%,#e0e0e0 50%,#f0f0f0 75%);
            background-size: 200% 100%; animation: shimmer 1.2s infinite; border-radius: 6px;
        }
        tbody tr { transition: background 0.12s; }
        tbody tr:hover { background: #f0f7ff; }

        .toast-container {
            position: fixed; top: 1rem; right: 1rem;
            z-index: 9999; display: flex; flex-direction: column; gap: 8px;
        }
        .toast {
            animation: slideUp 0.3s ease-out; min-width: 280px;
            display: flex; align-items: center; gap: 10px;
            padding: 12px 16px; border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.12); font-size: 14px; font-weight: 500;
        }
        .toast.success { background:#ecfdf5; border-left:4px solid #10b981; color:#065f46; }
        .toast.error   { background:#fef2f2; border-left:4px solid #ef4444; color:#7f1d1d; }
        .toast.info    { background:#eff6ff; border-left:4px solid #3b82f6; color:#1e3a8a; }

        .error-msg { font-size:12px; color:#dc2626; margin-top:3px; display:none; }
        .error-msg.show { display:block; }
        .field-error { border-color:#ef4444 !important; }

        .badge { display:inline-block; padding:2px 10px; border-radius:999px; font-size:12px; font-weight:600; }
        .badge-active   { background:#dcfce7; color:#15803d; }
        .badge-inactive { background:#fee2e2; color:#b91c1c; }
        .badge-pending  { background:#fef3c7; color:#b45309; }

        .modal-scroll { max-height: 65vh; overflow-y: auto; }

        /* Permission grid groups */
        .perm-group-header {
            display: flex; align-items: center; gap: 8px;
            padding: 8px 12px; border-radius: 8px;
            background: #f8fafc; cursor: pointer;
            font-size: 12px; font-weight: 700;
            text-transform: uppercase; letter-spacing: 0.05em;
            color: #475569; user-select: none;
        }
        .perm-group-header:hover { background: #f1f5f9; }
        .perm-group-body { padding: 4px 0 8px 8px; }
    </style>
</head>
<body class="bg-gray-50 min-h-screen">

<div class="w-full h-full p-2">
    <!-- Header -->
    <div class="flex items-center justify-between mb-3 border-b border-gray-100 pb-2">
        <div>
            <h1 class="text-sm font-bold text-gray-900">Role Management</h1>
        </div>
        <?php if (hasAnyPermissionSlugs(['role.create', 'roles.manage'])): ?><button onclick="openAddRoleModal()"
            class="inline-flex items-center gap-1 px-3 py-1 bg-blue-600 text-white rounded-lg font-bold hover:bg-blue-700 text-[10px]">
            <i class="fas fa-plus"></i> Add Role
        </button><?php endif; ?>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-2 mb-3">
        <div class="bg-white rounded-lg p-2 border border-gray-100 shadow-sm">
            <p class="text-[9px] font-bold text-gray-400 uppercase">Total</p>
            <p class="text-lg font-bold text-gray-900" id="statTotalRoles">—</p>
        </div>
        <div class="bg-white rounded-lg p-2 border border-gray-100 shadow-sm">
            <p class="text-[9px] font-bold text-gray-400 uppercase">Pending</p>
            <p class="text-lg font-bold text-gray-900" id="statPendingRoles">—</p>
        </div>
        <div class="bg-white rounded-lg p-2 border border-gray-100 shadow-sm">
            <p class="text-[9px] font-bold text-gray-400 uppercase">Active</p>
            <p class="text-lg font-bold text-gray-900" id="statActiveUsers">—</p>
        </div>
    </div>

    <!-- Search -->
    <div class="flex gap-2 mb-3">
        <input type="text" id="searchInput" placeholder="Search roles..."
            class="flex-1 px-3 py-1 border border-gray-200 rounded-lg text-[10px] focus:outline-none focus:ring-1 focus:ring-blue-500"
            oninput="debounceSearch(renderTable, 250)">
        <select id="filterStatus" onchange="renderTable()"
            class="px-2 py-1 border border-gray-200 rounded-lg text-[10px] focus:outline-none focus:ring-1 focus:ring-blue-500 bg-white">
            <option value="">All Status</option>
            <option value="active">Active</option>
            <option value="pending">Pending</option>
            <option value="inactive">Inactive</option>
        </select>
    </div>

    <!-- Table -->
    <div class="bg-white rounded-lg shadow-sm border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-[10px]">
                <thead class="bg-slate-800 text-white">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold">#</th>
                        <th class="px-3 py-2 text-left font-semibold">Name</th>
                        <th class="px-3 py-2 text-left font-semibold">Slug</th>
                        <th class="px-3 py-2 text-left font-semibold">Desc</th>
                        <th class="px-3 py-2 text-center font-semibold">Perms</th>
                        <th class="px-3 py-2 text-center font-semibold">Users</th>
                        <th class="px-3 py-2 text-center font-semibold">Status</th>
                        <th class="px-3 py-2 text-center font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody id="rolesTableBody">
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===================== ADD ROLE MODAL ===================== -->
<!-- Only: name, slug, description. No permissions here — add role first, then assign permissions -->
<div id="addRoleModal"
    class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4 modal-backdrop"
    onclick="if(event.target===this) closeModal('addRoleModal')">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full modal-box" role="dialog">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-900">Add New Role</h2>
            <button onclick="closeModal('addRoleModal')" class="text-gray-400 hover:text-gray-700 text-xl leading-none">×</button>
        </div>
        <div class="px-6 py-5 space-y-4">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Role Name <span class="text-red-500">*</span></label>
                <input type="text" id="addRoleName" placeholder="e.g. Supervisor"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    oninput="clearFieldError(this); autoSlug(this)">
                <p class="error-msg" id="addRoleNameErr">Role name is required</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Slug <span class="text-red-500">*</span></label>
                <input type="text" id="addRoleSlug" placeholder="e.g. supervisor"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    oninput="clearFieldError(this)">
                <p class="error-msg" id="addRoleSlugErr">Only lowercase letters, numbers, hyphens allowed</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                <textarea id="addRoleDesc" rows="2" placeholder="Describe this role..."
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
            </div>
            <p class="text-xs text-gray-400 bg-blue-50 border border-blue-100 rounded-lg px-3 py-2">
                <i class="fas fa-info-circle text-blue-400 mr-1"></i>
                After creating the role, use <strong>Manage Permissions</strong> to assign permissions.
            </p>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
            <button type="button" onclick="closeModal('addRoleModal')"
                class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-semibold text-sm">Cancel</button>
            <button type="button" onclick="submitAddRole()" id="addRoleBtn"
                class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold text-sm flex items-center gap-2">
                <i class="fas fa-plus"></i> Create Role
            </button>
        </div>
    </div>
</div>

<!-- ===================== EDIT ROLE MODAL ===================== -->
<!-- Only: name, description. Slug is readonly. No permissions. -->
<div id="editRoleModal"
    class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4 modal-backdrop"
    onclick="if(event.target===this) closeModal('editRoleModal')">
    <div class="bg-white rounded-xl shadow-xl max-w-md w-full modal-box" role="dialog">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-900" id="editRoleTitle">Edit Role</h2>
            <button onclick="closeModal('editRoleModal')" class="text-gray-400 hover:text-gray-700 text-xl leading-none">×</button>
        </div>
        <div class="px-6 py-5 space-y-4">
            <input type="hidden" id="editRoleId">
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Role Name <span class="text-red-500">*</span></label>
                <input type="text" id="editRoleName"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    oninput="clearFieldError(this)">
                <p class="error-msg" id="editRoleNameErr">Role name is required</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Slug</label>
                <input type="text" id="editRoleSlug" readonly
                    class="w-full px-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-500 cursor-not-allowed">
                <p class="text-xs text-gray-400 mt-1">Slug cannot be changed after creation</p>
            </div>
            <div>
                <label class="block text-sm font-semibold text-gray-700 mb-1">Description</label>
                <textarea id="editRoleDesc" rows="2"
                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 resize-none"></textarea>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
            <button type="button" onclick="closeModal('editRoleModal')"
                class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-semibold text-sm">Cancel</button>
            <button type="button" onclick="submitEditRole()" id="editRoleBtn"
                class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold text-sm flex items-center gap-2">
                <i class="fas fa-save"></i> Update Role
            </button>
        </div>
    </div>
</div>

<!-- ===================== MANAGE PERMISSIONS MODAL ===================== -->
<!-- Dedicated modal: full checkbox grid grouped by module -->
<div id="permissionsModal"
    class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4 modal-backdrop"
    onclick="if(event.target===this) closeModal('permissionsModal')">
    <div class="bg-white rounded-xl shadow-xl max-w-2xl w-full modal-box" role="dialog">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
            <div>
                <h2 class="text-lg font-bold text-gray-900">Manage Permissions</h2>
                <p class="text-sm text-gray-500 mt-0.5" id="permissionsModalSubtitle">Role: —</p>
            </div>
            <button onclick="closeModal('permissionsModal')" class="text-gray-400 hover:text-gray-700 text-xl leading-none">×</button>
        </div>

        <!-- Search inside modal -->
        <div class="px-6 pt-4">
            <div class="relative">
                <i class="fas fa-search absolute left-3 top-3 text-gray-400 text-sm"></i>
                <input type="text" id="permSearch" placeholder="Filter permissions..."
                    class="w-full pl-9 pr-4 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                    oninput="filterPermGrid()">
            </div>
        </div>

        <div id="permissionsGrid" class="modal-scroll px-6 py-4 space-y-3">
            <p class="text-center text-gray-400 py-8"><i class="fas fa-spinner fa-spin mr-2"></i>Loading permissions...</p>
        </div>

        <div class="px-6 py-4 border-t border-gray-100 flex items-center justify-between">
            <span class="text-sm text-gray-500"><span id="permSelectedCount">0</span> selected</span>
            <div class="flex gap-3">
                <button type="button" onclick="closeModal('permissionsModal')"
                    class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-semibold text-sm">Cancel</button>
                <button type="button" onclick="submitPermissions()" id="savePermBtn"
                    class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-semibold text-sm flex items-center gap-2">
                    <i class="fas fa-save"></i> Save Permissions
                </button>
            </div>
        </div>
    </div>
</div>

<!-- ===================== ACCEPT ROLE MODAL ===================== -->
<div id="acceptRoleModal"
    class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4 modal-backdrop"
    onclick="if(event.target===this) closeModal('acceptRoleModal')">
    <div class="bg-white rounded-xl shadow-xl max-w-sm w-full modal-box p-6 text-center" role="dialog">
        <div class="w-14 h-14 bg-green-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-check text-green-600 text-xl"></i>
        </div>
        <h2 class="text-lg font-bold text-gray-900 mb-1">Accept Role?</h2>
        <p class="text-gray-500 text-sm mb-6">
            Approve <strong id="acceptRoleName" class="text-gray-800"></strong> and make it available for assignment?
        </p>
        <input type="hidden" id="acceptRoleId">
        <div class="flex gap-3 justify-center">
            <button onclick="closeModal('acceptRoleModal')"
                class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-semibold text-sm">Cancel</button>
            <button onclick="confirmAccept()" id="acceptRoleBtn"
                class="px-5 py-2 bg-green-500 text-white rounded-lg hover:bg-green-600 font-semibold text-sm flex items-center gap-2">
                <i class="fas fa-check"></i> Accept
            </button>
        </div>
    </div>
</div>

<!-- ===================== DELETE ROLE MODAL ===================== -->
<div id="deleteRoleModal"
    class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4 modal-backdrop"
    onclick="if(event.target===this) closeModal('deleteRoleModal')">
    <div class="bg-white rounded-xl shadow-xl max-w-sm w-full modal-box p-6 text-center" role="dialog">
        <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-trash text-red-500 text-xl"></i>
        </div>
        <h2 class="text-lg font-bold text-gray-900 mb-1">Delete Role?</h2>
        <p class="text-gray-500 text-sm mb-6">
            Delete <strong id="deleteRoleName" class="text-gray-800"></strong>? This action cannot be undone.
        </p>
        <input type="hidden" id="deleteRoleId">
        <div class="flex gap-3 justify-center">
            <button onclick="closeModal('deleteRoleModal')"
                class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-semibold text-sm">Cancel</button>
            <button onclick="confirmDelete()" id="deleteRoleBtn"
                class="px-5 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 font-semibold text-sm flex items-center gap-2">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toastContainer" class="toast-container"></div>

<!-- ===================== JAVASCRIPT ===================== -->
<script>
// ─── CONFIG ───────────────────────────────────────────────
const API_BASE = (function() {
    const match = window.location.pathname.match(/^(.*\/admin)/);
    return match ? match[1] + '/api' : '/api';
})();
const canRoleUpdate = <?= json_encode(hasAnyPermissionSlugs(['role.update', 'roles.manage']), JSON_THROW_ON_ERROR) ?>;
const canRoleDelete = <?= json_encode(hasAnyPermissionSlugs(['role.delete', 'roles.manage']), JSON_THROW_ON_ERROR) ?>;

// ─── STATE ────────────────────────────────────────────────
let allRoles       = [];   // roles list from GET /api/roles (includes permission_count)
let allPermissions = [];   // all permissions for checkbox grid
let permRoleId     = null; // which role we're managing permissions for

// ─── INIT ─────────────────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
    loadRoles();
    loadAllPermissions(); // load in background for the permissions modal
});

// ─── LOAD ROLES ───────────────────────────────────────────
// GET /api/roles  → expects permission_count in each role (no N+1 calls)
async function loadRoles() {
    try {
        const res  = await fetch(`${API_BASE}/roles`, {credentials: 'include'});
        const json = await res.json();
        console.log("DEBUG: API Response:", json);
        if (!res.ok) throw new Error(json.message || 'Failed to load roles');

        allRoles = Array.isArray(json.data?.data) ? json.data.data
                 : Array.isArray(json.data)        ? json.data
                 : Array.isArray(json)             ? json : [];

        updateStats();
        renderTable();
    } catch (err) {
        console.error("DEBUG: Load Roles Error:", err);
        showToast('Failed to load roles: ' + err.message, 'error');
        document.getElementById('rolesTableBody').innerHTML =
            `<tr><td colspan="8" class="px-6 py-10 text-center text-gray-400">Failed to load. Please refresh.</td></tr>`;
    }
}

// ─── LOAD ALL PERMISSIONS (for the manage permissions modal) ──
// GET /api/permissions  → flat list, used once, reused for all roles
async function loadAllPermissions() {
    try {
        const res  = await fetch(`${API_BASE}/permissions`);
        const json = await res.json();
        allPermissions = Array.isArray(json.data?.data) ? json.data.data
                       : Array.isArray(json.data)       ? json.data
                       : Array.isArray(json)            ? json : [];
    } catch (err) {
        console.error('Failed to load permissions:', err);
    }
}

// ─── STATS ────────────────────────────────────────────────
function updateStats() {
    document.getElementById('statTotalRoles').textContent   = allRoles.length;
    document.getElementById('statPendingRoles').textContent = allRoles.filter(r => r.status === 'pending').length;
    document.getElementById('statActiveUsers').textContent  = allRoles.reduce((sum, r) => sum + (r.user_count || 0), 0);
}

// ─── RENDER TABLE ─────────────────────────────────────────
function renderTable() {
    console.log("DEBUG: renderTable called, allRoles count:", allRoles.length);
    const query  = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('filterStatus').value;

    const filtered = allRoles.filter(role => {
        const matchSearch =
            (role.name || '').toLowerCase().includes(query) ||
            (role.slug || '').toLowerCase().includes(query) ||
            (role.description || '').toLowerCase().includes(query);
        return matchSearch && (!status || role.status === status);
    });

    const tbody  = document.getElementById('rolesTableBody');
    
    if (!filtered.length) {
        tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-10 text-center text-gray-400">No roles found</td></tr>';
        return;
    }

    try {
        tbody.innerHTML = filtered.map((role, idx) => {
            const badgeClass = role.status === 'active'  ? 'badge-active'
                             : role.status === 'pending' ? 'badge-pending'
                             : 'badge-inactive';
            const statusText = role.status === 'active'  ? 'Active'
                             : role.status === 'pending' ? 'Pending'
                             : 'Inactive';

            // permission_count should come from the API directly — no extra calls needed
            const permCount = role.permission_count ?? (Array.isArray(role.permissions) ? role.permissions.length : 0);

            return `
            <tr class="border-b border-gray-100 hover:bg-blue-50 transition-colors">
                <td class="px-3 py-2 text-gray-500 font-medium">${idx + 1}</td>
                <td class="px-3 py-2 font-semibold text-gray-900">${escHtml(role.name || 'Unnamed')}</td>
                <td class="px-3 py-2">
                    <code class="text-[9px] bg-gray-100 text-gray-700 px-1.5 py-0.5 rounded font-mono">${escHtml(role.slug || 'no-slug')}</code>
                </td>
                <td class="px-3 py-2 text-gray-500 max-w-xs truncate text-[9px]">${escHtml(role.description || '—')}</td>
                <td class="px-3 py-2 text-center">
                    <span class="inline-block px-2 py-0.5 bg-blue-50 text-blue-700 rounded-full text-[9px] font-bold border border-blue-100">${permCount}</span>
                </td>
                <td class="px-3 py-2 text-center text-gray-700 font-bold">${role.user_count ?? 0}</td>
                <td class="px-3 py-2 text-center">
                    <span class="badge ${badgeClass} text-[9px] px-2 py-0.5">${statusText}</span>
                </td>
                <td class="px-3 py-2 text-center">
                    <div class="flex items-center justify-center gap-1 flex-wrap">
                        ${role.status === 'pending' && canRoleUpdate ? `<button onclick="openAcceptModal(${role.id}, '${escHtml(role.name)}')"
                            class="inline-flex items-center gap-1 px-2 py-1 bg-emerald-600 text-white rounded-md hover:bg-emerald-700 text-[10px] font-bold transition-all shadow-sm">
                            <i class="fas fa-check"></i> Accept
                        </button>` : ''}
                        ${canRoleUpdate ? `<button onclick="openEditRoleModal(${role.id})"
                            class="inline-flex items-center gap-1 px-2 py-1 bg-slate-50 text-slate-600 rounded-md hover:bg-blue-600 hover:text-white text-[10px] font-bold transition-all border border-slate-200">
                            <i class="fas fa-edit"></i> Edit
                        </button>` : ''}
                        ${canRoleUpdate ? `<button onclick="openPermissionsModal(${role.id}, '${escHtml(role.name)}')"
                            class="inline-flex items-center gap-1 px-2 py-1 bg-indigo-50 text-indigo-700 rounded-md hover:bg-indigo-600 hover:text-white text-[10px] font-bold transition-all border border-indigo-100">
                            <i class="fas fa-key"></i> Perms
                        </button>` : ''}
                        ${canRoleDelete ? `<button onclick="openDeleteModal(${role.id}, '${escHtml(role.name)}')"
                            class="inline-flex items-center gap-1 px-2 py-1 bg-slate-50 text-slate-600 rounded-md hover:bg-rose-600 hover:text-white text-[10px] font-bold transition-all border border-slate-200">
                            <i class="fas fa-trash"></i> Delete
                        </button>` : ''}
                    </div>
                </td>
            </tr>`;
        }).join('');
    } catch (e) {
        console.error("DEBUG: renderTable Error:", e);
        tbody.innerHTML = '<tr><td colspan="8" class="px-6 py-10 text-center text-red-500">Error rendering table: ' + e.message + '</td></tr>';
    }
}


// ─── ADD ROLE ─────────────────────────────────────────────
function openAddRoleModal() {
    document.getElementById('addRoleName').value = '';
    document.getElementById('addRoleSlug').value = '';
    document.getElementById('addRoleDesc').value = '';
    openModal('addRoleModal');
}

async function submitAddRole() {
    const name = document.getElementById('addRoleName').value.trim();
    const slug = document.getElementById('addRoleSlug').value.trim();
    const desc = document.getElementById('addRoleDesc').value.trim();

    let valid = true;
    if (!name) { showFieldError('addRoleName','addRoleNameErr','Role name is required'); valid = false; }
    if (!slug || !/^[a-z0-9_-]+$/.test(slug)) {
        showFieldError('addRoleSlug','addRoleSlugErr','Only lowercase letters, numbers, hyphens allowed');
        valid = false;
    }
    if (!valid) return;

    setLoading('addRoleBtn', true, 'Creating...');
    try {
        const res  = await fetch(`${API_BASE}/roles`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body:    JSON.stringify({ name, slug, description: desc }),
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Failed to create role');

        const newRole = json.data?.data ?? json.data ?? json;
        allRoles.push(newRole);
        updateStats();
        renderTable();
        closeModal('addRoleModal');
        showToast('Role created! You can now assign permissions.', 'success');
    } catch (err) {
        showToast(err.message, 'error');
    } finally {
        setLoading('addRoleBtn', false, '<i class="fas fa-plus"></i> Create Role');
    }
}

// ─── EDIT ROLE (info only) ────────────────────────────────
function openEditRoleModal(roleId) {
    const role = allRoles.find(r => r.id === roleId);
    if (!role) return;
    document.getElementById('editRoleId').value          = role.id;
    document.getElementById('editRoleName').value        = role.name;
    document.getElementById('editRoleSlug').value        = role.slug;
    document.getElementById('editRoleDesc').value        = role.description || '';
    document.getElementById('editRoleTitle').textContent = `Edit Role: ${role.name}`;
    openModal('editRoleModal');
}

async function submitEditRole() {
    const id   = parseInt(document.getElementById('editRoleId').value);
    const name = document.getElementById('editRoleName').value.trim();
    const desc = document.getElementById('editRoleDesc').value.trim();

    if (!name) { showFieldError('editRoleName','editRoleNameErr','Role name is required'); return; }

    setLoading('editRoleBtn', true, 'Updating...');
    try {
        const res  = await fetch(`${API_BASE}/roles/${id}`, {
            method:  'PUT',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body:    JSON.stringify({ name, description: desc }),
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Failed to update role');

        const idx = allRoles.findIndex(r => r.id === id);
        if (idx !== -1) allRoles[idx] = { ...allRoles[idx], name, description: desc };
        renderTable();
        closeModal('editRoleModal');
        showToast('Role updated!', 'success');
    } catch (err) {
        showToast(err.message, 'error');
    } finally {
        setLoading('editRoleBtn', false, '<i class="fas fa-save"></i> Update Role');
    }
}

// ─── MANAGE PERMISSIONS MODAL ─────────────────────────────
// Opens a dedicated modal: loads current role permissions, shows grouped checkbox grid
async function openPermissionsModal(roleId, roleName) {
    permRoleId = roleId;
    document.getElementById('permissionsModalSubtitle').textContent = `Role: ${roleName}`;
    document.getElementById('permSearch').value = '';
    document.getElementById('permissionsGrid').innerHTML =
        `<p class="text-center text-gray-400 py-8"><i class="fas fa-spinner fa-spin mr-2"></i>Loading...</p>`;
    openModal('permissionsModal');

    try {
        const res    = await fetch(`${API_BASE}/roles/${roleId}/permissions`);
        const json   = await res.json();
        const detail = json.data?.data ?? json.data ?? json;

        // permissions is already a flat array of slug strings e.g. ["users.create", "roles.manage"]
        const assigned = detail.permissions || [];

        buildPermGrid(assigned);
    } catch (err) {
        document.getElementById('permissionsGrid').innerHTML =
            `<p class="text-center text-red-400 py-8">Failed to load permissions</p>`;
    }
}

// Build grouped checkbox grid from allPermissions
function buildPermGrid(assignedSlugs = []) {
    if (!allPermissions.length) {
        document.getElementById('permissionsGrid').innerHTML =
            `<p class="text-center text-gray-400 py-8">No permissions found. Add permissions first.</p>`;
        return;
    }

    // Group by module
    const grouped = {};
    allPermissions.forEach(p => {
        if (!grouped[p.module]) grouped[p.module] = [];
        grouped[p.module].push(p);
    });

    const moduleColors = {
        users:'blue', employees:'purple', attendance:'green',
        leave:'yellow', report:'red', roles:'orange', permissions:'indigo'
    };

    document.getElementById('permissionsGrid').innerHTML = Object.entries(grouped).map(([mod, perms]) => {
        const color    = moduleColors[mod] ?? 'gray';
        const allCheck = perms.every(p => assignedSlugs.includes(`${p.module}.${p.action}`));
        const items    = perms.map(p => {
            const slug    = `${p.module}.${p.action}`;
            const checked = assignedSlugs.includes(slug);
            return `
            <label class="flex items-center gap-2 text-sm text-gray-700 cursor-pointer p-1.5 rounded hover:bg-gray-50 perm-item">
                <input type="checkbox" class="perm-cb w-4 h-4 rounded text-blue-600" value="${escHtml(slug)}"
                    ${checked ? 'checked' : ''} onchange="updatePermCount()">
                <span class="font-mono text-xs">${escHtml(slug)}</span>
                ${p.description ? `<span class="text-gray-400 text-xs ml-1">— ${escHtml(p.description)}</span>` : ''}
            </label>`;
        }).join('');

        return `
        <div class="border border-gray-200 rounded-lg overflow-hidden perm-group" data-module="${escHtml(mod)}">
            <div class="perm-group-header" onclick="toggleGroup(this)">
                <input type="checkbox" class="w-4 h-4 rounded" ${allCheck ? 'checked' : ''}
                    onchange="toggleGroupAll(this)" onclick="event.stopPropagation()">
                <span class="text-${color}-700">${mod.toUpperCase()}</span>
                <span class="ml-auto text-gray-400 font-normal normal-case text-xs">${perms.length} permissions</span>
                <i class="fas fa-chevron-down text-xs text-gray-400 transition-transform duration-200"></i>
            </div>
            <div class="perm-group-body grid grid-cols-1 sm:grid-cols-2">${items}</div>
        </div>`;
    }).join('');

    updatePermCount();
}

function toggleGroup(header) {
    const body    = header.nextElementSibling;
    const chevron = header.querySelector('.fa-chevron-down');
    body.classList.toggle('hidden');
    chevron.style.transform = body.classList.contains('hidden') ? 'rotate(-90deg)' : '';
}

function toggleGroupAll(masterCb) {
    const group = masterCb.closest('.perm-group');
    group.querySelectorAll('.perm-cb').forEach(cb => cb.checked = masterCb.checked);
    updatePermCount();
}

function filterPermGrid() {
    const q = document.getElementById('permSearch').value.toLowerCase();
    document.querySelectorAll('.perm-group').forEach(group => {
        let anyVisible = false;
        group.querySelectorAll('.perm-item').forEach(item => {
            const matches = item.textContent.toLowerCase().includes(q);
            item.style.display = matches ? '' : 'none';
            if (matches) anyVisible = true;
        });
        group.style.display = anyVisible ? '' : 'none';
    });
}

function updatePermCount() {
    const count = document.querySelectorAll('#permissionsGrid .perm-cb:checked').length;
    document.getElementById('permSelectedCount').textContent = count;
}

// Save permissions for the current role
// PUT /api/roles/{id}  with permissions array
async function submitPermissions() {
    if (!permRoleId) return;

    const permissions = [...document.querySelectorAll('#permissionsGrid .perm-cb:checked')]
        .map(cb => cb.value);

    setLoading('savePermBtn', true, 'Saving...');
    try {
        const res = await fetch(`${API_BASE}/roles/${permRoleId}/permissions`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body: JSON.stringify({ permissions }),
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Failed to save permissions');

        // Update local permission count
        const idx = allRoles.findIndex(r => r.id === permRoleId);
        if (idx !== -1) allRoles[idx].permission_count = permissions.length;
        renderTable();
        closeModal('permissionsModal');
        showToast(`${permissions.length} permissions saved!`, 'success');
    } catch (err) {
        showToast(err.message, 'error');
    } finally {
        setLoading('savePermBtn', false, '<i class="fas fa-save"></i> Save Permissions');
    }
}
// ─── ACCEPT ───────────────────────────────────────────────
function openAcceptModal(roleId, roleName) {
    document.getElementById('acceptRoleId').value         = roleId;
    document.getElementById('acceptRoleName').textContent = roleName;
    openModal('acceptRoleModal');
}

async function confirmAccept() {
    const id = parseInt(document.getElementById('acceptRoleId').value);
    setLoading('acceptRoleBtn', true, 'Accepting...');
    try {
        const res  = await fetch(`${API_BASE}/roles/${id}/status`, {
            method:  'PATCH',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body:    JSON.stringify({ status: 'active' }),
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Failed to accept role');

        const idx = allRoles.findIndex(r => r.id === id);
        if (idx !== -1) allRoles[idx].status = 'active';
        updateStats();
        renderTable();
        closeModal('acceptRoleModal');
        showToast('Role accepted and is now active!', 'success');
    } catch (err) {
        showToast(err.message, 'error');
    } finally {
        setLoading('acceptRoleBtn', false, '<i class="fas fa-check"></i> Accept');
    }
}
// ─── DELETE ───────────────────────────────────────────────
function openDeleteModal(roleId, roleName) {
    document.getElementById('deleteRoleId').value         = roleId;
    document.getElementById('deleteRoleName').textContent = roleName;
    openModal('deleteRoleModal');
}

async function confirmDelete() {
    const id = parseInt(document.getElementById('deleteRoleId').value);
    setLoading('deleteRoleBtn', true, 'Deleting...');
    try {
        const res  = await fetch(`${API_BASE}/roles/${id}`, {
            method:  'DELETE',
            headers: { 'Accept': 'application/json' },
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Failed to delete role');

        allRoles = allRoles.filter(r => r.id !== id);
        updateStats();
        renderTable();
        closeModal('deleteRoleModal');
        showToast('Role deleted!', 'success');
    } catch (err) {
        showToast(err.message, 'error');
    } finally {
        setLoading('deleteRoleBtn', false, '<i class="fas fa-trash"></i> Delete');
    }
}

// ─── MODAL HELPERS ────────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) {
    document.getElementById(id).classList.add('hidden');
    document.getElementById(id).querySelectorAll('.field-error').forEach(el => el.classList.remove('field-error'));
    document.getElementById(id).querySelectorAll('.error-msg').forEach(el => el.classList.remove('show'));
}
document.addEventListener('keydown', e => {
    if (e.key === 'Escape')
        document.querySelectorAll('.fixed[id$="Modal"]:not(.hidden)').forEach(m => closeModal(m.id));
});

// ─── UTILITIES ────────────────────────────────────────────
function autoSlug(input) {
    const slug = document.getElementById('addRoleSlug');
    if (slug) slug.value = input.value.toLowerCase().replace(/[^a-z0-9]+/g,'-').replace(/^-|-$/g,'');
}
function showFieldError(inputId, errId, msg) {
    document.getElementById(inputId).classList.add('field-error');
    const err = document.getElementById(errId);
    err.textContent = msg; err.classList.add('show');
}
function clearFieldError(input) {
    input.classList.remove('field-error');
    const err = input.parentElement.querySelector('.error-msg');
    if (err) err.classList.remove('show');
}
function setLoading(btnId, loading, html) {
    const btn = document.getElementById(btnId);
    btn.disabled  = loading;
    btn.innerHTML = loading ? `<span class="spinner"></span> ${html.replace(/(<([^>]+)>)/gi,'')}` : html;
}
function escHtml(str) {
    return String(str)
        .replace(/&/g,'&amp;').replace(/</g,'&lt;')
        .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
function showToast(message, type = 'success') {
    const icons = { success:'check-circle', error:'exclamation-circle', info:'info-circle' };
    const c = document.getElementById('toastContainer');
    const t = document.createElement('div');
    t.className = `toast ${type}`;
    t.innerHTML = `
        <i class="fas fa-${icons[type] || 'info-circle'}"></i>
        <span>${message}</span>
        <button onclick="this.parentElement.remove()" class="ml-auto text-lg leading-none hover:opacity-70">×</button>`;
    c.appendChild(t);
    setTimeout(() => t.remove(), 5000);
}
let debounceTimer;
function debounceSearch(fn, delay) {
    clearTimeout(debounceTimer);
    debounceTimer = setTimeout(fn, delay);
}
</script>
</body>
</html>
