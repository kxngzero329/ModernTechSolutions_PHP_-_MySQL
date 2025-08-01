<?php
// Payroll management page
require_once '../includes/db.php';
require_once '../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Fetch employee list for dropdown
$employeeList = $pdo->query("SELECT employee_id, name FROM employees ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

// Get selected employee
$employeeId = $_GET['employee_id'] ?? 0;
$employeeName = '';

if ($employeeId) {
    $stmt = $pdo->prepare("SELECT name FROM employees WHERE employee_id = ?");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    $employeeName = $employee ? $employee['name'] : '';
}

// Function to calculate payroll
function calculatePayroll($pdo, $employeeId = 0) {
    if ($employeeId) {
        $sql = "SELECT p.*, e.name, e.salary 
                FROM payroll p
                JOIN employees e ON p.employee_id = e.employee_id
                WHERE p.employee_id = ?";
        $stmt = $pdo->prepare($sql);
        $stmt->execute([$employeeId]);
    } else {
        $sql = "SELECT p.*, e.name, e.salary 
                FROM payroll p
                JOIN employees e ON p.employee_id = e.employee_id
                ORDER BY e.name";
        $stmt = $pdo->query($sql);
    }

    $results = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $hoursWorked = floatval($row['hours_worked']);
        $hourlyRate = ($hoursWorked > 0) ? ($row['final_salary'] / $hoursWorked) : 0;
        $leaveHours = $row['leave_deductions'] * 8;
        $deductionAmount = $hourlyRate * $leaveHours;
        $netSalary = $row['final_salary'] - $deductionAmount;

        $results[] = [
            'employeeId' => $row['employee_id'],
            'name' => $row['name'],
            'hoursWorked' => $row['hours_worked'],
            'leaveDeductions' => $row['leave_deductions'],
            'grossSalary' => $row['salary'],
            'hourlyRate' => round($hourlyRate, 2),
            'leaveHours' => $leaveHours,
            'deductionAmount' => round($deductionAmount, 2),
            'netSalary' => round($netSalary, 2)
        ];
    }
    return $results;
}

$payrollData = calculatePayroll($pdo, $employeeId);
?>

<!-- Fonts & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">

<style>
    body {
        font-family: 'Inter', sans-serif;
        background-color: #f7f9fc;
    }

    .card {
        border: none;
        border-radius: 14px;
        box-shadow: 0 4px 24px rgba(0, 0, 0, 0.06);
    }

    .table-responsive {
        overflow-x: auto;
    }

    .table {
        min-width: 768px;
    }

    .table th {
        vertical-align: middle;
        white-space: nowrap;
    }

    .table td {
        vertical-align: middle;
    }

    .table thead th {
        background-color: #343a40;
        color: #fff;
        font-weight: 600;
        font-size: 0.95rem;
    }

    .btn-outline-info {
        border-radius: 50px;
    }

    .filter-form {
        margin-bottom: 2rem;
        display: flex;
        flex-wrap: wrap;
        gap: 1rem;
        justify-content: center;
    }

    @media (max-width: 768px) {
        .table th,
        .table td {
            font-size: 0.875rem;
            padding: 0.6rem;
        }

        .btn-sm {
            font-size: 0.75rem;
            padding: 0.35rem 0.6rem;
        }
    }
</style>

<div class="container payroll-wrapper">
    <h2 class="text-center display-6">Payroll Management Table <?php echo $employeeName ? "for $employeeName" : ''; ?></h2>
    <hr class="mb-4">

    <!-- Filter Dropdown -->
    <form method="GET" id="filterForm" class="filter-form">
        <select name="employee_id" class="form-select w-auto" onchange="document.getElementById('filterForm').submit()">
            <option value="0">All Employees</option>
            <?php foreach ($employeeList as $emp): ?>
                <option value="<?= $emp['employee_id']; ?>" <?= $employeeId == $emp['employee_id'] ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($emp['name']); ?>
                </option>
            <?php endforeach; ?>
        </select>
    </form>

    <div class="row justify-content-center">
        <div class="col-lg-12">
            <?php if ($employeeId): ?>
                <div class="text-end">
                    <a href="payroll.php" class="btn btn-secondary mb-3"><i class="bi bi-arrow-left-circle"></i> View All Payroll</a>
                </div>
            <?php endif; ?>

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-striped table-hover table-bordered text-center">
                            <thead>
                                <tr>
                                    <?php if (!$employeeId): ?>
                                        <th>Employee</th>
                                    <?php endif; ?>
                                    <th>Hours Worked</th>
                                    <th>Leave Deductions</th>
                                    <th>Hourly Rate</th>
                                    <th>Gross Salary</th>
                                    <th>Deductions</th>
                                    <th>Net Salary</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($payrollData as $payroll): ?>
                                    <tr>
                                        <?php if (!$employeeId): ?>
                                            <td><?php echo htmlspecialchars($payroll['name']); ?></td>
                                        <?php endif; ?>
                                        <td><?php echo $payroll['hoursWorked']; ?></td>
                                        <td><?php echo $payroll['leaveDeductions']; ?> days</td>
                                        <td>R <?php echo number_format($payroll['hourlyRate'], 2); ?></td>
                                        <td>R <?php echo number_format($payroll['grossSalary'], 2); ?></td>
                                        <td>R <?php echo number_format($payroll['deductionAmount'], 2); ?></td>
                                        <td>R <?php echo number_format($payroll['netSalary'], 2); ?></td>
                                        <td>
                                            <a href="../pdf/generate_payslip.php?employee_id=<?php echo $payroll['employeeId']; ?>" class="btn btn-sm btn-outline-info" title="Download Payslip">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                <?php if (empty($payrollData)): ?>
                                    <tr>
                                        <td colspan="8" class="text-muted text-center">No payroll records found.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer.php'; ?>
