<?php
// PDF Payslip Generator
require_once '../pdf/dompdf/autoload.inc.php'; // Path to your manual dompdf installation
require_once '../includes/db.php';

use Dompdf\Dompdf;
use Dompdf\Options;

// Start session and check login
session_start();
if (!isset($_SESSION['loggedin'])) {
    header('Location: ../auth/login.php');
    exit;
}

// Get employee ID from URL
$employeeId = $_GET['employee_id'] ?? 0;

// Fetch employee data
$employeeStmt = $pdo->prepare("SELECT * FROM employees WHERE employee_id = ?");
$employeeStmt->execute([$employeeId]);
$employee = $employeeStmt->fetch(PDO::FETCH_ASSOC);

// Fetch payroll data
$payrollStmt = $pdo->prepare("SELECT * FROM payroll WHERE employee_id = ?");
$payrollStmt->execute([$employeeId]);
$payroll = $payrollStmt->fetch(PDO::FETCH_ASSOC);

// Check if data exists
if (!$employee || !$payroll) {
    die("Employee or payroll data not found");
}

// Calculate payroll values
$hourlyRate = $payroll['final_salary'] / $payroll['hours_worked'];
$leaveHours = $payroll['leave_deductions'] * 8;
$deductionAmount = $hourlyRate * $leaveHours;
$netSalary = $payroll['final_salary'] - $deductionAmount;

// Convert the logo to base64 encoded string
$logoPath = '../assets/logo-2.png';
if (file_exists($logoPath)) {
    $logoData = base64_encode(file_get_contents($logoPath));
    $logoType = mime_content_type($logoPath);
    $logoSrc = 'data:' . $logoType . ';base64,' . $logoData;
} else {
    $logoSrc = '';
}

// HTML template for PDF
$html = '
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Payslip - ' . htmlspecialchars($employee['name']) . '</title>
    <style>
        body {
            font-family: "Segoe UI", sans-serif;
            font-size: 13px;
            color: #333;
            margin: 0;
            padding: 30px;
        }
        .payslip {
            max-width: 800px;
            margin: auto;
            border: 1px solid #aaa;
            padding: 25px;
            box-shadow: 0 0 5px #ccc;
        }
        .header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            border-bottom: 2px solid #000;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }
        .header img {
            max-height: 60px;
        }
        .company-info {
            text-align: right;
        }
        .company-name {
            font-size: 22px;
            font-weight: bold;
        }
        .company-tagline {
            font-size: 14px;
            color: #555;
        }
        .section-title {
            font-weight: bold;
            background: #f2f2f2;
            padding: 6px;
            margin-top: 25px;
            border-left: 4px solid #007BFF;
        }
        .details-table,
        .salary-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
        }
        .details-table td {
            padding: 6px 10px;
        }
        .salary-table th,
        .salary-table td {
            padding: 10px;
            border: 1px solid #ccc;
        }
        .salary-table th {
            background-color: #f9f9f9;
            text-align: left;
        }
        .salary-table .total {
            font-weight: bold;
            background-color: #e9f5e9;
        }
        .footer {
            text-align: center;
            margin-top: 30px;
            font-size: 11px;
            color: #777;
            border-top: 1px solid #ddd;
            padding-top: 10px;
        }
    </style>
</head>
<body>
    <div class="payslip">
        <div class="header">
            <div><img src="' . $logoSrc . '" alt="Company Logo"></div>
            <div class="company-info">
                <div class="company-name">ModernTech Solutions</div>
                <div class="company-tagline">Empower & Perform.</div>
            </div>
        </div>

        <div class="section-title">Employee Details</div>
        <table class="details-table">
            <tr>
                <td><strong>Name:</strong> ' . htmlspecialchars($employee['name']) . '</td>
                <td><strong>Department:</strong> ' . htmlspecialchars($employee['department']) . '</td>
            </tr>
            <tr>
                <td><strong>Position:</strong> ' . htmlspecialchars($employee['position']) . '</td>
                <td><strong>Pay Date:</strong> ' . date('Y-m-d') . '</td>
            </tr>
        </table>

        <div class="section-title">Earnings & Deductions</div>
        <table class="salary-table">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Amount (R)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Basic Salary</td>
                    <td>' . number_format($employee["salary"], 2) . '</td>
                </tr>
                <tr>
                    <td>Hours Worked</td>
                    <td>' . $payroll["hours_worked"] . '</td>
                </tr>
                <tr>
                    <td>Hourly Rate</td>
                    <td>' . number_format($hourlyRate, 2) . '</td>
                </tr>
                <tr>
                    <td>Leave Deductions (' . $payroll["leave_deductions"] . ' days)</td>
                    <td>- ' . number_format($deductionAmount, 2) . '</td>
                </tr>
                <tr class="total">
                    <td>Net Salary</td>
                    <td>' . number_format($netSalary, 2) . '</td>
                </tr>
            </tbody>
        </table>

        <div class="footer">
            &copy; ' . date("Y") . ' ModernTech Solutions. All rights reserved.
        </div>
    </div>
</body>
</html>
';

// Configure dompdf
$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('isHtml5ParserEnabled', true);

// Create PDF
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

// Generate filename
$filename = 'payslip_' . $employee['employee_id'] . '_' . date('Y-m-d') . '.pdf';

// Output PDF to browser for download
$dompdf->stream($filename, [
    'Attachment' => true
]);

exit;
?>
