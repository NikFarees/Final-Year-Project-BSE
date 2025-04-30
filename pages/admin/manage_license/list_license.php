<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Fetch licenses from the database
$sql = "SELECT * FROM licenses";
$result = $conn->query($sql);
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

        <!-- Inner page content -->
        <div class="page-category">

            <!-- Card Structure with Table -->
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

        </div>

    </div>
</div>

<?php
include '../../../include/footer.html';
?>

<script>
    $(document).ready(function() {
        $("#basic-datatables").DataTable({});
    });
    
    $(document).ready(function() {
        // Toggle license card content visibility
        $('#license-toggle-btn').click(function() {
            var cardBody = $('#license-card-body');

            // Remove transition property to avoid conflicts
            cardBody.css('transition', 'none');

            // Use jQuery's slideToggle with a specified duration
            cardBody.slideToggle(300);

            // Toggle the icon
            var icon = $(this).find('i');
            if (icon.hasClass('fa-minus')) {
                icon.removeClass('fa-minus').addClass('fa-plus');
            } else {
                icon.removeClass('fa-plus').addClass('fa-minus');
            }
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
    #license-card-body {
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

</style>