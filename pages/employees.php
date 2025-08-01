<?php
ob_start(); // Buffer output to allow header redirects
require_once '../includes/db.php';
require_once '../includes/header.php';


// Check if user is logged in
if (!isset($_SESSION['loggedin'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Handle form submission for adding new employee
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add_employee'])) {
        // Handle adding new employee
        $name = trim($_POST['name']);
        $position = trim($_POST['position']);
        $department = trim($_POST['department']);
        $salary = floatval($_POST['salary']);
        $employmentHistory = trim($_POST['employment_history']);
        $contact = trim($_POST['contact']);

        // Validate all required fields
        $errors = [];
        if (empty($name))
            $errors[] = "Name is required";
        if (empty($position))
            $errors[] = "Position is required";
        if (empty($department))
            $errors[] = "Department is required";
        if (empty($salary) || $salary <= 0)
            $errors[] = "Valid salary is required";
        if (empty($contact) || !filter_var($contact, FILTER_VALIDATE_EMAIL))
            $errors[] = "Valid email is required";

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Insert into employees table
                $stmt = $pdo->prepare("INSERT INTO employees (name, position, department, salary, employment_history, contact) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([
                    $name,
                    $position,
                    $department,
                    $salary,
                    $employmentHistory,
                    $contact
                ]);
                $employeeId = $pdo->lastInsertId();

                $hoursWorked = 160; // 8 hours/day * 20 days
                $leaveDeductions = 0; // Default value

                // Insert into payroll table
                $payrollStmt = $pdo->prepare("INSERT INTO payroll (employee_id, hours_worked, leave_deductions, final_salary) VALUES (?, ?, ?, ?)");
                $payrollStmt->execute([$employeeId, $hoursWorked, $leaveDeductions, $salary]);

                $pdo->commit();

                $_SESSION['success'] = "Employee added successfully!";
                header("Location: ../pages/employees.php");
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    } elseif (isset($_POST['update_employee'])) {
        // Handle employee update
        $employeeId = $_POST['employee_id'];
        $name = trim($_POST['name']);
        $position = trim($_POST['position']);
        $department = trim($_POST['department']);
        $salary = floatval($_POST['salary']);
        $employmentHistory = trim($_POST['employment_history']);
        $contact = trim($_POST['contact']);

        // Validate all required fields
        $errors = [];
        if (empty($name))
            $errors[] = "Name is required";
        if (empty($position))
            $errors[] = "Position is required";
        if (empty($department))
            $errors[] = "Department is required";
        if (empty($salary) || $salary <= 0)
            $errors[] = "Valid salary is required";
        if (empty($contact) || !filter_var($contact, FILTER_VALIDATE_EMAIL))
            $errors[] = "Valid email is required";

        if (empty($errors)) {
            try {
                $pdo->beginTransaction();

                // Update employee record
                $stmt = $pdo->prepare("UPDATE employees SET 
                    name = ?, 
                    position = ?, 
                    department = ?, 
                    salary = ?, 
                    employment_history = ?, 
                    contact = ?
                    WHERE employee_id = ?");

                $stmt->execute([
                    $name,
                    $position,
                    $department,
                    $salary,
                    $employmentHistory,
                    $contact,
                    $employeeId
                ]);

                // Update payroll final_salary to match
                $payrollStmt = $pdo->prepare("UPDATE payroll SET final_salary = ? WHERE employee_id = ?");
                $payrollStmt->execute([$salary, $employeeId]);

                $pdo->commit();

                $_SESSION['success'] = "Employee updated successfully!";
                header("Location: ../pages/employees.php");
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $errors[] = "Database error: " . $e->getMessage();
            }
        }
    }
}

// Handle employee deletion
if (isset($_GET['delete'])) {
    $employeeId = $_GET['delete'];

    try {
        $pdo->beginTransaction();

        // Delete from payroll first (foreign key constraint)
        $pdo->prepare("DELETE FROM payroll WHERE employee_id = ?")->execute([$employeeId]);

        // Then delete from employees
        $pdo->prepare("DELETE FROM employees WHERE employee_id = ?")->execute([$employeeId]);

        $pdo->commit();

        $_SESSION['success'] = "Employee deleted successfully!";
        header("Location: ../pages/employees.php");
        exit;
    } catch (PDOException $e) {
        $pdo->rollBack();
        $errors[] = "Delete employee unsuccessful: " . $e->getMessage();
    }
}

// Handle adding a review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_review'])) {
    $review_employee_id = $_POST['review_employee_id'];
    $review_text = trim($_POST['review_text']);
    if (!empty($review_text)) {
        $stmt = $pdo->prepare("INSERT INTO employee_reviews (employee_id, review_text) VALUES (?, ?)");
        $stmt->execute([$review_employee_id, $review_text]);
        $_SESSION['success'] = "Review added successfully!";
        header("Location: ../pages/employees.php");
        exit;
    } else {
        $errors[] = "Review text cannot be empty.";
    }
}

// Handle deleting a review
if (isset($_GET['delete_review'])) {
    $review_id = $_GET['delete_review'];
    $stmt = $pdo->prepare("DELETE FROM employee_reviews WHERE review_id = ?");
    $stmt->execute([$review_id]);
    $_SESSION['success'] = "Review deleted successfully!";
    header("Location: ../pages/employees.php");
    exit;
}

// Get all employees
$stmt = $pdo->query("SELECT * FROM employees ORDER BY name");
$employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ModernTech HR | Employee Management</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Fonts & Icons -->
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
    <style>
        body {
            font-family: 'Inter', sans-serif;
        }
        .sidebar{
            background-color: #0f2027 !important;
        }

        .employee-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-top: 20px;
            
        }
        
        .employee-card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            background: white;
            overflow: hidden;
            border: 1px solid #a3a3a3ff ;
        }
        
        .employee-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .card-header {
            background-color: #152b35ff;
            color: white;
            padding: 15px;
            position: relative;
        }
        
        .card-header h5 {
            margin: 0;
            font-size: 1.2rem;
        }
        
        .card-body {
            padding: 20px;

        }
        
        .card-detail {
            margin-bottom: 10px;
            display: flex;
            
            

        }
        
        .card-detail-label {
            font-weight: bold;
            min-width: 100px;
        }
        
        .card-actions {
            display: flex;
            justify-content: center;
            gap: 15px;
            padding: 10px 15px;
            border-top: 1px solid #eee;
            background-color: #e9e9e9ff;
        }
        
        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }
        
        .search-container input {
            flex-grow: 1;
            padding: 10px;
            border-radius: 5px;
            border: 1px solid #ddd;
        }
        
        .no-results {
            grid-column: 1 / -1;
            text-align: center;
            padding: 40px;
            color: #6c757d;
        }
        
        .badge-department {
            background-color: #4442c1ff;
            position: absolute;
            right: 15px;
            top: 15px;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2 class="text-center display-6">Employee Management</h2>
        <hr class="mb-4">
        
        <!-- Success/Error Messages -->
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success"><?= $_SESSION['success'];
            unset($_SESSION['success']); ?></div>
        <?php endif; ?>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <?php foreach ($errors as $error): ?>
                    <div><?= htmlspecialchars($error) ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- Search and Add Employee Button -->
        <div class="search-container">
            <input type="text" id="employeeSearch" placeholder="Search employees by name, position, department...">
            <button type="button" class="btn btn-outline-primary mb-3" data-bs-toggle="modal" data-bs-target="#addEmployeeModal">
                <i class="bi bi-person-plus"></i> + Add Employee
            </button>
        </div>

        <!-- Employees Grid -->
        <div class="employee-grid">
            <?php if (empty($employees)): ?>
                <div class="no-results">
                    <i class="bi bi-people" style="font-size: 2rem;"></i>
                    <p>No employees found</p>
                </div>
            <?php else: ?>
                <?php foreach ($employees as $employee): ?>
                    <div class="employee-card">
                        <div class="card-header">
                            <h5><?= htmlspecialchars($employee['name']) ?></h5>
                            <span class="badge badge-department"><?= htmlspecialchars($employee['department']) ?></span>
                        </div>
                        <div class="card-body">
                            <div class="card-detail">
                                <span class="card-detail-label">Position:</span>
                                <span><?= htmlspecialchars($employee['position']) ?></span>
                            </div>
                            <div class="card-detail">
                                <span class="card-detail-label">Salary:</span>
                                <span>R<?= number_format($employee['salary'], 2) ?></span>
                            </div>
                            <div class="card-detail">
                                <span class="card-detail-label">Contact:</span>
                                <span class="text-truncate"><?= htmlspecialchars($employee['contact']) ?></span>
                            </div>
                            <?php if (!empty($employee['employment_history'])): ?>
                                <div class="card-detail">
                                    <span class="card-detail-label">History:</span>
                                    <span><?= nl2br(htmlspecialchars(substr($employee['employment_history'], 0, 50))) . (strlen($employee['employment_history']) > 50 ? '...' : '') ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div class="card-actions">
                            <!-- View Button -->
                            <a href="employee_details.php?id=<?= $employee['employee_id'] ?>" class="btn btn-sm btn-info"
                                title="View">
                                <i class="bi bi-eye"></i>
                            </a>

                            <!-- Edit Button (triggers modal) -->
                            <button type="button" class="btn btn-sm btn-warning" data-bs-toggle="modal"
                                data-bs-target="#editEmployeeModal<?= $employee['employee_id'] ?>" title="Edit">
                                <i class="bi bi-pencil"></i>
                            </button>

                            <!-- Delete Button -->
                            <a href="employees.php?delete=<?= $employee['employee_id'] ?>" class="btn btn-sm btn-danger"
                                onclick="return confirm('Are you sure you want to delete this employee?')" title="Delete">
                                <i class="bi bi-trash"></i>
                            </a>

                            <!-- Reviews Button -->
                            <button type="button" class="btn btn-sm btn-secondary" data-bs-toggle="modal"
                                data-bs-target="#reviewsModal<?= $employee['employee_id'] ?>" title="Reviews">
                                <i class="bi bi-chat-dots"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Edit Employee Modal -->
                    <div class="modal fade" id="editEmployeeModal<?= $employee['employee_id'] ?>" tabindex="-1"
                        aria-labelledby="editEmployeeModalLabel<?= $employee['employee_id'] ?>" aria-hidden="true">
                        <div class="modal-dialog modal-lg">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="editEmployeeModalLabel<?= $employee['employee_id'] ?>">
                                        Edit Employee: <?= htmlspecialchars($employee['name']) ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form method="POST">
                                    <div class="modal-body">
                                        <input type="hidden" name="employee_id" value="<?= $employee['employee_id'] ?>">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="name<?= $employee['employee_id'] ?>" class="form-label">Full
                                                        Name*</label>
                                                    <input type="text" class="form-control"
                                                        id="name<?= $employee['employee_id'] ?>" name="name"
                                                        value="<?= htmlspecialchars($employee['name']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="position<?= $employee['employee_id'] ?>"
                                                        class="form-label">Position*</label>
                                                    <input type="text" class="form-control"
                                                        id="position<?= $employee['employee_id'] ?>" name="position"
                                                        value="<?= htmlspecialchars($employee['position']) ?>" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="department<?= $employee['employee_id'] ?>"
                                                        class="form-label">Department*</label>
                                                    <select class="form-select" id="department<?= $employee['employee_id'] ?>"
                                                        name="department" required>
                                                        <option value="">Select Department</option>
                                                        <?php
                                                        $departments = ['Development', 'HR', 'QA', 'Sales', 'Marketing', 'Design', 'IT', 'Finance', 'Support'];
                                                        foreach ($departments as $dept) {
                                                            $selected = ($dept == $employee['department']) ? 'selected' : '';
                                                            echo "<option value=\"$dept\" $selected>$dept</option>";
                                                        }
                                                        ?>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="salary<?= $employee['employee_id'] ?>"
                                                        class="form-label">Monthly Salary (R)*</label>
                                                    <input type="number" class="form-control"
                                                        id="salary<?= $employee['employee_id'] ?>" name="salary"
                                                        value="<?= $employee['salary'] ?>" min="0" step="0.01" required>
                                                </div>
                                                <div class="mb-3">
                                                    <label for="contact<?= $employee['employee_id'] ?>"
                                                        class="form-label">Email*</label>
                                                    <input type="email" class="form-control"
                                                        id="contact<?= $employee['employee_id'] ?>" name="contact"
                                                        value="<?= htmlspecialchars($employee['contact']) ?>" required>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mb-3">
                                            <label for="employment_history<?= $employee['employee_id'] ?>"
                                                class="form-label">Employment History</label>
                                            <textarea class="form-control"
                                                id="employment_history<?= $employee['employee_id'] ?>" name="employment_history"
                                                rows="3"><?= htmlspecialchars($employee['employment_history']) ?></textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" name="update_employee" class="btn btn-primary">Save
                                            Changes</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Reviews Modal -->
                    <div class="modal fade" id="reviewsModal<?= $employee['employee_id'] ?>" tabindex="-1"
                        aria-labelledby="reviewsModalLabel<?= $employee['employee_id'] ?>" aria-hidden="true">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="reviewsModalLabel<?= $employee['employee_id'] ?>">
                                        Reviews for <?= htmlspecialchars($employee['name']) ?>
                                    </h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <!-- List reviews -->
                                    <?php
                                    $reviewStmt = $pdo->prepare("SELECT * FROM employee_reviews WHERE employee_id = ? ORDER BY created_at DESC");
                                    $reviewStmt->execute([$employee['employee_id']]);
                                    $reviews = $reviewStmt->fetchAll(PDO::FETCH_ASSOC);
                                    ?>
                                    <?php if ($reviews): ?>
                                        <ul class="list-group mb-3">
                                            <?php foreach ($reviews as $review): ?>
                                                <li class="list-group-item d-flex justify-content-between align-items-start">
                                                    <div>
                                                        <div><?= nl2br(htmlspecialchars($review['review_text'])) ?></div>
                                                        <small class="text-muted"><?= $review['created_at'] ?></small>
                                                    </div>
                                                    <a href="employees.php?delete_review=<?= $review['review_id'] ?>"
                                                        class="btn btn-sm btn-outline-danger ms-2"
                                                        onclick="return confirm('Delete this review?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <div class="text-muted mb-3">No reviews yet.</div>
                                    <?php endif; ?>

                                    <!-- Add review form -->
                                    <form method="POST">
                                        <input type="hidden" name="review_employee_id" value="<?= $employee['employee_id'] ?>">
                                        <div class="mb-3">
                                            <label for="review_text<?= $employee['employee_id'] ?>" class="form-label">Add
                                                Review</label>
                                            <textarea class="form-control" id="review_text<?= $employee['employee_id'] ?>"
                                                name="review_text" rows="2" required></textarea>
                                        </div>
                                        <button type="submit" name="add_review" class="btn btn-primary btn-sm">Submit
                                            Review</button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Add Employee Modal -->
        <div class="modal fade" id="addEmployeeModal" tabindex="-1" aria-labelledby="addEmployeeModalLabel" aria-hidden="true">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="addEmployeeModalLabel">Add New Employee</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="name" class="form-label">Full Name*</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="position" class="form-label">Position*</label>
                                        <input type="text" class="form-control" id="position" name="position" required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="department" class="form-label">Department*</label>
                                        <select class="form-select" id="department" name="department" required>
                                            <option value="">Select Department</option>
                                            <?php
                                            $departments = ['Development', 'HR', 'QA', 'Sales', 'Marketing', 'Design', 'IT', 'Finance', 'Support'];
                                            foreach ($departments as $dept) {
                                                echo "<option value=\"$dept\">$dept</option>";
                                            }
                                            ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="salary" class="form-label">Monthly Salary (R)*</label>
                                        <input type="number" class="form-control" id="salary" name="salary" min="0" step="0.01"
                                            required>
                                    </div>
                                    <div class="mb-3">
                                        <label for="contact" class="form-label">Email*</label>
                                        <input type="email" class="form-control" id="contact" name="contact" required>
                                    </div>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="employment_history" class="form-label">Employment History</label>
                                <textarea class="form-control" id="employment_history" name="employment_history"
                                    rows="3"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" name="add_employee" class="btn btn-primary">Add Employee</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const searchInput = document.getElementById('employeeSearch');
        const employeeCards = document.querySelectorAll('.employee-card');
        
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            
            employeeCards.forEach(card => {
                const cardText = card.textContent.toLowerCase();
                if (cardText.includes(searchTerm)) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        });
    });
    </script>
</body>
</html>

<?php
ob_end_flush(); // Flush the buffer
require_once '../includes/footer.php';
?>
