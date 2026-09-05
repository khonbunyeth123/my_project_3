<div class="w-full h-full"> 
    <div class="p-2 space-y-2">
        <!-- Header & Filters -->
        <?php 
            $title = 'Leave Applications';
            $icon = 'mdi:calendar-check text-indigo-500';
            ob_start();
        ?>
            <span class="text-[10px] font-black uppercase tracking-wider bg-indigo-50 text-indigo-600 px-2 py-0.5 rounded-md" id="totalCount">0 Applications</span>
        <?php 
            $headerRight = ob_get_clean();
            ob_start();
        ?>
            <div class="flex flex-col sm:flex-row gap-2">
                <div class="flex-1">
                    <?php 
                        $id = 'searchInput'; $placeholder = 'Search employee...'; $icon = 'mdi:magnify';
                        include 'component/input.php'; 
                        $id = null; $icon = null; // Reset
                    ?>
                </div>
                <div class="flex gap-2">
                    <div class="w-40">
                        <?php 
                            $id = 'leaveTypeFilter'; $placeholder = 'All Types';
                            include 'component/select.php'; 
                            $id = null; // Reset
                        ?>
                    </div>
                    <div class="w-32">
                        <?php 
                            $id = 'statusFilter'; $placeholder = 'All Status';
                            $options = ['0' => 'Pending', '1' => 'Approved', '2' => 'Rejected'];
                            include 'component/select.php'; 
                            $id = null; $options = []; // Reset
                        ?>
                    </div>
                </div>
            </div>
        <?php 
            $content = ob_get_clean();
            $title = 'Leave Applications'; $icon = 'mdi:calendar-check text-indigo-500'; // Set again as card uses them
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
                            <th class="px-3 py-2 font-black uppercase tracking-wider">Employee</th>
                            <th class="px-3 py-2 font-black uppercase tracking-wider">Type</th>
                            <th class="px-3 py-2 font-black uppercase tracking-wider">Dates</th>
                            <th class="px-3 py-2 font-black uppercase tracking-wider">Reason</th>
                            <th class="px-3 py-2 font-black uppercase tracking-wider">Status</th>
                            <th class="px-3 py-2 font-black uppercase tracking-wider">Created</th>
                            <th class="px-3 py-2 font-black uppercase tracking-wider text-right">Action</th>
                        </tr>
                    </thead>
                    <tbody id="leaveTableBody" class="divide-y divide-slate-100 bg-white">
                        <tr>
                            <td colspan="7" class="px-3 py-12 text-center text-slate-400">
                                <div class="flex flex-col items-center justify-center gap-2">
                                    <span class="iconify text-2xl animate-spin opacity-50" data-icon="mdi:loading"></span>
                                    <p class="text-[10px] font-black uppercase tracking-widest">Loading...</p>
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
            $padding = false; $title = null; $icon = null; $headerRight = null; $id = null; $class = '';
            include 'component/card.php'; 
            $padding = true; $footer = null; // Reset
        ?>
    </div>
</div>

<!-- Review Modal -->
<div id="leaveDecisionModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-950/40 backdrop-blur-[2px]">
    <div class="w-full max-w-sm">
        <?php 
            ob_start();
        ?>
            <div class="flex flex-col">
                <h2 id="leaveDecisionTitle" class="text-sm font-black text-slate-800">Review Leave</h2>
                <p id="leaveDecisionText" class="text-[10px] text-slate-500 font-medium">Confirm this action.</p>
            </div>
        <?php 
            $title = ob_get_clean();
            ob_start();
        ?>
            <div class="space-y-4">
                <div id="rejectReasonWrap" class="hidden">
                    <?php 
                        $label = 'Reject reason'; $required = true;
                    ?>
                    <div class="flex flex-col gap-1">
                        <label for="rejectReason" class="text-[10px] font-black text-slate-500 uppercase tracking-wider">
                            <?= $label ?> <span class="text-rose-500">*</span>
                        </label>
                        <textarea id="rejectReason" rows="3"
                            class="w-full border border-slate-200 rounded-lg py-1.5 px-3 text-xs focus:outline-none focus:ring-2 focus:ring-indigo-500/20 focus:border-indigo-500 transition-all placeholder:text-slate-400 bg-white"
                            placeholder="Explain why this request is rejected"></textarea>
                        <p id="rejectReasonError" class="mt-1 hidden text-[10px] font-bold text-rose-500">Please enter a reject reason.</p>
                    </div>
                </div>
                <p id="leaveDecisionError" class="hidden rounded-lg bg-rose-50 px-3 py-2 text-[11px] font-bold text-rose-600 border border-rose-100"></p>
            </div>
        <?php 
            $content = ob_get_clean();
            ob_start();
        ?>
            <div class="flex justify-end gap-2">
                <?php 
                    $label = 'Cancel'; $type = 'secondary'; $size = 'sm'; $attr = 'onclick="closeLeaveDecisionModal()"';
                    include 'component/button.php'; 
                    
                    $label = 'Confirm'; $type = 'primary'; $size = 'sm'; $id = 'confirmLeaveDecision'; $attr = 'onclick="submitLeaveDecision()"';
                    $icon = 'mdi:check';
                    include 'component/button.php';
                    $label = null; $attr = null; $icon = null; $id = null; // Reset
                ?>
            </div>
        <?php 
            $footer = ob_get_clean();
            include 'component/card.php'; 
            $title = null; $footer = null; // Reset
        ?>
    </div>
</div>

<div id="leaveFeedback" class="hidden fixed right-4 top-20 z-50 max-w-sm rounded-xl border border-slate-200 bg-white p-3 shadow-xl shadow-slate-950/10 transition-all duration-300 transform translate-x-full">
    <div class="flex items-start gap-3">
        <div id="feedbackIcon" class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0"></div>
        <div>
            <p id="leaveFeedbackTitle" class="text-xs font-black text-slate-800"></p>
            <p id="leaveFeedbackMessage" class="text-[10px] text-slate-500 font-medium"></p>
        </div>
    </div>
</div>

<script src="/assets/js/pagination.js"></script>
    <script>
        const perPage = 10;
        let currentPage = 1;
        let totalPages = 1;
        let pendingLeaveDecision = null;

        // Helper: Status badge
        function getStatusBadge(statusId) {
            const styles = {
                0: 'bg-amber-50 text-amber-600 border-amber-100', // Pending
                1: 'bg-emerald-50 text-emerald-600 border-emerald-100', // Approved
                2: 'bg-rose-50 text-rose-600 border-rose-100', // Rejected
            };
            const labels = { 0: 'Pending', 1: 'Approved', 2: 'Rejected' };
            const style = styles[statusId] || 'bg-slate-50 text-slate-600 border-slate-100';
            const label = labels[statusId] || 'Unknown';

            return `<span class="${style} px-1.5 py-0.5 rounded text-[9px] font-black uppercase tracking-wider border">${label}</span>`;
        }

        function showFeedback(title, message, type = 'success') {
            const box = document.getElementById('leaveFeedback');
            const iconBox = document.getElementById('feedbackIcon');
            const titleEl = document.getElementById('leaveFeedbackTitle');
            const msgEl = document.getElementById('leaveFeedbackMessage');

            titleEl.textContent = title;
            msgEl.textContent = message;

            if (type === 'success') {
                iconBox.className = 'w-8 h-8 rounded-lg bg-emerald-100 text-emerald-600 flex items-center justify-center shrink-0';
                iconBox.innerHTML = '<span class="iconify" data-icon="mdi:check-circle"></span>';
                box.classList.add('border-emerald-200');
                box.classList.remove('border-rose-200');
            } else {
                iconBox.className = 'w-8 h-8 rounded-lg bg-rose-100 text-rose-600 flex items-center justify-center shrink-0';
                iconBox.innerHTML = '<span class="iconify" data-icon="mdi:alert-circle"></span>';
                box.classList.add('border-rose-200');
                box.classList.remove('border-emerald-200');
            }

            box.classList.remove('hidden');
            setTimeout(() => box.classList.remove('translate-x-full'), 10);

            setTimeout(() => {
                box.classList.add('translate-x-full');
                setTimeout(() => box.classList.add('hidden'), 3000);
            }, 3000);
        }

        function setDecisionLoading(isLoading) {
            const btn = document.getElementById('confirmLeaveDecision');
            const label = document.getElementById('confirmLeaveDecisionText');
            const isReject = pendingLeaveDecision?.action === 'reject';
            btn.disabled = isLoading;
            if (isLoading) {
                btn.classList.add('opacity-50', 'pointer-events-none');
                btn.innerHTML = '<span class="iconify animate-spin" data-icon="mdi:loading"></span> Processing...';
            } else {
                btn.classList.remove('opacity-50', 'pointer-events-none');
                btn.innerHTML = `<span class="iconify" data-icon="mdi:check"></span> ${isReject ? 'Reject' : 'Approve'}`;
            }
        }

        function openLeaveDecisionModal(action, uuid) {
            pendingLeaveDecision = { action, uuid };
            const isReject = action === 'reject';

            document.getElementById('leaveDecisionTitle').textContent = isReject ? 'Reject Request' : 'Approve Request';
            document.getElementById('leaveDecisionText').textContent = isReject
                ? 'Please provide a reason for rejection.'
                : 'Confirm this leave application?';
            document.getElementById('rejectReasonWrap').classList.toggle('hidden', !isReject);
            document.getElementById('rejectReason').value = '';
            document.getElementById('rejectReasonError').classList.add('hidden');
            document.getElementById('leaveDecisionError').classList.add('hidden');
            
            const confirmBtn = document.getElementById('confirmLeaveDecision');
            confirmBtn.className = `inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 text-xs font-black text-white transition shadow-sm active:scale-95 ${
                isReject ? 'bg-rose-600 hover:bg-rose-700' : 'bg-emerald-600 hover:bg-emerald-700'
            }`;
            confirmBtn.innerHTML = `<span class="iconify" data-icon="mdi:check"></span> ${isReject ? 'Reject' : 'Approve'}`;
            
            document.getElementById('leaveDecisionModal').classList.remove('hidden');
            document.getElementById('leaveDecisionModal').classList.add('flex');

            if (isReject) {
                setTimeout(() => document.getElementById('rejectReason').focus(), 50);
            }
        }

        function closeLeaveDecisionModal() {
            if (document.getElementById('confirmLeaveDecision').disabled) return;
            pendingLeaveDecision = null;
            document.getElementById('leaveDecisionModal').classList.add('hidden');
            document.getElementById('leaveDecisionModal').classList.remove('flex');
        }

        // Load leave applications from server
        function loadLeaveApplications(page = 1) {
            const search = document.getElementById("searchInput").value;
            const leaveType = document.getElementById("leaveTypeFilter").value;
            const status = document.getElementById("statusFilter").value;

            const params = new URLSearchParams({
                "page": page,
                "per_page": perPage,
                "filters[employee_name]": search,
                "filters[leave_type]": leaveType,
                "filters[status_id]": status
            });

            fetch("/api/leave/list?" + params.toString())
                .then(res => res.json())
                .then(result => {
                    if (result.success && result.data) {
                        renderTable(result.data.leave_applications);

                        currentPage = result.pagination.page;
                        totalPages = result.pagination.total_pages;
                        document.getElementById("totalCount").textContent = `${result.pagination.total} Applications`;

                        // Populate leave type filter once
                        const typeSelect = document.getElementById("leaveTypeFilter");
                        if (typeSelect.options.length <= 1) {
                            result.data.leave_types?.forEach(type => {
                                const opt = document.createElement("option");
                                opt.value = type;
                                opt.textContent = type;
                                typeSelect.appendChild(opt);
                            });
                        }

                        renderPagination({
                            currentPage: currentPage,
                            totalPages: totalPages,
                            showingFrom: result.data.leave_applications.length
                                ? ((currentPage - 1) * perPage) + 1
                                : 0,
                            showingTo: result.data.leave_applications.length
                                ? ((currentPage - 1) * perPage) + result.data.leave_applications.length
                                : 0,
                            totalRecords: result.pagination.total,
                            onPrevious: () => currentPage > 1 && loadLeaveApplications(currentPage - 1),
                            onNext: () => currentPage < totalPages && loadLeaveApplications(currentPage + 1),
                            onPageClick: (p) => loadLeaveApplications(p)
                        });
                    } else {
                        document.getElementById("leaveTableBody").innerHTML = '<tr><td colspan="7" class="px-4 py-12 text-center text-slate-400">No applications found</td></tr>';
                    }
                })
                .catch(err => {
                    console.error(err);
                    document.getElementById("leaveTableBody").innerHTML = '<tr><td colspan="7" class="px-4 py-12 text-center text-rose-500">Error loading data</td></tr>';
                });
        }

        // Render table rows
        function renderTable(records) {
            const tbody = document.getElementById("leaveTableBody");
            if (!records.length) {
                tbody.innerHTML = '<tr><td colspan="7" class="px-3 py-12 text-center text-slate-400">No leave applications found</td></tr>';
                return;
            }

            tbody.innerHTML = records.map(rec => `
                <tr class="hover:bg-slate-50 transition-colors group">
                    <td class="px-3 py-2">
                        <div class="flex items-center gap-2">
                            <div class="flex flex-col">
                                <span class="text-[11px] font-black text-slate-800 group-hover:text-indigo-600 transition-colors">${rec.employee_name}</span>
                                <span class="text-[9px] text-slate-400 font-bold uppercase tracking-tight">${rec.employee_id ? '#' + rec.employee_id : ''}</span>
                            </div>
                        </div>
                    </td>
                    <td class="px-3 py-2"><span class="bg-indigo-50 no-wrap text-indigo-600 px-1.5 py-0.5 rounded text-[9px] font-black normal-case border border-indigo-100">${rec.leave_type}</span></td>
                    <td class="px-3 py-2">
                        <div class="flex flex-col">
                            <span class="text-[9px] font-bold text-slate-400 uppercase tracking-tighter">${new Date(rec.end_date).toLocaleDateString(undefined, {month:'short',day:'numeric',year:'2-digit'})}</span>
                        </div>
                    </td>
                    <td class="px-3 py-2"><p class="text-[10px] font-medium text-slate-500 max-w-[120px] truncate" title="${rec.reason || ''}">${rec.reason || '-'}</p></td>
                    <td class="px-3 py-2">${getStatusBadge(rec.status_id)}</td>
                    <td class="px-3 py-2"><span class="text-[9px] font-black text-slate-400 normal-case tracking-tight">${new Date(rec.created_at).toLocaleDateString(undefined, {month:'short',day:'numeric'})}</span></td>
                    <td class="px-3 py-2 text-right">
                    ${rec.status_id == 0 && (window.leavePermissions.approve || window.leavePermissions.reject) ? `
                            <div class="flex items-center justify-end gap-1">
                                ${window.leavePermissions.approve ? `<button
                                    onclick="approveLeave('${rec.uuid}')"
                                    class="inline-flex items-center gap-0.5 rounded-lg bg-white border border-emerald-200 px-2 py-1 text-[9px] font-black text-emerald-600 hover:bg-emerald-50 transition-all shadow-sm active:scale-95"
                                    title="Approve"
                                >
                                    Approve
                                </button>` : ''}
                                ${window.leavePermissions.reject ? `<button
                                    onclick="rejectLeave('${rec.uuid}')"
                                    class="inline-flex items-center gap-0.5 rounded-lg bg-white border border-rose-200 px-2 py-1 text-[9px] font-black text-rose-600 hover:bg-rose-50 transition-all shadow-sm active:scale-95"
                                    title="Reject"
                                >
                                    Reject
                                </button>` : ''}
                            </div>
                        ` : `
                            <span class="text-[10px] font-bold text-slate-300">—</span>
                        `}
                    </td>
                </tr>
            `).join('');
        }

        // Approve / Reject leave
        window.leavePermissions = <?= json_encode([
            'approve' => hasAnyPermissionSlugs(['leave.approve', 'leave.update', 'leave.view']),
            'reject' => hasAnyPermissionSlugs(['leave.reject', 'leave.update', 'leave.view']),
        ], JSON_THROW_ON_ERROR) ?>;

        function approveLeave(uuid) {
            if (!window.leavePermissions.approve) return;
            openLeaveDecisionModal('approve', uuid);
        }

        function rejectLeave(uuid) {
            if (!window.leavePermissions.reject) return;
            openLeaveDecisionModal('reject', uuid);
        }

        function submitLeaveDecision() {
            if (!pendingLeaveDecision) return;

            const isReject = pendingLeaveDecision.action === 'reject';
            const remark = document.getElementById('rejectReason').value.trim();
            const reasonError = document.getElementById('rejectReasonError');
            const modalError = document.getElementById('leaveDecisionError');

            reasonError.classList.add('hidden');
            modalError.classList.add('hidden');

            if (isReject && !remark) {
                reasonError.classList.remove('hidden');
                document.getElementById('rejectReason').focus();
                return;
            }

            setDecisionLoading(true);

            fetch(isReject ? "/api/leave/reject" : "/api/leave/approve", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify(isReject
                    ? { uuid: pendingLeaveDecision.uuid, remark }
                    : { uuid: pendingLeaveDecision.uuid })
            })
            .then(res => res.json())
            .then(data => {
                setDecisionLoading(false);

                if (data.success) {
                    closeLeaveDecisionModal();
                    showFeedback('Leave updated', data.message || 'The leave request was updated.');
                    loadLeaveApplications(currentPage);
                } else {
                    modalError.textContent = data.message || 'Unable to update this leave request.';
                    modalError.classList.remove('hidden');
                }
            })
            .catch(err => {
                setDecisionLoading(false);
                modalError.textContent = 'Server error. Please try again.';
                modalError.classList.remove('hidden');
                console.error(err);
            });
        }

        document.getElementById('leaveDecisionModal').addEventListener('click', (event) => {
            if (event.target.id === 'leaveDecisionModal') {
                closeLeaveDecisionModal();
            }
        });

        document.addEventListener('keydown', (event) => {
            if (event.key === 'Escape') {
                closeLeaveDecisionModal();
            }
        });


        // Event listeners
        document.getElementById("searchInput").addEventListener("input", ()=>loadLeaveApplications(1));
        document.getElementById("leaveTypeFilter").addEventListener("change", ()=>loadLeaveApplications(1));
        document.getElementById("statusFilter").addEventListener("change", ()=>loadLeaveApplications(1));

        // Initial load
        loadLeaveApplications(1);
    </script>
