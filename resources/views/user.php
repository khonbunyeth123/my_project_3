<div class="w-full h-full p-2">
    <!-- Header -->
    <div class="flex items-center justify-between mb-3 border-b border-gray-100 pb-2">
        <div>
            <h1 class="text-sm font-bold text-gray-900">User Management</h1>
        </div>
        <button onclick="openCreateModal()"
            class="inline-flex items-center gap-1 px-3 py-1 bg-indigo-600 text-white rounded-lg font-bold hover:bg-indigo-700 text-[10px]">
            <i class="fas fa-plus"></i> Add User
        </button>
    </div>

    <!-- Stats -->
    <div class="grid grid-cols-3 gap-2 mb-3">
        <div class="bg-white rounded-lg p-2 border border-gray-100 shadow-sm">
            <p class="text-[9px] font-bold text-gray-400 uppercase">Total</p>
            <p class="text-lg font-bold text-gray-900" id="statTotal">—</p>
        </div>
        <div class="bg-white rounded-lg p-2 border border-gray-100 shadow-sm">
            <p class="text-[9px] font-bold text-gray-400 uppercase">Active</p>
            <p class="text-lg font-bold text-gray-900" id="statActive">—</p>
        </div>
        <div class="bg-white rounded-lg p-2 border border-gray-100 shadow-sm">
            <p class="text-[9px] font-bold text-gray-400 uppercase">Inactive</p>
            <p class="text-lg font-bold text-gray-900" id="statInactive">—</p>
        </div>
    </div>

    <!-- Search -->
    <div class="flex gap-2 mb-3">
        <input type="text" id="searchInput" placeholder="Search..."
            class="flex-1 px-3 py-1 border border-gray-200 rounded-lg text-[10px] focus:outline-none focus:ring-1 focus:ring-indigo-500"
            oninput="applyFilters()">
        <select id="filterStatus" onchange="applyFilters()"
            class="px-2 py-1 border border-gray-200 rounded-lg text-[10px] focus:outline-none focus:ring-1 focus:ring-indigo-500 bg-white">
            <option value="">All Status</option>
            <option value="1">Active</option>
            <option value="0">Inactive</option>
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
                        <th class="px-3 py-2 text-left font-semibold">User</th>
                        <th class="px-3 py-2 text-left font-semibold">Email</th>
                        <th class="px-3 py-2 text-left font-semibold">Role</th>
                        <th class="px-3 py-2 text-center font-semibold">Status</th>
                        <th class="px-3 py-2 text-left font-semibold">Created</th>
                        <th class="px-3 py-2 text-center font-semibold">Actions</th>
                    </tr>
                </thead>
                <tbody id="userTableBody">
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- ===================== CREATE USER MODAL ===================== -->
<div id="createUserModal"
    class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4 modal-backdrop"
    onclick="if(event.target===this) closeModal('createUserModal')">
    <div class="bg-white rounded-xl shadow-xl max-w-lg w-full modal-box" role="dialog">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-900">Add New User</h2>
            <button onclick="closeModal('createUserModal')" class="text-gray-400 hover:text-gray-700 text-xl leading-none">×</button>
        </div>
        <div class="px-6 py-5 space-y-4 modal-scroll">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="createFullName" placeholder="John Doe"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        oninput="clearFieldError(this, 'createFullNameErr')">
                    <p class="hidden text-xs text-red-600 mt-1" id="createFullNameErr">Full name is required</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Username <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-gray-400 text-sm">@</span>
                        <input type="text" id="createUsername" placeholder="johndoe"
                            class="w-full pl-7 pr-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                            oninput="clearFieldError(this, 'createUsernameErr')">
                    </div>
                    <p class="hidden text-xs text-red-600 mt-1" id="createUsernameErr">Username is required</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="createEmail" placeholder="john@example.com"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        oninput="clearFieldError(this, 'createEmailErr')">
                    <p class="hidden text-xs text-red-600 mt-1" id="createEmailErr">Valid email is required</p>
                </div>


                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Password <span class="text-red-500">*</span></label>
                    <div class="relative">
                        <input type="password" id="createPassword" placeholder="Enter a strong password"
                            class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                            oninput="clearFieldError(this, 'createPasswordErr'); updatePasswordChecklist('createPassword', 'createPasswordChecklist')"
                            onfocus="if(this.value) document.getElementById('createPasswordChecklist').classList.remove('hidden')"
                            onblur="document.getElementById('createPasswordChecklist').classList.add('hidden')">
                            <button type="button" onclick="togglePasswordVisibility('createPassword', this)"
                                class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-indigo-600 bg-transparent border-0 p-0 leading-none focus:outline-none">
                                <i class="fas fa-eye text-sm"></i>
                            </button>

                        <!-- Strength meter + checklist card — now positioned relative to THIS wrapper, so it sits flush under the input -->
                        <div class="hidden absolute left-0 right-0 top-full mt-2 z-20 bg-white rounded-lg border border-gray-200 shadow-lg p-3" id="createPasswordChecklist">
                            <div class="flex gap-1 mb-1.5">
                                <span class="h-1 flex-1 rounded-full bg-gray-200 transition-colors duration-200" data-bar="0"></span>
                                <span class="h-1 flex-1 rounded-full bg-gray-200 transition-colors duration-200" data-bar="1"></span>
                                <span class="h-1 flex-1 rounded-full bg-gray-200 transition-colors duration-200" data-bar="2"></span>
                                <span class="h-1 flex-1 rounded-full bg-gray-200 transition-colors duration-200" data-bar="3"></span>
                                <span class="h-1 flex-1 rounded-full bg-gray-200 transition-colors duration-200" data-bar="4"></span>
                            </div>
                            <p class="text-xs font-semibold mb-2" id="createPasswordStatus"></p>

                            <div class="grid grid-cols-2 gap-x-2 gap-y-1.5 bg-gray-50 rounded-lg px-3 py-2.5 border border-gray-100">
                                <div class="flex items-center gap-1.5 text-[11px] text-gray-400 transition-colors whitespace-nowrap" data-rule="length">
                                    <i class="fas fa-circle text-[5px] w-3 text-center shrink-0"></i> 8+ characters
                                </div>
                                <div class="flex items-center gap-1.5 text-[11px] text-gray-400 transition-colors whitespace-nowrap" data-rule="upper" title="Uppercase letter A-Z">
                                    <i class="fas fa-circle text-[5px] w-3 text-center shrink-0"></i> Uppercase
                                </div>
                                <div class="flex items-center gap-1.5 text-[11px] text-gray-400 transition-colors whitespace-nowrap" data-rule="lower" title="Lowercase letter a-z">
                                    <i class="fas fa-circle text-[5px] w-3 text-center shrink-0"></i> Lowercase
                                </div>
                                <div class="flex items-center gap-1.5 text-[11px] text-gray-400 transition-colors whitespace-nowrap" data-rule="number" title="A number 0-9">
                                    <i class="fas fa-circle text-[5px] w-3 text-center shrink-0"></i> Number
                                </div>
                                <div class="flex items-center gap-1.5 text-[11px] text-gray-400 transition-colors col-span-2 whitespace-nowrap" data-rule="special" title="A special character such as @ # $ % !">
                                    <i class="fas fa-circle text-[5px] w-3 text-center shrink-0"></i> Special character (@ # $ % !)
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="hidden text-xs text-red-600 mt-1" id="createPasswordErr">Password is required</p>
                </div>

                
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                    <select id="createRoleId"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
                        onchange="clearFieldError(this, 'createRoleErr')">
                        <option value="">Select role...</option>
                    </select>
                    <p class="hidden text-xs text-red-600 mt-1" id="createRoleErr">Role is required</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                    <select id="createStatus"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
            <button type="button" onclick="closeModal('createUserModal')"
                class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-semibold text-sm">Cancel</button>
            <button type="button" onclick="submitCreateUser()" id="createUserBtn"
                class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold text-sm flex items-center gap-2">
                <i class="fas fa-plus"></i> Add User
            </button>
        </div>
    </div>
</div>

<!-- ===================== EDIT USER MODAL ===================== -->
<div id="editUserModal"
    class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4 modal-backdrop"
    onclick="if(event.target===this) closeModal('editUserModal')">
    <div class="bg-white rounded-xl shadow-xl max-w-lg w-full modal-box" role="dialog">
        <div class="flex justify-between items-center px-6 py-4 border-b border-gray-200">
            <h2 class="text-lg font-bold text-gray-900" id="editUserTitle">Edit User</h2>
            <button onclick="closeModal('editUserModal')" class="text-gray-400 hover:text-gray-700 text-xl leading-none">×</button>
        </div>
        <div class="px-6 py-5 space-y-4 modal-scroll">
            <input type="hidden" id="editUserId">
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Full Name <span class="text-red-500">*</span></label>
                    <input type="text" id="editFullName"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        oninput="clearFieldError(this, 'editFullNameErr')">
                    <p class="hidden text-xs text-red-600 mt-1" id="editFullNameErr">Full name is required</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Username</label>
                    <div class="relative">
                        <span class="absolute left-3 top-2.5 text-gray-400 text-sm">@</span>
                        <input type="text" id="editUsername" disabled
                            class="w-full pl-7 pr-3 py-2 border border-gray-200 rounded-lg text-sm bg-gray-50 text-gray-500 cursor-not-allowed">
                    </div>
                    <p class="text-xs text-gray-400 mt-1">Username cannot be changed</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                    <input type="email" id="editEmail"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500"
                        oninput="clearFieldError(this, 'editEmailErr')">
                    <p class="hidden text-xs text-red-600 mt-1" id="editEmailErr">Valid email is required</p>
                </div>


                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">
                        Password <span class="text-xs text-gray-400 font-normal">(leave blank to keep)</span>
                    </label>
                    <div class="relative">
                        <input type="password" id="editPassword" placeholder="New password (optional)"
                            class="w-full px-3 py-2 pr-10 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 transition-colors"
                            oninput="clearFieldError(this, 'editPasswordErr'); updatePasswordChecklist('editPassword', 'editPasswordChecklist')"
                            onfocus="if(this.value) document.getElementById('editPasswordChecklist').classList.remove('hidden')"
                            onblur="document.getElementById('editPasswordChecklist').classList.add('hidden')">
                        <button type="button" onclick="togglePasswordVisibility('editPassword', this)"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 hover:text-indigo-600 bg-transparent border-0 p-0 leading-none focus:outline-none">
                            <i class="fas fa-eye text-sm"></i>
                        </button>

                        <div class="hidden absolute left-0 right-0 top-full mt-2 z-20 bg-white rounded-lg border border-gray-200 shadow-lg p-3" id="editPasswordChecklist">
                            <div class="flex gap-1 mb-1.5">
                                <span class="h-1 flex-1 rounded-full bg-gray-200 transition-colors duration-200" data-bar="0"></span>
                                <span class="h-1 flex-1 rounded-full bg-gray-200 transition-colors duration-200" data-bar="1"></span>
                                <span class="h-1 flex-1 rounded-full bg-gray-200 transition-colors duration-200" data-bar="2"></span>
                                <span class="h-1 flex-1 rounded-full bg-gray-200 transition-colors duration-200" data-bar="3"></span>
                                <span class="h-1 flex-1 rounded-full bg-gray-200 transition-colors duration-200" data-bar="4"></span>
                            </div>
                            <p class="text-xs font-semibold mb-2" id="editPasswordStatus"></p>

                            <div class="grid grid-cols-2 gap-x-2 gap-y-1.5 bg-gray-50 rounded-lg px-3 py-2.5 border border-gray-100">
                                <div class="flex items-center gap-1.5 text-[11px] text-gray-400 transition-colors whitespace-nowrap" data-rule="length">
                                    <i class="fas fa-circle text-[5px] w-3 text-center shrink-0"></i> 8+ characters
                                </div>
                                <div class="flex items-center gap-1.5 text-[11px] text-gray-400 transition-colors whitespace-nowrap" data-rule="upper" title="Uppercase letter A-Z">
                                    <i class="fas fa-circle text-[5px] w-3 text-center shrink-0"></i> Uppercase
                                </div>
                                <div class="flex items-center gap-1.5 text-[11px] text-gray-400 transition-colors whitespace-nowrap" data-rule="lower" title="Lowercase letter a-z">
                                    <i class="fas fa-circle text-[5px] w-3 text-center shrink-0"></i> Lowercase
                                </div>
                                <div class="flex items-center gap-1.5 text-[11px] text-gray-400 transition-colors whitespace-nowrap" data-rule="number" title="A number 0-9">
                                    <i class="fas fa-circle text-[5px] w-3 text-center shrink-0"></i> Number
                                </div>
                                <div class="flex items-center gap-1.5 text-[11px] text-gray-400 transition-colors col-span-2 whitespace-nowrap" data-rule="special" title="A special character such as @ # $ % !">
                                    <i class="fas fa-circle text-[5px] w-3 text-center shrink-0"></i> Special character (@ # $ % !)
                                </div>
                            </div>
                        </div>
                    </div>
                    <p class="hidden text-xs text-red-600 mt-1" id="editPasswordErr">Password does not meet requirements</p>
                </div>


            </div>


            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Role <span class="text-red-500">*</span></label>
                    <select id="editRoleId"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white"
                        onchange="clearFieldError(this, 'editRoleErr')">
                        <option value="">Select role...</option>
                    </select>
                    <p class="hidden text-xs text-red-600 mt-1" id="editRoleErr">Role is required</p>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Status</label>
                    <select id="editStatus"
                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 bg-white">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-100 flex justify-end gap-3">
            <button type="button" onclick="closeModal('editUserModal')"
                class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-semibold text-sm">Cancel</button>
            <button type="button" onclick="submitEditUser()" id="editUserBtn"
                class="px-5 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 font-semibold text-sm flex items-center gap-2">
                <i class="fas fa-save"></i> Update User
            </button>
        </div>
    </div>
</div>

<!-- ===================== DELETE USER MODAL ===================== -->
<div id="deleteUserModal"
    class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center p-4 modal-backdrop"
    onclick="if(event.target===this) closeModal('deleteUserModal')">
    <div class="bg-white rounded-xl shadow-xl max-w-sm w-full modal-box p-6 text-center" role="dialog">
        <div class="w-14 h-14 bg-red-100 rounded-full flex items-center justify-center mx-auto mb-4">
            <i class="fas fa-trash text-red-500 text-xl"></i>
        </div>
        <h2 class="text-lg font-bold text-gray-900 mb-1">Delete User?</h2>
        <p class="text-gray-500 text-sm mb-6">
            Delete <strong id="deleteUserName" class="text-gray-800"></strong>? This action cannot be undone.
        </p>
        <input type="hidden" id="deleteUserId">
        <div class="flex gap-3 justify-center">
            <button onclick="closeModal('deleteUserModal')"
                class="px-5 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 font-semibold text-sm">Cancel</button>
            <button onclick="confirmDeleteUser()" id="deleteUserBtn"
                class="px-5 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 font-semibold text-sm flex items-center gap-2">
                <i class="fas fa-trash"></i> Delete
            </button>
        </div>
    </div>
</div>

<!-- Toast -->
<div id="toastContainer" class="toast-container"></div>

<style>
/* Hide native password reveal/clear icons so only our custom eye button shows */
input[type="password"]::-ms-reveal,
input[type="password"]::-ms-clear {
    display: none;
}
input[type="password"]::-webkit-credentials-auto-fill-button,
input[type="password"]::-webkit-strong-password-auto-fill-button {
    display: none !important;
    visibility: hidden;
}
</style>

<!-- ===================== JAVASCRIPT ===================== -->
<script>

document.addEventListener('click', (e) => {
    ['createPasswordChecklist', 'editPasswordChecklist'].forEach(id => {
        const checklist = document.getElementById(id);
        const input = document.getElementById(id.replace('Checklist', ''));
        if (checklist && !checklist.classList.contains('hidden')) {
            if (!checklist.contains(e.target) && e.target !== input) {
                checklist.classList.add('hidden');
            }
        }
    });
});

// ─── CONFIG ───────────────────────────────────────────────
const API_BASE = (function() {
    const match = window.location.pathname.match(/^(.*\/admin)/);
    return match ? match[1] + '/api' : '/api';
})();

let allUsers = [];
let allRoles = [];

document.addEventListener('DOMContentLoaded', () => {
    loadRoles();
    loadUsers();

    setTimeout(() => {
        const s = document.getElementById('searchInput');
        s.value = '';
        applyFilters();
    }, 200);
});


async function loadRoles() {
    try {
        const res  = await fetch(`${API_BASE}/roles`);
        const json = await res.json();

        allRoles = Array.isArray(json.data?.data) ? json.data.data
                 : Array.isArray(json.data)        ? json.data
                 : Array.isArray(json)             ? json : [];

        // Only show active roles for assignment
        const activeRoles = allRoles.filter(r => r.status === 'active');
        populateRoleSelects(activeRoles);
    } catch (err) {
        showToast('Failed to load roles', 'error');
    }
}

function populateRoleSelects(roles) {
    ['createRoleId', 'editRoleId'].forEach(id => {
        const sel = document.getElementById(id);
        const current = sel.value;
        sel.innerHTML = '<option value="">Select role...</option>';
        roles.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.id;
            opt.textContent = r.name;
            if (String(r.id) === String(current)) opt.selected = true;
            sel.appendChild(opt);
        });
    });
}

async function loadUsers() {
    try {
        const res  = await fetch(`${API_BASE}/users/show`);
        const json = await res.json();

        if (!res.ok) throw new Error(json.message || 'Failed to load users');

        allUsers = json.data?.users ?? json.data ?? [];
        updateStats();
        applyFilters();
    } catch (err) {
        showToast('Failed to load users: ' + err.message, 'error');
        document.getElementById('userTableBody').innerHTML =
            `<tr><td colspan="8" class="px-6 py-10 text-center text-gray-400">Failed to load. Please refresh.</td></tr>`;
    }
}

function updateStats() {
    const active   = allUsers.filter(u => parseInt(u.status_id) === 1).length;
    const inactive = allUsers.length - active;
    document.getElementById('statTotal').textContent   = allUsers.length;
    document.getElementById('statActive').textContent  = active;
    document.getElementById('statInactive').textContent = inactive;
}

function applyFilters() {
    const q      = document.getElementById('searchInput').value.toLowerCase();
    const status = document.getElementById('filterStatus').value;

    const filtered = allUsers.filter(u => {
        const matchSearch =
            (u.full_name || '').toLowerCase().includes(q) ||
            (u.username  || '').toLowerCase().includes(q) ||
            (u.email     || '').toLowerCase().includes(q);
        const matchStatus = !status || String(u.status_id) === status;
        return matchSearch && matchStatus;
    });

    renderTable(filtered);
}

function renderTable(users) {
    const tbody = document.getElementById('userTableBody');
    const noRes  = document.getElementById('noResults');
    const info  = document.getElementById('paginationInfo');

    if (!users.length) {
        tbody.innerHTML = '';
        if (noRes) noRes.classList.remove('hidden');
        if (info) info.textContent = 'No users found';
        return;
    }

    if (noRes) noRes.classList.add('hidden');
    if (info) info.textContent = `Showing ${users.length} user${users.length > 1 ? 's' : ''}`;

    tbody.innerHTML = users.map((user, idx) => {
        const active = parseInt(user.status_id) === 1;
        return `
        <tr class="border-b border-gray-100 hover:bg-slate-50 transition-colors">
            <td class="px-3 py-2 text-gray-500 font-medium">${idx + 1}</td>
            <td class="px-3 py-2 font-semibold text-gray-900">${escHtml(user.full_name || '—')}</td>
            <td class="px-3 py-2 font-mono text-[9px] text-gray-600">@${escHtml(user.username || '—')}</td>
            <td class="px-3 py-2 text-gray-600">${escHtml(user.email || '—')}</td>
            <td class="px-3 py-2">
                <span class="inline-block px-2 py-0.5 bg-indigo-50 text-indigo-700 text-[9px] font-bold rounded">
                    ${escHtml(user.role_name || '—')}
                </span>
            </td>
            <td class="px-3 py-2 text-center">
                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[9px] font-bold ${active ? 'bg-emerald-50 text-emerald-600' : 'bg-gray-50 text-gray-500'}">${active ? 'Active' : 'Inactive'}</span>
            </td>
            <td class="px-3 py-2 text-gray-400 text-[9px]">${formatDate(user.created_at)}</td>
            <td class="px-3 py-2 text-center">
                <div class="flex items-center justify-center gap-1">
                    <button onclick="openEditModal(${user.id})"
                        class="inline-flex items-center gap-1 px-2 py-1 bg-slate-50 text-slate-600 rounded-md hover:bg-indigo-600 hover:text-white text-[10px] font-bold transition-all">
                        <i class="fas fa-edit"></i> Edit
                    </button>
                    <button onclick="openDeleteModal(${user.id}, '${escHtml(user.full_name)}')"
                        class="inline-flex items-center gap-1 px-2 py-1 bg-slate-50 text-slate-600 rounded-md hover:bg-rose-600 hover:text-white text-[10px] font-bold transition-all">
                        <i class="fas fa-trash"></i> Delete
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
}

function openCreateModal() {
    document.getElementById('createFullName').value = '';
    document.getElementById('createUsername').value = '';
    document.getElementById('createEmail').value    = '';
    document.getElementById('createPassword').value = '';
    document.getElementById('createRoleId').value   = '';
    document.getElementById('createStatus').value   = '1';
    document.getElementById('createPasswordChecklist').classList.add('hidden');
    document.getElementById('createPasswordStatus').classList.add('hidden');
    openModal('createUserModal');
}

async function submitCreateUser() {
    const fullName = document.getElementById('createFullName').value.trim();
    const username = document.getElementById('createUsername').value.trim();
    const email    = document.getElementById('createEmail').value.trim();
    const password = document.getElementById('createPassword').value.trim();
    const roleId   = document.getElementById('createRoleId').value;
    const status   = document.getElementById('createStatus').value;

    let valid = true;
    if (!fullName) { showFieldError('createFullName','createFullNameErr','Full name is required'); valid = false; }
    if (!username) { showFieldError('createUsername','createUsernameErr','Username is required'); valid = false; }
    if (!email || !email.includes('@')) { showFieldError('createEmail','createEmailErr','Valid email is required'); valid = false; }

    if (!password) {
        showFieldError('createPassword','createPasswordErr','Password is required');
        valid = false;
    } else {
        const pwErr = getPasswordPolicyError(password);
        if (pwErr) {
            showFieldError('createPassword','createPasswordErr', pwErr);
            document.getElementById('createPasswordChecklist').classList.remove('hidden');
            valid = false;
        }
    }

    if (!roleId)   { showFieldError('createRoleId','createRoleErr','Role is required'); valid = false; }
    if (!valid) return;

    setLoading('createUserBtn', true, 'Creating...');
    try {
        // ✅ Consistent JSON body — same as permissions.php and roles.php
        const res  = await fetch(`${API_BASE}/users/create`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body:    JSON.stringify({ full_name: fullName, username, email, password, role_id: parseInt(roleId), status_id: parseInt(status) }),
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Failed to create user');

        showToast('User created successfully!', 'success');
        closeModal('createUserModal');
        loadUsers();
    } catch (err) {
        showToast(err.message, 'error');
    } finally {
        setLoading('createUserBtn', false, '<i class="fas fa-plus"></i> Add User');
    }
}

function openEditModal(userId) {
    const user = allUsers.find(u => u.id === userId);
    if (!user) return;

    document.getElementById('editUserId').value    = user.id;
    document.getElementById('editFullName').value  = user.full_name || '';
    document.getElementById('editUsername').value  = user.username  || '';
    document.getElementById('editEmail').value     = user.email     || '';
    document.getElementById('editPassword').value  = '';
    document.getElementById('editStatus').value    = String(user.status_id ?? 1);
    document.getElementById('editUserTitle').textContent = `Edit User: ${user.full_name}`;
    document.getElementById('editPasswordChecklist').classList.add('hidden');
    document.getElementById('editPasswordStatus').classList.add('hidden');

    // Set current role in dropdown
    const roleSelect = document.getElementById('editRoleId');
    roleSelect.value = String(user.role_id ?? '');

    openModal('editUserModal');
}

async function submitEditUser() {
    const id       = parseInt(document.getElementById('editUserId').value);
    const fullName = document.getElementById('editFullName').value.trim();
    const email    = document.getElementById('editEmail').value.trim();
    const password = document.getElementById('editPassword').value.trim();
    const roleId   = document.getElementById('editRoleId').value;
    const status   = document.getElementById('editStatus').value;

    let valid = true;
    if (!fullName)               { showFieldError('editFullName','editFullNameErr','Full name is required'); valid = false; }
    if (!email || !email.includes('@')) { showFieldError('editEmail','editEmailErr','Valid email is required'); valid = false; }
    if (!roleId)                 { showFieldError('editRoleId','editRoleErr','Role is required'); valid = false; }

    // Password is optional on edit — only enforce the policy if the user typed something
    if (password) {
        const pwErr = getPasswordPolicyError(password);
        if (pwErr) {
            showFieldError('editPassword','editPasswordErr', pwErr);
            document.getElementById('editPasswordChecklist').classList.remove('hidden');
            valid = false;
        }
    }

    if (!valid) return;

    const payload = { id, full_name: fullName, email, role_id: parseInt(roleId), status_id: parseInt(status) };
    if (password) payload.password = password; // only send if changed

    setLoading('editUserBtn', true, 'Updating...');
    try {
        const res  = await fetch(`${API_BASE}/users/update`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body:    JSON.stringify(payload),
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Failed to update user');

        showToast('User updated successfully!', 'success');
        closeModal('editUserModal');
        loadUsers();
    } catch (err) {
        showToast(err.message, 'error');
    } finally {
        setLoading('editUserBtn', false, '<i class="fas fa-save"></i> Update User');
    }
}

function openDeleteModal(userId, userName) {
    document.getElementById('deleteUserId').value         = userId;
    document.getElementById('deleteUserName').textContent = userName;
    openModal('deleteUserModal');
}

async function confirmDeleteUser() {
    const id = parseInt(document.getElementById('deleteUserId').value);
    setLoading('deleteUserBtn', true, 'Deleting...');
    try {
        const res  = await fetch(`${API_BASE}/users/delete`, {
            method:  'POST',
            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json' },
            body:    JSON.stringify({ id }),
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.message || 'Failed to delete user');

        allUsers = allUsers.filter(u => u.id !== id);
        updateStats();
        applyFilters();
        closeModal('deleteUserModal');
        showToast('User deleted!', 'success');
    } catch (err) {
        showToast(err.message, 'error');
    } finally {
        setLoading('deleteUserBtn', false, '<i class="fas fa-trash"></i> Delete');
    }
}

function openModal(id)  { document.getElementById(id).classList.remove('hidden'); }
function closeModal(id) {
    const modal = document.getElementById(id);
    modal.classList.add('hidden');

    // Reset any red error borders on inputs/selects
    modal.querySelectorAll('.border-red-500').forEach(el => {
        el.classList.remove('border-red-500');
        el.classList.add('border-gray-300');
    });

    // Re-hide every error message (Tailwind's `hidden` utility)
    modal.querySelectorAll('[id$="Err"]').forEach(el => el.classList.add('hidden'));

    // Re-hide password checklist/status messages (scoped to avoid matching
    // the createStatus/editStatus Active-Inactive <select> dropdowns)
    modal.querySelectorAll('[id$="PasswordChecklist"], [id$="PasswordStatus"]').forEach(el => el.classList.add('hidden'));
}

document.addEventListener('keydown', e => {
    if (e.key === 'Escape')
        document.querySelectorAll('.fixed[id$="Modal"]:not(.hidden)').forEach(m => closeModal(m.id));
});

function formatDate(str) {
    if (!str) return '<span class="text-gray-400">—</span>';
    try {
        const d = new Date(str);
        if (isNaN(d)) return '<span class="text-gray-400">—</span>';
        return d.toLocaleDateString(undefined, { year:'numeric', month:'short', day:'numeric' });
    } catch { return '<span class="text-gray-400">—</span>'; }
}

// ─── PASSWORD STRENGTH (mirrors App\Helpers\PasswordPolicy::validate()) ──
// Rules: min 8 chars, 1 uppercase, 1 lowercase, 1 number, 1 special char.
function getPasswordRuleResults(password) {
    return {
        length:  password.length >= 8,
        upper:   /[A-Z]/.test(password),
        lower:   /[a-z]/.test(password),
        number:  /[0-9]/.test(password),
        special: /[^A-Za-z0-9]/.test(password),
    };
}

// Returns the first failed rule's message (same order/wording as the backend),
// or null if the password satisfies every rule.
function getPasswordPolicyError(password) {
    const r = getPasswordRuleResults(password);
    if (!r.length)  return 'Password must be at least 8 characters long';
    if (!r.upper)   return 'Password must contain at least 1 uppercase letter';
    if (!r.lower)   return 'Password must contain at least 1 lowercase letter';
    if (!r.number)  return 'Password must contain at least 1 number';
    if (!r.special) return 'Password must contain at least 1 special character (e.g. @, #, $, %, !)';
    return null;
}

// Updates the ✓/✗ checklist under a password field as the user types.
function updatePasswordChecklist(inputId, listId) {
    const password  = document.getElementById(inputId).value;
    const container = document.getElementById(listId);
    if (!container) return;

    // Hide the whole card when the field is empty, show it once typing starts
    if (password.length === 0) {
        container.classList.add('hidden');
        return;
    }
    container.classList.remove('hidden');

    const results = getPasswordRuleResults(password);
    const passedCount = Object.values(results).filter(Boolean).length;

    // Update each rule row (icon + label color)
    container.querySelectorAll('[data-rule]').forEach(row => {
        const rule = row.dataset.rule;
        const icon = row.querySelector('i');
        const passed = results[rule];

        row.classList.toggle('text-emerald-600', passed);
        row.classList.toggle('text-gray-400', !passed);

        icon.className = passed
            ? 'fas fa-check-circle text-emerald-500 w-3 text-center'
            : 'fas fa-circle text-[5px] w-3 text-center';
    });

    // Strength color: red (weak) -> amber (fair) -> emerald (strong)
    let barColor = 'bg-red-400';
    if (passedCount >= 5)      barColor = 'bg-emerald-500';
    else if (passedCount >= 3) barColor = 'bg-amber-400';

    // Fill segments left-to-right based on how many rules currently pass
    container.querySelectorAll('[data-bar]').forEach(bar => {
        const segmentIndex = parseInt(bar.dataset.bar, 10);
        const filled = segmentIndex < passedCount;
        bar.className = `h-1 flex-1 rounded-full transition-colors duration-200 ${filled ? barColor : 'bg-gray-200'}`;
    });

    // Plain-language status message, e.g. "createPasswordChecklist" -> "createPasswordStatus"
    const statusId = listId.replace('Checklist', 'Status');
    const statusEl  = document.getElementById(statusId);
    if (!statusEl) return;

    if (passedCount >= 5) {
        statusEl.innerHTML = '<i class="fas fa-check-circle mr-1"></i>Strong password';
        statusEl.className = 'text-xs font-semibold mb-2 text-emerald-600';
    } else if (passedCount >= 3) {
        statusEl.innerHTML = '<i class="fas fa-exclamation-circle mr-1"></i>Almost there';
        statusEl.className = 'text-xs font-semibold mb-2 text-amber-600';
    } else {
        statusEl.innerHTML = '<i class="fas fa-times-circle mr-1"></i>Password is too weak';
        statusEl.className = 'text-xs font-semibold mb-2 text-red-600';
    }
}

function togglePasswordVisibility(inputId, btn) {
    const input = document.getElementById(inputId);
    const icon  = btn.querySelector('i');
    const showing = input.type === 'text';

    input.type = showing ? 'password' : 'text';
    icon.className = showing ? 'fas fa-eye' : 'fas fa-eye-slash';
}

// ─── FIELD ERROR HANDLING (pure Tailwind, no custom CSS needed) ──────────
function showFieldError(inputId, errId, msg) {
    const input = document.getElementById(inputId);
    input.classList.remove('border-gray-300');
    input.classList.add('border-red-500');

    const err = document.getElementById(errId);
    err.textContent = msg;
    err.classList.remove('hidden');
}

function clearFieldError(input, errId) {
    input.classList.remove('border-red-500');
    input.classList.add('border-gray-300');

    // errId is passed explicitly now so this works regardless of how deeply
    // the input is nested (e.g. Username's @ icon wrapper div).
    const err = errId
        ? document.getElementById(errId)
        : input.parentElement.querySelector('[id$="Err"]');
    if (err) err.classList.add('hidden');
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
</script>