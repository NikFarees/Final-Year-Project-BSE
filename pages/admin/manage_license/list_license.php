<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Fetch licenses from the database
$sql = "SELECT * FROM licenses";
$result = $conn->query($sql);

// Update licenses to "Pending" status if all tests are passed
function updateEligibleLicenses($conn)
{
    $update_query = "
        UPDATE issued_licenses il
        JOIN student_licenses sl ON il.student_license_id = sl.student_license_id
        SET il.status = 'Pending'
        WHERE il.status = 'Not Available'
        AND EXISTS (
            -- Check if student passed TES01 (Computer Test)
            SELECT 1 FROM student_tests st1
            WHERE st1.student_license_id = sl.student_license_id
            AND st1.test_id = 'TES01'
            AND st1.status = 'Passed'
        )
        AND EXISTS (
            -- Check if student passed TES02 (QTI Test)
            SELECT 1 FROM student_tests st2
            WHERE st2.student_license_id = sl.student_license_id
            AND st2.test_id = 'TES02'
            AND st2.status = 'Passed'
        )
        AND EXISTS (
            -- Check if student passed TES03 (Circuit Test)
            SELECT 1 FROM student_tests st3
            WHERE st3.student_license_id = sl.student_license_id
            AND st3.test_id = 'TES03'
            AND st3.status = 'Passed'
        )
        AND EXISTS (
            -- Check if student passed TES04 (On-Road Test)
            SELECT 1 FROM student_tests st4
            WHERE st4.student_license_id = sl.student_license_id
            AND st4.test_id = 'TES04'
            AND st4.status = 'Passed'
        )
    ";

    $conn->query($update_query);
    return $conn->affected_rows;
}

// Auto-update eligible licenses
$updated_count = updateEligibleLicenses($conn);
if ($updated_count > 0) {
    $_SESSION['success_message'] = "$updated_count license(s) have been automatically moved to Pending status.";
}

// Fetch all issued licenses grouped by status
$not_available_query = "
    SELECT 
        il.issued_license_id,
        il.student_license_id,
        il.status,
        u.name AS student_name,
        u.ic AS student_ic,
        l.license_type,
        l.license_name
    FROM 
        issued_licenses AS il
    JOIN 
        student_licenses AS sl ON il.student_license_id = sl.student_license_id
    JOIN 
        students AS s ON sl.student_id = s.student_id
    JOIN 
        users AS u ON s.user_id = u.user_id
    JOIN 
        licenses AS l ON sl.license_id = l.license_id
    WHERE 
        il.status = 'Not Available'
";
$not_available_result = $conn->query($not_available_query);
$not_available_count = mysqli_num_rows($not_available_result);

$pending_query = "
    SELECT 
        il.issued_license_id,
        il.student_license_id,
        il.status,
        u.name AS student_name,
        u.ic AS student_ic,
        l.license_type,
        l.license_name
    FROM 
        issued_licenses AS il
    JOIN 
        student_licenses AS sl ON il.student_license_id = sl.student_license_id
    JOIN 
        students AS s ON sl.student_id = s.student_id
    JOIN 
        users AS u ON s.user_id = u.user_id
    JOIN 
        licenses AS l ON sl.license_id = l.license_id
    WHERE 
        il.status = 'Pending'
";
$pending_result = $conn->query($pending_query);
$pending_count = mysqli_num_rows($pending_result);

$approved_query = "
    SELECT 
        il.issued_license_id,
        il.student_license_id,
        il.status,
        u.name AS student_name,
        u.ic AS student_ic,
        l.license_type,
        l.license_name
    FROM 
        issued_licenses AS il
    JOIN 
        student_licenses AS sl ON il.student_license_id = sl.student_license_id
    JOIN 
        students AS s ON sl.student_id = s.student_id
    JOIN 
        users AS u ON s.user_id = u.user_id
    JOIN 
        licenses AS l ON sl.license_id = l.license_id
    WHERE 
        il.status = 'Approved'
";
$approved_result = $conn->query($approved_query);
$approved_count = mysqli_num_rows($approved_result);

$issued_query = "
    SELECT 
        il.issued_license_id,
        il.student_license_id,
        il.status,
        il.issued_date,
        il.issued_time,
        u.name AS student_name,
        u.ic AS student_ic,
        l.license_type,
        l.license_name
    FROM 
        issued_licenses AS il
    JOIN 
        student_licenses AS sl ON il.student_license_id = sl.student_license_id
    JOIN 
        students AS s ON sl.student_id = s.student_id
    JOIN 
        users AS u ON s.user_id = u.user_id
    JOIN 
        licenses AS l ON sl.license_id = l.license_id
    WHERE 
        il.status = 'Issued'
";
$issued_result = $conn->query($issued_query);
$issued_count = mysqli_num_rows($issued_result);
?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Manage License</h4>
            <ul class="breadcrumbs">
                <li class="nav-home">
                    <a href="/pages/admin/dashboard.php">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">License List</a>
                </li>
            </ul>
        </div>

        <!-- Success message if any -->
        <?php if (isset($_SESSION['success_message'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <i class="fas fa-check-circle mr-2"></i> <?php echo $_SESSION['success_message']; ?>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <?php unset($_SESSION['success_message']); ?>
        <?php endif; ?>

        <!-- Inner page content -->
        <div class="page-category">
            <!-- License List Card -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title">License List</h4>
                    <div class="d-flex align-items-center">
                        <a href="add_license.php" class="btn btn-primary mr-3">Add License</a>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="license-toggle-btn">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body" id="license-card-body">
                    <!-- Table Section -->
                    <div class="table-responsive">
                        <table id="basic-datatables" class="display table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>License Name</th>
                                    <th>License Type</th>
                                    <th>Description</th>
                                    <th>License Fee</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                if ($result->num_rows > 0) {
                                    $counter = 1; // Initialize counter
                                    while ($row = $result->fetch_assoc()) {
                                        // Determine badge class based on license type
                                        $badgeClass = "badge-primary"; // Default

                                        $licenseType = strtoupper($row['license_type']);
                                        switch ($licenseType) {
                                            case 'A':
                                                $badgeClass = "badge-danger";
                                                break;
                                            case 'B':
                                                $badgeClass = "badge-warning";
                                                break;
                                            case 'C':
                                                $badgeClass = "badge-success";
                                                break;
                                            case 'D':
                                                $badgeClass = "badge-info";
                                                break;
                                            case 'E':
                                                $badgeClass = "badge-secondary";
                                                break;
                                        }

                                        echo "<tr>";
                                        echo "<td>" . $counter++ . "</td>"; // Display counter instead of license_id
                                        echo "<td>" . htmlspecialchars($row['license_name']) . "</td>";
                                        echo "<td><span class='badge " . $badgeClass . "'>" . htmlspecialchars($row['license_type']) . "</span></td>";
                                        echo "<td>" . htmlspecialchars($row['description'] ?? "") . "</td>";
                                        echo "<td>" . htmlspecialchars($row['license_fee']) . "</td>";
                                        echo "<td class='action-buttons'>";
                                        echo "<a href='edit_license.php?id=" . htmlspecialchars($row['license_id']) . "' class='btn btn-info btn-sm mr-1'><i class='fas fa-edit'></i> Edit</a>";
                                        echo "<a href='delete_license.php?id=" . htmlspecialchars($row['license_id']) . "' class='btn btn-danger btn-sm'><i class='fas fa-trash'></i> Delete</a>";
                                        echo "</td>";
                                        echo "</tr>";
                                    }
                                } else {
                                    echo "<tr><td colspan='6'>No licenses found</td></tr>";
                                }
                                ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Issued Licenses Management -->
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Issued License Management</h4>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="issued-license-toggle-btn">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
                <div class="card-body" id="issued-license-card-body">

                    <!-- Toggle Cards -->
                    <div class="row mb-4">
                        <div class="col-md-3">
                            <div class="card card-stats card-round toggle-card active" data-target="not-available-container">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-times-circle text-danger"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Not Available</p>
                                                <h4 class="card-title"><?php echo $not_available_count; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card card-stats card-round toggle-card" data-target="pending-container">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-hourglass-half text-warning"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Pending</p>
                                                <h4 class="card-title"><?php echo $pending_count; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card card-stats card-round toggle-card" data-target="approved-container">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-check-circle text-success"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Approved</p>
                                                <h4 class="card-title"><?php echo $approved_count; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-3">
                            <div class="card card-stats card-round toggle-card" data-target="issued-container">
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-5">
                                            <div class="icon-big text-center">
                                                <i class="fas fa-id-card text-primary"></i>
                                            </div>
                                        </div>
                                        <div class="col-7 col-stats">
                                            <div class="numbers">
                                                <p class="card-category">Issued</p>
                                                <h4 class="card-title"><?php echo $issued_count; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Not Available Licenses -->
                    <div class="table-container" id="not-available-container">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0"><i class="fas fa-times-circle text-danger"></i> Not Available Licenses</h4>
                        </div>
                        <div class="table-responsive">
                            <table id="not-available-table" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student Name</th>
                                        <th>License Type</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($not_available_count > 0) {
                                        mysqli_data_seek($not_available_result, 0); // Reset pointer
                                        $counter = 1; // Initialize counter
                                        while ($row = $not_available_result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . $counter++ . "</td>"; // Counter column
                                            echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                                            echo "<td><span class='badge badge-primary'>" . htmlspecialchars($row['license_type']) . "</span></td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='7' class='text-center'>No licenses with 'Not Available' status found.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pending Licenses -->
                    <div class="table-container" id="pending-container" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0"><i class="fas fa-hourglass-half text-warning"></i> Pending Licenses</h4>
                        </div>
                        <div class="table-responsive">
                            <table id="pending-table" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student Name</th>
                                        <th>License Type</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($pending_count > 0) {
                                        mysqli_data_seek($pending_result, 0); // Reset pointer
                                        $counter = 1; // Initialize counter
                                        while ($row = $pending_result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . $counter++ . "</td>"; // Counter column
                                            echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                                            echo "<td><span class='badge badge-primary'>" . htmlspecialchars($row['license_type']) . "</span></td>";
                                            echo "<td>
                                                    <a href='approve_license.php?license_id=" . htmlspecialchars($row['issued_license_id']) . "' class='btn btn-sm btn-success'>
                                                        <i class='fas fa-check mr-1'></i> Approve
                                                    </a>
                                                </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='8' class='text-center'>No licenses with 'Pending' status found.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Approved Licenses -->
                    <div class="table-container" id="approved-container" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0"><i class="fas fa-check-circle text-success"></i> Approved Licenses</h4>
                        </div>
                        <div class="table-responsive">
                            <table id="approved-table" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student Name</th>
                                        <th>License Type</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($approved_count > 0) {
                                        mysqli_data_seek($approved_result, 0); // Reset pointer
                                        $counter = 1; // Initialize counter
                                        while ($row = $approved_result->fetch_assoc()) {
                                            echo "<tr>";
                                            echo "<td>" . $counter++ . "</td>"; // Counter column
                                            echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                                            echo "<td><span class='badge badge-primary'>" . htmlspecialchars($row['license_type']) . "</span></td>";
                                            echo "<td>
                                                    <a href='issued_license.php?id=" . htmlspecialchars($row['issued_license_id']) . "' class='btn btn-sm btn-primary'>
                                                        <i class='fas fa-handshake mr-1'></i> Mark as Collected
                                                    </a>
                                                </td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='8' class='text-center'>No licenses with 'Approved' status found.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Issued Licenses -->
                    <div class="table-container" id="issued-container" style="display: none;">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <h4 class="mb-0"><i class="fas fa-id-card text-primary"></i> Issued Licenses</h4>
                        </div>
                        <div class="table-responsive">
                            <table id="issued-table" class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Student Name</th>
                                        <th>License Type</th>
                                        <th>Issued Date</th>
                                        <th>Issued Time</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if ($issued_count > 0) {
                                        mysqli_data_seek($issued_result, 0); // Reset pointer
                                        $counter = 1; // Initialize counter
                                        while ($row = $issued_result->fetch_assoc()) {
                                            // Format issued date and time
                                            $issued_date = date('d M Y', strtotime($row['issued_date']));
                                            $issued_time = date('h:i A', strtotime($row['issued_time']));

                                            echo "<tr>";
                                            echo "<td>" . $counter++ . "</td>"; // Counter column
                                            echo "<td>" . htmlspecialchars($row['student_name']) . "</td>";
                                            echo "<td><span class='badge badge-primary'>" . htmlspecialchars($row['license_type']) . "</span></td>";
                                            echo "<td>" . $issued_date . "</td>";
                                            echo "<td>" . $issued_time . "</td>";
                                            echo "</tr>";
                                        }
                                    } else {
                                        echo "<tr><td colspan='10' class='text-center'>No licenses with 'Issued' status found.</td></tr>";
                                    }
                                    ?>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </div>



        </div>

    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
include '../../../include/footer.html';
?>

<script>
    $(document).ready(function() {
        $("#basic-datatables").DataTable({});
    });

    $(document).ready(function() {
        $("#not-available-table").DataTable({});
    });

    $(document).ready(function() {
        $("#pending-table").DataTable({});
    });

    $(document).ready(function() {
        $("#approved-table").DataTable({});
    });

    $(document).ready(function() {
        $("#issued-table").DataTable({});
    });

    $(document).ready(function() {
        // Toggle license card content visibility
        $('#license-toggle-btn').click(function() {
            var cardBody = $('#license-card-body');
            cardBody.css('transition', 'none');
            cardBody.slideToggle(300);

            var icon = $(this).find('i');
            if (icon.hasClass('fa-minus')) {
                icon.removeClass('fa-minus').addClass('fa-plus');
            } else {
                icon.removeClass('fa-plus').addClass('fa-minus');
            }
        });
    });

    $(document).ready(function() {
        // Toggle issued license card content visibility
        $('#issued-license-toggle-btn').click(function() {
            var cardBody = $('#issued-license-card-body');
            cardBody.css('transition', 'none');
            cardBody.slideToggle(300);

            var icon = $(this).find('i');
            if (icon.hasClass('fa-minus')) {
                icon.removeClass('fa-minus').addClass('fa-plus');
            } else {
                icon.removeClass('fa-plus').addClass('fa-minus');
            }
        });
    });

    $(document).ready(function() {
        // Add click event for the toggle cards
        $('.toggle-card').on('click', function() {
            // Remove active class from all cards
            $('.toggle-card').removeClass('active');

            // Add active class to clicked card
            $(this).addClass('active');

            // Hide all tables
            $('.table-container').hide();

            // Show the table corresponding to the clicked card
            $('#' + $(this).data('target')).show();
        });
    });

    $(document).ready(function() {
        // Add visual feedback when hovering over cards
        $('.toggle-card').hover(
            function() {
                if (!$(this).hasClass('active')) {
                    $(this).css('cursor', 'pointer');
                    $(this).addClass('shadow-sm');
                }
            },
            function() {
                $(this).removeClass('shadow-sm');
            }
        );
    });

    $(document).ready(function() {
        // Auto dismiss alerts after 5 seconds
        setTimeout(function() {
            $('.alert').alert('close');
        }, 5000);
    });

    $(document).ready(function() {
        // Add event listener for approve license buttons
        $('.approve-license-btn').on('click', function() {
            const licenseId = $(this).data('id');

            // First AJAX call to get redirect URL
            $.ajax({
                url: 'approve_license.php',
                type: 'POST',
                data: {
                    license_id: licenseId
                },
                success: function(response) {
                    try {
                        const data = JSON.parse(response);
                        if (data.success && data.redirect) {
                            // Redirect to the confirmation page
                            window.location.href = data.redirect;
                        } else {
                            Swal.fire(
                                'Error!',
                                'There was an issue processing your request.',
                                'error'
                            );
                        }
                    } catch (e) {
                        // If response isn't JSON, just reload
                        location.reload();
                    }
                },
                error: function() {
                    Swal.fire(
                        'Error!',
                        'There was a server error. Please try again later.',
                        'error'
                    );
                }
            });
        });
    });
</script>

<style>
    /* Badge styling */
    .badge {
        padding: 5px 10px;
        border-radius: 4px;
        font-weight: 600;
        font-size: 0.85em;
    }

    .badge-primary {
        background-color: #1572E8;
        color: white;
    }

    .badge-secondary {
        background-color: #6c757d;
        color: white;
    }

    .badge-success {
        background-color: #31ce36;
        color: white;
    }

    .badge-danger {
        background-color: #f25961;
        color: white;
    }

    .badge-warning {
        background-color: #ffad46;
        color: white;
    }

    .badge-info {
        background-color: #48abf7;
        color: white;
    }

    /* Button spacing */
    .mr-1 {
        margin-right: 0.25rem;
    }

    .mr-3 {
        margin-right: 1rem;
    }

    /* Card body transition handling */
    #license-card-body,
    #issued-license-card-body {
        transition: none;
    }

    /* Card header styling */
    .card-header {
        padding: 0.75rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .card-title {
        margin-bottom: 0;
    }

    /* Action buttons container */
    .action-buttons {
        white-space: nowrap;
    }

    .toggle-card {
        transition: all 0.3s ease;
        cursor: pointer;
    }

    .toggle-card.active {
        border-bottom: 3px solid #1572E8;
        box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    }

    .toggle-card:hover:not(.active) {
        transform: translateY(-5px);
    }

    .table-container {
        transition: all 0.3s ease;
    }
</style>