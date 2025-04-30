<?php
include '../../../include/st_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Fetch student ID from the `students` table using the `user_id` from the session
$user_id = $_SESSION['user_id'];

// Fetch student ID
$student_sql = "SELECT student_id FROM students WHERE user_id = ?";
$student_stmt = $conn->prepare($student_sql);
$student_stmt->bind_param("s", $user_id);
$student_stmt->execute();
$student_result = $student_stmt->get_result();

if ($student_result->num_rows === 0) {
    echo "<div class='alert alert-danger text-center'>Student not found. <a href='../index.php' class='btn btn-primary btn-round mt-3'>Back to Dashboard</a></div>";
    exit;
}

$student = $student_result->fetch_assoc();
$student_id = $student['student_id'];

// Fetch licenses from the `licenses` table
$sql = "SELECT * FROM licenses";
$result = $conn->query($sql);
?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Book License</h4>
            <ul class="breadcrumbs">
                <li class="nav-home">
                    <a href="/pages/student/dashboard.php">
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
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="card-title mb-0">License List</h4>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="license-toggle-btn">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>
                <div class="card-body" id="license-card-body">
                    <div class="row">
                        <?php if ($result->num_rows > 0): ?>
                            <?php while ($row = $result->fetch_assoc()): ?>
                                <?php
                                // Check if the student has already booked this license
                                $license_id = $row['license_id'];
                                $check_sql = "SELECT * FROM student_licenses WHERE student_id = ? AND license_id = ?";
                                $check_stmt = $conn->prepare($check_sql);
                                $check_stmt->bind_param("ss", $student_id, $license_id);
                                $check_stmt->execute();
                                $check_result = $check_stmt->get_result();
                                $already_booked = $check_result->num_rows > 0;

                                // Determine the image to use based on license type
                                $image_src = ($row['license_type'] == 'D') ? '/assets/img/car_d.png' : '/assets/img/car_da.png';
                                ?>
                                <div class="col-md-6 mb-3">
                                    <div class="card card-post card-round shadow-sm">
                                        <img class="card-img-top d-block mx-auto" src="<?php echo $image_src; ?>" alt="Card image cap" style="width: 300px; height: 300px;" />
                                        <div class="card-body">
                                            <h3 class="card-title"><?php echo htmlspecialchars($row['license_name']); ?></h3>
                                            <p class="card-text"><strong>License Fee:</strong> RM <?php echo number_format($row['license_fee'], 2); ?></p>
                                            <p class="card-text"><?php echo htmlspecialchars($row['description']); ?></p>

                                            <div class="text-center mt-3">
                                                <?php if ($already_booked): ?>
                                                    <button class="btn btn-sm btn-primary" disabled>Already Booked</button>
                                                <?php else: ?>
                                                    <a href="book_license.php?id=<?php echo urlencode($row['license_id']); ?>" class="btn btn-sm btn-primary">Book Now</a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <div class="col-12">
                                <p class="text-center">No licenses found.</p>
                            </div>
                        <?php endif; ?>
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
    /* Styles for toggle functionality */
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

    /* Fix for clickability issues with header elements */
    .navbar .nav-link, .navbar .dropdown-item {
        z-index: 1000;
        position: relative;
    }
    
    #license-card-body {
        transition: none;
    }
</style>