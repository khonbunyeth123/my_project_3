<?php
$requestedDate = $_GET['date'] ?? date('Y-m-d');
$today = preg_match('/^\d{4}-\d{2}-\d{2}$/', (string) $requestedDate)
    ? (string) $requestedDate
    : date('Y-m-d');
$currentRole = strtolower((string) ($_SESSION['role'] ?? $_SESSION['role_name'] ?? ''));
$currentRoleId = (int) ($_SESSION['role_id'] ?? 0);
?>
<script>window.__calendarInitialDate = <?= json_encode($today) ?>;</script>
<script>window.__calendarUserRole = <?= json_encode($currentRole) ?>;</script>
<script>window.__calendarUserRoleId = <?= json_encode($currentRoleId) ?>;</script>
<script>window.__calendarPermissions = <?= json_encode([
    'view' => hasAnyPermissionSlugs(['calendar.view', 'calendar.manage']),
    'manage' => hasPermissionSlug('calendar.manage'),
    'approveLeave' => hasAnyPermissionSlugs(['leave.approve', 'leave.update', 'leave.view', 'calendar.manage']),
    'rejectLeave' => hasAnyPermissionSlugs(['leave.reject', 'leave.update', 'leave.view', 'calendar.manage']),
], JSON_THROW_ON_ERROR) ?>;</script>

<style>
/* ── Toast ── */
#toastContainer {
    position: fixed; bottom: 24px; right: 24px; z-index: 9999;
    display: flex; flex-direction: column; gap: 10px; pointer-events: none;
}
.toast {
    display: flex; align-items: center; gap: 10px;
    padding: 12px 18px; border-radius: 16px;
    font-size: 13px; font-weight: 700; color: #fff;
    opacity: 0; transform: translateY(12px);
    transition: opacity .25s, transform .25s;
    pointer-events: auto; max-width: 320px;
    box-shadow: 0 4px 20px rgba(0,0,0,.18);
}
.toast.show { opacity: 1; transform: translateY(0); }
.toast.success { background: #059669; }
.toast.error   { background: #dc2626; }
.toast.info    { background: #4f46e5; }

/* ── Confirm / Reject modal ── */
#confirmOverlay {
    position: fixed; inset: 0; z-index: 8000;
    background: rgba(15,23,42,.55);
    display: flex; align-items: center; justify-content: center;
    padding: 16px; opacity: 0; pointer-events: none;
    transition: opacity .2s;
}
#confirmOverlay.open { opacity: 1; pointer-events: auto; }
#confirmBox {
    background: #fff; border-radius: 24px;
    padding: 28px 28px 22px; width: 100%; max-width: 420px;
    transform: scale(.96); transition: transform .2s;
    box-shadow: 0 24px 64px rgba(0,0,0,.18);
}
#confirmOverlay.open #confirmBox { transform: scale(1); }
#confirmBox h4 { margin: 0 0 6px; font-size: 17px; font-weight: 900; color: #0f172a; }
#confirmBox p  { margin: 0 0 18px; font-size: 13px; color: #64748b; line-height: 1.6; }
#rejectRemarkWrap { display: none; margin-bottom: 16px; }
#rejectRemarkWrap label { display: block; font-size: 11px; font-weight: 800; text-transform: uppercase; letter-spacing: .18em; color: #64748b; margin-bottom: 6px; }
#rejectRemark { width: 100%; border: 1.5px solid #e2e8f0; border-radius: 12px; padding: 10px 14px; font-size: 13px; font-family: inherit; resize: vertical; min-height: 80px; box-sizing: border-box; }
#rejectRemark:focus { outline: none; border-color: #6366f1; }
.confirm-btns { display: flex; gap: 8px; justify-content: flex-end; }
.confirm-btns button { border-radius: 14px; padding: 10px 20px; font-size: 13px; font-weight: 800; border: none; cursor: pointer; transition: opacity .15s; }
.confirm-btns button:hover { opacity: .85; }
#confirmCancelBtn { background: #f1f5f9; color: #334155; }
#confirmOkBtn { background: #0f172a; color: #fff; }
#confirmOkBtn.danger { background: #dc2626; }
#confirmOkBtn.success-btn { background: #059669; }

/* ── Event modal ── */
#eventModal {
    position: fixed; inset: 0; z-index: 7000;
    background: rgba(15,23,42,.55);
    display: flex; align-items: center; justify-content: center;
    padding: 16px; opacity: 0; pointer-events: none;
    transition: opacity .2s;
}
#eventModal.open { opacity: 1; pointer-events: auto; }
#eventModalBox {
    width: 100%; max-width: 860px;
    background: #fff; border-radius: 28px;
    overflow: hidden; transform: scale(.97);
    transition: transform .2s;
    box-shadow: 0 24px 64px rgba(0,0,0,.18);
    max-height: 92vh; overflow-y: auto;
}
#eventModal.open #eventModalBox { transform: scale(1); }

/* ── Loading overlay on calendar panel ── */
#calendarLoadMask {
    position: absolute; inset: 0; border-radius: 24px;
    background: rgba(255,255,255,.75);
    display: flex; align-items: center; justify-content: center;
    z-index: 10; opacity: 0; pointer-events: none;
    transition: opacity .2s;
}
#calendarLoadMask.show { opacity: 1; pointer-events: auto; }
.spinner {
    width: 32px; height: 32px; border: 3px solid #e2e8f0;
    border-top-color: #6366f1; border-radius: 50%;
    animation: spin .7s linear infinite;
}
@keyframes spin { to { transform: rotate(360deg); } }

/* ── Debounce visual for filters ── */
.filter-loading select { opacity: .6; pointer-events: none; }

/* ── Weekday buttons ── */
.weekday-btn.active { background: #0f172a !important; color: #fff !important; border-color: #0f172a !important; }

/* ── Scrollbar in right panel table ── */
.events-table-wrap::-webkit-scrollbar { width: 5px; }
.events-table-wrap::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 99px; }
</style>

<div id="toastContainer" aria-live="polite" aria-atomic="true"></div>

<!-- ── Confirm/Reject overlay ── -->
<div id="confirmOverlay" role="dialog" aria-modal="true" aria-labelledby="confirmTitle">
    <div id="confirmBox">
        <h4 id="confirmTitle">Confirm action</h4>
        <p id="confirmMessage">Are you sure?</p>
        <div id="rejectRemarkWrap">
            <label for="rejectRemark">Rejection reason <span style="color:#dc2626">*</span></label>
            <textarea id="rejectRemark" placeholder="Enter reason for rejection..."></textarea>
            <p id="remarkError" style="font-size:12px; color:#dc2626; margin:4px 0 0; display:none;">Reason is required.</p>
        </div>
        <div class="confirm-btns">
            <button id="confirmCancelBtn" type="button">Cancel</button>
            <button id="confirmOkBtn" type="button">Confirm</button>
        </div>
    </div>
</div>

<div class="w-full min-h-full">
    <div class="p-2 space-y-2">

        <!-- Header -->
        <div class="rounded-lg bg-gradient-to-r from-slate-950 via-slate-900 to-indigo-950 px-3 py-2 text-white shadow-sm">
            <div class="flex flex-col gap-2 xl:flex-row xl:items-center xl:justify-between">
                <div>
                    <div class="inline-flex items-center gap-1 rounded-full border border-white/10 bg-white/5 px-2 py-0.5 text-[8px] font-black uppercase tracking-[0.25em] text-indigo-200">
                        <span class="iconify text-[10px]" data-icon="mdi:calendar-multiselect" aria-hidden="true"></span>
                        HRM Calendar
                    </div>
                </div>
                <div class="flex flex-wrap gap-1">
                    <?php
                        $label = 'Month';
                        $class = 'view-switch';
                        $attr = 'data-view-switch="month" aria-pressed="false"';
                        $size = 'xs';
                        $type = 'ghost';
                        include 'component/button.php';
                    ?>
                    <?php
                        $label = 'Week';
                        $class = 'view-switch';
                        $attr = 'data-view-switch="week" aria-pressed="false"';
                        $size = 'xs';
                        $type = 'ghost';
                        include 'component/button.php';
                    ?>
                    <?php
                        $label = 'Day';
                        $class = 'view-switch';
                        $attr = 'data-view-switch="day" aria-pressed="false"';
                        $size = 'xs';
                        $type = 'ghost';
                        include 'component/button.php';
                    ?>
                    <?php
                        if (hasPermissionSlug('calendar.manage')) {
                            $label = '+ New';
                            $id = 'openCreateEvent';
                            $size = 'xs';
                            $type = 'primary';
                            $class = '';
                            include 'component/button.php';
                        }
                    ?>
                </div>
            </div>
        </div>

        <!-- Main layout -->
        <div class="space-y-2">
            <div class="rounded-lg border border-slate-200 bg-white p-2 shadow-sm">
                <div class="flex items-center justify-between">
                <h2 class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-500">Filters</h2>
                <?php
                    $label = 'Reset';
                    $id = 'clearFilters';
                    $type = 'link';
                    $size = 'xs';
                    $class = 'text-[9px] font-bold text-indigo-600 hover:text-indigo-700';
                    include 'component/button.php';
                ?>
                </div>

                <div id="filterPanel" class="mt-2 grid grid-cols-2 gap-2 md:grid-cols-5">
                    <div>
                        <label for="employeeFilter" class="mb-0.5 block text-[9px] font-bold text-slate-500">Employee</label>
                        <select id="employeeFilter" class="w-full rounded-xl border border-slate-200 px-2 py-1 text-[9px] font-semibold">
                            <option value="">All employees</option>
                        </select>
                    </div>
                    <div>
                        <label for="departmentFilter" class="mb-0.5 block text-[9px] font-bold text-slate-500">Department</label>
                        <select id="departmentFilter" class="w-full rounded-xl border border-slate-200 px-2 py-1 text-[9px] font-semibold">
                            <option value="">All departments</option>
                        </select>
                    </div>
                    <div>
                        <label for="branchFilter" class="mb-0.5 block text-[9px] font-bold text-slate-500">Branch</label>
                        <select id="branchFilter" class="w-full rounded-xl border border-slate-200 px-2 py-1 text-[9px] font-semibold">
                            <option value="">All branches</option>
                        </select>
                    </div>
                    <div>
                        <label for="eventTypeFilter" class="mb-0.5 block text-[9px] font-bold text-slate-500">Event Type</label>
                        <select id="eventTypeFilter" class="w-full rounded-xl border border-slate-200 px-2 py-1 text-[9px] font-semibold">
                            <option value="">All types</option>
                        </select>
                    </div>
                    <div>
                        <label for="statusFilter" class="mb-0.5 block text-[9px] font-bold text-slate-500">Status</label>
                        <select id="statusFilter" class="w-full rounded-xl border border-slate-200 px-2 py-1 text-[9px] font-semibold">
                            <option value="">All status</option>
                        </select>
                    </div>
                </div>
            </div>

            <section class="min-w-0 space-y-4 xl:flex xl:flex-col">
                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm xl:flex xl:flex-1 xl:flex-col">
                    <div class="flex flex-col gap-3 border-b border-slate-100 px-5 py-4 lg:flex-row lg:items-center lg:justify-between">
                        <div>
                            <p class="text-xs font-black uppercase tracking-[0.2em] text-slate-400">Calendar Panel</p>
                            <h2 id="calendarHeading" class="mt-1 text-xl font-black text-slate-900">Month view</h2>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <?php
                                $label = '';
                                $id = 'prevPeriod';
                                $icon = 'mdi:chevron-left';
                                $size = 'sm';
                                $type = 'secondary';
                                $attr = 'aria-label="Previous period"';
                                include 'component/button.php';
                            ?>
                            <?php
                                $label = 'Today';
                                $id = 'todayPeriod';
                                $size = 'sm';
                                $type = 'secondary';
                                include 'component/button.php';
                            ?>
                            <?php
                                $label = '';
                                $id = 'nextPeriod';
                                $icon = 'mdi:chevron-right';
                                $size = 'sm';
                                $type = 'secondary';
                                $attr = 'aria-label="Next period"';
                                include 'component/button.php';
                            ?>
                            <label for="dateJump" class="sr-only">Jump to date</label>
                            <input id="dateJump" type="date" value="<?= htmlspecialchars($today) ?>" class="rounded-2xl border border-slate-200 px-3 py-2 text-sm font-semibold text-slate-700">
                        </div>
                    </div>
                    <div style="position:relative;" class="p-4 lg:p-5 xl:flex-1 xl:min-h-0">
                        <div id="calendarLoadMask" aria-label="Loading calendar" role="status">
                            <div class="spinner"></div>
                        </div>
                        <div id="calendarPanel" class="min-h-[560px] xl:min-h-0 xl:h-full">
                            <div class="flex h-full min-h-[520px] items-center justify-center rounded-3xl border border-dashed border-slate-200 bg-slate-50 py-24 text-slate-400">
                                <div class="text-center">
                                    <div class="spinner mx-auto"></div>
                                    <p class="mt-3 text-sm font-semibold">Loading calendar...</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Bottom: Summary + Event table -->
            <div class="space-y-4">
                <div class="rounded-3xl border border-slate-200 bg-white p-4 shadow-sm">
                    <div class="flex items-center justify-between">
                        <h2 class="text-sm font-black normal-case tracking-[0.15em] text-slate-500">Live Summary</h2>
                        <span id="eventCountBadge" class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-black text-indigo-700" aria-live="polite">0 events</span>
                    </div>
                    <div id="summaryGrid" class="mt-4 grid grid-cols-2 gap-3 lg:grid-cols-3">
                        <div class="rounded-2xl bg-slate-50 p-3">
                            <p class="text-[10px] font-black normal-case tracking-[0.15em] text-slate-400">Pending</p>
                            <p id="summaryPending" class="mt-2 text-2xl font-black text-slate-900">0</p>
                        </div>
                        <div class="rounded-2xl bg-emerald-50 p-3">
                            <p class="text-[10px] font-black normal-case tracking-[0.15em] text-emerald-500">Approved</p>
                            <p id="summaryApproved" class="mt-2 text-2xl font-black text-emerald-700">0</p>
                        </div>
                        <div class="rounded-2xl bg-rose-50 p-3">
                            <p class="text-[10px] font-black normal-case tracking-[0.15em] text-rose-400">Rejected</p>
                            <p id="summaryRejected" class="mt-2 text-2xl font-black text-rose-700">0</p>
                        </div>
                    </div>
                </div>

                <div class="rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-4 py-4 flex items-center justify-between">
                        <h2 class="text-sm font-black normal-case tracking-[0.15em] text-slate-500">Event List</h2>
                        <span id="eventListCount" class="text-xs font-bold text-slate-400"></span>
                    </div>
                    <?php
                        $tableHead = '<tr>
                            <th scope="col" class="w-[22%] px-4 py-3 text-xs font-black normal-case tracking-[0.15em]">Date</th>
                            <th scope="col" class="w-[18%] px-4 py-3 text-xs font-black normal-case tracking-[0.15em] whitespace-nowrap">Time</th>
                            <th scope="col" class="w-[34%] px-4 py-3 text-xs font-black normal-case tracking-[0.15em]">Event</th>
                            <th scope="col" class="w-[14%] px-4 py-3 text-xs font-black normal-case tracking-[0.15em]">Status</th>
                            <th scope="col" class="sticky right-0 z-50 w-[12%] px-4 py-3 text-xs font-black normal-case tracking-[0.15em] shadow-[-12px_0_24px_rgba(15,23,42,0.12)]">Actions</th>
                        </tr>';
                        $tbodyId = 'eventTableBody';
                        $tableBody = '<tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Loading events...</td></tr>';
                        $paginationId = 'eventPagination'; // Not used yet, placeholder
                        include 'component/table.php';
                    ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ── Event modal ── -->
<div id="eventModal" role="dialog" aria-modal="true" aria-labelledby="modalTitle">
    <div id="eventModalBox">
        <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
            <div>
                <p class="text-xs font-black normal-case tracking-[0.15em] text-slate-400">Calendar Form</p>
                <h3 id="modalTitle" class="mt-1 text-xl font-black text-slate-900">New Event</h3>
            </div>
            <?php
                $label = '';
                $id = 'closeEventModal';
                $icon = 'mdi:close';
                $type = 'secondary';
                $size = 'sm';
                $attr = 'aria-label="Close modal"';
                include 'component/button.php';
            ?>
        </div>

        <form id="eventForm" class="p-5 space-y-4" novalidate>
            <input type="hidden" id="eventUuid">

            <!-- Event Details Card -->
            <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-black text-slate-900 mb-4">Event Details</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div class="md:col-span-2">
                        <?php
                            $label = 'Title';
                            $name = 'evTitle';
                            $id = 'evTitle';
                            $placeholder = 'Quarterly planning meeting';
                            $required = true;
                            $class = 'rounded-xl px-3 py-2 text-sm font-semibold focus:border-indigo-400 focus:outline-none';
                            include 'component/input.php';
                        ?>
                        <p class="field-error hidden mt-1 text-xs text-rose-500">Title is required.</p>
                    </div>
                    <div>
                        <?php
                            $label = 'Event Type';
                            $name = 'evEventType';
                            $id = 'evEventType';
                            $required = true;
                            $options = [
                                'holiday' => 'Holiday',
                                'shift' => 'Shift',
                                'leave' => 'Leave',
                                'meeting' => 'Meeting',
                                'reminder' => 'Reminder',
                                'task' => 'Task',
                            ];
                            $placeholder = 'Choose type';
                            $class = 'rounded-xl px-3 py-2 text-sm font-semibold focus:border-indigo-400 focus:outline-none';
                            include 'component/select.php';
                        ?>
                        <p class="field-error hidden mt-1 text-xs text-rose-500">Event type is required.</p>
                    </div>
                    <div>
                        <?php
                            $label = 'Status';
                            $name = 'evStatus';
                            $id = 'evStatusSelect';
                            $options = [
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'cancelled' => 'Cancelled',
                            ];
                            $value = 'pending';
                            $class = 'rounded-xl px-3 py-2 text-sm font-semibold focus:border-indigo-400 focus:outline-none';
                            include 'component/select.php';
                        ?>
                    </div>
                    <div class="md:col-span-2">
                        <label for="evDescription" class="mb-1 block text-xs font-bold uppercase tracking-wider text-slate-500">Description</label>
                        <textarea id="evDescription" rows="2" class="w-full rounded-xl border border-slate-200 px-3 py-2 text-sm font-semibold focus:border-indigo-400 focus:outline-none" placeholder="Notes for the team..."></textarea>
                    </div>
                </div>
            </div>

            <!-- Schedule Card -->
            <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-sm">
                <h3 class="text-sm font-black text-slate-900 mb-4">Schedule</h3>
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <div>
                        <?php
                            $label = 'Start';
                            $name = 'evStartAt';
                            $id = 'evStartAt';
                            $type = 'datetime-local';
                            $required = true;
                            $class = 'rounded-xl px-3 py-2 text-sm font-semibold focus:border-indigo-400 focus:outline-none';
                            include 'component/input.php';
                        ?>
                        <p class="field-error hidden mt-1 text-xs text-rose-500">Start date is required.</p>
                    </div>
                    <div>
                        <?php
                            $label = 'End';
                            $name = 'evEndAt';
                            $id = 'evEndAt';
                            $type = 'datetime-local';
                            $required = true;
                            $class = 'rounded-xl px-3 py-2 text-sm font-semibold focus:border-indigo-400 focus:outline-none';
                            include 'component/input.php';
                        ?>
                        <p class="field-error hidden mt-1 text-xs text-rose-500">End date is required.</p>
                    </div>
                </div>
            </div>

            <!-- Assignments Card -->
            <div class="rounded-3xl border border-slate-100 bg-white p-5 shadow-sm">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <h3 class="text-sm font-black text-slate-900">Assignments</h3>
                        <p class="text-xs text-slate-500">Company-wide, employees, departments, branches, or teams.</p>
                    </div>
                    <?php
                        $label = 'Add target';
                        $id = 'addTargetBtn';
                        $type = 'secondary';
                        $size = 'xs';
                        include 'component/button.php';
                    ?>
                </div>
                <div id="targetsWrap" class="space-y-3"></div>
            </div>

            <!-- Actions -->
            <div class="flex flex-col gap-2 pt-2">
                <?php
                    $label = '<span id="saveEventLabel">Save Event</span><span id="saveEventSpinner" class="hidden"></span>';
                    $id = 'saveEventBtn';
                    $type = 'primary';
                    $attr = 'type="submit"';
                    $class = 'w-full text-base py-3';
                    include 'component/button.php';
                ?>
                <?php
                    $label = 'Delete Event';
                    $id = 'deleteEventBtn';
                    $type = 'danger';
                    $class = 'hidden w-full text-sm py-2';
                    include 'component/button.php';
                ?>
            </div>
        </form>
    </div>
</div>

<template id="targetRowTemplate">
    <div class="target-row grid gap-2 rounded-xl border border-slate-200 bg-white p-2 md:grid-cols-[140px_minmax(0,1fr)_auto]">
        <select class="target-type rounded-lg border border-slate-200 px-2 py-1 text-xs font-semibold" aria-label="Target type">
            <option value="company">Company-wide</option>
            <option value="employee">Employee</option>
            <option value="department">Department</option>
            <option value="branch">Branch</option>
            <option value="team">Team</option>
        </select>
        <input class="target-value rounded-lg border border-slate-200 px-2 py-1 text-xs font-semibold" placeholder="Target value" aria-label="Target value">
        <?php
            $label = 'Remove';
            $class = 'remove-target';
            $type = 'secondary';
            $size = 'xs';
            include 'component/button.php';
        ?>
    </div>
</template>

<script>
(() => {
    /* ── State ── */
    const state = {
        view: 'month',
        current: new Date(`${window.__calendarInitialDate || '<?= $today ?>'}T12:00:00`),
        filters: { employee_id:'', department:'', branch:'', event_type:'', status:'' },
        events: [],
        options: { employees:[], departments:[], branches:[], event_types:[], statuses:[] },
        editing: null,
        filterDebounce: null,
        fetchAbort: null,
        modalReadOnly: false,
    };

    /* ── Element refs ── */
    const el = (id) => document.getElementById(id);
    const els = {
        calendarPanel: el('calendarPanel'),
        calendarHeading: el('calendarHeading'),
        calendarLoadMask: el('calendarLoadMask'),
        eventTableBody: el('eventTableBody'),
        eventListCount: el('eventListCount'),
        summaryPending: el('summaryPending'),
        summaryApproved: el('summaryApproved'),
        summaryRejected: el('summaryRejected'),
        eventCountBadge: el('eventCountBadge'),
        employeeFilter: el('employeeFilter'),
        departmentFilter: el('departmentFilter'),
        branchFilter: el('branchFilter'),
        eventTypeFilter: el('eventTypeFilter'),
        statusFilter: el('statusFilter'),
        filterPanel: el('filterPanel'),
        dateJump: el('dateJump'),
        modal: el('eventModal'),
        form: el('eventForm'),
        modalTitle: el('modalTitle'),
        eventUuid: el('eventUuid'),
        evTitle: el('evTitle'),
        evEventType: el('evEventType'),
        evStatus: el('evStatusSelect'),
        evStartAt: el('evStartAt'),
        evEndAt: el('evEndAt'),
        evDescription: el('evDescription'),
        targetsWrap: el('targetsWrap'),
        targetRowTemplate: el('targetRowTemplate'),
        addTargetBtn: el('addTargetBtn'),
        saveEventBtn: el('saveEventBtn'),
        saveEventLabel: el('saveEventLabel'),
        get saveEventSpinner() { return el('saveEventSpinner'); },
        deleteEventBtn: el('deleteEventBtn'),
        confirmOverlay: el('confirmOverlay'),
        confirmTitle: el('confirmTitle'),
        confirmMessage: el('confirmMessage'),
        confirmOkBtn: el('confirmOkBtn'),
        confirmCancelBtn: el('confirmCancelBtn'),
        rejectRemarkWrap: el('rejectRemarkWrap'),
        rejectRemark: el('rejectRemark'),
        remarkError: el('remarkError'),
    };

    /* ── Toast ── */
    function showToast(msg, type = 'info', duration = 3500) {
        const container = el('toastContainer');
        const t = document.createElement('div');
        t.className = `toast ${type}`;
        t.setAttribute('role', 'alert');
        t.textContent = msg;
        container.appendChild(t);
        requestAnimationFrame(() => { requestAnimationFrame(() => t.classList.add('show')); });
        setTimeout(() => {
            t.classList.remove('show');
            setTimeout(() => t.remove(), 300);
        }, duration);
    }

    /* ── Confirm dialog ── */
    function openConfirm({ title, message, okLabel = 'Confirm', okClass = '', withRemark = false }) {
        return new Promise((resolve) => {
            els.confirmTitle.textContent = title;
            els.confirmMessage.textContent = message;
            els.confirmOkBtn.textContent = okLabel;
            els.confirmOkBtn.className = `${okClass}`;
            els.rejectRemarkWrap.style.display = withRemark ? 'block' : 'none';
            els.rejectRemark.value = '';
            els.remarkError.style.display = 'none';
            els.confirmOverlay.classList.add('open');

            const cleanup = () => {
                els.confirmOverlay.classList.remove('open');
                els.confirmOkBtn.removeEventListener('click', onOk);
                els.confirmCancelBtn.removeEventListener('click', onCancel);
            };
            const onOk = () => {
                if (withRemark && !els.rejectRemark.value.trim()) {
                    els.remarkError.style.display = 'block';
                    els.rejectRemark.focus();
                    return;
                }
                cleanup();
                resolve({ confirmed: true, remark: els.rejectRemark.value.trim() });
            };
            const onCancel = () => { cleanup(); resolve({ confirmed: false }); };
            els.confirmOkBtn.addEventListener('click', onOk);
            els.confirmCancelBtn.addEventListener('click', onCancel);
        });
    }

    /* ── Loading mask ── */
    function setLoading(on) {
        els.calendarLoadMask.classList.toggle('show', on);
    }

    // ✅ FIXED
    function escapeHtml(v) {
        return String(v ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;').replace(/'/g,'&#39;');
    }  // ← this closing brace was missing

    /* ── Helpers ── */
    function toDatetimeLocal(date) {
        const pad = (n) => String(n).padStart(2, '0');
        return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}T${pad(date.getHours())}:${pad(date.getMinutes())}`;
    }

    function toLocalInputValue(value) {
        if (!value) return '';
        const dt = parseApiDate(value);
        if (Number.isNaN(dt.getTime())) return '';
        return toDatetimeLocal(dt);
    }
    function parseApiDate(v) { return new Date(String(v || '').replace(' ','T')); }
    function localDateKey(date) {
        const pad = (n) => String(n).padStart(2,'0');
        return `${date.getFullYear()}-${pad(date.getMonth()+1)}-${pad(date.getDate())}`;
    }
    function formatTimeRange(ev) {
        if ((ev.is_all_day ?? ev.all_day) || ['holiday', 'leave'].includes(String(ev.event_type || '').toLowerCase())) return 'All Day';
        if (!ev.start_time || !ev.end_time || ev.start_time === '--' || ev.end_time === '--') return '--';
        return `${formatTimeValue(ev.start_time)} - ${formatTimeValue(ev.end_time)}`;
    }
    function formatTimeValue(value) {
        if (!value || value === '--') return '--';
        const [h, m] = String(value).split(':');
        const date = new Date();
        date.setHours(Number(h), Number(m), 0, 0);
        return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
    }
    function formatDateRange(ev) {
        if (ev.date_label) return ev.date_label;
        const start = ev.start_date || String(ev.start || '').slice(0, 10);
        const end = ev.end_date || String(ev.end || '').slice(0, 10);
        if (!start) return '--';
        const s = new Date(`${start}T12:00:00`);
        const e = end ? new Date(`${end}T12:00:00`) : s;
        const startLabel = s.toLocaleDateString('en-US', { month: 'short', day: '2-digit' });
        const endLabel = e.toLocaleDateString('en-US', { month: 'short', day: '2-digit', year: 'numeric' });
        return start === end ? `${startLabel}, ${endLabel.slice(-4)}` : `${startLabel} - ${endLabel}`;
    }
    function timeCell(ev) {
        const label = formatTimeRange(ev);
        if (label === 'All Day') {
            return `<span class="inline-flex items-center rounded-full bg-indigo-100 px-2 py-1 text-[11px] font-black text-indigo-700 whitespace-nowrap">All Day</span>`;
        }
        return `<span class="whitespace-nowrap">${escapeHtml(label)}</span>`;
    }
    function timeBadge(ev) {
        return timeCell(ev);
    }
    function badge(status) {
        const map = { pending:'bg-amber-100 text-amber-700', approved:'bg-emerald-100 text-emerald-700', rejected:'bg-rose-100 text-rose-700', cancelled:'bg-slate-100 text-slate-700' };
        return `<span class="inline-flex rounded-full px-2 py-1 text-[11px] font-black ${map[status]||map.cancelled}">${escapeHtml(status)}</span>`;
    }
    function currentUserRole() {
        return String(window.__calendarUserRole || '').toLowerCase();
    }
    function isAdmin() {
        return currentUserRole() === 'admin' || Number(window.__calendarUserRoleId || 0) === 1;
    }
    function canManageLeaves() {
        return Boolean(window.__calendarPermissions?.approveLeave || window.__calendarPermissions?.rejectLeave);
    }
    function canManageEvents() {
        return Boolean(window.__calendarPermissions?.manage);
    }
    function isCompletedEvent(ev) {
        if (!ev || !ev.end) return false;
        const end = parseApiDate(ev.end);
        if (Number.isNaN(end.getTime())) return false;
        const compare = new Date();
        compare.setHours(0,0,0,0);
        end.setHours(23,59,59,999);
        return end < compare;
    }
    function buildActionMenu(items, compact = false) {
        if (!items.length) return '';
        const itemCls = compact ? 'px-3 py-2 text-xs' : 'px-3 py-2 text-sm';
        return `
            <div class="relative inline-flex overflow-visible">
                <button type="button" class="action-menu-toggle inline-flex items-center justify-center rounded-xl border border-slate-200 bg-white ${compact ? 'px-3 py-2 text-xs' : 'px-3 py-2 text-sm'} font-black text-slate-700 hover:bg-slate-50 transition" aria-expanded="false">
                    <span class="iconify" data-icon="mdi:dots-vertical" aria-hidden="true"></span>
                </button>
                <div class="action-menu-panel fixed hidden min-w-max overflow-visible rounded-2xl border border-slate-200 bg-white shadow-xl shadow-slate-900/10" style="z-index:9000;">
                    ${items.map(item => `
                        <button type="button" class="flex w-full min-w-48 items-center gap-2 border-b border-slate-100 px-4 py-3 text-left text-sm font-semibold text-slate-700 last:border-b-0 hover:bg-slate-50 whitespace-nowrap ${item.className || ''}" data-action="${escapeHtml(item.action)}" data-uuid="${escapeHtml(item.uuid)}">
                            ${item.icon ? `<span class="iconify shrink-0 text-base ${item.iconClass || ''}" data-icon="${item.icon}" aria-hidden="true"></span>` : ''}
                            <span class="shrink-0">${escapeHtml(item.label)}</span>
                        </button>
                    `).join('')}
                </div>
            </div>`;
    }
    function typeBadge(type) {
        const map = { holiday:'bg-indigo-100 text-indigo-700', shift:'bg-sky-100 text-sky-700', leave:'bg-amber-100 text-amber-700', meeting:'bg-violet-100 text-violet-700', reminder:'bg-emerald-100 text-emerald-700', task:'bg-slate-100 text-slate-700' };
        return `<span class="inline-flex rounded-full px-2 py-1 text-[11px] font-black ${map[type]||map.task}">${escapeHtml(type)}</span>`;
    }
    function colorClass(ev) {
        const map = { holiday:'bg-indigo-50 text-indigo-700', shift:'bg-sky-50 text-sky-700', leave:'bg-amber-50 text-amber-700', meeting:'bg-violet-50 text-violet-700', reminder:'bg-emerald-50 text-emerald-700', task:'bg-slate-50 text-slate-700' };
        return map[ev.event_type]||map.task;
    }
    function monthName(d) { return d.toLocaleDateString([],{month:'long',year:'numeric'}); }
    function weekRange(d) {
        const s = new Date(d); s.setDate(d.getDate()-((d.getDay()+6)%7));
        const e = new Date(s); e.setDate(s.getDate()+6);
        return `${s.toLocaleDateString([],{month:'short',day:'numeric'})} – ${e.toLocaleDateString([],{month:'short',day:'numeric',year:'numeric'})}`;
    }
    function dayLabel(d) { return d.toLocaleDateString([],{weekday:'long',month:'long',day:'numeric',year:'numeric'}); }

    /* ── Date range for current view ── */
    function getRange() {
        const d = new Date(state.current), s = new Date(d), e = new Date(d);
        const pad = (n) => String(n).padStart(2,'0');
        if (state.view === 'month') {
            s.setDate(1); e.setMonth(e.getMonth()+1,0);
        } else if (state.view === 'week') {
            const off = (d.getDay()+6)%7; s.setDate(d.getDate()-off); e.setDate(s.getDate()+6);
        } else { s.setHours(0,0,0,0); e.setHours(23,59,59,999); }
        return {
            start: `${s.getFullYear()}-${pad(s.getMonth()+1)}-${pad(s.getDate())}`,
            end:   `${e.getFullYear()}-${pad(e.getMonth()+1)}-${pad(e.getDate())}`,
        };
    }

    /* ── Load filter options ── */
    async function loadOptions() {
        const res = await fetch('/api/calendar/filters');
        const result = await res.json();
        if (!result.success) return;
        state.options = result.data || state.options;
        els.employeeFilter.innerHTML = '<option value="">All employees</option>' + (state.options.employees||[]).map(e=>`<option value="${e.id}">${escapeHtml(e.full_name)}${e.department?` – ${escapeHtml(e.department)}`:''}</option>`).join('');
        els.departmentFilter.innerHTML = '<option value="">All departments</option>' + (state.options.departments||[]).map(v=>`<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`).join('');
        els.branchFilter.innerHTML = '<option value="">All branches</option>' + (state.options.branches||[]).map(v=>`<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`).join('');
        els.eventTypeFilter.innerHTML = '<option value="">All types</option>' + (state.options.event_types||[]).map(v=>`<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`).join('');
        els.statusFilter.innerHTML = '<option value="">All status</option>' + (state.options.statuses||[]).map(v=>`<option value="${escapeHtml(v)}">${escapeHtml(v)}</option>`).join('');
    }

    /* ── Load events (with abort + loading mask) ── */
    async function loadEvents() {
        if (state.fetchAbort) state.fetchAbort.abort();
        state.fetchAbort = new AbortController();
        setLoading(true);
        els.eventTableBody.innerHTML = '<tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">Loading events...</td></tr>';
        const range = getRange();
        const params = new URLSearchParams({
            start: range.start, end: range.end,
            employee_id: state.filters.employee_id,
            department: state.filters.department,
            branch: state.filters.branch,
            event_type: state.filters.event_type,
            status: state.filters.status,
        });
        try {
            const res = await fetch(`/api/calendar/events?${params}`, { signal: state.fetchAbort.signal });
            const result = await res.json();
            if (!result.success) throw new Error(result.message || 'Unable to load calendar');
            state.events = result.data?.events || [];
            renderSummary(result.data?.summary || {});
            renderCalendar();
            renderTable();
        } catch (err) {
            if (err.name === 'AbortError') return;
            state.events = [];
            renderSummary({});
            els.eventTableBody.innerHTML = '<tr><td colspan="5" class="px-4 py-10 text-center text-rose-500">Failed to load events</td></tr>';
            els.calendarPanel.innerHTML = `<div class="rounded-3xl border border-dashed border-rose-200 bg-rose-50 px-4 py-16 text-center text-rose-600 font-semibold">${escapeHtml(err.message)}</div>`;
            showToast(err.message || 'Failed to load events', 'error');
        } finally {
            setLoading(false);
        }
    }

    /* ── Debounced filter reload ── */
    function scheduleReload() {
        clearTimeout(state.filterDebounce);
        els.filterPanel.classList.add('filter-loading');
        state.filterDebounce = setTimeout(async () => {
            els.filterPanel.classList.remove('filter-loading');
            await loadEvents();
        }, 350);
    }

    /* ── Summary ── */
    function renderSummary(summary) {
        const total = summary.total || 0;
        els.eventCountBadge.textContent = `${total} event${total !== 1 ? 's' : ''}`;
        els.summaryPending.textContent   = summary.pending   || 0;
        els.summaryApproved.textContent  = summary.approved  || 0;
        els.summaryRejected.textContent  = summary.rejected  || 0;
    }

    /* ── Grouped events by date ── */
    function groupedByDate() {
        const g = {};
        state.events.forEach(ev => {
            const key = String(ev.start||'').slice(0,10);
            if (!g[key]) g[key] = [];
            g[key].push(ev);
        });
        return g;
    }

    /* ── Render dispatch ── */
    function renderCalendar() {
        const d = new Date(state.current);
        if (state.view === 'month')      { els.calendarHeading.textContent = monthName(d); renderMonthView(); }
        else if (state.view === 'week')  { els.calendarHeading.textContent = `Week · ${weekRange(d)}`; renderWeekView(); }
        else                             { els.calendarHeading.textContent = `Day · ${dayLabel(d)}`; renderDayView(); }
    }

    /* ── Month view ── */
    function renderMonthView() {
        const d = new Date(state.current), year = d.getFullYear(), month = d.getMonth();
        const first = new Date(year, month, 1);
        const startOff = (first.getDay()+6)%7;
        const daysInMonth = new Date(year, month+1, 0).getDate();
        const groups = groupedByDate();
        const todayKey = localDateKey(new Date());
        const totalCells = Math.ceil((startOff + daysInMonth) / 7) * 7;
        let html = `<div class="grid grid-cols-7 gap-px overflow-hidden rounded-[24px] border border-slate-100 bg-slate-100">`;
        ['Mon','Tue','Wed','Thu','Fri','Sat','Sun'].forEach(day => {
            html += `<div class="bg-slate-50 px-2 py-3 text-center text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">${day}</div>`;
        });
        for (let cell = 0; cell < totalCells; cell++) {
            const dayNum = cell - startOff + 1;
            const inMonth = dayNum > 0 && dayNum <= daysInMonth;
            const cellDate = new Date(year, month, dayNum);
            const key = localDateKey(cellDate);
            const items = groups[key] || [];
            const isToday = key === todayKey;
            html += `<div class="min-h-[80px] bg-white p-2 transition hover:bg-slate-50 ${inMonth?'':'opacity-40'}" onclick="${inMonth ? `openEventModal(null, '${key}', !window.__calendarPermissions?.manage)` : ''}">
                <div class="mb-1 flex items-center justify-between">
                    <span class="inline-flex h-7 w-7 items-center justify-center rounded-xl text-sm font-black ${isToday?'bg-slate-950 text-white':'text-slate-700'}">${inMonth?dayNum:''}</span>
                    ${items.length>0&&inMonth?`<span class="text-[10px] font-bold text-slate-400">${items.length}</span>`:''}
                </div>
                <div class="space-y-1">
                    ${items.slice(0,3).map(renderChip).join('')}
                    ${items.length>3?`<button class="text-[11px] font-black text-indigo-600 hover:underline" onclick="window.__calendarJump('${key}')">+${items.length-3} more</button>`:''}
                </div>
            </div>`;
        }
        html += '</div>';
        els.calendarPanel.innerHTML = html;
    }

    /* ── Week view ── */
    function renderWeekView() {
        const d = new Date(state.current);
        const start = new Date(d); start.setDate(d.getDate()-((d.getDay()+6)%7));
        const groups = groupedByDate();
        const days = Array.from({length:7},(_,i)=>{ const x=new Date(start); x.setDate(start.getDate()+i); return x; });
        const todayKey = localDateKey(new Date());

        els.calendarPanel.innerHTML = `
            <div class="overflow-x-auto -mx-1 pb-2">
                <div class="grid min-w-[700px] grid-cols-7 gap-2 px-1">
                    ${days.map(dd => {
                        const key = localDateKey(dd);
                        const items = groups[key] || [];
                        const isToday = key === todayKey;
                        return `
                        <div class="flex flex-col rounded-[20px] border ${isToday ? 'border-indigo-300 ring-2 ring-indigo-100' : 'border-slate-100'} bg-white overflow-hidden">
                            <!-- Day header -->
                            <button
                                onclick="window.__calendarJump('${key}')"
                                class="flex flex-col items-center gap-0.5 px-2 py-3 transition
                                    ${isToday ? 'bg-indigo-500 text-white hover:bg-indigo-400' : 'bg-slate-50 text-slate-700 hover:bg-slate-100'}"
                                aria-label="Open ${dd.toLocaleDateString()} day view"
                            >
                                <span class="text-[10px] font-black uppercase tracking-[0.18em] ${isToday ? 'text-indigo-100' : 'text-slate-400'}">
                                    ${dd.toLocaleDateString([], {weekday:'short'})}
                                </span>
                                <span class="text-xl font-black leading-none">${dd.getDate()}</span>
                                ${items.length ? `<span class="mt-1 rounded-full px-2 py-0.5 text-[9px] font-black ${isToday ? 'bg-white/20 text-white' : 'bg-indigo-100 text-indigo-600'}">${items.length}</span>` : '<span class="mt-1 h-4"></span>'}
                            </button>
                            <!-- Events -->
                            <div class="flex flex-col gap-1.5 overflow-y-auto p-2" style="max-height:420px; min-height:80px;">
                                ${items.length
                                    ? items.map(ev => `
                                        <button
                                            type="button"
                                            onclick="openEventFromView('${ev.uuid}','${ev.source_type}')"
                                            class="group w-full rounded-xl border border-slate-100 p-2 text-left transition hover:shadow-sm ${colorClass(ev)}"
                                        >
                                            <p class="truncate text-[11px] font-black leading-tight">${escapeHtml(ev.title)}</p>
                                            <p class="mt-0.5 text-[10px] font-semibold opacity-70">${formatTimeRange(ev)}</p>
                                        </button>`).join('')
                                    : `<div class="flex flex-1 items-center justify-center py-6 text-[11px] font-semibold text-slate-300">—</div>`
                                }
                            </div>
                        </div>`;
                    }).join('')}
                </div>
            </div>`;
    }

    /* ── Day view ── */
    function renderDayView() {
        const key = localDateKey(state.current);
        const items = groupedByDate()[key]||[];
        els.calendarPanel.innerHTML = `<div class="rounded-[26px] border border-slate-100 bg-slate-50 p-4">
            <div class="mb-4 flex items-center justify-between">
                <div>
                    <p class="text-[10px] font-black uppercase tracking-[0.2em] text-slate-400">Agenda</p>
                    <h3 class="mt-1 text-2xl font-black text-slate-900">${dayLabel(state.current)}</h3>
                </div>
            </div>
            <div class="space-y-3">
                ${items.length?items.map(ev=>`
                    <div class="rounded-[22px] border border-slate-100 bg-white p-4 shadow-sm">
                        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">${typeBadge(ev.event_type)}${badge(ev.status)}</div>
                                <h4 class="mt-2 text-base font-black text-slate-900">${escapeHtml(ev.title)}</h4>
                                ${ev.description?`<p class="mt-1 text-sm text-slate-500">${escapeHtml(ev.description)}</p>`:''}
                                <p class="mt-2 text-xs font-bold uppercase tracking-[0.15em] text-slate-400">${formatTimeRange(ev)}${ev.scope?.label?` · ${escapeHtml(ev.scope.label)}`:''}</p>
                            </div>
                            <div class="flex flex-wrap gap-2">${actionButtons(ev)}</div>
                        </div>
                    </div>`).join('')
                :'<div class="rounded-[22px] border border-dashed border-slate-200 bg-white px-4 py-16 text-center text-sm font-semibold text-slate-400">No events on this date.</div>'}
            </div>
        </div>`;
    }

    /* ── Chips & blocks ── */
    function renderChip(ev) {
        return `<button type="button" class="w-full rounded-lg border border-slate-200 px-1.5 py-0.5 text-left text-[10px] font-bold ${colorClass(ev)} hover:opacity-80 transition" onclick="openEventFromView('${ev.uuid}','${ev.source_type}')" aria-label="View ${escapeHtml(ev.title)}">
            <span class="block truncate">${escapeHtml(ev.title)}</span>
        </button>`;
    }
    function renderBlock(ev) {
        return `<div class="rounded-2xl border border-slate-100 bg-white p-3 shadow-sm">
            <div class="flex items-center gap-2">${typeBadge(ev.event_type)}${badge(ev.status)}</div>
            <p class="mt-2 truncate text-sm font-black text-slate-900">${escapeHtml(ev.title)}</p>
            <p class="mt-1 text-[11px] font-semibold text-slate-500">${formatTimeRange(ev)}</p>
            <div class="mt-3 flex gap-2">${actionButtons(ev, true)}</div>
        </div>`;
    }

    /* ── Event table ── */
    function renderTable() {
        const count = state.events.length;
        els.eventListCount.textContent = count ? `${count} event${count!==1?'s':''}` : '';
        if (!count) {
            els.eventTableBody.innerHTML = '<tr><td colspan="5" class="px-4 py-10 text-center text-slate-400">No events found</td></tr>';
            return;
        }
        els.eventTableBody.innerHTML = state.events.map(ev=>`
            <tr class="block border-b border-slate-100 md:table-row hover:bg-slate-50 transition">
                <td class="block px-4 py-2 align-top md:table-cell md:px-4 md:py-3">
                    <div class="text-xs font-bold text-slate-900 md:whitespace-nowrap">${escapeHtml(formatDateRange(ev))}</div>
                    <div class="mt-1 flex md:hidden items-center gap-2">
                        ${timeBadge(ev)}
                    </div>
                </td>
                <td class="hidden px-4 py-3 align-top text-xs font-bold text-slate-500 whitespace-nowrap md:table-cell md:align-top">${timeCell(ev)}</td>
                <td class="block px-4 py-2 align-top md:table-cell md:px-4 md:py-3">
                    <div class="font-black text-slate-600 text-sm">${escapeHtml(ev.title)}</div>
                    <div class="mt-1 flex flex-wrap items-center gap-2">
                        ${typeBadge(ev.event_type)}
                        <span class="text-xs text-slate-500">${escapeHtml(ev.scope?.label||'Company-wide')}</span>
                    </div>
                </td>
                <td class="block px-4 py-2 align-top md:table-cell md:px-4 md:py-3">${badge(ev.status)}</td>
                <td class="sticky right-0 z-10 block border-l border-slate-100 bg-white px-4 py-2 align-top overflow-visible md:table-cell md:px-4 md:py-3 md:sticky md:right-0 md:bg-white md:shadow-[-12px_0_24px_rgba(15,23,42,0.06)]">
                    <div class="flex flex-wrap gap-2 md:justify-start">${actionButtons(ev)}</div>
                </td>
            </tr>`).join('');
    }

    function actionButtons(ev, compact = false) {
        const role = currentUserRole();
        const isLeave = ev.source_type === 'leave';
        const completed = !isLeave && isCompletedEvent(ev);
        const primary = [];
        const secondary = [];

        primary.push({ label: 'View', action: 'view', uuid: ev.uuid, icon: 'mdi:eye-outline', className: '' });

        if (isLeave) {
            const status = String(ev.status || '').toLowerCase();
            if (status === 'pending' && canManageLeaves()) {
                primary.push({ label: 'Approve', action: 'approve', uuid: ev.uuid, icon: 'mdi:check', className: 'text-emerald-700' });
                secondary.push({ label: 'Reject', action: 'reject', uuid: ev.uuid, icon: 'mdi:close-circle-outline', className: 'text-rose-700' });
            } else if (status === 'approved' && canManageLeaves()) {
                secondary.push({ label: 'Cancel Approval', action: 'cancel-approval', uuid: ev.uuid, icon: 'mdi:undo', className: 'text-amber-700' });
            } else if (status === 'rejected' && canManageLeaves()) {
                secondary.push({ label: 'Reopen', action: 'reopen', uuid: ev.uuid, icon: 'mdi:restart', className: 'text-indigo-700' });
            }
        } else {
            if (!completed && canManageEvents()) {
                primary.push({ label: 'Edit', action: 'edit', uuid: ev.uuid, icon: 'mdi:pencil-outline', className: '' });
                secondary.push({ label: 'Delete', action: 'delete', uuid: ev.uuid, icon: 'mdi:trash-can-outline', className: 'text-rose-700' });
            }
        }

        const renderButtonHtml = (item, type = 'secondary') => {
            const label = item.label;
            const action = item.action;
            const uuid = item.uuid;
            const icon = item.icon;
            const class_ = item.className || '';
            const btnType = type === 'primary' ? 'primary' : 'secondary';
            
            // Replicating component/button.php styles
            const baseClasses = "inline-flex items-center justify-center gap-1.5 font-black rounded-lg transition-all duration-200 shadow-sm active:scale-95 disabled:opacity-50 disabled:pointer-events-none whitespace-nowrap px-2 py-1 text-[11px]";
            const typeClasses = btnType === 'primary' ? "bg-indigo-600 text-white hover:bg-indigo-700 hover:shadow-indigo-100" : "bg-white text-slate-700 border border-slate-200 hover:bg-slate-50 hover:border-slate-300";
            
            return '<button type="button" class="' + baseClasses + ' ' + typeClasses + ' ' + class_ + '" ' +
                   'data-action="' + escapeHtml(action) + '" data-uuid="' + escapeHtml(uuid) + '">' +
                   (icon ? '<span class="iconify text-[12px] shrink-0" data-icon="' + icon + '"></span>' : '') +
                   escapeHtml(label) + '</button>';
        };

        const primaryBtn = primary[0] ? renderButtonHtml(primary[0], 'secondary') : '';
        const desktopSecondary = (primary.length > 1 || secondary.length > 0) ? buildActionMenu([...primary.slice(1), ...secondary], false) : '';
        const mobileMenu = buildActionMenu([...primary, ...secondary], true);

        return '<div class="flex items-center gap-2">' +
                '<div class="hidden md:flex items-center gap-2">' +
                    primaryBtn +
                    desktopSecondary +
                '</div>' +
                '<div class="md:hidden">' +
                    mobileMenu +
                '</div>' +
            '</div>';
    }

    /* ── Modal helpers ── */
    function validateForm() {
        let valid = true;
        [['evTitle','Title is required.'],['evEventType','Event type is required.'],['evStartAt','Start date is required.'],['evEndAt','End date is required.']].forEach(([id, msg]) => {
            const input = el(id);
            const err = input.parentElement.querySelector('.field-error');
            if (!input.value.trim()) {
                if (err) { err.textContent = msg; err.classList.remove('hidden'); }
                input.classList.add('border-rose-300');
                valid = false;
            } else {
                if (err) err.classList.add('hidden');
                input.classList.remove('border-rose-300');
            }
        });
        if (els.evStartAt.value && els.evEndAt.value && els.evEndAt.value < els.evStartAt.value) {
            const err = els.evEndAt.parentElement.querySelector('.field-error');
            if (err) { err.textContent = 'End must be after start.'; err.classList.remove('hidden'); }
            els.evEndAt.classList.add('border-rose-300');
            valid = false;
        }
        return valid;
    }

    function setModalReadOnly(readOnly) {
        state.modalReadOnly = readOnly;
        [els.evTitle, els.evEventType, els.evStatus, els.evStartAt, els.evEndAt, els.evDescription].forEach(input => {
            if (input) input.disabled = readOnly;
        });
        els.addTargetBtn?.classList.toggle('hidden', readOnly);
        els.saveEventBtn?.classList.toggle('hidden', readOnly);
        els.targetsWrap.querySelectorAll('input, select, button').forEach(input => {
            input.disabled = readOnly;
        });
    }

    function openEventModal(event = null, startDate = null, readOnly = false) {
        state.editing = event;
        els.form.reset();
        els.targetsWrap.innerHTML = '';
        state.selectedWeekdays = new Set();
        updateWeekdayButtons();
        addTargetRow();
        els.evStatus.value = 'pending';
        els.deleteEventBtn?.classList.add('hidden');

        if (event) {
            const isReadOnly = readOnly || event.source_type === 'leave';
            els.modalTitle.textContent = isReadOnly ? 'View Event' : 'Edit Event';
            els.eventUuid.value = event.uuid;
            els.evTitle.value = event.title || '';
            els.evEventType.value = event.event_type || '';
            els.evStatus.value = event.status || 'pending';
            els.evStartAt.value = toLocalInputValue(event.start);
            els.evEndAt.value = toLocalInputValue(event.end);
            els.evDescription.value = event.description || '';
            
            els.targetsWrap.innerHTML = '';
            (event.targets||[]).forEach(t => addTargetRow(t));
            if (!(event.targets||[]).length) addTargetRow();
            
            // Only show delete button for non-leave events
            if (!isReadOnly && event.source_type !== 'leave' && canManageEvents()) {
                els.deleteEventBtn?.classList.remove('hidden');
            }
            setModalReadOnly(isReadOnly);
        } else {
            els.modalTitle.textContent = 'New Event';
            els.eventUuid.value = '';
            const start = startDate ? new Date(`${startDate}T09:00:00`) : new Date(state.current);
            els.evStartAt.value = toDatetimeLocal(start);
            els.evEndAt.value   = toDatetimeLocal(new Date(start.getTime() + 60 * 60 * 1000));
            setModalReadOnly(readOnly || !canManageEvents());
        }
        // clear field errors
        els.form.querySelectorAll('.field-error').forEach(e => e.classList.add('hidden'));
        els.form.querySelectorAll('input, select').forEach(e => e.classList.remove('border-rose-300'));
        els.modal.classList.add('open');
        els.evTitle.focus();
    }

    function openEventFromView(uuid, sourceType) {
        const ev = state.events.find(e => e.uuid === uuid && e.source_type === sourceType);
        if (!ev) return;
        openEventModal(ev, null, true);
    }

    function editEvent(uuid) {
        const ev = state.events.find(e => e.uuid === uuid);
        // Do not open edit modal for leave applications
        if (ev && ev.source_type !== 'leave') openEventModal(ev);
    }

    function closeModal() {
        els.modal.classList.remove('open');
    }

    /* ── Target rows ── */
    function addTargetRow(target = null) {
        const node = els.targetRowTemplate.content.cloneNode(true);
        const row = node.querySelector('.target-row');
        const type = row.querySelector('.target-type');
        const value = row.querySelector('.target-value');
        type.value = target?.type || target?.target_type || 'company';
        value.value = target?.value || target?.target_value || '';
        value.placeholder = placeholderForType(type.value);
        type.addEventListener('change', () => { value.placeholder = placeholderForType(type.value); });
        row.querySelector('.remove-target').addEventListener('click', () => row.remove());
        els.targetsWrap.appendChild(node);
    }

    function placeholderForType(type) {
        return { company:'Company-wide', employee:'Employee ID', department:'Department name', branch:'Branch name', team:'Team name' }[type] || 'Target value';
    }

    function updateWeekdayButtons() {
        document.querySelectorAll('.weekday-btn').forEach(btn => {
            const active = state.selectedWeekdays.has(btn.dataset.weekday);
            btn.classList.toggle('active', active);
            btn.setAttribute('aria-pressed', String(active));
        });
    }

    function gatherTargets() {
        return [...document.querySelectorAll('.target-row')].map(row => ({
            type: row.querySelector('.target-type').value,
            value: row.querySelector('.target-value').value,
            label: row.querySelector('.target-value').value,
        })).filter(t => t.value || t.type === 'company');
    }

    function gatherRecurrence() {
        return null;
    }

    /* ── Save event ── */
    async function saveEvent(e) {
        e.preventDefault();
        if (state.modalReadOnly) return;
        if (!validateForm()) return;

        els.saveEventBtn.disabled = true;
        els.saveEventLabel.textContent = 'Saving...';
        els.saveEventSpinner.classList.remove('hidden');

        const payload = {
            title: els.evTitle.value.trim(),
            event_type: els.evEventType.value,
            status: els.evStatus.value,
            start_at: els.evStartAt.value,
            end_at: els.evEndAt.value,
            description: els.evDescription.value.trim(),
            recurrence: gatherRecurrence(),
            targets: gatherTargets(),
        };
        const uuid = els.eventUuid.value;
        try {
            const res = await fetch(uuid ? `/api/calendar/events/${uuid}` : '/api/calendar/events', {
                method: uuid ? 'PUT' : 'POST',
                headers: {'Content-Type':'application/json'},
                body: JSON.stringify(payload),
            });
            const result = await res.json();
            if (!result.success) throw new Error(result.message || 'Unable to save event');
            closeModal();
            showToast(uuid ? 'Event updated successfully.' : 'Event created successfully.', 'success');
            await loadEvents();
        } catch (err) {
            showToast(err.message || 'Save failed', 'error');
        } finally {
            els.saveEventBtn.disabled = false;
            els.saveEventLabel.textContent = 'Save Event';
            els.saveEventSpinner.classList.add('hidden');
        }
    }

    async function requestActionConfirmation(action, uuid) {
        const labels = {
            approve: { title: 'Approve leave request', message: 'Are you sure you want to approve this leave request?', okLabel: 'Approve', okClass: 'success-btn' },
            reject: { title: 'Reject leave request', message: 'Provide a reason for rejecting this leave request.', okLabel: 'Reject', okClass: 'danger', withRemark: true },
            reopen: { title: 'Reopen leave request', message: 'Are you sure you want to reopen this rejected leave request?', okLabel: 'Reopen', okClass: 'danger' },
            'cancel-approval': { title: 'Cancel approval', message: 'Are you sure you want to cancel this approval?', okLabel: 'Cancel approval', okClass: 'danger' },
            delete: { title: 'Delete event', message: 'This will permanently delete the event. This action cannot be undone.', okLabel: 'Delete', okClass: 'danger' },
        };
        const cfg = labels[action];
        if (!cfg) return;
        const result = await openConfirm(cfg);
        if (!result.confirmed) return;

        try {
            let res;
            if (action === 'approve') {
                res = await fetch(`/api/calendar/leaves/${uuid}/approve`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({uuid}) });
            } else if (action === 'reject') {
                res = await fetch(`/api/calendar/leaves/${uuid}/reject`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({uuid, remark: result.remark}) });
            } else if (action === 'cancel-approval') {
                res = await fetch(`/api/leaves/${uuid}/cancel-approval`, { method:'PATCH', headers:{'Content-Type':'application/json'} });
            } else if (action === 'reopen') {
                res = await fetch(`/api/leaves/${uuid}/reopen`, { method:'PATCH', headers:{'Content-Type':'application/json'} });
            } else if (action === 'delete') {
                res = await fetch(`/api/calendar/events/${uuid}`, { method:'DELETE' });
            }
            const json = await res.json();
            if (!json.success) throw new Error(json.message || 'Action failed');
            
            // If the deleted/modified event was open in the modal, close it
            if (els.eventUuid.value === uuid) closeModal();
            
            showToast(json.message || 'Action completed.', 'success');
            await loadEvents();
        } catch (err) {
            showToast(err.message || 'Action failed', 'error');
        }
    }

    /* ── Leave approve/reject ── */
    async function approveLeaveRequest(uuid) {
        const { confirmed } = await openConfirm({ title:'Approve leave request', message:'Are you sure you want to approve this leave request?', okLabel:'Approve', okClass:'success-btn' });
        if (!confirmed) return;
        try {
            const res = await fetch(`/api/calendar/leaves/${uuid}/approve`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({uuid}) });
            const result = await res.json();
            if (!result.success) throw new Error(result.message || 'Unable to approve');
            showToast('Leave request approved.', 'success');
            await loadEvents();
        } catch (err) {
            showToast(err.message || 'Approval failed', 'error');
        }
    }

    async function rejectLeaveRequest(uuid) {
        const { confirmed, remark } = await openConfirm({ title:'Reject leave request', message:'Provide a reason for rejecting this leave request.', okLabel:'Reject', okClass:'danger', withRemark:true });
        if (!confirmed) return;
        try {
            const res = await fetch(`/api/calendar/leaves/${uuid}/reject`, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({uuid, remark}) });
            const result = await res.json();
            if (!result.success) throw new Error(result.message || 'Unable to reject');
            showToast('Leave request rejected.', 'info');
            await loadEvents();
        } catch (err) {
            showToast(err.message || 'Rejection failed', 'error');
        }
    }

    /* ── Global handlers (called from inline onclick) ── */
    window.__calendarJump = (date) => {
        state.view = 'day';
        state.current = new Date(`${date}T12:00:00`);
        els.dateJump.value = date;
        
        // Update buttons visual state
        document.querySelectorAll('.view-switch').forEach(item => {
            const isActive = (item.dataset.viewSwitch === 'day');
            item.classList.toggle('active', isActive);
            item.setAttribute('aria-pressed', String(isActive));
        });
        
        loadEvents().catch(console.error);
    };
    window.openEventFromView = openEventFromView;
    window.openEventModal = openEventModal;
    window.editEvent = editEvent;
    window.approveLeaveRequest = approveLeaveRequest;
    window.rejectLeaveRequest = rejectLeaveRequest;

    function closeActionMenus() {
        document.querySelectorAll('.action-menu-panel').forEach(menu => {
            menu.classList.add('hidden');
            menu.style.left = '';
            menu.style.top = '';
            if (menu.__actionMenuOwner) {
                menu.__actionMenuOwner.appendChild(menu);
            }
        });
        document.querySelectorAll('.action-menu-toggle').forEach(btn => btn.setAttribute('aria-expanded', 'false'));
    }

    function positionActionMenu(toggle, panel) {
        const gap = 8;
        const edgePadding = 12;
        const toggleRect = toggle.getBoundingClientRect();

        panel.style.left = '0px';
        panel.style.top = '0px';
        panel.__actionMenuOwner = panel.__actionMenuOwner || panel.parentElement;
        document.body.appendChild(panel);
        panel.classList.remove('hidden');

        const panelRect = panel.getBoundingClientRect();
        const left = Math.min(
            Math.max(edgePadding, toggleRect.right - panelRect.width),
            window.innerWidth - panelRect.width - edgePadding
        );
        const openBelow = toggleRect.bottom + gap + panelRect.height <= window.innerHeight - edgePadding;
        const top = openBelow
            ? toggleRect.bottom + gap
            : Math.max(edgePadding, toggleRect.top - panelRect.height - gap);

        panel.style.left = `${left}px`;
        panel.style.top = `${top}px`;
    }

    document.addEventListener('click', (e) => {
        const toggle = e.target.closest('.action-menu-toggle');
        if (toggle) {
            const wrapper = toggle.parentElement;
            const panel = wrapper?.querySelector('.action-menu-panel');
            const willOpen = panel?.classList.contains('hidden');
            closeActionMenus();
            if (panel && willOpen) {
                positionActionMenu(toggle, panel);
                toggle.setAttribute('aria-expanded', 'true');
            }
            return;
        }

        const menuAction = e.target.closest('[data-action]');
        if (menuAction && menuAction.dataset.uuid) {
            const { action, uuid } = menuAction.dataset;
            closeActionMenus();
            if (action === 'view') openEventFromView(uuid, state.events.find(ev => ev.uuid === uuid)?.source_type || 'calendar');
            else if (action === 'edit') editEvent(uuid);
            else if (action === 'delete') requestActionConfirmation('delete', uuid);
            else if (action === 'approve') requestActionConfirmation('approve', uuid);
            else if (action === 'reject') requestActionConfirmation('reject', uuid);
            else if (action === 'cancel-approval') requestActionConfirmation('cancel-approval', uuid);
            else if (action === 'reopen') requestActionConfirmation('reopen', uuid);
            return;
        }

        if (!e.target.closest('.action-menu-panel')) {
            closeActionMenus();
        }
    });

    /* ── Event listeners ── */
    document.querySelectorAll('.view-switch').forEach(btn => {
        btn.addEventListener('click', () => {
            const view = btn.dataset.viewSwitch;
            state.view = view;

            // Update buttons visual state
            document.querySelectorAll('.view-switch').forEach(item => {
                const isActive = (item.dataset.viewSwitch === view);
                item.classList.toggle('active', isActive);
                item.setAttribute('aria-pressed', String(isActive));
            });
            loadEvents().catch(console.error);
        });
    });

    el('prevPeriod').addEventListener('click', () => {
        if (state.view==='month') state.current.setMonth(state.current.getMonth()-1);
        else if (state.view==='week') state.current.setDate(state.current.getDate()-7);
        else state.current.setDate(state.current.getDate()-1);
        els.dateJump.value = localDateKey(state.current);
        loadEvents().catch(console.error);
    });
    el('nextPeriod').addEventListener('click', () => {
        if (state.view==='month') state.current.setMonth(state.current.getMonth()+1);
        else if (state.view==='week') state.current.setDate(state.current.getDate()+7);
        else state.current.setDate(state.current.getDate()+1);
        els.dateJump.value = localDateKey(state.current);
        loadEvents().catch(console.error);
    });
    el('todayPeriod').addEventListener('click', () => { state.current = new Date(); els.dateJump.value = localDateKey(state.current); loadEvents().catch(console.error); });
    el('openCreateEvent').addEventListener('click', () => openEventModal());
    el('closeEventModal').addEventListener('click', closeModal);
    el('clearFilters').addEventListener('click', () => {
        state.filters = { employee_id:'', department:'', branch:'', event_type:'', status:'' };
        ['employeeFilter','departmentFilter','branchFilter','eventTypeFilter','statusFilter'].forEach(id => { el(id).value = ''; });
        loadEvents().catch(console.error);
    });
    el('addTargetBtn').addEventListener('click', () => {
        if (!state.modalReadOnly) addTargetRow();
    });
    el('deleteEventBtn').addEventListener('click', () => deleteEvent().catch(console.error));
    el('dateJump').addEventListener('change', (e) => { state.current = new Date(`${e.target.value}T12:00:00`); loadEvents().catch(console.error); });

    ['employeeFilter','departmentFilter','branchFilter','eventTypeFilter','statusFilter'].forEach(id => {
        el(id).addEventListener('change', (e) => {
            const key = id.replace('Filter','');
            state.filters[key === 'eventType' ? 'event_type' : key] = e.target.value;
            scheduleReload();
        });
    });

    document.querySelectorAll('.weekday-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const d = btn.dataset.weekday;
            if (state.selectedWeekdays.has(d)) state.selectedWeekdays.delete(d);
            else state.selectedWeekdays.add(d);
            updateWeekdayButtons();
        });
    });

    els.form.addEventListener('submit', (e) => saveEvent(e).catch(err => showToast(err.message,'error')));

    /* ── Keyboard: Escape closes modals ── */
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            if (els.confirmOverlay.classList.contains('open')) { els.confirmCancelBtn.click(); return; }
            if (els.modal.classList.contains('open')) { closeModal(); }
        }
    });

    /* ── Backdrop click closes modals ── */
    els.modal.addEventListener('click', (e) => { if (e.target === els.modal) closeModal(); });
    els.confirmOverlay.addEventListener('click', (e) => { if (e.target === els.confirmOverlay) els.confirmCancelBtn.click(); });

    function updateViewButtons() {
        document.querySelectorAll('.view-switch').forEach(btn => {
            const active = btn.dataset.viewSwitch === state.view;
            btn.classList.toggle('active', active);
            btn.setAttribute('aria-pressed', String(active));
        });
    }

    /* ── Init ── */
    // Initial state setup: Set active view based on state
    updateViewButtons();
    
    loadOptions()
        .then(() => loadEvents())
        .catch(err => {
            console.error(err);
            showToast('Failed to initialise calendar.', 'error');
        });
})();
</script>
