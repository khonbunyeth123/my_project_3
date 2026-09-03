<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use App\Enum\LeaveStatus;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use PDO;
use PDOException;

/**
 * Model for dashboard metrics with caching.
 */
class Dashboard
{
    private PDO $db;

    public function __construct(
        private readonly CacheInterface $cache
    ) {
        $this->db = Database::getInstance()->getConnection();
    }

    /**
     * Get total count of non-deleted employees.
     */
    public function totalEmployees(): int
    {
        return $this->cache->get('dashboard.total_employees', function (ItemInterface $item) {
            $item->expiresAfter(300);
            $result = $this->db->query("SELECT COUNT(*) as total FROM tbl_employees WHERE deleted_at IS NULL")->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['total'] : 0;
        });
    }

    /**
     * Get count of active users.
     */
    public function activeEmployees(): int
    {
        return $this->cache->get('dashboard.active_employees', function (ItemInterface $item) {
            $item->expiresAfter(300);
            $result = $this->db->query("SELECT COUNT(*) as total FROM tbl_users WHERE status_id = 1 AND deleted_at IS NULL")->fetch(PDO::FETCH_ASSOC);
            return $result ? (int)$result['total'] : 0;
        });
    }

    /**
     * Get count of active departments.
     */
    public function totalDepartments(): int
    {
        return $this->cache->get('dashboard.total_departments', function (ItemInterface $item) {
            $item->expiresAfter(300);
            $result = $this->db->query("SELECT COUNT(*) AS total FROM tbl_departments WHERE deleted_at IS NULL AND status_id = 1")->fetch(PDO::FETCH_ASSOC);
            return $result ? (int) $result['total'] : 0;
        });
    }

    /**
     * Get count of pending leave applications.
     */
    public function pendingLeaves(): int
    {
        return $this->cache->get('dashboard.pending_leaves_count', function (ItemInterface $item) {
            $item->expiresAfter(60);
            $sql = "SELECT COUNT(*) FROM tbl_leave_applications WHERE status_id = :status AND deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':status' => LeaveStatus::PENDING->value]);
            return (int)$stmt->fetchColumn();
        });
    }

    /**
     * Get count of employees on leave today.
     */
    public function onLeaveToday(): int
    {
        return $this->cache->get('dashboard.on_leave_today_count', function (ItemInterface $item) {
            $item->expiresAfter(60);
            $sql = "SELECT COUNT(*) FROM tbl_leave_applications 
                    WHERE status_id = :status 
                      AND CURDATE() BETWEEN start_date AND end_date 
                      AND deleted_at IS NULL";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':status' => LeaveStatus::APPROVED->value]);
            return (int)$stmt->fetchColumn();
        });
    }

    /**
     * Get summary stats.
     */
    public function getSummaryStats(): array
    {
        return [
            'total_employees' => $this->totalEmployees(),
            'active_employees' => $this->activeEmployees(),
            'total_departments' => $this->totalDepartments(),
            'pending_leaves' => $this->pendingLeaves(),
            'on_leave_today' => $this->onLeaveToday(),
        ];
    }

    /**
     * Get department statistics.
     */
    public function departmentStats(): array
    {
        $sql = "
            SELECT
                d.id,
                d.name,
                COUNT(e.id) AS count
            FROM tbl_departments d
            LEFT JOIN tbl_employees e
                ON e.department_id = d.id
               AND e.deleted_at IS NULL
            WHERE d.deleted_at IS NULL
              AND d.status_id = 1
            GROUP BY d.id, d.name
            ORDER BY count DESC, d.id DESC
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        $departments = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $total = array_sum(array_map(static fn (array $dept): int => (int) ($dept['count'] ?? 0), $departments));

        return array_map(static function (array $dept) use ($total): array {
            $count = (int) ($dept['count'] ?? 0);

            return [
                'name' => (string) ($dept['name'] ?? 'Unknown'),
                'count' => $count,
                'percentage' => $total > 0 ? round(($count / $total) * 100, 1) : 0,
            ];
        }, $departments);
    }

    /**
     * Get recent leave applications.
     */
    public function recentLeaves(int $limit = 5): array
    {
        $sql = "
            SELECT la.id, la.uuid, e.full_name AS name, lt.name AS type,
                   la.start_date, la.end_date,
                   DATE_FORMAT(la.start_date, '%M %d') AS start_date_formatted,
                   DATE_FORMAT(la.end_date, '%M %d') AS end_date_formatted,
                   DATEDIFF(la.end_date, la.start_date) + 1 AS total_days,
                   la.status_id, la.reason, la.created_at
            FROM tbl_leave_applications la
            INNER JOIN tbl_employees e ON la.employee_id = e.id
            INNER JOIN tbl_leave_types lt ON la.leave_type_id = lt.id
            WHERE la.deleted_at IS NULL
              AND e.deleted_at IS NULL
              AND CURDATE() BETWEEN la.start_date AND la.end_date
            ORDER BY la.start_date DESC, la.created_at DESC LIMIT :limit
        ";
        $stmt = $this->db->prepare($sql);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get calendar events.
     */
    public function getCalendarEvents(string $month, int $employeeId, array $auth = []): array
    {
        $calendar = new CalendarEvent();
        $rangeStart = $month . '-01';
        $rangeEnd = date('Y-m-t', strtotime($rangeStart));
        $result = $calendar->list(
            ['employee_id' => $employeeId, 'department' => '', 'branch' => '', 'event_type' => '', 'status' => ''],
            $rangeStart,
            $rangeEnd,
            $auth
        );
        return array_map(static function (array $event): array {
            return [
                'uuid' => (string)($event['uuid'] ?? ''),
                'title' => (string)($event['title'] ?? 'Untitled'),
                'start' => (string)($event['start'] ?? ''),
                'end' => (string)($event['end'] ?? ''),
                'start_date' => (string)($event['start_date'] ?? substr((string)($event['start'] ?? ''), 0, 10)),
                'end_date' => (string)($event['end_date'] ?? substr((string)($event['end'] ?? ''), 0, 10)),
                'start_time' => (string)($event['start_time'] ?? ''),
                'end_time' => (string)($event['end_time'] ?? ''),
                'time_label' => (string)($event['time_label'] ?? ''),
                'type' => (string)($event['leave_type'] ?? $event['event_type'] ?? 'event'),
                'status' => (string)($event['status'] ?? 'pending'),
                'all_day' => (bool)($event['all_day'] ?? $event['is_all_day'] ?? false),
            ];
        }, $result['events'] ?? []);
    }
}
