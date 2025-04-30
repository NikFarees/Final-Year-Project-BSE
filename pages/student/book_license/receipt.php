<?php
include '../../../include/st_header.php';
include '../../../database/db_connection.php';

// Check if payment_id is passed
if (!isset($_GET['payment_id']) || empty($_GET['payment_id'])) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Payment ID is missing.</div></div>";
    include '../../../include/footer.html';
    exit();
}

$payment_id = $_GET['payment_id'];

// Query to fetch payment details
$sqlPayment = "SELECT p.payment_id, p.student_license_id, p.payment_type, p.payment_method, 
                      p.total_amount, p.payment_datetime, p.payment_status, u.name AS student_name
               FROM payments p
               INNER JOIN student_licenses sl ON p.student_license_id = sl.student_license_id
               INNER JOIN students s ON sl.student_id = s.student_id
               INNER JOIN users u ON s.user_id = u.user_id
               WHERE p.payment_id = ?";

$sqlDetails = "SELECT pd.item_type, pd.item_id, pd.amount
               FROM payment_details pd
               WHERE pd.payment_id = ?";

try {
    $stmtPayment = $conn->prepare($sqlPayment);
    $stmtPayment->bind_param("s", $payment_id);
    $stmtPayment->execute();
    $resultPayment = $stmtPayment->get_result();

    if ($resultPayment->num_rows === 0) {
        echo "<div class='container mt-5'><div class='alert alert-danger'>No payment record found for the given ID.</div></div>";
        include '../../../include/footer.html';
        exit();
    }

    $payment = $resultPayment->fetch_assoc();

    $stmtDetails = $conn->prepare($sqlDetails);
    $stmtDetails->bind_param("s", $payment_id);
    $stmtDetails->execute();
    $resultDetails = $stmtDetails->get_result();

    // Get license information
    $sqlLicense = "SELECT l.license_name, l.license_type 
                  FROM student_licenses sl
                  JOIN licenses l ON sl.license_id = l.license_id
                  WHERE sl.student_license_id = ?";
    $stmtLicense = $conn->prepare($sqlLicense);
    $stmtLicense->bind_param("s", $payment['student_license_id']);
    $stmtLicense->execute();
    $licenseResult = $stmtLicense->get_result();
    $licenseInfo = $licenseResult->fetch_assoc();
} catch (Exception $e) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>An error occurred: " . $e->getMessage() . "</div></div>";
    include '../../../include/footer.html';
    exit();
}

// Helper function to fetch item name based on type and ID
function fetchItemName($conn, $type, $id)
{
    $tableMap = [
        'License' => ['table' => 'licenses', 'id_column' => 'license_id', 'name_column' => 'license_name', 'extra' => 'license_type'],
        'Lesson' => ['table' => 'lessons', 'id_column' => 'lesson_id', 'name_column' => 'lesson_name'],
        'Test' => ['table' => 'tests', 'id_column' => 'test_id', 'name_column' => 'test_name']
    ];

    if (!isset($tableMap[$type])) return "Unknown";

    $table = $tableMap[$type]['table'];
    $idColumn = $tableMap[$type]['id_column'];
    $nameColumn = $tableMap[$type]['name_column'];
    $extra = $tableMap[$type]['extra'] ?? null;

    $query = "SELECT $nameColumn" . ($extra ? ", $extra" : "") . " FROM $table WHERE $idColumn = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        return $row[$nameColumn] . ($extra ? " (" . $row[$extra] . ")" : "");
    }
    return "Unknown";
}

// Group payment details by type for easier display
$itemsByType = ['License' => [], 'Lesson' => [], 'Test' => []];

// Fetch items and group them by type
while ($detail = $resultDetails->fetch_assoc()) {
    $itemName = fetchItemName($conn, $detail['item_type'], $detail['item_id']);
    $itemsByType[$detail['item_type']][] = ['name' => $itemName, 'amount' => $detail['amount']];
}
?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
        </div>

        <div class="page-category">
            <div class="row justify-content-center">
                <div class="col-md-8 col-lg-6">
                    <div class="card shadow-lg" id="receipt-card">
                        <div class="card-header bg-success text-white text-center">
                            <i class="fas fa-check-circle fa-3x my-3"></i>
                            <h3>License Registration Successful</h3>
                        </div>
                        <div class="card-body">
                            <div class="text-center mb-4">
                                <h5>Your license registration has been successfully processed.</h5>
                                <p class="text-muted">Payment Reference: <?php echo $payment_id; ?></p>
                            </div>

                            <div class="alert alert-info">
                                <i class="fas fa-info-circle"></i>
                                Your registration is complete. Good luck with your training! Check your dashboard for updates.
                            </div>

                            <div class="table-responsive mb-4">
                                <table class="table table-bordered">
                                    <tbody>
                                        <tr>
                                            <th>Student Name</th>
                                            <td><?php echo htmlspecialchars($payment['student_name']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>License</th>
                                            <td><?php echo htmlspecialchars($itemsByType['License'][0]['name'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Lesson Package</th>
                                            <td><?php echo htmlspecialchars($itemsByType['Lesson'][0]['name'] ?? 'N/A'); ?></td>
                                        </tr>
                                        <?php if (!empty($itemsByType['Test'])): ?>
                                            <tr>
                                                <th>Tests</th>
                                                <td>
                                                    <?php foreach ($itemsByType['Test'] as $test): ?>
                                                        <?php echo htmlspecialchars($test['name']); ?><br>
                                                    <?php endforeach; ?>
                                                </td>
                                            </tr>
                                        <?php endif; ?>
                                        <tr>
                                            <th>Total Amount</th>
                                            <td>RM <?php echo number_format($payment['total_amount'], 2); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Payment Method</th>
                                            <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                        </tr>
                                        <tr>
                                            <th>Date</th>
                                            <td><?php echo date('d M Y, h:i A', strtotime($payment['payment_datetime'])); ?></td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <div class="text-center mt-4">
                                <a href="../book_license/list_license.php" class="btn btn-primary mr-2">
                                    Ok
                                </a>
                                <button onclick="window.print()" class="btn btn-secondary">
                                    <i class="fas fa-print"></i> Print Receipt
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    @media print {

        .navbar,
        .sidebar,
        .footer,
        .breadcrumbs,
        .btn {
            display: none !important;
        }

        .card {
            border: none !important;
            box-shadow: none !important;
        }

        .card-header {
            color: #000 !important;
            background-color: #fff !important;
        }
    }
</style>

<?php
include '../../../include/footer.html';
?>