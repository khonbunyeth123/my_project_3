<div class="w-full h-full"> 
    <div class="p-2 space-y-2">
        <!-- Header & Filters -->
        <?php 
            $title = 'Employee Directory';
            $icon = 'mdi:users-group text-indigo-500';
            ob_start();
        ?>
            <div class="flex items-center gap-2">
                <span class="text-[10px] font-bold normal-case tracking-wider bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-md" id="totalCount">0 Staff</span>
                <?php 
                    $label = 'Add Employee'; $type = 'primary'; $size = 'xs'; $icon = 'mdi:plus-circle'; $attr = 'onclick="openCreateModal()"'; $id = null;
                    include 'component/button.php'; 
                    $label = null; $attr = null; $icon = null; // Important: Reset
                ?>
            </div>
        <?php 
            $headerRight = ob_get_clean();
            ob_start();
        ?>
            <div class="flex flex-col sm:flex-row gap-2">
                <div class="flex-1">
                    <?php 
                        $id = 'searchInput'; $placeholder = 'Search employee...'; $icon = 'mdi:magnify'; $label = null;
                        include 'component/input.php'; 
                        $id = null; $icon = null; // Reset
                    ?>
                </div>
                <div class="flex flex-wrap gap-2">
                    <div class="w-40">
                        <?php 
                            $id = 'departmentFilter'; $placeholder = 'All Departments'; $label = null;
                            include 'component/select.php'; 
                            $id = null; $placeholder = null; // Reset
                        ?>
                    </div>
                    <div class="w-40">
                        <?php 
                            $id = 'positionFilter'; $placeholder = 'All Positions'; $label = null;
                            include 'component/select.php'; 
                            $id = null; $placeholder = null; // Reset
                        ?>
                    </div>
                    <div class="w-40">
                        <?php 
                            $id = 'statusFilter'; $placeholder = 'All Statuses'; $label = null;
                            $options = ['1' => 'Active', '2' => 'Inactive'];
                            include 'component/select.php'; 
                            $id = null; $placeholder = null; $options = []; // Reset
                        ?>
                    </div>
                </div>
            </div>
        <?php 
            $content = ob_get_clean();
            $title = 'Employee Directory'; $icon = 'mdi:users-group text-indigo-500'; // Set for card
            include 'component/card.php'; 
            $title = null; $icon = null; $headerRight = null; // Reset
        ?>

        <!-- Table Card -->
        <?php 
            ob_start();
        ?>
            <div class="sticky-table-wrapper overflow-x-auto">
                <table class="w-full text-left text-[11px]">
                    <thead class="bg-slate-900 text-white sticky top-0 z-10">
                        <tr>
                            <th class="px-3 py-2 font-bold tracking-wider">Emp ID</th>
                            <th class="px-3 py-2 font-bold tracking-wider">Name</th>
                            <th class="px-3 py-2 font-bold tracking-wider">Username</th>
                            <th class="px-3 py-2 font-bold tracking-wider">Phone</th>
                            <th class="px-3 py-2 font-bold tracking-wider">Position</th>
                            <th class="px-3 py-2 font-bold tracking-wider">Department</th>
                            <th class="px-3 py-2 font-bold tracking-wider">Hired</th>
                            <th class="px-3 py-2 font-bold tracking-wider">Status</th>
                            <th class="px-3 py-2 font-bold tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="employeeTableBody" class="divide-y divide-slate-100 bg-white">
                        <tr>
                            <td colspan="9" class="px-3 py-12 text-center text-slate-400">
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
            ob_start();
        ?>
            <div id="paginationContainer"></div>
        <?php 
            $footer = ob_get_clean();
            $padding = false; $title = null; $icon = null; $headerRight = null; $id = null; $class = ''; $bodyClass = '';
            include 'component/card.php'; 
            $padding = true; $footer = null; // Reset
        ?>
    </div>
</div>

<!-- ── Create / Edit Modal ────────────────────────────── -->
<div id="employeeModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/40 backdrop-blur-[2px] p-4">
    <div class="w-full max-w-3xl max-h-[95vh] overflow-y-auto no-scrollbar">
        <?php 
            ob_start();
        ?>
            <div class="flex flex-col">
                <h2 id="modalTitle" class="text-sm font-bold text-slate-800">Add New Employee</h2>
                <p class="text-[10px] text-slate-500 font-medium">Fill in the employee details below.</p>
            </div>
        <?php 
            $title = ob_get_clean();
            ob_start();
        ?>
            <button onclick="closeModal()" class="w-6 h-6 flex items-center justify-center rounded-full bg-slate-50 text-slate-500 hover:text-slate-900 transition-colors text-xs">✕</button>
        <?php 
            $headerRight = ob_get_clean();
            ob_start();
        ?>
            <form id="employeeForm" class="space-y-4">
                <input type="hidden" id="employeeId">
                
                <!-- Personal Information -->
                <div class="space-y-3">
                    <h3 class="text-[10px] font-bold uppercase tracking-widest text-indigo-500 border-b border-slate-100 pb-1 flex items-center gap-1.5">
                        <span class="iconify" data-icon="mdi:account"></span>
                        Personal Information
                    </h3>
                    
                    <!-- Photo Upload -->
                    <div class="bg-slate-50 p-3 rounded-xl border border-slate-100 flex items-center gap-4">
                        <div class="relative shrink-0">
                            <img id="photoPreview"
                                src="data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='96' height='96' viewBox='0 0 96 96'%3E%3Ccircle cx='48' cy='48' r='48' fill='%23e0e7ff'/%3E%3Ccircle cx='48' cy='38' r='17' fill='%23a5b4fc'/%3E%3Cellipse cx='48' cy='84' rx='26' ry='22' fill='%23a5b4fc'/%3E%3C/svg%3E"
                                class="w-14 h-14 rounded-full object-cover border-2 border-white shadow-sm">
                            <button type="button" id="removePhotoBtn" onclick="removePhoto()"
                                class="hidden absolute -top-1 -right-1 w-5 h-5 rounded-full bg-rose-500 text-white text-[10px] flex items-center justify-center border-2 border-white hover:bg-rose-600 transition-colors">✕</button>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p id="photoFileName" class="hidden text-[10px] font-semibold text-indigo-600 truncate mb-0.5"></p>
                            <p class="text-[11px] font-semibold text-slate-700">Profile Photo</p>
                            <p id="photoHint" class="text-[9px] text-slate-400 uppercase font-bold tracking-tight">JPG, PNG, WEBP — max 2 MB</p>
                            <p id="photoError" class="hidden text-[9px] text-rose-500 font-bold mt-1"></p>
                        </div>
                        <label for="photo" class="cursor-pointer shrink-0">
                            <?php 
                                $label = 'Upload'; $type = 'secondary'; $size = 'xs'; $icon = 'mdi:upload'; $attr = 'style="pointer-events:none"';
                                include 'component/button.php';
                                $label = null; $attr = null; $icon = null; // Reset icon too!
                            ?>
                        </label>
                        <input type="file" id="photo" accept="image/jpeg,image/png,image/webp" class="hidden">
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                        <?php 
                            $label = 'First Name'; $id = 'first_name'; $placeholder = 'John'; $required = true; $icon = 'mdi:account-outline';
                            include 'component/input.php';
                            $placeholder = null; // Reset

                            $label = 'Last Name'; $id = 'last_name'; $placeholder = 'Doe'; $required = true; $icon = 'mdi:account-outline';
                            include 'component/input.php';
                            $placeholder = null; // Reset

                            $label = 'Gender'; $id = 'gender'; $required = true; $placeholder = 'Select Gender'; $icon = 'mdi:gender-male-female';
                            $options = ['male' => 'Male', 'female' => 'Female'];
                            include 'component/select.php';
                            $options = []; $icon = null; $placeholder = null; // Reset
                            
                            $label = 'Username'; $id = 'username'; $placeholder = 'johndoe'; $required = true; $icon = 'mdi:at';
                            include 'component/input.php';
                            $icon = null; $placeholder = null; // Reset

                            $label = 'Employee ID'; $id = 'employee_code'; $placeholder = 'Auto-generated'; $attr = 'readonly'; $icon = 'mdi:badge-account';
                            include 'component/input.php';
                            $icon = null; $attr = null; $placeholder = null; // Reset

                            $label = 'Email'; $id = 'email'; $type = 'email'; $placeholder = 'john@example.com'; $required = true; $icon = 'mdi:email-outline';
                            include 'component/input.php';
                            $icon = null; $placeholder = null; // Reset

                            $label = 'Address'; $id = 'address'; $type = 'text'; $placeholder = '123 Main St...'; $required = true; $containerClass = 'md:col-span-2'; $icon = 'mdi:map-marker-outline';
                            include 'component/input.php';
                            $icon = null; $containerClass = null; $placeholder = null; // Reset

                            $label = 'Date of Birth'; $id = 'dob'; $type = 'date'; $required = true; $containerClass = ''; $icon = 'mdi:calendar-outline';
                            include 'component/input.php';
                            $icon = null; $placeholder = null; // Reset

                            $label = 'Phone'; $id = 'phone'; $type = 'tel'; $placeholder = '012 345 678'; $icon = 'mdi:phone-outline';
                            include 'component/input.php';
                            $icon = null; $placeholder = null; // Reset

                            $label = 'Password'; $id = 'password'; $type = 'password'; $placeholder = '••••••••'; $icon = 'mdi:lock-outline';
                            include 'component/input.php';
                            $label = null; $icon = null; $placeholder = null; // Reset
                        ?>
                        <div class="md:col-span-1 flex flex-col justify-end">
                            <p class="text-[9px] text-slate-400 font-bold uppercase leading-tight" id="passwordHelp">Mobile login password.</p>
                        </div>
                    </div>
                </div>

                <div class="h-px bg-slate-100 my-2"></div>

                <!-- Job Information -->
                <div class="space-y-3">
                    <h3 class="text-[10px] font-bold uppercase tracking-widest text-emerald-500 border-b border-slate-100 pb-1 flex items-center gap-1.5">
                        <span class="iconify" data-icon="mdi:briefcase-outline"></span>
                        Job Information
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                        <?php 
                            $label = 'Position'; $id = 'position'; $type = 'text'; $placeholder = 'Software Engineer'; $required = true; $icon = 'mdi:tag-outline';
                            include 'component/input.php';
                            $label = 'Date Hired'; $id = 'date_hired'; $type = 'date'; $required = true;
                            include 'component/input.php';
                            $label = 'Status'; $id = 'status_id'; $required = true; $placeholder = 'Select Status';
                            $options = ['1' => 'Active', '2' => 'Inactive'];
                            include 'component/select.php';
                            $label = null; // Reset 
                        ?>
                        <div class="flex flex-col gap-1">
                            <label for="department_id" class="text-[10px] font-black text-slate-500 normal-case tracking-wider">
                                Department <span class="text-rose-500">*</span>
                            </label>
                            <div class="flex items-center gap-2">
                                <div class="relative flex-1">
                                    <select
                                        id="department_id"
                                        name="department_id"
                                        required
                                        class="w-full border border-slate-200 rounded-lg py-1.5 px-3 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all bg-white appearance-none cursor-pointer pl-8"
                                    >
                                        <option value="">Loading departments...</option>
                                    </select>
                                    <div class="absolute left-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none flex items-center justify-center">
                                        <span class="iconify text-[14px]" data-icon="mdi:office-building-outline"></span>
                                    </div>
                                    <div class="absolute right-2.5 top-1/2 -translate-y-1/2 text-slate-400 pointer-events-none flex items-center justify-center">
                                        <span class="iconify text-[14px]" data-icon="mdi:chevron-down"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        <?php 
            $content = ob_get_clean();
            ob_start();
        ?>
            <div class="flex justify-end gap-2">
                <?php 
                    $label = 'Cancel'; $type = 'secondary'; $size = 'sm'; $attr = 'onclick="closeModal()"';
                    include 'component/button.php';
                    $label = 'Save Employee'; $type = 'primary'; $size = 'sm'; $icon = 'mdi:content-save-outline'; $attr = 'form="employeeForm" type="submit"'; $id = 'submitButton';
                    include 'component/button.php';
                    $label = null; $attr = null; $id = null; // Reset
                ?>
            </div>
        <?php 
            $footer = ob_get_clean();
            include 'component/card.php'; 
            $title = null; $footer = null; // Reset
        ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div id="deleteModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/40 backdrop-blur-[2px] p-4">
    <div class="w-full max-w-xs">
        <?php 
            $title = 'Delete Employee';
            ob_start();
        ?>
            <div class="text-center py-2">
                <div class="w-12 h-12 rounded-full bg-rose-50 text-rose-500 flex items-center justify-center mx-auto mb-3 border border-rose-100">
                    <span class="iconify text-2xl" data-icon="mdi:alert-outline"></span>
                </div>
                <p class="text-[11px] text-slate-600 font-medium">Are you sure you want to delete <strong id="deleteEmployeeName" class="text-slate-900"></strong>? This action cannot be undone.</p>
            </div>
        <?php 
            $content = ob_get_clean();
            ob_start();
        ?>
            <div class="grid grid-cols-2 gap-2">
                <?php 
                    $label = 'Cancel'; $type = 'secondary'; $size = 'sm'; $attr = 'onclick="closeDeleteModal()"';
                    include 'component/button.php';
                    $label = 'Delete'; $type = 'danger'; $size = 'sm'; $icon = 'mdi:delete-outline'; $attr = 'onclick="confirmDelete()"';
                    include 'component/button.php';
                    $label = null; $attr = null; // Reset
                ?>
            </div>
        <?php 
            $footer = ob_get_clean();
            include 'component/card.php'; 
            $title = null; $footer = null; // Reset
        ?>
    </div>
</div>

<!-- Toast placeholder -->
<div id="toast" class="hidden fixed top-4 right-4 z-[9999]"></div>

<script src="/assets/js/pagination.js"></script>
<script>
/* ── DOM References ─────────────────────────── */
const employeeForm     = document.getElementById('employeeForm');
const employeeIdInput  = document.getElementById('employeeId');
const employeeCodeInput = document.getElementById('employee_code');
const usernameInput    = document.getElementById('username');
const firstNameInput   = document.getElementById('first_name');
const lastNameInput    = document.getElementById('last_name');
const genderInput      = document.getElementById('gender');
const emailInput       = document.getElementById('email');
const phoneInput       = document.getElementById('phone');
const addressInput     = document.getElementById('address');
const dobInput         = document.getElementById('dob');
const positionInput    = document.getElementById('position');
const departmentSelect = document.getElementById('department_id');
const dateHiredInput   = document.getElementById('date_hired');
const statusIdInput    = document.getElementById('status_id');
const passwordInput    = document.getElementById('password');
const passwordHelp     = document.getElementById('passwordHelp');

const photoInput       = document.getElementById('photo');
const photoPreview     = document.getElementById('photoPreview');
const removePhotoBtn   = document.getElementById('removePhotoBtn');
const photoHint        = document.getElementById('photoHint');
const photoFileName    = document.getElementById('photoFileName');
const photoError       = document.getElementById('photoError');

const searchInput          = document.getElementById('searchInput');
const departmentFilter     = document.getElementById('departmentFilter');
const positionFilter       = document.getElementById('positionFilter');
const statusFilter         = document.getElementById('statusFilter');
const employeeTableBody    = document.getElementById('employeeTableBody');
const totalCount           = document.getElementById('totalCount');
const paginationContainer  = document.getElementById('paginationContainer');

const employeeModal    = document.getElementById('employeeModal');
const deleteModal      = document.getElementById('deleteModal');
const deleteEmpName    = document.getElementById('deleteEmployeeName');

/* ── State ──────────────────────────────────── */
let allEmployees    = [];
let departmentCatalog = [];
let currentPage     = 1;
const perPage       = 18;
let totalPages      = 1;
let deleteEmpId     = null;
let photoBase64     = null;   // holds selected photo as base64 data URL

const DEFAULT_AVATAR = photoPreview.src;   // save original placeholder

/* ── Helpers: Status Badge ───────────────────── */
function statusBadge(statusId) {
    const styles = {
        1: 'bg-emerald-50 text-emerald-600 border-emerald-100', // Active
        2: 'bg-slate-50 text-slate-400 border-slate-100',      // Inactive
        3: 'bg-amber-50 text-amber-600 border-amber-100',      // On Leave
    };
    const labels = { 1: 'Active', 2: 'Inactive', 3: 'On Leave' };
    const style = styles[statusId] || 'bg-slate-50 text-slate-400 border-slate-100';
    const label = labels[statusId] || 'Unknown';

    return `<span class="${style} px-1.5 py-0.5 rounded text-[9px] font-bold normal-case tracking-wider border">${label}</span>`;
}

function getCurrentDateString() {
    return new Date().toISOString().split('T')[0];
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

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

function hasSelectOption(selectEl, value) {
    return Array.from(selectEl?.options || []).some(option => String(option.value) === String(value));
}

function normalizeDepartmentRecord(raw) {
    return {
        id: raw?.id ?? '',
        name: raw?.name ?? '',
    };
}

function renderDepartmentOptions() {
    if (!departmentSelect) return;

    const currentValue = departmentSelect.value;
    const currentFilterValue = departmentFilter ? departmentFilter.value : '';
    const pendingId = departmentSelect.dataset.pendingId || '';
    const pendingName = departmentSelect.dataset.pendingName || '';

    departmentSelect.innerHTML = '<option value="">Select department</option>' + departmentCatalog
        .map(dept => `<option value="${escapeHtml(dept.id)}">${escapeHtml(dept.name)}</option>`)
        .join('');

    const departmentFilterHtml = '<option value="">All Departments</option>' + departmentCatalog
        .map(dept => `<option value="${escapeHtml(dept.name)}">${escapeHtml(dept.name)}</option>`)
        .join('');

    if (departmentFilter) {
        departmentFilter.innerHTML = departmentFilterHtml;
        if (currentFilterValue && hasSelectOption(departmentFilter, currentFilterValue)) {
            departmentFilter.value = currentFilterValue;
        }
    }

    if (pendingId && hasSelectOption(departmentSelect, pendingId)) {
        departmentSelect.value = pendingId;
    } else if (pendingName) {
        const match = departmentCatalog.find(dept => String(dept.name).toLowerCase() === String(pendingName).toLowerCase());
        if (match) {
            departmentSelect.value = String(match.id);
        }
    } else if (currentValue && hasSelectOption(departmentSelect, currentValue)) {
        departmentSelect.value = currentValue;
    }

    delete departmentSelect.dataset.pendingId;
    delete departmentSelect.dataset.pendingName;
}

function setDepartmentSelection(id = '', name = '') {
    if (!departmentSelect) return;

    departmentSelect.dataset.pendingId = id ? String(id) : '';
    departmentSelect.dataset.pendingName = name ? String(name) : '';
    renderDepartmentOptions();
}

async function loadDepartments() {
    try {
        const res = await fetch('/api/departments');
        const json = await res.json();

        if (json.success && Array.isArray(json.data)) {
            departmentCatalog = json.data.map(normalizeDepartmentRecord).filter(dept => dept.id && dept.name);
        } else {
            departmentCatalog = [];
        }
    } catch (err) {
        console.error('Failed to load departments', err);
        departmentCatalog = [];
    }

    renderDepartmentOptions();
}

/* ── Toast ──────────────────────────────────── */
function showToast(title, message, type = 'success') {
    const container = document.getElementById('toast');
    const toast = document.createElement('div');
    toast.className = `flex items-center gap-3 p-3 rounded-xl border bg-white shadow-xl shadow-slate-950/10 transition-all duration-300 transform translate-x-full mb-2`;
    
    if (type === 'success') {
        toast.classList.add('border-emerald-200');
        iconHtml = '<div class="w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0"><span class="iconify" data-icon="mdi:check-circle"></span></div>';
    } else {
        toast.classList.add('border-rose-200');
        iconHtml = '<div class="w-8 h-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center shrink-0"><span class="iconify" data-icon="mdi:alert-circle"></span></div>';
    }

    toast.innerHTML = `
        ${iconHtml}
        <div>
            <p class="text-xs font-semibold text-slate-800">${title}</p>
            <p class="text-[10px] text-slate-500 font-medium">${message}</p>
        </div>
    `;

    container.appendChild(toast);
    container.classList.remove('hidden');
    
    setTimeout(() => toast.classList.remove('translate-x-full'), 10);

    setTimeout(() => {
        toast.classList.add('translate-x-full');
        setTimeout(() => {
            toast.remove();
            if (container.children.length === 0) container.classList.add('hidden');
        }, 300);
    }, 3500);
}

/* ── Photo Upload Logic ─────────────────────── */
photoInput.addEventListener('change', () => {
    const file = photoInput.files[0];
    photoError.classList.add('hidden');
    photoFileName.classList.add('hidden');

    if (!file) return;

    if (!file.type.startsWith('image/')) {
        photoError.textContent = 'Please select a valid image file (JPG, PNG, WEBP).';
        photoError.classList.remove('hidden');
        photoInput.value = '';
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        photoError.textContent = 'Image must be under 2 MB. Please choose a smaller file.';
        photoError.classList.remove('hidden');
        photoInput.value = '';
        return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
        photoBase64 = e.target.result;
        photoPreview.src = photoBase64;
        removePhotoBtn.classList.remove('hidden');
        photoHint.classList.add('hidden');
        photoFileName.textContent = file.name;
        photoFileName.classList.remove('hidden');
    };
    reader.readAsDataURL(file);
});

function removePhoto() {
    photoBase64 = null;
    photoPreview.src = DEFAULT_AVATAR;
    removePhotoBtn.classList.add('hidden');
    photoInput.value = '';
    photoFileName.classList.add('hidden');
    photoError.classList.add('hidden');
    photoHint.classList.remove('hidden');
}

/* ── Load Employees ─────────────────────────── */
async function loadEmployees() {
    console.log('loadEmployees: starting');
    try {
        const res  = await fetch('/api/employees');
        console.log('loadEmployees: fetch completed');
        const json = await res.json();
        console.log('loadEmployees: API Response parsed:', json);

        // The API returns {success: true, message: 'Success', data: [...]}
        if (!json.success) { 
            console.error('loadEmployees: json.success is false');
            showToast('Error', 'Failed to load employees: ' + (json.message || 'Unknown error'), 'error'); 
            return; 
        }

        console.log('loadEmployees: processing data');
        const rows = Array.isArray(json.data) ? json.data : [];
        allEmployees = rows.map(normalizeEmployeeRecord);
        console.log('loadEmployees: data processed, count:', allEmployees.length);

        totalCount.textContent = `${allEmployees.length} Staff`;
        populateFilters();
        currentPage = 1;
        applyFilters();
        console.log('loadEmployees: completed');
    } catch (err) {
        console.error('loadEmployees: catch block triggered', err);
        showToast('Error', 'Failed to connect to server', 'error');
    }
}

/* ── Populate Filter Dropdowns ──────────────── */
function populateFilters() {
    const currentDepartmentFilter = departmentFilter ? departmentFilter.value : '';
    const currentPositionFilter = positionFilter ? positionFilter.value : '';

    const sourceDepartments = departmentCatalog.length
        ? departmentCatalog.map(dept => dept.name)
        : [...new Set(allEmployees.map(e => e.department).filter(Boolean))].sort();
    const posts = [...new Set(allEmployees.map(e => e.position).filter(Boolean))].sort();

    departmentFilter.innerHTML = '<option value="">All Departments</option>' +
        sourceDepartments.map(d => `<option value="${escapeHtml(d)}">${escapeHtml(d)}</option>`).join('');
    positionFilter.innerHTML = '<option value="">All Positions</option>' +
        posts.map(p => `<option value="${escapeHtml(p)}">${escapeHtml(p)}</option>`).join('');

    if (currentDepartmentFilter && hasSelectOption(departmentFilter, currentDepartmentFilter)) {
        departmentFilter.value = currentDepartmentFilter;
    }
    if (currentPositionFilter && hasSelectOption(positionFilter, currentPositionFilter)) {
        positionFilter.value = currentPositionFilter;
    }
}

/* ── Filter & Pagination ────────────────────── */
function applyFilters() {
    console.log('applyFilters: starting');
    const search = searchInput.value.toLowerCase();
    const dept   = departmentFilter.value;
    const pos    = positionFilter.value;
    const status = statusFilter.value;

    const filtered = allEmployees.filter(e => {
        const matchSearch = (e.full_name || '').toLowerCase().includes(search) ||
                            (e.username  || '').toLowerCase().includes(search) ||
                            (e.employee_code || '').toLowerCase().includes(search) ||
                            (e.phone     || '').toLowerCase().includes(search);
        const matchDept   = !dept || e.department === dept;
        const matchPos    = !pos  || e.position   === pos;
        const matchStatus = !status || String(e.status_id ?? '') === status;
        return matchSearch && matchDept && matchPos && matchStatus;
    });
    console.log('applyFilters: filtered count:', filtered.length);

    totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
    if (currentPage > totalPages) currentPage = totalPages;

    renderTable(filtered);
    
    // Call Global Pagination
    console.log('applyFilters: calling renderPagination');
    renderPagination({
        currentPage: currentPage,
        totalPages: totalPages,
        showingFrom: filtered.length > 0 ? ((currentPage - 1) * perPage) + 1 : 0,
        showingTo: filtered.length > 0 ? Math.min(currentPage * perPage, filtered.length) : 0,
        totalRecords: filtered.length,
        onPrevious: () => goToPage(currentPage - 1),
        onNext: () => goToPage(currentPage + 1),
        onPageClick: (page) => goToPage(page)
    });
    console.log('applyFilters: completed');
}

function renderTable(data) {
    console.log('renderTable: starting, data count:', data.length);
    const start = (currentPage - 1) * perPage;
    const rows  = data.slice(start, start + perPage);
    console.log('renderTable: rows count:', rows.length);

    if (!rows.length) {
        employeeTableBody.innerHTML = `
            <tr><td colspan="9" class="px-3 py-12 text-center text-slate-400 font-medium">
                <span class="iconify text-3xl mx-auto mb-2 opacity-20" data-icon="mdi:account-search"></span>
                No employees found
            </td></tr>`;
        return;
    }

    employeeTableBody.innerHTML = rows.map(e => {

        return `
        <tr class="hover:bg-slate-50 transition-colors group">
            <td class="px-3 py-1.5 text-[10px] font-medium text-slate-500 normal-case tracking-tight">${e.employee_code || '-'}</td>
            <td class="px-3 py-1.5">
                <div class="flex items-center gap-2">
                    <span class="font-semibold text-slate-700 group-hover:text-indigo-600 transition-colors">${e.full_name}</span>
                </div>
            </td>
            <td class="px-3 py-1.5 text-[10px] font-medium text-slate-600 normal-case tracking-tight">@${e.username}</td>
            <td class="px-3 py-1.5 text-[10px] font-medium text-slate-600">${e.phone || '-'}</td>
            <td class="px-3 py-1.5 text-[10px] font-medium text-slate-600 normal-case tracking-tight">${e.position}</td>
            <td class="px-3 py-1.5 text-[10px] font-medium text-slate-400 normal-case tracking-tight">${e.department}</td>
            <td class="px-3 py-1.5 text-[9px] font-medium text-slate-400 normal-case tracking-tight">${e.date_hired ?? '-'}</td>
            <td class="px-3 py-1.5">${statusBadge(e.status_id)}</td>
            <td class="px-3 py-1.5">
                <div class="flex gap-1 justify-center">
                    <button type="button" class="js-edit-employee inline-flex items-center gap-0.5 px-2 py-0.5 bg-white border border-slate-200 text-slate-600 rounded-md hover:bg-indigo-50 hover:border-indigo-200 hover:text-indigo-600 text-[9px] font-bold normal-case tracking-wider transition-all shadow-sm active:scale-95" data-id="${encodeDataAttr(e.id)}">
                        <span class="iconify" data-icon="mdi:pencil"></span> Edit
                    </button>
                    <button type="button" class="js-delete-employee inline-flex items-center gap-0.5 px-2 py-0.5 bg-white border border-slate-200 text-slate-600 rounded-md hover:bg-rose-50 hover:border-rose-200 hover:text-rose-600 text-[9px] font-bold normal-case tracking-wider transition-all shadow-sm active:scale-95" data-id="${encodeDataAttr(e.id)}" data-name="${encodeDataAttr(e.full_name || '')}">
                        <span class="iconify" data-icon="mdi:trash-can-outline"></span> Del
                    </button>
                </div>
            </td>
        </tr>`;
    }).join('');
    console.log('renderTable: completed');
}

function goToPage(p) {
    currentPage = Math.min(Math.max(p, 1), totalPages);
    applyFilters();
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

/* ── Modals ─────────────────────────────────── */
function resetPhotoUI() {
    photoBase64 = null;
    photoPreview.src = DEFAULT_AVATAR;
    removePhotoBtn.classList.add('hidden');
    photoInput.value = '';
    photoFileName.classList.add('hidden');
    photoError.classList.add('hidden');
    photoHint.classList.remove('hidden');
}

function toDateInputValue(value) {
    if (!value) return '';
    const normalized = String(value).slice(0, 10);
    return /^\d{4}-\d{2}-\d{2}$/.test(normalized) ? normalized : '';
}

function normalizeGender(value) {
    const v = String(value ?? '').trim().toLowerCase();
    if (v === 'male' || v === 'm') return 'male';
    if (v === 'female' || v === 'f') return 'female';
    return '';
}

function formatEmployeeCode(value) {
    const n = Number(value);
    if (!Number.isInteger(n) || n <= 0) return '';
    return String(n);
}

function normalizeEmployeeRecord(raw) {
    const e = raw || {};
    const firstName = e.first_name ?? e.firstName ?? e.firstname ?? '';
    const lastName  = e.last_name ?? e.lastName ?? e.lastname ?? '';
    const fullName  = e.full_name ?? e.fullName ?? `${firstName} ${lastName}`.trim();

    return {
        id: e.id ?? '',
        employee_code: formatEmployeeCode(e.id),
        username: e.username ?? e.user_name ?? '',
        first_name: firstName,
        last_name: lastName,
        full_name: fullName,
        gender: normalizeGender(e.gender),
        email: e.email ?? '',
        phone: e.phone ?? e.phone_number ?? e.phoneNumber ?? '',
        address: e.address ?? '',
        dob: e.dob ?? e.date_of_birth ?? e.birth_date ?? '',
        position: e.position ?? '',
        department: e.department ?? '',
        department_id: e.department_id ?? '',
        date_hired: e.date_hired ?? e.hire_date ?? '',
        status_id: e.status_id ?? e.status ?? 1,
        photo: e.photo ?? e.avatar ?? null,
        uuid: e.uuid ?? '',
    };
}

function fillEmployeeForm(rawEmployee) {
    const e = normalizeEmployeeRecord(rawEmployee);

    employeeIdInput.value   = e.id ?? '';
    employeeCodeInput.value = e.employee_code ?? '';
    usernameInput.value     = e.username ?? '';
    firstNameInput.value    = e.first_name ?? '';
    lastNameInput.value     = e.last_name ?? '';
    genderInput.value       = e.gender ?? '';
    emailInput.value        = e.email ?? '';
    phoneInput.value        = e.phone ?? '';
    addressInput.value      = e.address ?? '';
    dobInput.value          = toDateInputValue(e.dob);
    positionInput.value     = e.position ?? '';
    setDepartmentSelection(e.department_id ?? '', e.department ?? '');
    dateHiredInput.value    = toDateInputValue(e.date_hired);
    statusIdInput.value     = String(e.status_id ?? 1);
    passwordInput.value     = '';

    resetPhotoUI();
    if (e.photo) {
        photoBase64 = e.photo;
        photoPreview.src = e.photo;
        removePhotoBtn.classList.remove('hidden');
        photoHint.classList.add('hidden');
        photoFileName.textContent = 'Existing photo';
        photoFileName.classList.remove('hidden');
    }
}

function setPasswordMode(isCreate) {
    passwordInput.required = isCreate;
    passwordInput.placeholder = isCreate
        ? 'Required for new employee'
        : 'Leave blank to keep current';
    passwordHelp.textContent = isCreate
        ? 'SET THE MOBILE LOGIN PASSWORD.'
        : 'LEAVE BLANK IF NO CHANGE.';
}

function openCreateModal() {
    // 1. Reset the form via the browser API
    employeeForm.reset();

    // 2. Explicitly clear every specific field to ensure no lingering data
    const fields = [
        'employeeId', 'employee_code', 'username', 'first_name', 
        'last_name', 'gender', 'email', 'phone', 'address', 
        'dob', 'position', 'department_id', 'date_hired', 'status_id', 'password'
    ];
    
    fields.forEach(id => {
        const el = document.getElementById(id);
        if (el) {
            el.value = '';
        }
    });

    // 3. Reset UI components
    setPasswordMode(true);
    document.getElementById('date_hired').value = getCurrentDateString();
    setDepartmentSelection('', '');
    document.getElementById('modalTitle').textContent = 'Add New Employee';
    document.getElementById('submitButton').innerHTML = '<span class="iconify" data-icon="mdi:content-save-outline"></span> Save Employee';
    resetPhotoUI();
    employeeModal.classList.remove('hidden');
    employeeModal.classList.add('flex');
}

async function openEditModal(id) {
    const cached = allEmployees.find(emp => String(emp.id) === String(id));
    if (cached) {
        fillEmployeeForm(cached);
    } else {
        employeeForm.reset();
        employeeIdInput.value = String(id);
        employeeCodeInput.value = '';
        resetPhotoUI();
    }

    try {
        const res = await fetch(`/api/employees/${id}`);
        const json = await res.json();
        if (res.ok && json.success) {
            const apiEmployee = json.data ?? json.employee ?? null;
            if (apiEmployee) {
                fillEmployeeForm(apiEmployee);
            }
        } else if (!cached) {
            showToast('Error', json.message || 'Failed to load employee details.', 'error');
        }
    } catch (err) {
        console.error(err);
        if (!cached) {
            showToast('Error', 'Network error while loading employee details.', 'error');
        }
    }

    document.getElementById('modalTitle').textContent = 'Edit Employee';
    document.getElementById('submitButton').innerHTML = '<span class="iconify" data-icon="mdi:content-save-outline"></span> Update Employee';
    setPasswordMode(false);
    employeeModal.classList.remove('hidden');
    employeeModal.classList.add('flex');
}

function closeModal() {
    employeeModal.classList.add('hidden');
    employeeModal.classList.remove('flex');
    resetPhotoUI();
}

function openDeleteModal(id, name) {
    deleteEmpId = id;
    deleteEmpName.textContent = name;
    deleteModal.classList.remove('hidden');
    deleteModal.classList.add('flex');
}

function closeDeleteModal() {
    deleteEmpId = null;
    deleteModal.classList.add('hidden');
    deleteModal.classList.remove('flex');
}

/* ── Create / Update ────────────────────────── */
employeeForm.addEventListener('submit', async e => {
    e.preventDefault();
    const id = employeeIdInput.value;

    const payload = {
        username:   usernameInput.value.trim(),
        password:   passwordInput.value,
        first_name: firstNameInput.value.trim(),
        last_name:  lastNameInput.value.trim(),
        full_name:  (firstNameInput.value.trim() + ' ' + lastNameInput.value.trim()).trim(),
        gender:     genderInput.value,
        email:      emailInput.value.trim(),
        phone:      phoneInput.value.trim(),
        address:    addressInput.value.trim(),
        dob:        dobInput.value,
        position:   positionInput.value.trim(),
        department_id: departmentSelect.value,
        date_hired: dateHiredInput.value,
        status_id:  Number(statusIdInput.value),
    };

    if (!payload.department_id) {
        showToast('Error', 'Please select a department.', 'error');
        return;
    }

    const url    = id ? `/api/employees/${id}` : '/api/employees';
    const method = 'POST'; // Always use POST to support multipart/form-data (required for file uploads in PHP)

    const btn = document.getElementById('submitButton');
    const originalContent = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="iconify animate-spin" data-icon="mdi:loading"></span> Saving...';

    try {
        const fd = new FormData();
        Object.entries(payload).forEach(([key, val]) => {
            if (val !== null && val !== undefined) fd.append(key, val);
        });
        
        // Only upload photo if it's a new one (Base64 data URL)
        if (photoBase64 && photoBase64.startsWith('data:')) {
            const photoRes = await fetch(photoBase64);
            const blob = await photoRes.blob();
            fd.append('photo', blob, 'photo.jpg');
        }

        const res  = await fetch(url, {
            method,
            body: fd,
        });
        const json = await res.json();

        if (json.success) {
            showToast('Success', json.message || (id ? 'Employee updated.' : 'Employee added.'));
            closeModal();
            loadEmployees();
        } else {
            showToast('Error', json.message || 'Operation failed.', 'error');
        }
    } catch (err) {
        showToast('Error', 'Network or server error.', 'error');
        console.error(err);
    } finally {
        btn.disabled = false;
        btn.innerHTML = originalContent;
    }
});

/* ── Delete ─────────────────────────────────── */
async function confirmDelete() {
    if (!deleteEmpId) return;
    try {
        const res  = await fetch(`/api/employees/${deleteEmpId}`, { method: 'DELETE' });
        const json = await res.json();

        if (json.success) {
            showToast('Deleted', 'Employee removed successfully.');
            closeDeleteModal();
            loadEmployees();
        } else {
            showToast('Error', json.message || 'Delete failed.', 'error');
        }
    } catch (err) {
        showToast('Error', 'Network or server error.', 'error');
        console.error(err);
    }
}

/* ── Close modals on backdrop click ─────────── */
employeeModal.addEventListener('click', e => { if (e.target === employeeModal) closeModal(); });
deleteModal.addEventListener('click',   e => { if (e.target === deleteModal)   closeDeleteModal(); });

employeeTableBody.addEventListener('click', e => {
    const editButton = e.target.closest('.js-edit-employee');
    if (editButton) {
        openEditModal(decodeDataAttr(editButton.dataset.id));
        return;
    }

    const deleteButton = e.target.closest('.js-delete-employee');
    if (deleteButton) {
        openDeleteModal(
            decodeDataAttr(deleteButton.dataset.id),
            decodeDataAttr(deleteButton.dataset.name)
        );
    }
});

searchInput.addEventListener('input', () => { currentPage = 1; applyFilters(); });
departmentFilter.addEventListener('change', () => { currentPage = 1; applyFilters(); });
positionFilter.addEventListener('change',   () => { currentPage = 1; applyFilters(); });
statusFilter.addEventListener('change',     () => { currentPage = 1; applyFilters(); });

/* ── Init ────────────────────────────────────── */
loadDepartments();
loadEmployees();

const actionFromQuery = new URLSearchParams(window.location.search).get('action');
if ((actionFromQuery || '').toLowerCase() === 'add') {
    openCreateModal();
}
</script>
