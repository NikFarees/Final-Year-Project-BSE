<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Fetch licenses and their corresponding specialties
$query = "
    SELECT 
        l.license_id, 
        l.license_name, 
        l.license_type, 
        COUNT(s.speciality_id) AS total_instructors
    FROM licenses l
    LEFT JOIN specialities s ON l.license_id = s.license_id
    GROUP BY l.license_id, l.license_name, l.license_type
";
$licenses_result = $conn->query($query);
$licenses = $licenses_result->fetch_all(MYSQLI_ASSOC);

// Track the active license ID for button functionality
$active_license_id = !empty($licenses) ? $licenses[0]['license_id'] : 0;
?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Manage Speciality</h4>
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
                    <a href="#">Speciality List</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div class="card-title">Speciality List</div>
                    <div class="d-flex align-items-center">
                        <div id="assign-button-container" class="mr-3">
                            <?php if($active_license_id): ?>
                            <a href="../manage_speciality/assign_speciality.php?license_id=<?php echo htmlspecialchars($active_license_id); ?>" class="btn btn-primary " id="assign-speciality-btn">Assign Speciality</a>
                            <?php endif; ?>
                        </div>
                        <!-- Add minimize button -->
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="speciality-toggle-btn">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body" id="speciality-card-body">
                    <div class="row mb-4">
                        <?php foreach ($licenses as $index => $license): ?>
                            <div class="col-md-6 mb-3">
                                <div class="card card-stats card-round toggle-card <?php echo $index === 0 ? 'active' : ''; ?>" 
                                     data-target="license-<?php echo $license['license_id']; ?>-container"
                                     data-license-id="<?php echo $license['license_id']; ?>">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-5">
                                                <div class="icon-big text-center">
                                                    <i class="fas fa-certificate <?php echo $index % 4 === 0 ? 'text-primary' : ($index % 4 === 1 ? 'text-success' : ($index % 4 === 2 ? 'text-warning' : 'text-danger')); ?>"></i>
                                                </div>
                                            </div>
                                            <div class="col-7 col-stats">
                                                <div class="numbers">
                                                    <p class="card-category"><?php echo htmlspecialchars($license['license_name']); ?></p>
                                                    <h4 class="card-title"><?php echo $license['total_instructors']; ?> Instructors</h4>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>

                    <?php foreach ($licenses as $index => $license): 
                        // Fetch instructors for the current license
                        $license_id = $license['license_id'];
                        $instructors_query = "
                            SELECT s.speciality_id, u.name AS instructor_name, s.instructor_id
                            FROM specialities s
                            JOIN instructors i ON s.instructor_id = i.instructor_id
                            JOIN users u ON i.user_id = u.user_id
                            WHERE s.license_id = '$license_id'
                        ";
                        $instructors_result = $conn->query($instructors_query);
                    ?>
                        <div class="table-container" id="license-<?php echo $license['license_id']; ?>-container" style="<?php echo $index === 0 ? '' : 'display: none;'; ?>">
                            <h4 class="mb-3">
                                <i class="fas fa-certificate <?php echo $index % 4 === 0 ? 'text-primary' : ($index % 4 === 1 ? 'text-success' : ($index % 4 === 2 ? 'text-warning' : 'text-danger')); ?>"></i> 
                                <?php echo htmlspecialchars($license['license_name']); ?> (<?php echo htmlspecialchars($license['license_type']); ?>)
                            </h4>
                            <div class="table-responsive">
                                <?php if ($instructors_result->num_rows > 0): ?>
                                    <table class="table table-striped datatable-table" id="datatable-license-<?php echo $license['license_id']; ?>">
                                        <thead>
                                            <tr>
                                                <th>#</th>
                                                <th>Instructor Name</th>
                                                <th>Actions</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                            $counter = 1;
                                            while ($speciality = $instructors_result->fetch_assoc()): ?>
                                                <tr>
                                                    <td><?php echo $counter++; ?></td>
                                                    <td><?php echo htmlspecialchars($speciality['instructor_name']); ?></td>
                                                    <td class="action-buttons">
                                                        <a href="delete_speciality.php?instructor_id=<?php echo htmlspecialchars($speciality['instructor_id']); ?>&license_id=<?php echo htmlspecialchars($license_id); ?>" class="btn btn-danger btn-sm">
                                                            <i class="fas fa-trash"></i> Delete
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <div class="alert alert-info">
                                        No instructors assigned to this specialty.
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
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
        // Initialize DataTables for each specialty table
        $('.datatable-table').each(function() {
            $(this).DataTable({});
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
            
            // Update the assign speciality button URL
            const licenseId = $(this).data('license-id');
            const buttonUrl = '../manage_speciality/assign_speciality.php?license_id=' + licenseId;
            $('#assign-speciality-btn').attr('href', buttonUrl);
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
        // Toggle speciality card content visibility
        $('#speciality-toggle-btn').click(function() {
            var cardBody = $('#speciality-card-body');

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
    
    /* Button spacing */
    .mr-1 {
        margin-right: 0.25rem;
    }
    
    .mr-3 {
        margin-right: 1rem;
    }
    
    /* Card body transition handling */
    #speciality-card-body {
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
    
    /* Action buttons styling */
    .action-buttons {
        white-space: nowrap;
    }
</style>