<?php
require_once '../includes/db.php';
require_once '../includes/header.php';

if (!isset($_SESSION['loggedin'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Metrics
$employeeCount = $pdo->query("SELECT COUNT(*) FROM employees")->fetchColumn();
$pendingLeave = $pdo->query("SELECT COUNT(*) FROM leave_requests WHERE status = 'Pending'")->fetchColumn();
$absentToday = $pdo->query("SELECT COUNT(*) FROM attendance WHERE date = CURDATE() AND status = 'Absent'")->fetchColumn();

// Leave chart data
$leaveStats = $pdo->query("
    SELECT status, COUNT(*) as count 
    FROM leave_requests 
    GROUP BY status
")->fetchAll(PDO::FETCH_KEY_PAIR);

// Department chart data
$departmentStats = $pdo->query("
    SELECT department, COUNT(*) as count
    FROM employees
    GROUP BY department
")->fetchAll(PDO::FETCH_KEY_PAIR);
?>
<!-- Fonts & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
<style>
    body {
        font-family: 'Inter', sans-serif;
    }

    .card-metric {
        border-left: 5px solid #0d6efd;
        transition: transform 0.2s ease;
        border-radius: 0.5rem;
    }

    .card-metric:hover {
        transform: scale(1.02);
    }

    .metric-icon {
        font-size: 2.2rem;
        color: #0d6efd;
    }

    .badge-status {
        font-size: 0.9rem;
    }

    canvas {
        width: 100% !important;
        max-height: 240px;
    }
</style>

<div class="container-fluid">
    <h2 class="display-6 text-center ">Dashboard Overview</h2>
    <hr class="mb-4">

    <!-- Summary Cards -->
    <div class="row g-4 mb-4">
        <div class="col-md-4">
            <div class="card card-metric shadow-sm p-3">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Total Employees</h6>
                        <h3 class="fw-bold"><?php echo $employeeCount; ?></h3>
                        <a href="../pages/employees.php" class="btn btn-sm btn-outline-primary mt-2">View All</a>
                    </div>
                    <i class="bi bi-people-fill metric-icon"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-metric shadow-sm p-3 border-warning">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Pending Leave Requests</h6>
                        <h3 class="fw-bold text-warning"><?php echo $pendingLeave; ?></h3>
                        <a href="../pages/leave.php" class="btn btn-sm btn-outline-warning mt-2">Manage Requests</a>
                    </div>
                    <i class="bi bi-calendar-event metric-icon text-warning"></i>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card card-metric shadow-sm p-3 border-danger">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h6 class="text-muted">Absent Today</h6>
                        <h3 class="fw-bold text-danger"><?php echo $absentToday; ?></h3>
                        <a href="../pages/attendance.php" class="btn btn-sm btn-outline-danger mt-2">View Attendance</a>
                    </div>
                    <i class="bi bi-x-circle-fill metric-icon text-danger"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Chart Section -->
    <div class="row g-4">
        <!-- Leave Requests Chart -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Leave Requests Overview</h5>
                    <canvas id="leaveChart"></canvas>
                </div>
            </div>
        </div>

        <!-- Department Overview Pie Chart -->
        <div class="col-md-6">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h5 class="card-title">Department Overview</h5>
                    <canvas id="departmentChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
    // Leave Requests Bar Chart
    const leaveCtx = document.getElementById('leaveChart').getContext('2d');
    new Chart(leaveCtx, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode(array_keys($leaveStats)); ?>,
            datasets: [{
                label: 'Leave Requests',
                data: <?php echo json_encode(array_values($leaveStats)); ?>,
                backgroundColor: ['#198754', '#ffc107', '#dc3545'],
                borderRadius: 8
            }]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { display: false }
            },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    // Department Pie Chart
    const deptCtx = document.getElementById('departmentChart').getContext('2d');
    new Chart(deptCtx, {
        type: 'pie',
        data: {
            labels: <?php echo json_encode(array_keys($departmentStats)); ?>,
            datasets: [{
                data: <?php echo json_encode(array_values($departmentStats)); ?>,
                backgroundColor: [
                    '#0d6efd', '#20c997', '#ffc107',
                    '#dc3545', '#6f42c1', '#198754',
                    '#6610f2', '#fd7e14'
                ]
            }]
        },
        options: {
            responsive: true
        }
    });
</script>

<?php require_once '../includes/footer.php'; ?>
