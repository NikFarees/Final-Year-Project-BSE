<?php
include '../../../include/in_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Get the current user's ID from the session
$user_id = $_SESSION['user_id'];

// Get the instructor_id from the user_id
$sql_instructor = "SELECT instructor_id FROM instructors WHERE user_id = ?";
$stmt_instructor = $conn->prepare($sql_instructor);
$stmt_instructor->bind_param("s", $user_id);
$stmt_instructor->execute();
$result_instructor = $stmt_instructor->get_result();

if ($result_instructor->num_rows > 0) {
    $instructor_row = $result_instructor->fetch_assoc();
    $instructor_id = $instructor_row['instructor_id'];
} else {
    // Handle the case where the user is not an instructor
    echo "Error: Unauthorized access. User is not an instructor.";
    exit;
}

// Get instructor specialties
$sql_specialties = "SELECT s.speciality_id, l.license_id, l.license_name, l.license_type 
                   FROM specialities s 
                   JOIN licenses l ON s.license_id = l.license_id 
                   WHERE s.instructor_id = ?";
$stmt_specialties = $conn->prepare($sql_specialties);
$stmt_specialties->bind_param("s", $instructor_id);
$stmt_specialties->execute();
$result_specialties = $stmt_specialties->get_result();
$specialties = [];
while ($row = $result_specialties->fetch_assoc()) {
    $specialties[] = $row;
}

// Query to get all students assigned to this instructor with their licenses
$sql = "SELECT DISTINCT s.student_id, u.name, u.email, u.phone, 
        GROUP_CONCAT(DISTINCT l.license_name SEPARATOR ', ') as licenses
        FROM student_lessons sl
        JOIN student_licenses sl2 ON sl.student_license_id = sl2.student_license_id
        JOIN students s ON sl2.student_id = s.student_id
        JOIN users u ON s.user_id = u.user_id
        JOIN licenses l ON sl2.license_id = l.license_id
        JOIN specialities sp ON l.license_id = sp.license_id
        WHERE sl.instructor_id = ? AND sp.instructor_id = ?
        GROUP BY s.student_id";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $instructor_id, $instructor_id);
$stmt->execute();
$result = $stmt->get_result();

// 1. Total Students
$sql_total_students = "SELECT COUNT(DISTINCT s.student_id) as total_students
                      FROM student_lessons sl
                      JOIN student_licenses sl2 ON sl.student_license_id = sl2.student_license_id
                      JOIN students s ON sl2.student_id = s.student_id
                      JOIN specialities sp ON sl2.license_id = sp.license_id
                      WHERE sl.instructor_id = ? AND sp.instructor_id = ?";

$stmt_total_students = $conn->prepare($sql_total_students);
$stmt_total_students->bind_param("ss", $instructor_id, $instructor_id);
$stmt_total_students->execute();
$result_total_students = $stmt_total_students->get_result();
$total_students = $result_total_students->fetch_assoc()['total_students'];

// 2. Total Active Licenses (students currently pursuing)
$sql_total_licenses = "SELECT COUNT(DISTINCT sl2.student_license_id) as total_licenses
                       FROM student_lessons sl
                       JOIN student_licenses sl2 ON sl.student_license_id = sl2.student_license_id
                       JOIN specialities sp ON sl2.license_id = sp.license_id
                       WHERE sl.instructor_id = ? AND sp.instructor_id = ?";

$stmt_total_licenses = $conn->prepare($sql_total_licenses);
$stmt_total_licenses->bind_param("ss", $instructor_id, $instructor_id);
$stmt_total_licenses->execute();
$result_total_licenses = $stmt_total_licenses->get_result();
$total_licenses = $result_total_licenses->fetch_assoc()['total_licenses'];

// 3. License Type Distribution
$sql_license_distribution = "SELECT l.license_type, COUNT(DISTINCT sl2.student_license_id) as count
                            FROM student_lessons sl
                            JOIN student_licenses sl2 ON sl.student_license_id = sl2.student_license_id
                            JOIN licenses l ON sl2.license_id = l.license_id
                            JOIN specialities sp ON l.license_id = sp.license_id
                            WHERE sl.instructor_id = ? AND sp.instructor_id = ?
                            GROUP BY l.license_type";

$stmt_license_distribution = $conn->prepare($sql_license_distribution);
$stmt_license_distribution->bind_param("ss", $instructor_id, $instructor_id);
$stmt_license_distribution->execute();
$result_license_distribution = $stmt_license_distribution->get_result();
$license_distribution = [];
while ($row = $result_license_distribution->fetch_assoc()) {
    $license_distribution[] = $row;
}

// 4. New Students This Month
$sql_new_students = "SELECT s.student_id, u.name, u.created_at
                     FROM student_lessons sl
                     JOIN student_licenses sl2 ON sl.student_license_id = sl2.student_license_id
                     JOIN students s ON sl2.student_id = s.student_id
                     JOIN users u ON s.user_id = u.user_id
                     JOIN specialities sp ON sl2.license_id = sp.license_id
                     WHERE sl.instructor_id = ? 
                       AND sp.instructor_id = ?
                       AND MONTH(u.created_at) = MONTH(CURRENT_DATE())
                       AND YEAR(u.created_at) = YEAR(CURRENT_DATE())
                     GROUP BY s.student_id, u.name, u.created_at
                     ORDER BY u.created_at DESC
                     LIMIT 3";

$stmt_new_students = $conn->prepare($sql_new_students);
$stmt_new_students->bind_param("ss", $instructor_id, $instructor_id);
$stmt_new_students->execute();
$result_new_students = $stmt_new_students->get_result();
$new_student_list = $result_new_students->fetch_all(MYSQLI_ASSOC);
$new_students_count = count($new_student_list);
?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Manage Students</h4>
            <ul class="breadcrumbs">
                <li class="nav-home">
                    <a href="/pages/instructor/dashboard.php">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Overview Student Information</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">

            <!-- Card Statistical Data -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">Statistical Data</h4>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="stats-toggle-btn">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
                <div class="card-body" id="stats-card-body">
                    <!-- Card Statistic Data - Updated Layout -->
                    <div class="row">
                        <!-- ROW 1, COL 1 - Total Students -->
                        <div class="col-md-6 mb-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-primary bubble-shadow-small">
                                                <i class="fas fa-users"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Total Students</p>
                                                <h4 class="card-title"><?php echo $total_students; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- ROW 1, COL 2 - Active Licenses -->
                        <div class="col-md-6 mb-3">
                            <div class="card card-stats card-round">
                                <div class="card-body">
                                    <div class="row align-items-center">
                                        <div class="col-icon">
                                            <div class="icon-big text-center icon-info bubble-shadow-small">
                                                <i class="far fa-id-card"></i>
                                            </div>
                                        </div>
                                        <div class="col col-stats ml-3 ml-sm-0">
                                            <div class="numbers">
                                                <p class="card-category">Active Licenses</p>
                                                <h4 class="card-title"><?php echo $total_licenses; ?></h4>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- ROW 2, COL 1 - New Students This Month -->
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-header">
                                    <h5 class="mb-0"><i class="fas fa-user-plus"></i> Top 3 New Students This Month</h5>
                                </div>
                                <div class="card-body">
                                    <?php if ($new_students_count > 0): ?>
                                        <table class="table table-sm">
                                            <thead>
                                                <tr>
                                                    <th>Student</th>
                                                    <th>Joined On</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($new_student_list as $student): ?>
                                                    <tr>
                                                        <td><?= htmlspecialchars($student['name']) ?></td>
                                                        <td>
                                                            <span class="badge badge-success">
                                                                <?= date('d M Y', strtotime($student['created_at'])) ?>
                                                            </span>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php else: ?>
                                        <p>No new students enrolled this month.</p>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- ROW 2, COL 2 - Teaching Specialties -->
                        <div class="col-md-6 mb-3">
                            <div class="card">
                                <div class="card-header">
                                    <h4 class="card-title">My Teaching Specialties</h4>
                                </div>
                                <div class="card-body d-flex align-items-center justify-content-center">
                                    <div style="height: 220px; width: 220px;">
                                        <canvas id="licenseDistributionChart"></canvas>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Card Student List -->
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title mb-0">Student List</h4>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="student-list-toggle-btn">
                    <i class="fas fa-minus"></i>
                </button>
            </div>
            <div class="card-body" id="student-list-card-body">
                <!-- Table Section -->
                <div class="table-responsive">
                    <table id="student-datatables" class="display table table-striped">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Licenses</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            if ($result->num_rows > 0) {
                                $counter = 1; // Initialize counter
                                while ($row = $result->fetch_assoc()) {
                                    echo "<tr>";
                                    echo "<td>" . $counter++ . "</td>";
                                    echo "<td>" . htmlspecialchars($row['name']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['email']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['phone']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['licenses']) . "</td>";
                                    echo "<td>";
                                    echo "<div class='btn-group'>";
                                    echo "<a href='view_student.php?id=" . htmlspecialchars($row['student_id']) . "' class='btn btn-primary btn-sm'>View</a>";
                                    echo "</div>";
                                    echo "</td>";
                                    echo "</tr>";
                                }
                            } else {
                                echo "<tr><td colspan='6'>No students assigned to you</td></tr>";
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@3.7.0/dist/chart.min.js"></script>
<script>
    $(document).ready(function() {
        // Initialize DataTable
        var table = $("#student-datatables").DataTable({});

        // License filter functionality
        $('.dropdown-item').on('click', function(e) {
            e.preventDefault();
            var filterValue = $(this).data('filter');

            if (filterValue === 'all') {
                table.column(4).search('').draw();
            } else {
                table.column(4).search(filterValue).draw();
            }

            $('#licenseFilterDropdown').text('Filter: ' + $(this).text());
        });

        // License Distribution Chart
        var ctx = document.getElementById('licenseDistributionChart').getContext('2d');
        var licenseDistribution = new Chart(ctx, {
            type: 'pie',
            data: {
                labels: [
                    <?php
                    foreach ($license_distribution as $dist) {
                        echo "'" . $dist['license_type'] . "',";
                    }
                    ?>
                ],
                datasets: [{
                    data: [
                        <?php
                        foreach ($license_distribution as $dist) {
                            echo $dist['count'] . ",";
                        }
                        ?>
                    ],
                    backgroundColor: [
                        '#36a2eb',
                        '#ff6384',
                        '#4bc0c0',
                        '#ffcd56',
                        '#9966ff'
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                layout: {
                    padding: 10
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 10,
                            font: {
                                size: 10
                            }
                        }
                    },
                    title: {
                        display: true,
                        text: 'License Type Distribution',
                        font: {
                            size: 12
                        }
                    }
                }
            }
        });

        // Toggle stats card content visibility
        $('#stats-toggle-btn').click(function() {
            var statsContent = $('#stats-card-body');

            // Remove transition property entirely
            statsContent.css('transition', 'none');

            // Use jQuery's slideToggle with a specified duration
            statsContent.slideToggle(300);

            // Toggle the icon
            var icon = $(this).find('i');
            if (icon.hasClass('fa-minus')) {
                icon.removeClass('fa-minus').addClass('fa-plus');
            } else {
                icon.removeClass('fa-plus').addClass('fa-minus');
            }
        });

        // Toggle student list card content visibility
        $('#student-list-toggle-btn').click(function() {
            var cardBody = $('#student-list-card-body');

            // Remove transition property entirely
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
    /* Add styles to match the first code */
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

    .card-header h5 {
        font-size: 1rem;
        font-weight: 600;
    }

    .table-sm td,
    .table-sm th {
        padding: 0.5rem;
        font-size: 0.9rem;
    }

    #stats-card-body,
    #student-list-card-body {
        transition: none;
    }

    
</style>

<script>
    // Fix for header navigation issues
    $(document).ready(function() {
        // Ensure header links work by refreshing their click handlers
        $('.navbar .nav-link, .navbar .dropdown-item').off('click').on('click', function(e) {
            const href = $(this).attr('href');
            if (href && href !== '#') {
                window.location.href = href;
            }
        });
        
        // Specifically fix logout button if it has a form submission
        $('#logoutButton, .logout-btn').off('click').on('click', function(e) {
            e.preventDefault();
            const $form = $(this).closest('form');
            if ($form.length) {
                $form.submit();
            } else {
                // If no form, try to navigate to logout URL
                window.location.href = '/logout.php'; // Adjust this path if needed
            }
        });
    });
</script>