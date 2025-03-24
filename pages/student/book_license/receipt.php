<?php
include '../../../include/st_header.php';
include '../../../database/db_connection.php';

// Check if payment_id is passed
if (!isset($_GET['payment_id']) || empty($_GET['payment_id'])) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>Payment ID is missing.</div></div>";
    include '../include/footer.html';
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
        include '../include/footer.html';
        exit();
    }

    $payment = $resultPayment->fetch_assoc();

    $stmtDetails = $conn->prepare($sqlDetails);
    $stmtDetails->bind_param("s", $payment_id);
    $stmtDetails->execute();
    $resultDetails = $stmtDetails->get_result();
} catch (Exception $e) {
    echo "<div class='container mt-5'><div class='alert alert-danger'>An error occurred: " . $e->getMessage() . "</div></div>";
    include '../include/footer.html';
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
?>

<style>
    @media print {
        body * {
            visibility: hidden;
            /* Hide everything except the receipt */
        }

        #receipt-card,
        #receipt-card * {
            visibility: visible;
            /* Make the receipt visible for printing */
            font-size: 15px;
            /* Ensure consistent font size for print */
        }

        /* Prevent scaling */
        @page {
            size: auto;
            /* Let the content scale naturally */
            margin: 15mm;
            /* Adjust margins to ensure content doesn't get cut off */
        }
    }
</style>

<div class="container">
    <div class="page-inner">

        <!-- Inner page content -->
        <div class="page-category">

            <div class="row justify-content-center">
                <div class="col-12 col-md-8 col-lg-7">
                    <div class="card" id="receipt-card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <div>
                                <h3 class="m-0">Receipt</h3>
                                <p class="text-muted mb-0">Payment ID: <?php echo htmlspecialchars($payment['payment_id']); ?></p>
                            </div>
                            <button onclick="window.print()" class="btn btn-secondary btn-sm">
                                <i class="fa fa-print"></i> Print
                            </button>
                        </div>

                        <div class="card-body p-4"> <!-- Added p-4 class for padding -->
                            <h5 class="card-title">Payment Details</h5>
                            <div class="row d-flex justify-content-between">
                                <div class="col-8"><strong>Student Name:</strong></div>
                                <div class="col-4 text-right"><?php echo htmlspecialchars($payment['student_name']); ?></div>
                            </div>
                            <div class="row d-flex justify-content-between">
                                <div class="col-8"><strong>Payment Type:</strong></div>
                                <div class="col-4 text-right"><?php echo htmlspecialchars($payment['payment_type']); ?></div>
                            </div>
                            <div class="row d-flex justify-content-between">
                                <div class="col-8"><strong>Payment Method:</strong></div>
                                <div class="col-4 text-right"><?php echo htmlspecialchars($payment['payment_method']); ?></div>
                            </div>
                            <div class="row d-flex justify-content-between">
                                <div class="col-8"><strong>Total Amount:</strong></div>
                                <div class="col-4 text-right">RM <?php echo number_format($payment['total_amount'], 2); ?></div>
                            </div>
                            <div class="row d-flex justify-content-between">
                                <div class="col-8"><strong>Payment Date:</strong></div>
                                <div class="col-4 text-right"><?php echo htmlspecialchars($payment['payment_datetime']); ?></div>
                            </div>
                            <div class="row d-flex justify-content-between">
                                <div class="col-8"><strong>Status:</strong></div>
                                <div class="col-4 text-right"><?php echo htmlspecialchars($payment['payment_status']); ?></div>
                            </div>
                            <hr>

                            <h5 class="card-title">Payment Items</h5>

                            <?php
                            // Initialize arrays to hold items by type
                            $itemsByType = ['License' => [], 'Lesson' => [], 'Test' => []];

                            // Fetch items and group them by type
                            while ($detail = $resultDetails->fetch_assoc()) {
                                $itemName = fetchItemName($conn, $detail['item_type'], $detail['item_id']);
                                $itemsByType[$detail['item_type']][] = ['name' => $itemName, 'amount' => $detail['amount']];
                            }

                            // Display items grouped by type
                            foreach ($itemsByType as $type => $items) {
                                if (!empty($items)) {
                                    if ($type != 'Test') {
                                        // Display License and Lesson items with the item name beside the type and the amount right-aligned
                                        echo "<div class='row d-flex justify-content-between'>
                                            <div class='col-8'><strong>$type:</strong> {$items[0]['name']}</div>
                                            <div class='col-4 text-right'>RM " . number_format($items[0]['amount'], 2) . "</div>
                                        </div>";
                                    } else {
                                        // Display Test items with bullet points and the amount right-aligned
                                        echo "<div class='row d-flex justify-content-between'>
                                            <div class='col-8'><strong>$type:</strong></div>
                                            <div class='col-4 text-right'></div>
                                        </div>"; // Empty space for the 'Test:' label
                                        foreach ($items as $item) {
                                            echo "<div class='row d-flex justify-content-between'>
                                                <div class='col-8'>- {$item['name']}</div>
                                                <div class='col-4 text-right'>RM " . number_format($item['amount'], 2) . "</div>
                                            </div>";
                                        }
                                    }
                                }
                            }
                            ?>

                        </div>

                        <div class="card-footer text-center p-3"> <!-- Added p-3 class for padding -->
                            <a href="../book_license/list_license.php" class="btn btn-primary">OK</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
include '../../../include/footer.html';
?>