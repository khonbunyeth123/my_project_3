<?php
declare(strict_types=1);
 
namespace App\Services;
 
use App\Models\Employee;
use App\Models\Dashboard;
use App\Services\DepartmentService;

class EmployeeService
{
    public function __construct(
        private readonly Employee $employeeModel,
        private readonly Dashboard $dashboardModel,
        private readonly DepartmentService $departmentService
    ) {}
 
    // List all employees
    public function list(): array
    {
        return $this->employeeModel->getAll();
    }
 
    // Show single employee
    public function show($id): ?array
    {
        if (is_numeric($id)) {
            return $this->employeeModel->getById((int) $id);
        }
        
        if (is_string($id) && strlen($id) >= 32) {
            return $this->employeeModel->findActiveEmployeeByUuid($id);
        }

        return null;
    }
 
    // Create
    public function create(array $data, int $authUserId): bool
    {
        // Handle photo upload if present
        if (isset($_FILES['photo']) && $_FILES['photo']['error'] === UPLOAD_ERR_OK) {
            $photoPath = $this->handlePhotoUpload($_FILES['photo']);
            if ($photoPath) {
                $data['photo'] = $photoPath;
            }
        }
 
        // UUID is generated in model if missing.
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['created_by'] = $authUserId;
 
        return $this->employeeModel->create($data);
    }
 
    // Update
    public function update(int $id, array $data, int $authUserId, array $files = []): bool
    {
        // Merge $_FILES (POST) with manually parsed $files (PUT)
        $fileSource = !empty($files) ? $files : $_FILES;

        if (isset($fileSource['photo']) && $fileSource['photo']['error'] === UPLOAD_ERR_OK) {
            $photoPath = $this->handlePhotoUpload($fileSource['photo']);
            if ($photoPath) {
                $data['photo'] = $photoPath;
            }
        }

        $data['updated_by'] = $authUserId;

        return $this->employeeModel->update($id, $data);
    }
 
    // Delete
    public function delete(int $id, int $userId): bool
    {
        return $this->employeeModel->Delete($id, $userId);
    }

    public function getDepartments(): array
    {
        $departments = $this->departmentService->listNames();

        if (!empty($departments)) {
            return $departments;
        }

        return $this->employeeModel->getDepartments();
    }

    public function getCalendarEvents(string $month, int $employeeId): array
    {
        return $this->dashboardModel->getCalendarEvents($month, $employeeId);
    }
 
    // Handle photo upload
    private function handlePhotoUpload(array $file): ?string
    {
        $uploadDir = '../public/uploads/employees/';

        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

        // Use finfo instead of $file['type'] — prevents spoofed MIME from client
        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);
        if (!in_array($mimeType, $allowedTypes)) {
            return null;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            return null;
        }

        $fileExtension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $fileName      = 'employee_' . uniqid() . '.' . $fileExtension;
        $filePath      = $uploadDir . $fileName;

        // move_uploaded_file() only works for native $_FILES (POST)
        // rename() handles manually parsed PUT temp files
        $moved = move_uploaded_file($file['tmp_name'], $filePath)
            || rename($file['tmp_name'], $filePath);

        return $moved ? 'uploads/employees/' . $fileName : null;
    }
}
