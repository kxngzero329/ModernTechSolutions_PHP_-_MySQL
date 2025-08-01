<?php
ob_start();
require_once '../includes/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['loggedin'])) {
    header('Location: ../auth/login.php');
    exit;
}

$employeeId = $_GET['employee_id'] ?? 0;
$employeeName = '';

if ($employeeId) {
    $stmt = $pdo->prepare("SELECT name FROM employees WHERE employee_id = ?");
    $stmt->execute([$employeeId]);
    $employee = $stmt->fetch(PDO::FETCH_ASSOC);
    $employeeName = $employee ? $employee['name'] : '';
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['request_id'], $_POST['status'])) {
    $requestId = $_POST['request_id'];
    $status = $_POST['status'];

    $stmt = $pdo->prepare("UPDATE leave_requests SET status = ? WHERE id = ?");
    $stmt->execute([$status, $requestId]);

    if ($status === 'Approved') {
        $leaveStmt = $pdo->prepare("SELECT employee_id, date FROM leave_requests WHERE id = ?");
        $leaveStmt->execute([$requestId]);
        $leave = $leaveStmt->fetch(PDO::FETCH_ASSOC);

        if ($leave) {
            $check = $pdo->prepare("SELECT id FROM attendance WHERE employee_id = ? AND date = ?");
            $check->execute([$leave['employee_id'], $leave['date']]);

            if ($check->rowCount() === 0) {
                $insert = $pdo->prepare("INSERT INTO attendance (employee_id, date, status) VALUES (?, ?, 'Absent')");
                $insert->execute([$leave['employee_id'], $leave['date']]);
            }
        }
    }

    header("Location: leave.php" . ($employeeId ? "?employee_id=$employeeId" : ""));
    exit;
}

// Handle new request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_leave'])) {
    $employeeId = $_POST['employee_id'];
    $date = $_POST['date'];
    $reason = $_POST['reason'];

    if (empty($employeeId) || empty($date) || empty($reason)) {
        $error = "All fields are required.";
    } else {
        $check = $pdo->prepare("SELECT id FROM leave_requests WHERE employee_id = ? AND date = ?");
        $check->execute([$employeeId, $date]);

        if ($check->rowCount() > 0) {
            $error = "Leave request already exists for this date.";
        } else {
            $stmt = $pdo->prepare("INSERT INTO leave_requests (employee_id, date, reason, status) VALUES (?, ?, ?, 'Pending')");
            $stmt->execute([$employeeId, $date, $reason]);

            header("Location: leave.php" . ($employeeId ? "?employee_id=$employeeId" : ""));
            exit;
        }
    }
}

// Fetch data
if ($employeeId) {
    $stmt = $pdo->prepare("SELECT l.*, e.name FROM leave_requests l JOIN employees e ON l.employee_id = e.employee_id WHERE l.employee_id = ? ORDER BY l.status = 'Pending' DESC, l.date DESC");
    $stmt->execute([$employeeId]);
} else {
    $stmt = $pdo->query("SELECT l.*, e.name FROM leave_requests l JOIN employees e ON l.employee_id = e.employee_id ORDER BY l.status = 'Pending' DESC, l.date DESC");
}
$leaveRequests = $stmt->fetchAll(PDO::FETCH_ASSOC);
$employees = $pdo->query("SELECT employee_id, name FROM employees ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
?>
<!-- Fonts & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<style>
    body {
        font-family: 'Inter', sans-serif ;
    }
</style>

<h2 class="display-6 text-center">Leave Requests <?= $employeeName ? "for $employeeName" : '' ?></h2>
<hr class="mb-4">

<div class="row mb-4 justify-content-center">
    <div class="col-md-6">
        <form method="GET" class="d-flex">
            <select name="employee_id" class="form-select me-2">
                <option value="">Filter by employee</option>
                <?php foreach ($employees as $emp): ?>
                    <option value="<?= $emp['employee_id'] ?>" <?= $employeeId == $emp['employee_id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($emp['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
            <button class="btn btn-outline-primary">Filter</button>
        </form>
    </div>
    <?php if ($employeeId): ?>
        <div class="col-md-6 text-md-end mt-2 mt-md-0">
            <a href="leave.php" class="btn btn-secondary">Clear Filter</a>
        </div>
    <?php endif; ?>
</div>

<!-- Submit Leave Form -->
<div class="card mb-4">
    <div class="card-header text-white text-center" style="background-color: #152b35ff;">
        Submit New Leave Request
    </div>
    <div class="card-body">
        <?php if (isset($error)): ?>
            <div class="alert alert-danger"><?= $error ?></div>
        <?php endif; ?>
        <form method="POST">
            <div class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label for="employee_id" class="form-label">Employee</label>
                    <select class="form-select" name="employee_id" id="employee_id" required <?= $employeeId ? 'disabled' : '' ?>>
                        <?php if ($employeeId): ?>
                            <option value="<?= $employeeId ?>" selected><?= htmlspecialchars($employeeName) ?></option>
                            <input type="hidden" name="employee_id" value="<?= $employeeId ?>">
                        <?php else: ?>
                            <option disabled selected value="">Select Employee</option>
                            <?php foreach ($employees as $emp): ?>
                                <option value="<?= $emp['employee_id'] ?>"><?= htmlspecialchars($emp['name']) ?></option>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                </div>
                <div class="col-md-4">
                    <label for="date" class="form-label">Date</label>
                    <input type="date" class="form-control" name="date" id="date" required value="<?= $_POST['date'] ?? '' ?>">
                </div>
                <div class="col-md-4">
                    <label for="reason" class="form-label">Reason</label>
                    <input type="text" class="form-control" name="reason" id="reason" required placeholder="e.g. Sick, Family event" value="<?= $_POST['reason'] ?? '' ?>">
                </div>
            </div>
            <div class="text-center mt-3">
                <button class="btn btn-outline-primary px-5" name="submit_leave">Submit</button>
            </div>
        </form>
    </div>
</div>

<!-- Requests Table -->
<div class="card">
    <div class="card-header text-white text-center" style="background-color: #152b35ff;">
        Leave Request History
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-bordered table-hover align-middle text-center">
                <thead class="table-light">
                    <tr>
                        <?php if (!$employeeId): ?><th>Employee</th><?php endif; ?>
                        <th>Date</th>
                        <th>Reason</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($leaveRequests)): ?>
                        <tr>
                            <td colspan="<?= $employeeId ? 4 : 5 ?>">No leave requests found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($leaveRequests as $req): ?>
                            <tr>
                                <?php if (!$employeeId): ?>
                                    <td><?= htmlspecialchars($req['name']) ?></td>
                                <?php endif; ?>
                                <td><?= $req['date'] ?></td>
                                <td><?= htmlspecialchars($req['reason']) ?></td>
                                <td>
                                    <span class="badge bg-<?= $req['status'] === 'Approved' ? 'success' : ($req['status'] === 'Denied' ? 'danger' : 'warning') ?>">
                                        <?= $req['status'] ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($req['status'] === 'Pending'): ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                            <button name="status" value="Approved" class="btn btn-sm btn-outline-success">Approve</button>
                                        </form>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                            <button name="status" value="Denied" class="btn btn-sm btn-outline-danger">Deny</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php
ob_end_flush(); // Flush the buffer
require_once '../includes/footer.php'; 
?>
