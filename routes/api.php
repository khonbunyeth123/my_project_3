<?php

declare(strict_types=1);

// START SESSION - Required for permission checking
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Helpers/PermissionHelper.php';
use App\Core\Router;

// Create router instance
$router = new Router();

/* ================= AUTH ROUTES ================= */
$router->post('/api/auth/login',            'ControllerAuth@login');
$router->post('/api/auth/admin/login',      'ControllerAuth@adminLogin');
$router->post('/api/auth/employee/login',   'ControllerAuth@employeeLogin');
$router->post('/api/auth/logout',           'ControllerAuth@logout');
$router->get('/api/auth/me',                'ControllerAuth@me');
$router->get('/api/auth/admin/me',          'ControllerAuth@adminMe');
$router->get('/api/auth/employee/me',       'ControllerAuth@employeeMe');
$router->post('/api/auth/fcm-token', 'ControllerAuth@saveFcmToken');
$router->post('/api/auth/forgot-password',  'ControllerAuth@forgotPassword');
$router->post('/api/auth/reset-password',   'ControllerAuth@resetPassword');


/* ================= DASHBOARD ROUTES ================= */
$router->get('/api/dashboard/summary',       'ControllerDashboard@summary');
$router->get('/api/dashboard/department',    'ControllerDashboard@department');
$router->get('/api/dashboard/recent-leaves', 'ControllerDashboard@recentLeaves');
$router->get('/api/dashboard/calendar-events', 'ControllerDashboard@calendarEvents');

/* ================= ATTENDANCE ROUTES ================= */
$router->post('/api/attendance/scan',    'ControllerAttendance@scan');
$router->post('/api/attendance/scan/',   'ControllerAttendance@scan');
$router->get('/api/attendance/show',     'ControllerAttendance@show');
$router->post('/api/attendance/checkin', 'ControllerAttendance@checkin');
$router->get('/api/attendance/qr', 'ControllerAttendance@qr');
$router->put('/api/attendance/{uuid}', 'ControllerAttendance@update');
$router->patch('/api/attendance/{uuid}', 'ControllerAttendance@update');
$router->get('/api/attendance/locations', 'ControllerAttendanceLocation@index');
$router->get('/api/attendance/locations/current', 'ControllerAttendanceLocation@index');
$router->get('/api/attendance/locations/{id}', 'ControllerAttendanceLocation@show');
$router->post('/api/attendance/locations', 'ControllerAttendanceLocation@store');
$router->put('/api/attendance/locations/{id}', 'ControllerAttendanceLocation@update');
$router->patch('/api/attendance/locations/{id}', 'ControllerAttendanceLocation@update');
$router->delete('/api/attendance/locations/{id}', 'ControllerAttendanceLocation@destroy');
$router->get('/attendance/checkin',  'ControllerAttendance@checkin');
$router->post('/attendance/checkin', 'ControllerAttendance@checkin');


/* ================= EMPLOYEE ROUTES ================= */
$router->get('/api/employees',       'ControllerEmployee@index');
$router->get('/api/employees/departments', 'ControllerEmployee@departments');
$router->get('/api/employees/show',  'ControllerEmployee@index');
$router->get('/api/employee/calendar-events', 'ControllerEmployee@calendarEvents');
$router->get('/api/employees/{id}',  'ControllerEmployee@show');
$router->post('/api/employees',      'ControllerEmployee@store');
$router->post('/api/employees/{id}', 'ControllerEmployee@update');
$router->put('/api/employees/{id}',  'ControllerEmployee@update');
$router->delete('/api/employees/{id}','ControllerEmployee@delete');

/* ================= DEPARTMENT ROUTES ================= */
$router->get('/api/departments',         'ControllerDepartment@index');
$router->get('/api/departments/{id}',    'ControllerDepartment@show');
$router->post('/api/departments',        'ControllerDepartment@store');
$router->put('/api/departments/{id}',    'ControllerDepartment@update');
$router->patch('/api/departments/{id}',  'ControllerDepartment@update');
$router->delete('/api/departments/{id}', 'ControllerDepartment@destroy');

/* ================= LEAVE ROUTES ================= */
$router->get('/api/leave/list',      'ControllerLeave@index');
$router->get('/api/leaves',          'ControllerLeave@index');
$router->post('/api/leave/create',   'ControllerLeave@create');
$router->post('/api/leave/request',   'ControllerLeave@create');
$router->post('/api/leave/employee/create', 'ControllerLeave@create');
$router->post('/api/employee/leave/create', 'ControllerLeave@create');
$router->post('/api/employee/leave/request', 'ControllerLeave@create');
$router->post('/api/auth/employee/leave/create', 'ControllerLeave@create');
$router->post('/api/leaves',         'ControllerLeave@create');
$router->post('/api/leave/approve',  'ControllerLeave@approve');
$router->patch('/api/leaves/{uuid}/approve', 'ControllerLeave@approve');
$router->post('/api/leave/reject',   'ControllerLeave@reject');
$router->patch('/api/leaves/{uuid}/reject',  'ControllerLeave@reject');
$router->patch('/api/leaves/{uuid}/reopen', 'ControllerLeave@reopen');
$router->patch('/api/leaves/{uuid}/cancel-approval', 'ControllerLeave@cancelApproval');

/* ================= CALENDAR ROUTES ================= */
$router->get('/api/calendar/events',            'ControllerCalendar@index');
$router->get('/api/calendar/events/{uuid}',     'ControllerCalendar@show');
$router->post('/api/calendar/events',           'ControllerCalendar@store');
$router->put('/api/calendar/events/{uuid}',     'ControllerCalendar@update');
$router->delete('/api/calendar/events/{uuid}',  'ControllerCalendar@destroy');
$router->get('/api/calendar/filters',           'ControllerCalendar@filters');
$router->post('/api/calendar/leaves/{uuid}/approve', 'ControllerCalendar@approveLeave');
$router->post('/api/calendar/leaves/{uuid}/reject',  'ControllerCalendar@rejectLeave');

/* ================= HISTORY ROUTES (Mobile) ================= */
$router->get('/api/attendance/history', 'ControllerAttendance@history');
$router->get('/api/leave/history',      'ControllerLeave@history');

/* ================= REPORT ROUTES ================= */
$router->get('/api/report/daily',         'ControllerReport@dailyList');
$router->get('/api/report/summary',       'ControllerReport@summary');
$router->get('/api/report/detailed',      'ControllerReport@detailedList');
$router->get('/api/report/top-employees', 'ControllerReport@topEmployees');

/* ================= USER ROUTES ================= */
$router->get('/api/users',          'ControllerUser@show');
$router->get('/api/users/show',     'ControllerUser@show');
$router->post('/api/users/create',  'ControllerUser@create');
$router->post('/api/users/update', 'ControllerUser@update');
$router->post('/api/users/delete',  'ControllerUser@delete');

/* ================= ROLE ROUTES ================= */
$router->get('/api/roles',              'ControllerRole@index');
$router->post('/api/roles',             'ControllerRole@store');
$router->get('/api/roles/stats',        'ControllerRole@stats');
$router->get('/api/roles/search',       'ControllerRole@search');
$router->get('/api/roles/{id}',         'ControllerRole@show');
$router->put('/api/roles/{id}',         'ControllerRole@update');
$router->delete('/api/roles/{id}',      'ControllerRole@destroy');
$router->patch('/api/roles/{id}/status','ControllerRole@updateStatus');

// Role → Permission relations (via RoleController)
$router->get('/api/roles/{id}/permissions',  'ControllerRole@rolePermissions');
$router->post('/api/roles/{id}/permissions', 'ControllerRole@updateRolePermissions');
// HTML view
$router->get('/roles',            'ControllerRole@show');
$router->post('/roles/create',    'ControllerRole@create');
$router->post('/roles/update',    'ControllerRole@update');
$router->put('/roles/{id}',       'ControllerRole@update');
$router->post('/roles/delete',    'ControllerRole@delete');
$router->delete('/roles/{id}',    'ControllerRole@delete');

/* ================= PERMISSION API ROUTES ================= */
// NOTE: specific paths MUST come before wildcard {id} paths

// List & grouped
$router->get('/api/permissions/list',    'ControllerPermission@index');
$router->get('/api/permissions/grouped', 'ControllerPermission@getByCategory');
$router->get('/api/permissions/categories', 'ControllerPermission@getCategories');

// Role-permission relations
$router->get('/api/permissions/role/{roleId}',           'ControllerPermission@getPermissionsByRole');
$router->post('/api/permissions/assign-to-role',         'ControllerPermission@assignToRole');
$router->post('/api/permissions/remove-from-role',       'ControllerPermission@removeFromRole');
$router->post('/api/permissions/assign-multiple-to-role','ControllerPermission@assignMultipleToRole');

// Permission check
$router->get('/api/permissions/check', 'ControllerPermission@checkUserPermission');

// CRUD (wildcard {id} last to avoid swallowing named paths)
$router->get('/api/permissions',         'ControllerPermission@index');
$router->post('/api/permissions',        'ControllerPermission@create');
$router->get('/api/permissions/{id}',    'ControllerPermission@getById');
$router->put('/api/permissions/{id}',    'ControllerPermission@update');
$router->delete('/api/permissions/{id}', 'ControllerPermission@delete');

/* ================= PERMISSION VIEW ROUTES ================= */
$router->get('/permissions',                              'ControllerPermission@show');
$router->get('/permissions/list',                         'ControllerPermission@index');
$router->get('/permissions/category',                     'ControllerPermission@getByCategory');
$router->get('/permissions/categories',                   'ControllerPermission@getCategories');
$router->get('/permissions/check',                        'ControllerPermission@checkUserPermission');
$router->get('/permissions/role/{roleId}',                'ControllerPermission@getPermissionsByRole');
$router->post('/permissions/create',                      'ControllerPermission@create');
$router->post('/permissions/assign-to-role',              'ControllerPermission@assignToRole');
$router->post('/permissions/remove-from-role',            'ControllerPermission@removeFromRole');
$router->post('/permissions/assign-multiple-to-role',     'ControllerPermission@assignMultipleToRole');
$router->get('/permissions/{id}',                         'ControllerPermission@getById');
$router->put('/permissions/{id}',                         'ControllerPermission@update');
$router->delete('/permissions/{id}',                      'ControllerPermission@delete');

/* ================= PAYROLL ROUTES ================= */
$router->get('/api/payroll/summary',      'ControllerPayroll@summary');
$router->post('/api/payroll/generate',    'ControllerPayroll@generate');
$router->post('/api/payroll/approve',     'ControllerPayroll@approve');
$router->get('/api/payroll/config/{id}',  'ControllerPayroll@getConfig');
$router->post('/api/payroll/config/{id}', 'ControllerPayroll@updateConfig');

/* ================= DISPATCH REQUEST ================= */
$router->dispatch();
