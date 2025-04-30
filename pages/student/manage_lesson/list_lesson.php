<?php
include '../../../include/st_header.php';
include '../../../database/db_connection.php';

$user_id = $_SESSION['user_id'];

// Step 1: Get student_id from the current user
$stmt = $conn->prepare("SELECT student_id FROM students WHERE user_id = ?");
if (!$stmt) {
    die("Prepare failed: " . $conn->error);
}
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$student = $result->fetch_assoc();

if (!$student) {
    $error_message = "Student record not found.";
} else {
    $student_id = $student['student_id'];

    // Set the correct timezone
    date_default_timezone_set('Asia/Kuala_Lumpur');
    $today = date('Y-m-d');
    $current_time = date('H:i:s');
    $current_datetime = date('Y-m-d H:i:s');

    // Auto-update lesson status from Pending to Completed for lessons that have ended
    $update_stmt = $conn->prepare("
        UPDATE student_lessons 
        SET status = 'Completed' 
        WHERE status = 'Pending' 
        AND date < ? 
        OR (date = ? AND end_time < ?)
        AND EXISTS (
            SELECT 1 
            FROM student_licenses 
            WHERE student_licenses.student_license_id = student_lessons.student_license_id 
            AND student_licenses.student_id = ?
        )
    ");
    $update_stmt->bind_param("ssss", $today, $today, $current_time, $student_id);
    $update_stmt->execute();
    $updated_rows = $update_stmt->affected_rows;

    // Step 2: Get all student lessons based on student_id
    $stmt = $conn->prepare("
        SELECT 
            sl.student_lesson_id, 
            sl.student_lesson_name, 
            sl.date, 
            sl.start_time, 
            sl.end_time, 
            sl.status, 
            sl.schedule_status,
            sl.instructor_id,
            l.lesson_name, 
            lic.license_name,
            u.name AS instructor_name
        FROM student_lessons sl
        INNER JOIN student_licenses slc ON sl.student_license_id = slc.student_license_id
        INNER JOIN lessons l ON slc.lesson_id = l.lesson_id
        INNER JOIN licenses lic ON slc.license_id = lic.license_id
        LEFT JOIN instructors i ON sl.instructor_id = i.instructor_id
        LEFT JOIN users u ON i.user_id = u.user_id
        WHERE slc.student_id = ?
        ORDER BY sl.date ASC, sl.start_time ASC, sl.student_lesson_name ASC
    ");
    $stmt->bind_param("s", $student_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $all_lessons = $result->fetch_all(MYSQLI_ASSOC);

    // Filter for today's lessons based on the specified conditions
    $today_lessons = array_filter($all_lessons, function ($lesson) use ($today) {
        return !empty($lesson['instructor_id']) && 
               $lesson['date'] == $today && 
               !empty($lesson['start_time']) && 
               !empty($lesson['end_time']) && 
               $lesson['status'] === 'Pending' && 
               $lesson['schedule_status'] === 'Assigned';
    });

    // Filter for upcoming lessons based on the specified conditions
    $upcoming_lessons = array_filter($all_lessons, function ($lesson) use ($today) {
        return !empty($lesson['instructor_id']) && 
               !empty($lesson['date']) && 
               $lesson['date'] > $today && 
               !empty($lesson['start_time']) && 
               !empty($lesson['end_time']) && 
               $lesson['status'] === 'Pending' && 
               $lesson['schedule_status'] === 'Assigned';
    });

    // Filter for past lessons based on the specified conditions
    $past_lessons = array_filter($all_lessons, function ($lesson) use ($today) {
        return !empty($lesson['instructor_id']) && 
               !empty($lesson['date']) && 
               ($lesson['date'] < $today || 
                ($lesson['date'] == $today && $lesson['end_time'] < date('H:i:s'))) && 
               !empty($lesson['start_time']) && 
               !empty($lesson['end_time']) && 
               $lesson['status'] === 'Completed' && 
               $lesson['schedule_status'] === 'Assigned';
    });
}
?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">My Lesson</h4>
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
                    <a href="#">Lesson List</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">
            <?php if (isset($error_message)): ?>
                <div class="alert alert-danger">
                    <?php echo $error_message; ?>
                </div>
            <?php else: ?>
                <?php if (isset($updated_rows) && $updated_rows > 0): ?>
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <?php echo $updated_rows; ?> lesson(s) have been automatically marked as completed.
                    <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <?php endif; ?>

                <!-- Schedule Overview Card -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <div class="card-title">Lesson List</div>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="lesson-toggle-btn">
                                    <i class="fas fa-minus"></i>
                                </button>
                            </div>
                            <div class="card-body" id="lesson-card-body">
                                <div class="row mb-4">

                                    <div class="col-md-4">
                                        <div class="card card-stats card-round toggle-card active" data-target="today-table">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-5">
                                                        <div class="icon-big text-center">
                                                            <i class="fas fa-calendar-alt text-warning"></i>
                                                        </div>
                                                    </div>
                                                    <div class="col-7 col-stats">
                                                        <div class="numbers">
                                                            <p class="card-category">Today</p>
                                                            <h4 class="card-title"><?php echo count($today_lessons); ?></h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="card card-stats card-round toggle-card" data-target="upcoming-table">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-5">
                                                        <div class="icon-big text-center">
                                                            <i class="fas fa-clock text-primary"></i>
                                                        </div>
                                                    </div>
                                                    <div class="col-7 col-stats">
                                                        <div class="numbers">
                                                            <p class="card-category">Upcoming</p>
                                                            <h4 class="card-title"><?php echo count($upcoming_lessons); ?></h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="col-md-4">
                                        <div class="card card-stats card-round toggle-card" data-target="past-table">
                                            <div class="card-body">
                                                <div class="row">
                                                    <div class="col-5">
                                                        <div class="icon-big text-center">
                                                            <i class="fas fa-calendar-check text-success"></i>
                                                        </div>
                                                    </div>
                                                    <div class="col-7 col-stats">
                                                        <div class="numbers">
                                                            <p class="card-category">Completed</p>
                                                            <h4 class="card-title"><?php echo count($past_lessons); ?></h4>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Today's Lessons Table -->
                                <div class="table-container" id="today-table">
                                    <h4 class="mt-4 mb-3"><i class="fas fa-calendar-alt text-warning"></i> Today's Lessons (<?php echo date('d M Y'); ?>)</h4>
                                    <div class="table-responsive">
                                        <table id="today-lessons-table" class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Lesson</th>
                                                    <th>License</th>
                                                    <th>Time</th>
                                                    <th>Instructor</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($today_lessons) > 0): ?>
                                                    <?php $counter = 1; ?>
                                                    <?php foreach ($today_lessons as $lesson): ?>
                                                        <tr>
                                                            <td><?php echo $counter++; ?></td>
                                                            <td><?php echo htmlspecialchars($lesson['student_lesson_name'] ?? ''); ?></td>
                                                            <td><?php echo htmlspecialchars($lesson['license_name'] ?? ''); ?></td>
                                                            <td><?php echo !empty($lesson['start_time']) && !empty($lesson['end_time']) ? date('h:i A', strtotime($lesson['start_time'])) . ' - ' . date('h:i A', strtotime($lesson['end_time'])) : ''; ?></td>
                                                            <td><?php echo htmlspecialchars($lesson['instructor_name'] ?? ''); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="5" class="text-center">No lessons scheduled for today.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Upcoming Lessons Table -->
                                <div class="table-container" id="upcoming-table" style="display: none;">
                                    <h4 class="mt-4 mb-3"><i class="fas fa-clock text-primary"></i> Upcoming Lessons</h4>
                                    <div class="table-responsive">
                                        <table id="upcoming-lessons-table" class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Lesson</th>
                                                    <th>License</th>
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Instructor</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($upcoming_lessons) > 0): ?>
                                                    <?php $counter = 1; ?>
                                                    <?php foreach ($upcoming_lessons as $lesson): ?>
                                                        <tr>
                                                            <td><?php echo $counter++; ?></td>
                                                            <td><?php echo htmlspecialchars($lesson['student_lesson_name'] ?? ''); ?></td>
                                                            <td><?php echo htmlspecialchars($lesson['license_name'] ?? ''); ?></td>
                                                            <td><?php echo !empty($lesson['date']) ? date('d M Y', strtotime($lesson['date'])) : ''; ?></td>
                                                            <td><?php echo !empty($lesson['start_time']) && !empty($lesson['end_time']) ? date('h:i A', strtotime($lesson['start_time'])) . ' - ' . date('h:i A', strtotime($lesson['end_time'])) : ''; ?></td>
                                                            <td><?php echo htmlspecialchars($lesson['instructor_name'] ?? ''); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center">No upcoming lessons found.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>

                                <!-- Past Lessons Table -->
                                <div class="table-container" id="past-table" style="display: none;">
                                    <h4 class="mt-4 mb-3"><i class="fas fa-calendar-check text-success"></i> Completed Lessons</h4>
                                    <div class="table-responsive">
                                        <table id="past-lessons-table" class="table table-striped">
                                            <thead>
                                                <tr>
                                                    <th>#</th>
                                                    <th>Lesson</th>
                                                    <th>License</th>
                                                    <th>Date</th>
                                                    <th>Time</th>
                                                    <th>Instructor</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php if (count($past_lessons) > 0): ?>
                                                    <?php $counter = 1; ?>
                                                    <?php foreach ($past_lessons as $lesson): ?>
                                                        <tr>
                                                            <td><?php echo $counter++; ?></td>
                                                            <td><?php echo htmlspecialchars($lesson['student_lesson_name'] ?? ''); ?></td>
                                                            <td><?php echo htmlspecialchars($lesson['license_name'] ?? ''); ?></td>
                                                            <td><?php echo !empty($lesson['date']) ? date('d M Y', strtotime($lesson['date'])) : ''; ?></td>
                                                            <td><?php echo !empty($lesson['start_time']) && !empty($lesson['end_time']) ? date('h:i A', strtotime($lesson['start_time'])) . ' - ' . date('h:i A', strtotime($lesson['end_time'])) : ''; ?></td>
                                                            <td><?php echo htmlspecialchars($lesson['instructor_name'] ?? ''); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                <?php else: ?>
                                                    <tr>
                                                        <td colspan="6" class="text-center">No completed lessons found.</td>
                                                    </tr>
                                                <?php endif; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
include '../../../include/footer.html';
?>

<script>
    $(document).ready(function() {
        $('#today-lessons-table').DataTable();
    });

    $(document).ready(function() {
        $('#upcoming-lessons-table').DataTable();
    });

    $(document).ready(function() {
        $('#past-lessons-table').DataTable();
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
        // Add some visual feedback when hovering over cards
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
        // Auto-dismiss alert after 5 seconds
        setTimeout(function() {
            $('.alert-dismissible').alert('close');
        }, 5000);
        
        // Toggle lesson card content visibility
        $('#lesson-toggle-btn').click(function() {
            var cardBody = $('#lesson-card-body');

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

    #lesson-card-body {
        transition: none;
    }

    .card-header {
        padding: 0.75rem 1.25rem;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .card-title {
        margin-bottom: 0;
    }

    /* Fix for header interaction issues */
    .navbar .nav-link,
    .navbar .dropdown-item {
        z-index: 1000;
        position: relative;
    }
</style>