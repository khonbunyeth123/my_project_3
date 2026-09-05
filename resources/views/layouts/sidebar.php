<?php require_once __DIR__ . '/../../../app/Helpers/PermissionHelper.php'; ?>

<aside id="appSidebar"
  class="fixed left-0 top-[50px] z-40 h-[calc(100vh-50px)] w-60 -translate-x-full bg-white text-slate-600 border-r border-slate-100 transition-transform duration-300 ease-in-out md:static md:translate-x-0 flex flex-col"
  aria-label="Primary navigation">
  
  <div class="flex-1 overflow-y-auto overflow-x-hidden p-4 space-y-6">
    
    <!-- Menu Section -->
    <nav>
      <div class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 px-2">Main</div>
      <ul class="space-y-0.5">
        <?php
        $current_page = $_GET['page'] ?? 'dashboard';
        
        $menu_items = [
          ['page'=>'dashboard','label'=>'Dashboard','icon'=>'mdi:view-dashboard-outline','permissions'=>['dashboard.view']],
          ['page'=>'attendance','label'=>'Attendance','icon'=>'mdi:clock-check-outline','permissions'=>['attendance.view']],
          ['page'=>'employee','label'=>'Employees','icon'=>'mdi:account-group-outline','permissions'=>['employee.view','employees.view']],
          ['page'=>'departments','label'=>'Departments','icon'=>'mdi:office-building-outline','permissions'=>['department.view','departments.view','roles.manage']],
          ['page'=>'leave','label'=>'Leave','icon'=>'mdi:calendar-month-outline','permissions'=>['leave.view']],
          ['page'=>'calendar','label'=>'Calendar','icon'=>'mdi:calendar-range-outline','permissions'=>['calendar.view','calendar.manage']],
          // ['page'=>'payroll','label'=>'Payroll','icon'=>'mdi:cash-register'],
        ];

        foreach ($menu_items as $item):
          if (!$canAccessSlugs($item['permissions'])) continue;
          $is_active = $current_page === $item['page'];
        ?>
        <li>
          <a href="?page=<?= $item['page'] ?>"
            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold transition-all duration-200 group
                  <?= $is_active 
                      ? 'bg-indigo-50 text-indigo-600' 
                      : 'hover:bg-slate-50 hover:text-slate-900' ?>">
            <span class="iconify text-base <?= $is_active ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' ?>" data-icon="<?= $item['icon'] ?>"></span>
            <span><?= $item['label'] ?></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </nav>

    <!-- Analytics Section -->
    <nav>
      <div class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 px-2">Analytics</div>
      <ul class="space-y-0.5">
        <?php
        $is_report_active = strpos($current_page, 'report') === 0;
        ?>
        <li>
          <button type="button" 
            class="nav-toggle flex items-center justify-between w-full px-3 py-2 rounded-lg text-xs font-bold transition-all duration-200 group
                  <?= $is_report_active ? 'bg-indigo-50 text-indigo-600' : 'hover:bg-slate-50 hover:text-slate-900' ?>"
            data-menu="reports">
            <div class="flex items-center gap-2.5">
              <span class="iconify text-base <?= $is_report_active ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' ?>" data-icon="mdi:chart-bar"></span>
              <span>Reports</span>
            </div>
            <span class="iconify dropdown-arrow text-sm transition-transform duration-300 <?= $is_report_active ? 'rotate-180' : '' ?>" data-icon="mdi:chevron-down"></span>
          </button>
          
          <ul class="submenu space-y-0.5 mt-0.5 px-2 <?= $is_report_active ? 'open' : '' ?>" data-submenu="reports">
            <?php
            $report_items = [
              ['page' => 'report/report_daily',   'label' => 'Daily Attendance'],
              ['page' => 'report/report_summary', 'label' => 'Summary'],
              ['page' => 'report/report_detail',  'label' => 'Detailed'],
              ['page' => 'report/report_top_employee', 'label' => 'Top'],
            ];
            foreach ($report_items as $sub):
              $reportPermission = match ($sub['page']) {
                'report/report_daily' => ['report.view_daily', 'report.view'],
                'report/report_summary' => ['report.view_summary', 'report.view'],
                'report/report_detail' => ['report.view_detail', 'report.view'],
                default => ['report.view_top', 'report.view'],
              };
              if (!$canAccessSlugs($reportPermission)) continue;
              $is_sub_active = $current_page === $sub['page'];
            ?>
            <li>
              <a href="?page=<?= $sub['page'] ?>"
                class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-[10px] font-bold transition-all duration-200
                      <?= $is_sub_active 
                          ? 'text-indigo-600 bg-indigo-50/50' 
                          : 'text-slate-500 hover:text-indigo-600 hover:bg-slate-50' ?>">
                <span class="w-1 h-1 rounded-full <?= $is_sub_active ? 'bg-indigo-600' : 'bg-slate-300' ?>"></span>
                <span><?= $sub['label'] ?></span>
              </a>
            </li>
            <?php endforeach; ?>
          </ul>
        </li>
      </ul>
    </nav>

    <!-- System Section -->
    <nav>
      <div class="text-[9px] font-black text-slate-400 uppercase tracking-[0.2em] mb-2 px-2">System</div>
      <ul class="space-y-0.5">
        <?php
        $system_items = [
          ['page'=>'user','label'=>'Users','icon'=>'mdi:account-cog-outline','permissions'=>['user.view','users.view']],
          ['page'=>'roles','label'=>'Roles','icon'=>'mdi:shield-account-outline','permissions'=>['role.view','roles.view']],
          
          ['page'=>'permissions','label'=>'Permissions','icon'=>'mdi:key-outline','permissions'=>['permission.view','permissions.view']],
        ];
        foreach ($system_items as $item):
          if (!$canAccessSlugs($item['permissions'])) continue;
          $is_active = $current_page === $item['page'];
        ?>
        <li>
          <a href="?page=<?= $item['page'] ?>"
            class="flex items-center gap-2.5 px-3 py-2 rounded-lg text-xs font-bold transition-all duration-200 group
                  <?= $is_active 
                      ? 'bg-indigo-50 text-indigo-600' 
                      : 'hover:bg-slate-50 hover:text-slate-900' ?>">
            <span class="iconify text-base <?= $is_active ? 'text-indigo-600' : 'text-slate-400 group-hover:text-slate-600' ?>" data-icon="<?= $item['icon'] ?>"></span>
            <span><?= $item['label'] ?></span>
          </a>
        </li>
        <?php endforeach; ?>
      </ul>
    </nav>

  </div>

  <!-- Sidebar Footer -->
  <div class="p-4 border-t border-slate-100">
    <div class="bg-indigo-600 rounded-xl p-3 relative overflow-hidden group">
      <div class="relative z-10">
        <div class="text-[8px] font-black text-white/60 uppercase tracking-widest">Status</div>
        <div class="text-[10px] font-black text-white flex items-center gap-1.5">
            <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 animate-pulse"></span>
            Online
        </div>
      </div>
    </div>
  </div>
</aside>

<script>
document.addEventListener('DOMContentLoaded', () => {
  if (window.Iconify) {
    Iconify.scan();
  }

  document.querySelectorAll('.nav-toggle').forEach(toggle => {
    toggle.addEventListener('click', function () {
      const menu = this.dataset.menu;
      const submenu = document.querySelector(`[data-submenu="${menu}"]`);
      const arrow = this.querySelector('.dropdown-arrow');
      if (!submenu) return;

      const isOpen = submenu.classList.contains('open');

      document.querySelectorAll('.submenu').forEach(sm => {
        if (sm !== submenu) {
          sm.classList.remove('open');
          sm.previousElementSibling
            ?.querySelector('.dropdown-arrow')
            ?.classList.remove('rotate-180');
        }
      });

      submenu.classList.toggle('open', !isOpen);
      arrow?.classList.toggle('rotate-180', !isOpen);

      if (window.Iconify) {
        Iconify.scan();
      }
    });
  });
});
</script>

<style>
.submenu { display: none; }
.submenu.open { display: block; }
.navigation a { text-decoration: none; }
.dropdown-arrow { transition: transform 0.3s ease; }
.rotate-180 { transform: rotate(180deg); }
</style>
