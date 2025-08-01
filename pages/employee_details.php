<?php
// Employee details page
require_once '../includes/db.php';
require_once '../includes/header.php';

// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Get employee ID from URL
$employeeId = $_GET['id'] ?? 0;

// Get employee details
$stmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
$stmt->execute([$employeeId]);
$employee = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$employee) {
    echo "<div class='alert alert-danger'>Employee not found</div>";
    require_once 'footer.php';
    exit;
}

// Get attendance for this employee
$attendanceStmt = $pdo->prepare("SELECT * FROM attendance WHERE employee_id = ? ORDER BY date DESC LIMIT 5");
$attendanceStmt->execute([$employeeId]);

// Get leave requests for this employee
$leaveStmt = $pdo->prepare("SELECT * FROM leave_requests WHERE employee_id = ? ORDER BY date DESC");
$leaveStmt->execute([$employeeId]);

// Get payroll data for this employee
$payrollStmt = $pdo->prepare("SELECT * FROM payroll WHERE employee_id = ?");
$payrollStmt->execute([$employeeId]);
$payroll = $payrollStmt->fetch(PDO::FETCH_ASSOC);
?>

<h2 class="text-center display-4 mb-4">Employee Details</h2>
<hr class="mb-5">
<div class="row mt-4">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Personal Information</h5>
            </div>
            <div class="card-body">
                <p><strong>Name:</strong> <?php echo htmlspecialchars($employee['name']); ?></p>
                <p><strong>Position:</strong> <?php echo htmlspecialchars($employee['position']); ?></p>
                <p><strong>Department:</strong> <?php echo htmlspecialchars($employee['department']); ?></p>
                <p><strong>Salary:</strong> R<?php echo number_format($employee['salary'], 2); ?></p>
                <p><strong>Contact:</strong> <?php echo htmlspecialchars($employee['contact']); ?></p>
                <hr>
                <p><strong>Employment History:</strong><br><?php echo htmlspecialchars($employee['employment_history']); ?></p>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Recent Attendance</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($attendance = $attendanceStmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo $attendance['date']; ?></td>
                                <td class="<?php echo $attendance['status'] == 'Present' ? 'attendance-present' : 'attendance-absent'; ?>">
                                    <?php echo $attendance['status']; ?>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <a href="../pages/attendance.php?employee_id=<?php echo $employeeId; ?>" class="btn btn-sm btn-outline-primary w-100">View All</a>
            </div>
        </div>
    </div>
    
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title">Leave Requests</h5>
            </div>
            <div class="card-body">
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Reason</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($leave = $leaveStmt->fetch(PDO::FETCH_ASSOC)): ?>
                            <tr>
                                <td><?php echo $leave['date']; ?></td>
                                <td><?php echo htmlspecialchars($leave['reason']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo $leave['status'] == 'Approved' ? 'success' : ($leave['status'] == 'Denied' ? 'danger' : 'warning'); ?>">
                                        <?php echo $leave['status']; ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
                <a href="../pages/leave.php?employee_id=<?php echo $employeeId; ?>" class="btn btn-sm btn-outline-primary w-100">View All</a>
            </div>
        </div>
    </div>
</div>

<?php if ($payroll): ?>
<div class="row mt-4">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h5 class="card-title text-center">Payroll Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <p><strong>Hours Worked:</strong> <?php echo $payroll['hours_worked']; ?></p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Leave Deductions:</strong> <?php echo $payroll['leave_deductions']; ?> days</p>
                    </div>
                    <div class="col-md-3">
                        <p><strong>Final Salary:</strong> R<?php echo number_format($payroll['final_salary'], 2); ?></p>
                    </div>
                    <div class="col-md-3">
                        <a href="../pages/payroll.php?employee_id=<?php echo $employeeId; ?>" class="btn btn-sm btn-outline-primary w-100">View Payroll Details</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="mt-4 text-center mb-4">
    <a href="../pages/employees.php" class="btn btn-outline-secondary">Back to Employees</a>
</div>

<?php require_once '../includes/footer.php'; ?>