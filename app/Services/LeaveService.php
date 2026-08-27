<?php
declare(strict_types=1);

namespace App\Services;

use App\Repository\LeaveRepositoryInterface;
use App\Enum\LeaveStatus;
use App\Event\LeaveApprovedEvent;
use App\Event\LeaveRejectedEvent;
use App\Event\LeaveCancelledEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use App\Services\LeaveAuditService;
use App\Services\NotificationService;
use LogicException;

/**
 * Service for managing leave business logic.
 */
class LeaveService
{
    public function __construct(
        private readonly LeaveRepositoryInterface $repository,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly LeaveAuditService $auditService,
        private readonly NotificationService $notificationService
    ) {}

    /**
     * List leaves with pagination.
     */
    public function listLeaves(array $filters, int $page, int $perPage): array
    {
        $page = max(1, $page);
        $perPage = max(1, min(50, $perPage));
        
        $result = $this->repository->listAll($filters, $page, $perPage);
        
        return [
            'total' => $result['total'],
            'rows' => $result['rows'],
            'pages' => max(1, (int) ceil($result['total'] / $perPage))
        ];
    }

    /**
     * Get all leave types.
     */
    public function leaveTypes(): array
    {
        return $this->repository->getLeaveTypes();
    }

    /**
     * Get a leave type ID by name.
     */
    public function getLeaveTypeIdByName(string $name): ?int
    {
        return $this->repository->getLeaveTypeIdByName($name);
    }

    /**
     * Approve a leave application.
     * 
     * @param string $uuid
     * @param int $actorId The user ID performing the approval
     * @return bool
     * @throws LogicException
     */
    public function approveLeave(string $uuid, int $actorId): bool
    {
        $leave = $this->repository->findByUuid($uuid);
        if (!$leave) {
            return false;
        }

        if ($leave['status_id'] === LeaveStatus::APPROVED->value) {
            throw new LogicException('Leave is already approved.');
        }

        $ok = $this->repository->approve((int)$leave['id'], $actorId);
        
        if ($ok) {
            $updatedLeave = array_merge($leave, ['status_id' => LeaveStatus::APPROVED->value]);
            
            // Dispatch event
            $this->eventDispatcher->dispatch(new LeaveApprovedEvent($updatedLeave));
            
            // Record audit log
            $this->auditService->logApproved((int)$leave['id'], $actorId);
            
            // Send notification
            $this->notificationService->sendLeaveApproved((int)$leave['employee_id']);
        }

        return $ok;
    }

    /**
     * Reject a leave application.
     * 
     * @param string $uuid
     * @param string $remark
     * @param int $actorId The user ID performing the rejection
     * @return bool
     */
    public function rejectLeave(string $uuid, string $remark, int $actorId): bool
    {
        $leave = $this->repository->findByUuid($uuid);
        if (!$leave) {
            return false;
        }

        $ok = $this->repository->reject((int)$leave['id'], $actorId, $remark);
        
        if ($ok) {
            $updatedLeave = array_merge($leave, ['status_id' => LeaveStatus::REJECTED->value, 'remark' => $remark]);
            
            // Dispatch event
            $this->eventDispatcher->dispatch(new LeaveRejectedEvent($updatedLeave, $remark));
            
            // Record audit log
            $this->auditService->logRejected((int)$leave['id'], $actorId);
            
            // Send notification
            $this->notificationService->sendLeaveRejected((int)$leave['employee_id'], $remark);
        }

        return $ok;
    }

    public function reopenLeave(string $uuid, int $actorId): bool
    {
        $leave = $this->repository->findByUuid($uuid);
        if (!$leave) {
            return false;
        }

        if ((int) ($leave['status_id'] ?? 0) !== LeaveStatus::REJECTED->value) {
            throw new LogicException('Only rejected leaves can be reopened.');
        }

        return $this->repository->reopen((int) $leave['id'], $actorId);
    }

    public function cancelApproval(string $uuid, int $actorId): bool
    {
        $leave = $this->repository->findByUuid($uuid);
        if (!$leave) {
            return false;
        }

        if ((int) ($leave['status_id'] ?? 0) !== LeaveStatus::APPROVED->value) {
            throw new LogicException('Only approved leaves can be cancelled.');
        }

        $result = $this->repository->cancelApproval((int) $leave['id'], $actorId);
        if ($result) {
            $this->eventDispatcher->dispatch(new LeaveCancelledEvent($leave));
        }
        return $result;
    }

    /**
     * Create a new leave application.
     * 
     * @param array $input
     * @return array
     */
    public function create(array $input): array
    {
        if (!isset($input['leave_type_id']) || $input['leave_type_id'] === '') {
            $leaveTypeName = trim((string) ($input['leave_type'] ?? $input['leave_type_name'] ?? ''));
            
            // Map 'Personal Leave' to 'Casual Leave' if necessary
            if (strtolower($leaveTypeName) === 'personal leave') {
                $leaveTypeName = 'Casual Leave';
            }

            if ($leaveTypeName !== '') {
                $input['leave_type_id'] = $this->repository->getLeaveTypeIdByName($leaveTypeName);
            }
        }

        if (isset($input['leave_type_id']) && is_numeric($input['leave_type_id'])) {
            $input['leave_type_id'] = (int) $input['leave_type_id'];
        }

        $required = [
            'employee_id' => 'Employee ID',
            'leave_type_id' => 'Leave Type ID',
            'start_date' => 'Start Date',
            'end_date' => 'End Date',
            'reason' => 'Reason'
        ];
        foreach ($required as $field => $label) {
            if (empty($input[$field])) {
                return ['success' => false, 'error' => "Missing or empty required field: $label"];
            }
        }

        $startTimestamp = strtotime((string) $input['start_date']);
        $endTimestamp = strtotime((string) $input['end_date']);

        if ($startTimestamp === false) {
            return ['success' => false, 'error' => 'Invalid start_date format: ' . $input['start_date']];
        }
        if ($endTimestamp === false) {
            return ['success' => false, 'error' => 'Invalid end_date format: ' . $input['end_date']];
        }

        if ($endTimestamp < $startTimestamp) {
            return ['success' => false, 'error' => 'End date cannot be before start date'];
        }

        $result = $this->repository->create($input);
        
        if ($result['success']) {
            $this->auditService->logCreated((int)$result['data']['id'], (int)$input['employee_id']);
        }

        if (!$result['success']) {
            error_log('Leave create failed: ' . ($result['error'] ?? 'unknown error'));
        }

        return $result;
    }

    /**
     * Get employee leave history.
     */
    public function getEmployeeHistory(int $employeeId, int $page, int $perPage): array
    {
        $rows = $this->repository->findByEmployee($employeeId);
        return [
            'rows' => $rows,
            'total' => count($rows),
            'total_pages' => 1
        ];
    }

    /**
     * Get a leave application by UUID.
     */
    public function getLeaveByUuid(string $uuid): ?array
    {
        return $this->repository->findByUuid($uuid);
    }
}
