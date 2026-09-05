<div class="w-full h-full">
    <div class="p-2 space-y-4">
        <!-- Header & Top Stats -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <?php 
                $title = 'Total Payroll';
                $icon = 'mdi:cash-multiple text-indigo-500';
                ob_start();
            ?>
                <div class="flex flex-col">
                    <span class="text-2xl font-black text-slate-800" id="totalAmount">$0.00</span>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest" id="payrollPeriodName">No period selected</span>
                </div>
            <?php 
                $content = ob_get_clean();
                include 'component/card.php';

                $title = 'Employees Paid';
                $icon = 'mdi:account-group text-emerald-500';
                ob_start();
            ?>
                <div class="flex flex-col">
                    <span class="text-2xl font-black text-slate-800" id="totalEmployees">0</span>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest">In this period</span>
                </div>
            <?php 
                $content = ob_get_clean();
                include 'component/card.php';

                $title = 'Status';
                $icon = 'mdi:info-outline text-amber-500';
                ob_start();
            ?>
                <div class="flex flex-col">
                    <div id="payrollStatusBadge">
                        <span class="bg-slate-100 text-slate-500 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider">N/A</span>
                    </div>
                    <span class="text-[10px] text-slate-400 font-bold uppercase tracking-widest mt-1" id="processedDate">-</span>
                </div>
            <?php 
                $content = ob_get_clean();
                include 'component/card.php';

                $title = 'Quick Actions';
                $icon = 'mdi:lightning-bolt text-rose-500';
                ob_start();
            ?>
                <div class="flex gap-2">
                    <?php 
                        if (hasAnyPermissionSlugs(['payroll.manage'])) {
                            $label = 'Run Payroll'; $type = 'primary'; $size = 'xs'; $icon = 'mdi:play-circle-outline'; $attr = 'onclick="openGenerateModal()"';
                            include 'component/button.php';
                            $label = 'Approve'; $type = 'success'; $size = 'xs'; $icon = 'mdi:check-decagram'; $attr = 'id="approveBtn" onclick="approvePayroll()" disabled';
                            include 'component/button.php';
                        }
                    ?>
                </div>
            <?php 
                $content = ob_get_clean();
                include 'component/card.php';
            ?>
        </div>

        <!-- Filters & Tools -->
        <?php 
            ob_start();
        ?>
            <div class="flex flex-col md:flex-row gap-4 items-center justify-between">
                <div class="flex gap-2 items-center">
                    <div class="w-32">
                        <select id="monthFilter" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-bold text-slate-700 outline-none focus:border-indigo-500 transition-all">
                            <?php for($m=1; $m<=12; $m++): ?>
                                <option value="<?= $m ?>" <?= $m == date('n') ? 'selected' : '' ?>><?= date('F', mktime(0, 0, 0, $m, 1)) ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="w-24">
                        <select id="yearFilter" class="w-full bg-slate-50 border border-slate-200 rounded-lg px-3 py-1.5 text-xs font-bold text-slate-700 outline-none focus:border-indigo-500 transition-all">
                            <?php for($y=date('Y'); $y>=2025; $y--): ?>
                                <option value="<?= $y ?>"><?= $y ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <?php 
                        $label = 'Load Data'; $type = 'secondary'; $size = 'sm'; $icon = 'mdi:refresh'; $attr = 'onclick="loadPayrollData()"';
                        include 'component/button.php';
                    ?>
                </div>
                <div class="flex gap-2">
                    <?php 
                        if (hasAnyPermissionSlugs(['payroll.manage'])) {
                            $label = 'Salary Settings'; $type = 'secondary'; $size = 'sm'; $icon = 'mdi:cog-outline'; $attr = 'onclick="openConfigModal()"';
                            include 'component/button.php';
                        }
                    ?>
                </div>
            </div>
        <?php 
            $content = ob_get_clean();
            include 'component/card.php';
        ?>

        <!-- Payroll Records Table -->
        <?php 
            ob_start();
        ?>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-[11px]">
                    <thead class="bg-slate-900 text-white">
                        <tr>
                            <th class="px-3 py-3 font-black uppercase tracking-wider">Employee</th>
                            <th class="px-3 py-3 font-black uppercase tracking-wider">Base Salary</th>
                            <th class="px-3 py-3 font-black uppercase tracking-wider text-emerald-400">Allowances</th>
                            <th class="px-3 py-3 font-black uppercase tracking-wider text-amber-400">OT Pay</th>
                            <th class="px-3 py-3 font-black uppercase tracking-wider text-rose-400">Unpaid Days</th>
                            <th class="px-3 py-3 font-black uppercase tracking-wider text-rose-400">Deduction</th>
                            <th class="px-3 py-3 font-black uppercase tracking-wider bg-indigo-600">Net Salary</th>
                            <th class="px-3 py-3 font-black uppercase tracking-wider text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="payrollTableBody" class="divide-y divide-slate-100 bg-white">
                        <tr>
                            <td colspan="8" class="px-3 py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="iconify text-2xl opacity-20" data-icon="mdi:cash-clock"></span>
                                    <p class="text-[10px] font-black uppercase tracking-widest">Select a period and load data</p>
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
        ?>
    </div>
</div>

<!-- Generate Payroll Modal -->
<div id="generateModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/40 backdrop-blur-[2px] p-4">
    <div class="w-full max-w-sm">
        <?php 
            $title = 'Run Monthly Payroll';
            ob_start();
        ?>
            <div class="space-y-4">
                <p class="text-[11px] text-slate-500 font-medium">This will calculate salaries for all employees based on their attendance and leave records.</p>
                <div class="grid grid-cols-2 gap-3">
                    <?php 
                        $label = 'Month'; $id = 'genMonth'; $placeholder = 'Month';
                        include 'component/select.php';
                        $label = 'Year'; $id = 'genYear'; $placeholder = 'Year';
                        include 'component/select.php';
                    ?>
                </div>
            </div>
        <?php 
            $content = ob_get_clean();
            ob_start();
        ?>
            <div class="flex justify-end gap-2">
                <?php 
                    $label = 'Cancel'; $type = 'secondary'; $size = 'sm'; $attr = 'onclick="closeGenerateModal()"';
                    include 'component/button.php';
                    $label = 'Generate'; $type = 'primary'; $size = 'sm'; $icon = 'mdi:play-circle-outline'; $attr = 'onclick="submitGenerate()"'; $id = "genSubmitBtn";
                    include 'component/button.php';
                ?>
            </div>
        <?php 
            $footer = ob_get_clean();
            include 'component/card.php';
        ?>
    </div>
</div>

<!-- Salary Config Modal -->
<div id="configModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/40 backdrop-blur-[2px] p-4">
    <div class="w-full max-w-lg">
        <?php 
            $title = 'Salary Configuration';
            ob_start();
        ?>
            <form id="configForm" class="space-y-4">
                <div class="space-y-3">
                    <?php 
                        $label = 'Select Employee'; $id = 'configEmployeeId'; $placeholder = 'Choose staff...';
                        include 'component/select.php';
                    ?>
                </div>
                <div id="configFields" class="hidden space-y-4 pt-4 border-t border-slate-100">
                    <div class="grid grid-cols-2 gap-3">
                        <?php 
                            $label = 'Base Salary ($)'; $id = 'base_salary'; $type = 'number'; $attr = 'step="0.01"';
                            include 'component/input.php';
                            $label = 'Transport Allowance ($)'; $id = 'allowance_transport'; $type = 'number'; $attr = 'step="0.01"';
                            include 'component/input.php';
                            $label = 'Food Allowance ($)'; $id = 'allowance_food'; $type = 'number'; $attr = 'step="0.01"';
                            include 'component/input.php';
                            $label = 'Phone Allowance ($)'; $id = 'allowance_phone'; $type = 'number'; $attr = 'step="0.01"';
                            include 'component/input.php';
                            $label = 'Other Allowance ($)'; $id = 'allowance_other'; $type = 'number'; $attr = 'step="0.01"';
                            include 'component/input.php';
                        ?>
                    </div>
                </div>
            </form>
        <?php 
            $content = ob_get_clean();
            ob_start();
        ?>
            <div class="flex justify-end gap-2">
                <?php 
                    $label = 'Cancel'; $type = 'secondary'; $size = 'sm'; $attr = 'onclick="closeConfigModal()"';
                    include 'component/button.php';
                    $label = 'Save Config'; $type = 'primary'; $size = 'sm'; $icon = 'mdi:content-save-outline'; $attr = 'onclick="submitConfig()"'; $id = "configSubmitBtn";
                    include 'component/button.php';
                ?>
            </div>
        <?php 
            $footer = ob_get_clean();
            include 'component/card.php';
        ?>
    </div>
</div>

<script>
let currentPeriodId = null;

async function loadPayrollData() {
    const month = document.getElementById('monthFilter').value;
    const year = document.getElementById('yearFilter').value;
    const tbody = document.getElementById('payrollTableBody');

    tbody.innerHTML = `<tr><td colspan="8" class="px-3 py-12 text-center text-slate-400"><div class="flex flex-col items-center justify-center gap-2"><span class="iconify text-2xl animate-spin opacity-50" data-icon="mdi:loading"></span><p class="text-[10px] font-black uppercase tracking-widest">Loading Records...</p></div></td></tr>`;

    try {
        const res = await fetch(`/api/payroll/summary?month=${month}&year=${year}`);
        const json = await res.json();

        if (json.success && json.data.period) {
            const data = json.data;
            currentPeriodId = data.period.id;
            
            document.getElementById('totalAmount').textContent = '$' + parseFloat(data.period.total_amount).toLocaleString(undefined, {minimumFractionDigits: 2});
            document.getElementById('totalEmployees').textContent = data.period.total_employees;
            document.getElementById('payrollPeriodName').textContent = data.period.name;
            
            const statusBadge = document.getElementById('payrollStatusBadge');
            const processedDate = document.getElementById('processedDate');
            const approveBtn = document.getElementById('approveBtn');

            if (data.period.status === 'approved' || data.period.status === 'paid') {
                statusBadge.innerHTML = `<span class="bg-emerald-100 text-emerald-600 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider">${data.period.status}</span>`;
                processedDate.textContent = data.period.approved_at || data.period.processed_at;
                approveBtn.disabled = true;
            } else {
                statusBadge.innerHTML = `<span class="bg-amber-100 text-amber-600 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider">Draft</span>`;
                processedDate.textContent = 'Awaiting Approval';
                approveBtn.disabled = false;
            }

            if (data.records.length > 0) {
                tbody.innerHTML = data.records.map(r => `
                    <tr class="hover:bg-slate-50 transition-colors">
                        <td class="px-3 py-3">
                            <div class="flex flex-col">
                                <span class="font-black text-slate-800">${r.full_name}</span>
                                <span class="text-[9px] text-slate-400 font-bold uppercase">${r.position} • ${r.department}</span>
                            </div>
                        </td>
                        <td class="px-3 py-3 font-bold text-slate-600">$${parseFloat(r.base_salary).toFixed(2)}</td>
                        <td class="px-3 py-3 font-bold text-emerald-600">$${parseFloat(r.total_allowances).toFixed(2)}</td>
                        <td class="px-3 py-3">
                            <div class="flex flex-col">
                                <span class="font-bold text-amber-600">$${parseFloat(r.overtime_amount).toFixed(2)}</span>
                                <span class="text-[9px] text-slate-400 font-bold">${r.overtime_hours} hrs</span>
                            </div>
                        </td>
                        <td class="px-3 py-3 font-bold text-rose-500">${r.unpaid_leave_days} d</td>
                        <td class="px-3 py-3 font-bold text-rose-600">-$${parseFloat(r.unpaid_leave_deduction).toFixed(2)}</td>
                        <td class="px-3 py-3 font-black text-indigo-700 bg-indigo-50/30">$${parseFloat(r.net_salary).toFixed(2)}</td>
                        <td class="px-3 py-3 text-center">
                             <button class="w-7 h-7 rounded-lg bg-slate-100 text-slate-600 flex items-center justify-center hover:bg-indigo-600 hover:text-white transition-all mx-auto shadow-sm">
                                <span class="iconify" data-icon="mdi:eye-outline"></span>
                             </button>
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = `<tr><td colspan="8" class="px-3 py-12 text-center text-slate-400"><div class="flex flex-col items-center justify-center gap-2"><span class="iconify text-2xl opacity-20" data-icon="mdi:cash-off"></span><p class="text-[10px] font-black uppercase tracking-widest">No records for this period</p></div></td></tr>`;
            }
        } else {
            resetDashboard();
            tbody.innerHTML = `<tr><td colspan="8" class="px-3 py-12 text-center text-slate-400"><div class="flex flex-col items-center justify-center gap-2"><span class="iconify text-2xl opacity-20" data-icon="mdi:cash-clock"></span><p class="text-[10px] font-black uppercase tracking-widest">Payroll not yet generated for this month</p></div></td></tr>`;
        }
    } catch (err) {
        console.error(err);
    }
}

function resetDashboard() {
    currentPeriodId = null;
    document.getElementById('totalAmount').textContent = '$0.00';
    document.getElementById('totalEmployees').textContent = '0';
    document.getElementById('payrollPeriodName').textContent = 'No period selected';
    document.getElementById('payrollStatusBadge').innerHTML = `<span class="bg-slate-100 text-slate-500 px-2 py-0.5 rounded-md text-[10px] font-black uppercase tracking-wider">N/A</span>`;
    document.getElementById('processedDate').textContent = '-';
    document.getElementById('approveBtn').disabled = true;
}

// Generate Modal Logic
function openGenerateModal() {
    const m = document.getElementById('monthFilter').value;
    const y = document.getElementById('yearFilter').value;
    
    // Populate select options
    const mSelect = document.getElementById('genMonth');
    const ySelect = document.getElementById('genYear');
    
    mSelect.innerHTML = document.getElementById('monthFilter').innerHTML;
    ySelect.innerHTML = document.getElementById('yearFilter').innerHTML;
    
    mSelect.value = m;
    ySelect.value = y;
    
    document.getElementById('generateModal').classList.replace('hidden', 'flex');
}

function closeGenerateModal() {
    document.getElementById('generateModal').classList.replace('flex', 'hidden');
}

async function submitGenerate() {
    const month = document.getElementById('genMonth').value;
    const year = document.getElementById('genYear').value;
    const btn = document.getElementById('genSubmitBtn');
    
    btn.disabled = true;
    btn.innerHTML = '<span class="iconify animate-spin" data-icon="mdi:loading"></span> Generating...';

    try {
        const res = await fetch('/api/payroll/generate', {
            method: 'POST',
            body: JSON.stringify({ month, year }),
            headers: { 'Content-Type': 'application/json' }
        });
        const json = await res.json();
        
        if (json.success) {
            closeGenerateModal();
            loadPayrollData();
        } else {
            alert(json.message);
        }
    } catch (err) {
        console.error(err);
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Generate';
    }
}

// Approve Logic
async function approvePayroll() {
    if (!currentPeriodId) return;
    
    if (!confirm('Are you sure you want to approve this payroll? Once approved, it cannot be edited.')) return;

    try {
        const res = await fetch('/api/payroll/approve', {
            method: 'POST',
            body: JSON.stringify({ period_id: currentPeriodId }),
            headers: { 'Content-Type': 'application/json' }
        });
        const json = await res.json();
        
        if (json.success) {
            loadPayrollData();
        } else {
            alert(json.message);
        }
    } catch (err) {
        console.error(err);
    }
}

// Config Modal Logic
async function openConfigModal() {
    const modal = document.getElementById('configModal');
    const select = document.getElementById('configEmployeeId');
    
    modal.classList.replace('hidden', 'flex');
    
    // Load employees list
    try {
        const res = await fetch('/api/employees');
        const json = await res.json();
        if (json.success) {
            select.innerHTML = '<option value="">Choose staff...</option>' + 
                json.data.map(e => `<option value="${e.id}">${e.full_name}</option>`).join('');
        }
    } catch (err) {
        console.error(err);
    }
}

document.getElementById('configEmployeeId').addEventListener('change', async function() {
    const empId = this.value;
    const fields = document.getElementById('configFields');
    
    if (!empId) {
        fields.classList.add('hidden');
        return;
    }

    try {
        const res = await fetch(`/api/payroll/config/${empId}`);
        const json = await res.json();
        
        fields.classList.remove('hidden');
        const data = json.data || {};
        
        document.getElementById('base_salary').value = data.base_salary || 0;
        document.getElementById('allowance_transport').value = data.allowance_transport || 0;
        document.getElementById('allowance_food').value = data.allowance_food || 0;
        document.getElementById('allowance_phone').value = data.allowance_phone || 0;
        document.getElementById('allowance_other').value = data.allowance_other || 0;
        
    } catch (err) {
        console.error(err);
    }
});

function closeConfigModal() {
    document.getElementById('configModal').classList.replace('flex', 'hidden');
    document.getElementById('configFields').classList.add('hidden');
    document.getElementById('configForm').reset();
}

async function submitConfig() {
    const empId = document.getElementById('configEmployeeId').value;
    if (!empId) return;

    const btn = document.getElementById('configSubmitBtn');
    const formData = new FormData(document.getElementById('configForm'));
    const data = Object.fromEntries(formData.entries());
    
    btn.disabled = true;
    btn.innerHTML = '<span class="iconify animate-spin" data-icon="mdi:loading"></span> Saving...';

    try {
        const res = await fetch(`/api/payroll/config/${empId}`, {
            method: 'POST',
            body: JSON.stringify(data),
            headers: { 'Content-Type': 'application/json' }
        });
        const json = await res.json();
        
        if (json.success) {
            closeConfigModal();
        } else {
            alert(json.message);
        }
    } catch (err) {
        console.error(err);
    } finally {
        btn.disabled = false;
        btn.innerHTML = 'Save Config';
    }
}

// Initial Load
document.addEventListener('DOMContentLoaded', loadPayrollData);
</script>
