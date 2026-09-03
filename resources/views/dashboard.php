<div class="w-full h-full"> 
    <div class="p-2 space-y-2">
    <!-- Stats Grid -->
    <div id="statsGrid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-2">
        <!-- Loading placeholders -->
        <?php for($i=0; $i<5; $i++): ?>
        <div class="bg-white p-3 rounded-xl shadow-sm border border-slate-100 animate-pulse">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 bg-slate-100 rounded-lg"></div>
                <div class="space-y-1">
                    <div class="h-2 bg-slate-100 rounded w-12"></div>
                    <div class="h-4 bg-slate-100 rounded w-8"></div>
                </div>
            </div>
        </div>
        <?php endfor; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-2">
        <!-- Calendar Section -->
        <div class="lg:col-span-2">
            <?php 
                $title = 'Calendar';
                $icon = 'mdi:calendar-month text-indigo-500';
                ob_start();
            ?>
                <div class="flex flex-wrap items-center gap-2">
                    <?php 
                        $label = 'Full'; $type = 'primary'; $size = 'xs'; $icon = 'mdi:calendar-open'; 
                        $href = '?page=calendar'; $id = 'openFullCalendarLink';
                        include 'component/button.php'; 
                        $label = null; $icon = null; $id = null; // Reset
                    ?>
                    <div class="hidden sm:flex items-center gap-1.5 ml-1">
                        <span class="flex items-center gap-0.5 text-[9px] font-bold text-slate-500 uppercase tracking-wider">
                            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400"></span> Appr
                        </span>
                        <span class="flex items-center gap-0.5 text-[9px] font-bold text-slate-500 uppercase tracking-wider">
                            <span class="w-1.5 h-1.5 rounded-full bg-amber-400"></span> Pend
                        </span>
                        <span class="flex items-center gap-0.5 text-[9px] font-bold text-slate-500 uppercase tracking-wider">
                            <span class="w-1.5 h-1.5 rounded-full bg-rose-400"></span> Rej
                        </span>
                    </div>
                </div>
            <?php 
                $headerRight = ob_get_clean();
                ob_start();
            ?>
                <div id="calendarContainer" class="min-h-[400px]">
                    <div class="flex flex-col items-center justify-center py-12 text-slate-400">
                        <span class="iconify text-2xl mb-1 animate-spin" data-icon="mdi:loading"></span>
                        <p class="text-[10px]">Initializing calendar...</p>
                    </div>
                </div>
            <?php 
                $content = ob_get_clean();
                $padding = false;
                include 'component/card.php'; 
                $title = null; $icon = null; $headerRight = null; $padding = true; // Reset
            ?>
        </div>

        <div class="space-y-2">
            <!-- Departments -->
            <?php 
                $title = 'Depts';
                $icon = 'mdi:office-building text-emerald-500';
                $content = '<div id="departments" class="space-y-2 max-h-[250px] overflow-y-auto no-scrollbar">
                    <div class="flex flex-col items-center justify-center py-6 text-slate-400">
                        <span class="iconify text-2xl mb-1 animate-spin" data-icon="mdi:loading"></span>
                        <p class="text-[10px]">Loading...</p>
                    </div>
                </div>';
                include 'component/card.php'; 
                $title = null; $icon = null; // Reset
            ?>

            <!-- Recent Leave Requests -->
            <?php 
                $title = 'Recent Leave';
                $icon = 'mdi:calendar-clock text-indigo-500';
                $content = '<div id="miniLeaveRequests" class="space-y-1.5 max-h-[250px] overflow-y-auto no-scrollbar">
                    <p class="text-[10px] text-slate-400 text-center py-2">Loading...</p>
                </div>';
                ob_start();
                $label = 'View All'; $type = 'secondary'; $size = 'xs'; $href = '?page=leave'; $class = 'w-full';
                include 'component/button.php';
                $footer = ob_get_clean();
                include 'component/card.php'; 
                $title = null; $icon = null; $footer = null; $label = null; $class = ''; // Reset
            ?>
        </div>
    </div>
</div>

<style>
    .no-scrollbar::-webkit-scrollbar { display: none; }
    .no-scrollbar { -ms-overflow-style: none; scrollbar-width: none; }
</style>

<script>
    /* ---------- SimpleCalendar Class ---------- */
    class SimpleCalendar {
        constructor(containerId) {
            this.container = document.getElementById(containerId);
            this.currentDate = new Date();
            this.events = [];
            this.isLoading = false;
        }

        async fetchEvents() {
            this.isLoading = true;
            const monthStr = this.currentDate.getFullYear() + '-' + String(this.currentDate.getMonth() + 1).padStart(2, '0');
            try {
                const res = await fetch(`/api/dashboard/calendar-events?month=${monthStr}`);
                const result = await res.json();
                this.events = result.success ? result.data : [];
            } catch (e) {
                console.error("Failed to fetch events", e);
                window.Toast?.error("Calendar Error", "Failed to load events.");
            } finally {
                this.isLoading = false;
            }
        }

        getEventClasses(event) {
            const eventType = (event.event_type || event.type || 'event').toLowerCase();
            const status = (event.status || 'pending').toLowerCase();

            const typeStyles = {
                holiday: 'bg-indigo-50 text-indigo-700 border-indigo-200',
                shift: 'bg-sky-50 text-sky-700 border-sky-200',
                leave: 'bg-amber-50 text-amber-700 border-amber-200',
                meeting: 'bg-violet-50 text-violet-700 border-violet-200',
                reminder: 'bg-emerald-50 text-emerald-700 border-emerald-200',
                task: 'bg-slate-50 text-slate-700 border-slate-200',
                event: 'bg-slate-50 text-slate-700 border-slate-200',
            };

            const statusBorder = {
                approved: 'ring-1 ring-emerald-100',
                pending: 'ring-1 ring-amber-100',
                rejected: 'ring-1 ring-rose-100',
                cancelled: 'ring-1 ring-slate-100',
            };

            return `${typeStyles[eventType] || typeStyles.event} ${statusBorder[status] || statusBorder.pending}`;
        }

        async render() {
            await this.fetchEvents();
            
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();
            const monthNames = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"];
            const pad = (value) => String(value).padStart(2, '0');
            const fullCalendarLink = document.getElementById('openFullCalendarLink');
            if (fullCalendarLink) {
                fullCalendarLink.href = `?page=calendar&date=${year}-${pad(month + 1)}-01`;
            }
            
            const firstDay = new Date(year, month, 1).getDay();
            const daysInMonth = new Date(year, month + 1, 0).getDate();
            const today = new Date();
            const isCurrentMonth = today.getFullYear() === year && today.getMonth() === month;

            let html = `
                <div class="p-4 sm:p-6">
                    <div class="flex justify-between items-center mb-6">
                        <div class="flex items-center gap-3">
                            <h3 class="text-lg font-black text-slate-800 tracking-tight">${monthNames[month]} ${year}</h3>
                            ${isCurrentMonth ? '<span class="px-2 py-0.5 bg-indigo-100 text-indigo-600 text-[9px] font-black rounded-full uppercase tracking-wider">Today</span>' : ''}
                        </div>
                        <div class="flex gap-1">
                            <button id="calPrev" class="w-8 h-8 flex items-center justify-center hover:bg-slate-100 rounded-lg transition-all text-slate-600">
                                <span class="iconify" data-icon="mdi:chevron-left" data-width="20"></span>
                            </button>
                            <button id="calToday" class="px-3 py-1 hover:bg-slate-100 rounded-lg transition-all text-[10px] font-black uppercase tracking-wider text-slate-600">Now</button>
                            <button id="calNext" class="w-8 h-8 flex items-center justify-center hover:bg-slate-100 rounded-lg transition-all text-slate-600">
                                <span class="iconify" data-icon="mdi:chevron-right" data-width="20"></span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-7 gap-px bg-slate-100 border border-slate-100 rounded-xl overflow-hidden shadow-sm">
                        ${['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'].map(d => 
                            `<div class="bg-slate-50/80 py-2 text-center text-[9px] font-black text-slate-400 uppercase tracking-widest">${d}</div>`
                        ).join('')}
                        ${this.generateDays(firstDay, daysInMonth)}
                    </div>
                </div>
            `;

            this.container.innerHTML = html;
            this.attachEvents();
        }

        generateDays(firstDay, daysInMonth) {
            let daysHtml = '';
            const today = new Date();
            const year = this.currentDate.getFullYear();
            const month = this.currentDate.getMonth();
            const toDateOnly = (value) => (value || '').toString().slice(0, 10);
            
            // Empty days from previous month
            for (let i = 0; i < firstDay; i++) {
                daysHtml += `<div class="bg-white/50 h-24 sm:h-32 p-1.5"></div>`;
            }

            // Days of the month
            for (let d = 1; d <= daysInMonth; d++) {
                const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
                const dayEvents = this.events.filter(e => {
                    const start = toDateOnly(e.start_date || e.start);
                    const end = toDateOnly(e.end_date || e.end || e.start_date || e.start);
                    return dateStr >= start && dateStr <= end;
                });
                const isToday = today.getFullYear() === year && today.getMonth() === month && today.getDate() === d;
                
                daysHtml += `
                    <div class="bg-white h-24 sm:h-32 p-1.5 relative group hover:bg-slate-50/80 transition-all border-transparent border-2 hover:border-indigo-100">
                        <div class="flex justify-between items-start mb-1">
                            <span class="text-xs font-black ${isToday ? 'bg-indigo-600 text-white w-6 h-6 flex items-center justify-center rounded-lg shadow-lg shadow-indigo-200' : 'text-slate-400 group-hover:text-slate-600'} transition-colors">${d}</span>
                            ${dayEvents.length ? `<span class="text-[9px] font-black rounded-full px-1.5 bg-slate-100 text-slate-500">${dayEvents.length}</span>` : ''}
                        </div>
                        <div class="space-y-1 overflow-y-auto max-h-14 sm:max-h-20 no-scrollbar pb-1">
                            ${dayEvents.map(e => {
                                const classes = this.getEventClasses(e);
                                const label = (e.event_type || e.type || 'event').toUpperCase();
                                const scope = e.scope_label ? ` • ${e.scope_label}` : '';

                                return `
                                    <div class="text-[8px] px-1.5 py-0.5 rounded-md border truncate font-bold shadow-sm ${classes}"
                                         title="${e.title} (${label} - ${e.status})${scope}">
                                        <span class="block truncate">${e.title}</span>
                                    </div>
                                `;
                            }).join('')}
                        </div>
                    </div>
                `;
            }

            // Fill remaining slots
            const totalSlots = firstDay + daysInMonth;
            const remaining = totalSlots > 35 ? 42 - totalSlots : 35 - totalSlots;
            for (let i = 0; i < remaining; i++) {
                daysHtml += `<div class="bg-white/50 h-24 sm:h-32 p-1.5"></div>`;
            }

            return daysHtml;
        }

        attachEvents() {
            document.getElementById('calPrev').onclick = () => {
                this.currentDate.setMonth(this.currentDate.getMonth() - 1);
                this.render();
            };
            document.getElementById('calNext').onclick = () => {
                this.currentDate.setMonth(this.currentDate.getMonth() + 1);
                this.render();
            };
            document.getElementById('calToday').onclick = () => {
                this.currentDate = new Date();
                this.render();
            };
        }
    }

    document.addEventListener("DOMContentLoaded", () => {

        /* ---------- Helpers ---------- */

        function createStat(icon, value, label, colorClass, bgColor) {
            return `
            <div class="bg-white p-3 rounded-xl shadow-sm border border-slate-100 flex items-center gap-3 hover:shadow-md transition-all duration-300">
                <div class="w-10 h-10 ${bgColor} rounded-lg flex items-center justify-center shrink-0">
                    <span class="iconify text-xl ${colorClass}" data-icon="${icon}"></span>
                </div>
                <div class="min-w-0">
                    <div class="text-[10px] font-black text-slate-400 uppercase tracking-wider truncate">${label}</div>
                    <div class="text-lg font-black text-slate-800">${value}</div>
                </div>
            </div>`;
        }

        function formatRelativeLeaveDate(dateValue) {
            if (!dateValue) {
                return 'N/A';
            }

            const input = new Date(`${dateValue}T12:00:00`);
            if (Number.isNaN(input.getTime())) {
                return dateValue;
            }

            const today = new Date();
            const startOfToday = new Date(today.getFullYear(), today.getMonth(), today.getDate());
            const startOfInput = new Date(input.getFullYear(), input.getMonth(), input.getDate());
            const diffDays = Math.round((startOfInput - startOfToday) / 86400000);

            if (diffDays === 0) {
                return 'Today';
            }

            if (diffDays === 1) {
                return 'Tomorrow';
            }

            return input.toLocaleDateString(undefined, { month: 'short', day: 'numeric' });
        }

        function createMiniLeave(req) {
            const statusColors = {
                'pending': 'bg-amber-400',
                'approved': 'bg-emerald-400',
                'rejected': 'bg-rose-400'
            };
            const statusDot = statusColors[req.status?.toLowerCase()] || 'bg-slate-300';
            const leaveDateLabel = formatRelativeLeaveDate(req.start_date);
            const endDateLabel = req.end_date && req.end_date !== req.start_date
                ? ` - ${formatRelativeLeaveDate(req.end_date)}`
                : '';
            
            return `
            <div class="flex items-center gap-3 p-2 rounded-xl hover:bg-slate-50 transition-all border border-transparent hover:border-slate-100 group">
                <div class="w-8 h-8 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 font-black text-[10px] uppercase">
                    ${(req.name || 'U').charAt(0)}
                </div>
                <div class="flex-grow min-w-0">
                    <div class="text-xs font-black text-slate-800 truncate group-hover:text-indigo-600 transition-colors">${req.name || 'Unknown'}</div>
                    <div class="text-[10px] font-medium text-slate-500 truncate">${req.type || 'N/A'} • ${leaveDateLabel}${endDateLabel}</div>
                </div>
                <div class="w-2 h-2 rounded-full ${statusDot}"></div>
            </div>`;
        }

        function groupLeavesByStartDate(leaves) {
            const grouped = [];
            const bucket = new Map();

            leaves.forEach((leave) => {
                const key = leave.start_date || 'unknown';
                if (!bucket.has(key)) {
                    const label = formatRelativeLeaveDate(key);
                    const bucketItem = { key, label, items: [] };
                    bucket.set(key, bucketItem);
                    grouped.push(bucketItem);
                }

                bucket.get(key).items.push(leave);
            });

            return grouped;
        }

        function createMiniLeaveGroup(group) {
            return `
                <div class="space-y-1">
                    <div class="flex items-center justify-between px-1">
                        <div class="text-[9px] font-black uppercase tracking-widest text-indigo-500/70">${group.label}</div>
                        <div class="text-[9px] font-black text-slate-300">${group.items.length}</div>
                    </div>
                    <div class="space-y-1">
                        ${group.items.map((leave) => createMiniLeave(leave)).join('')}
                    </div>
                </div>
            `;
        }

        function createDepartment(dept) {
            return `
            <div class="space-y-1.5">
                <div class="flex justify-between text-[11px]">
                    <span class="font-black text-slate-700 uppercase tracking-tight">${dept.name || 'Unknown'}</span>
                    <span class="text-slate-400 font-black">${dept.count || 0}</span>
                </div>
                <div class="w-full bg-slate-100 rounded-full h-1.5">
                    <div class="bg-indigo-500 h-1.5 rounded-full shadow-sm shadow-indigo-100" style="width: ${dept.percentage || 0}%"></div>
                </div>
            </div>`;
        }

        function loadDepartments() {
            const departmentsContainer = document.getElementById("departments");

            if (departmentsContainer) {
                departmentsContainer.innerHTML = `
                    <div class="flex flex-col items-center justify-center py-12 text-slate-400">
                        <span class="iconify text-2xl mb-2 animate-spin" data-icon="mdi:loading"></span>
                        <p class="text-[10px]">Loading departments...</p>
                    </div>`;
            }

            fetch("/api/dashboard/department")
                .then(res => res.json())
                .then(result => {
                    const data = result.success ? (result.data || []) : (Array.isArray(result) ? result : []);

                    if (!departmentsContainer) {
                        return;
                    }

                    departmentsContainer.scrollTop = 0;

                    if (!data.length) {
                        departmentsContainer.innerHTML = '<div class="text-center text-slate-400 py-12 text-[10px]">No departments found</div>';
                        return;
                    }

                    departmentsContainer.innerHTML = data.map(d => createDepartment(d)).join('');
                })
                .catch(() => {
                    if (departmentsContainer) {
                        departmentsContainer.innerHTML = '<div class="text-center text-rose-400 py-12 text-[10px]">Failed to load departments</div>';
                    }
                });
        }

        /* ---------- Dashboard Summary ---------- */
        fetch("/api/dashboard/summary")
            .then(res => res.json())
            .then(result => {
                const data = result.success ? result.data : result;
                if (!data || result.success === false) throw new Error("API error");
                
                const statsGrid = document.getElementById("statsGrid");
                statsGrid.innerHTML = `
                    ${createStat("mdi:account-multiple", data.total_employees || 0, "Employees", "text-blue-500", "bg-blue-50")}
                    ${createStat("mdi:account-check", data.active_employees || 0, "Active", "text-emerald-500", "bg-emerald-50")}
                    ${createStat("mdi:office-building", data.total_departments || 0, "Departments", "text-indigo-500", "bg-indigo-50")}
                    ${createStat("mdi:clock-alert", data.pending_leaves || 0, "Pending", "text-amber-500", "bg-amber-50")}
                    ${createStat("mdi:calendar-remove", data.on_leave_today || 0, "On Leave", "text-rose-500", "bg-rose-50")}
                `;
            })
            .catch(error => {
                console.error(error);
                window.Toast?.error("Dashboard Error", "Failed to load summary statistics.");
            });

        /* ---------- Recent Leaves (Mini) ---------- */
        fetch("/api/dashboard/recent-leaves?limit=4")
            .then(res => res.json())
            .then(result => {
                const div = document.getElementById("miniLeaveRequests");
                const data = result.success ? result.data : (Array.isArray(result) ? result : []);
                
                if (data.length === 0) {
                    div.innerHTML = '<p class="text-[10px] text-slate-400 text-center py-4">No recent requests</p>';
                    return;
                }

                const grouped = groupLeavesByStartDate(data);
                div.innerHTML = grouped.map(group => createMiniLeaveGroup(group)).join('');
            })
            .catch(() => {
                const div = document.getElementById("miniLeaveRequests");
                div.innerHTML = '<p class="text-[10px] text-rose-400 text-center py-4">Failed to load</p>';
            });

        /* ---------- Departments ---------- */
        loadDepartments();

        /* ---------- Initialize Calendar ---------- */
        const cal = new SimpleCalendar("calendarContainer");
        cal.render();
    });
</script>
