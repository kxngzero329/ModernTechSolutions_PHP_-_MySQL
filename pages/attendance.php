<?php
// Attendance tracking page
require_once '../includes/db.php';
require_once '../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Fetch employee list for filter
$employeeList = $pdo->query("SELECT employee_id, name FROM employees ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Filtering
$employeeId = $_GET['employee_id'] ?? 0;
$dateFilter = $_GET['date'] ?? '';
$employeeName = '';

if ($employeeId) {
    $stmt = $pdo->prepare("SELECT name FROM employees WHERE employee_id = ?");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    $employeeName = $employee ? $employee['name'] : '';
}

// Build query
$params = [];
$sql = "SELECT a.*, e.name FROM attendance a 
        JOIN employees e ON a.employee_id = e.employee_id 
        WHERE 1";

if ($employeeId) {
    $sql .= " AND a.employee_id = ?";
    $params[] = $employeeId;
}

if ($dateFilter) {
    $sql .= " AND a.date = ?";
    $params[] = $dateFilter;
}

$sql .= " ORDER BY a.date DESC, e.name";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);

$attendanceRecords = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!-- Fonts & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f4f6f8;
    }

    .attendance-card {
        border: none;
        border-radius: 14px;
        background: #ffffff;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.18);
        transition: transform 0.3s ease;
    }

    .attendance-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }

    .attendance-card .card-body {
        padding: 1.75rem;
    }

    .card-title {
        font-size: 1.1rem;
        font-weight: 600;
        margin-bottom: 1rem;
    }

    .info-line {
        margin-bottom: 0.75rem;
        font-size: 0.95rem;
    }

    .info-line i {
        color: #6c757d;
        margin-right: 8px;
    }

    .badge-status {
        font-size: 0.8rem;
        padding: 0.4em 0.8em;
        border-radius: 50px;
        font-weight: 500;
    }

    .badge-present {
        background-color: #28a7451a;
        color: #28a745;
        border: 1px solid #28a745;
    }

    .badge-absent {
        background-color: #dc35451a;
        color: #dc3545;
        border: 1px solid #dc3545;
    }

    .filter-form {
        margin-bottom: 2rem;
    }

    .legend-box {
        background: #fff;
        border-radius: 10px;
        padding: 0.75rem 1rem;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        margin-bottom: 2rem;
        display: inline-flex;
        gap: 1rem;
        align-items: center;
    }

    .legend-box span {
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }

    .legend-circle {
        width: 12px;
        height: 12px;
        border-radius: 50%;
        display: inline-block;
    }

    .legend-present {
        background-color: #28a745;
    }

    .legend-absent {
        background-color: #dc3545;
    }
</style>

<div class="container">
    <h2 class="display-6 text-center">Attendance Tracking</h2>
    <hr class="mb-4">

    <!-- Filters -->
    <form method="GET" class="row g-3 filter-form justify-content-center">
        <div class="col-md-4">
            <select name="employee_id" class="form-select">
                <option value="0">All Employees</option>
                <?php foreach ($employeeList as $emp): ?>
                    <option value="<?= $emp['employee_id']; ?>" <?= $employeeId == $emp['employee_id'] ? 'selected' : ''; ?>>
                        <?= htmlspecialchars($emp['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-2">
            <button class="btn btn-primary w-100" type="submit"><i class="bi bi-funnel"></i> Filter</button>
        </div>
    </form>

    <!-- icons centralise it -->
    <div class="d-flex justify-content-center">
        <div class="legend-box text-center">
            <span>
                <div class="legend-circle legend-present"></div> Present
            </span>
            <span>
                <div class="legend-circle legend-absent"></div> Absent
            </span>
            <!-- view all attendance records -->
            <div>
                <a href="attendance.php" class="btn btn-secondary"><i class="bi bi-eye"></i> View All Attendance Records</a>
            </div>
        </div>
    </div>



    <!-- Attendance Cards -->
    <div class="row g-4">
        <?php foreach ($attendanceRecords as $record): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card attendance-card h-100">
                    <div class="card-body">
                        <h5 class="card-title">
                            <?php if (!$employeeId): ?>
                                <i class="bi bi-person-circle me-1"></i>
                                <?= htmlspecialchars($record['name']); ?>
                            <?php else: ?>
                                <i class="bi bi-calendar-check me-1"></i> Attendance Entry
                            <?php endif; ?>
                        </h5>

                        <div class="info-line">
                            <i class="bi bi-calendar-event"></i>
                            <strong>Date:</strong> <?= $record['date']; ?>
                        </div>

                        <div class="info-line">
                            <i class="bi bi-clock"></i>
                            <strong>Day:</strong> <?= date('l', strtotime($record['date'])); ?>
                        </div>

                        <div class="info-line">
                            <i class="bi bi-check-circle"></i>
                            <strong>Status:</strong>
                            <span
                                class="badge-status <?= $record['status'] == 'Present' ? 'badge-present' : 'badge-absent'; ?>">
                                <?= $record['status']; ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>