<?php
include '../../../include/in_header.php';
include '../../../database/db_connection.php';

// Check if student_license_id is provided
if (!isset($_GET['student_license_id']) || empty($_GET['student_license_id'])) {
  echo "<script>alert('Invalid request!'); window.location.href='list_lesson.php';</script>";
  exit();
}

$student_license_id = $_GET['student_license_id'];
$type = isset($_GET['type']) ? $_GET['type'] : 'upcoming'; // Default to upcoming if not specified

// Get instructor_id from session user
$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT instructor_id FROM instructors WHERE user_id = ?");
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$instructor = $result->fetch_assoc();

if (!$instructor) {
  echo "<script>alert('Instructor not found!'); window.location.href='list_lesson.php';</script>";
  exit();
}

$instructor_id = $instructor['instructor_id'];

// Fetch student license information
$license_query = "SELECT sl.*, s.student_id, u_student.name as student_name, l.license_name, 
                  les.lesson_name, les.lesson_fee, COUNT(stl.student_lesson_id) as total_lessons,
                  SUM(CASE WHEN stl.status = 'Completed' THEN 1 ELSE 0 END) as completed_lessons
                  FROM student_licenses sl
                  JOIN students s ON sl.student_id = s.student_id
                  JOIN users u_student ON s.user_id = u_student.user_id
                  JOIN licenses l ON sl.license_id = l.license_id
                  JOIN lessons les ON sl.lesson_id = les.lesson_id
                  LEFT JOIN student_lessons stl ON sl.student_license_id = stl.student_license_id
                  WHERE sl.student_license_id = ?
                  AND stl.instructor_id = ? 
                  GROUP BY sl.student_license_id";

$stmt = $conn->prepare($license_query);
$stmt->bind_param("ss", $student_license_id, $instructor_id);
$stmt->execute();
$license_result = $stmt->get_result();

if ($license_result->num_rows === 0) {
  echo "<script>alert('No records found or you do not have permission to view this record!'); window.location.href='list_lesson.php';</script>";
  exit();
}

$license_info = $license_result->fetch_assoc();
$total_lessons = $license_info['total_lessons'];
$completed_lessons = $license_info['completed_lessons'];
$remaining_lessons = $total_lessons - $completed_lessons;
$progress_percent = ($completed_lessons / $total_lessons) * 100;
$rounded_percent = round($progress_percent);

// Fetch lessons based on the type parameter
$lessons_query = "SELECT stl.*, i.instructor_id, u_instructor.name as instructor_name
                 FROM student_lessons stl
                 LEFT JOIN instructors i ON stl.instructor_id = i.instructor_id
                 LEFT JOIN users u_instructor ON i.user_id = u_instructor.user_id
                 WHERE stl.student_license_id = ? 
                 AND stl.instructor_id = ? ";

// Add filtering based on the type
if ($type == 'upcoming') {
  $lessons_query .= "AND stl.status = 'Pending' 
                      AND stl.schedule_status = 'Assigned'
                      ORDER BY stl.date ASC, stl.start_time ASC";
} else { // Past lessons
  $lessons_query .= "AND stl.status = 'Completed'
                      ORDER BY stl.date DESC, stl.start_time DESC";
}

$stmt = $conn->prepare($lessons_query);
$stmt->bind_param("ss", $student_license_id, $instructor_id);
$stmt->execute();
$lessons_result = $stmt->get_result();
$total_filtered_lessons = $lessons_result->num_rows;

// Check if a specific lesson number is requested
$lesson_number = isset($_GET['lesson']) ? intval($_GET['lesson']) : null;
$active_lesson = null;

// Store lessons by their sequential number
$lessons_by_number = [];
$lesson_counter = 1;

while ($row = $lessons_result->fetch_assoc()) {
  $lessons_by_number[$lesson_counter] = $row;

  // If this is the requested lesson or we haven't found a lesson yet, mark it as active
  if ($lesson_number === $lesson_counter || $active_lesson === null) {
    $active_lesson = $row;
    $active_lesson_number = $lesson_counter;
  }

  $lesson_counter++;
}
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Manage Lesson</h4>
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
          <a href="list_lesson.php">Lesson Overview</a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="#"><?= ucfirst($type) ?> Lesson Details</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      <div class="row">
        <!-- Student License Information Card -->
        <div class="col-md-12">
          <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
              <h4 class="card-title">Student & License Information</h4>
              <button type="button" class="btn btn-sm btn-outline-secondary" id="student-info-toggle-btn">
                <i class="fas fa-minus"></i>
              </button>
            </div>
            <div class="card-body" id="student-info-card-body">
              <div class="row">
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Student Name</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($license_info['student_name']) ?>" readonly>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>License Type</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($license_info['license_name']) ?>" readonly>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Lesson Name</label>
                    <input type="text" class="form-control" value="<?= htmlspecialchars($license_info['lesson_name']) ?>" readonly>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Instructor Name</label>
                    <?php
                    // Get instructor name from the first lesson if available
                    $instructor_display = 'Not Assigned';
                    if ($total_filtered_lessons > 0) {
                      $lessons_result->data_seek(0); // Reset pointer
                      $first_lesson = $lessons_result->fetch_assoc();
                      if (!empty($first_lesson['instructor_name'])) {
                        $instructor_display = htmlspecialchars($first_lesson['instructor_name']);
                      }
                      $lessons_result->data_seek(0); // Reset pointer again for later use
                    }
                    ?>
                    <input type="text" class="form-control" value="<?= $instructor_display ?>" readonly>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Progress</label>
                    <div class="progress-card">
                      <?php
                      // Calculate attendance statistics
                      $attendance_query = "SELECT 
                                            SUM(CASE WHEN attendance_status = 'Absent' THEN 1 ELSE 0 END) as absent_count,
                                            SUM(CASE WHEN attendance_status = 'Attend' THEN 1 ELSE 0 END) as attend_count
                                          FROM student_lessons 
                                          WHERE student_license_id = ? 
                                          AND instructor_id = ?
                                          AND status = 'Completed'";

                      $stmt = $conn->prepare($attendance_query);
                      $stmt->bind_param("ss", $student_license_id, $instructor_id);
                      $stmt->execute();
                      $attendance_result = $stmt->get_result();
                      $attendance_stats = $attendance_result->fetch_assoc();

                      $absent_count = $attendance_stats['absent_count'] ?? 0;
                      $attend_count = $attendance_stats['attend_count'] ?? 0;

                      $total_recorded = $absent_count + $attend_count;
                      $absent_percent = $total_recorded > 0 ? ($absent_count / $total_lessons) * 100 : 0;
                      $attend_percent = $total_recorded > 0 ? ($attend_count / $total_lessons) * 100 : 0;
                      $pending_percent = 100 - ($absent_percent + $attend_percent);
                      ?>

                      <div class="progress-status">
                        <span class="text-muted">Completed <?= $completed_lessons ?> of <?= $total_lessons ?> Lessons</span>
                        <span class="text-muted font-weight-bold"><?= $rounded_percent ?>%</span>
                      </div>
                      <div class="progress">
                        <?php if ($absent_percent > 0): ?>
                          <div class="progress-bar progress-bar-striped bg-danger" role="progressbar"
                            style="width: <?= $absent_percent ?>%"
                            aria-valuenow="<?= $absent_count ?>" aria-valuemin="0" aria-valuemax="<?= $total_lessons ?>"
                            data-toggle="tooltip" title="<?= $absent_count ?> Absent">
                          </div>
                        <?php endif; ?>

                        <?php if ($attend_percent > 0): ?>
                          <div class="progress-bar progress-bar-striped bg-success" role="progressbar"
                            style="width: <?= $attend_percent ?>%"
                            aria-valuenow="<?= $attend_count ?>" aria-valuemin="0" aria-valuemax="<?= $total_lessons ?>"
                            data-toggle="tooltip" title="<?= $attend_count ?> Attended">
                          </div>
                        <?php endif; ?>

                        <?php if ($pending_percent > 0): ?>
                          <div class="progress-bar progress-bar-striped bg-primary" role="progressbar"
                            style="width: <?= $pending_percent ?>%"
                            aria-valuenow="<?= $total_lessons - $total_recorded ?>" aria-valuemin="0" aria-valuemax="<?= $total_lessons ?>"
                            data-toggle="tooltip" title="<?= $total_lessons - $total_recorded ?> Pending"
                            style="background-color: #007bff;">
                          </div>
                        <?php endif; ?>
                      </div>
                      <div class="mt-2 small">
                        <span class="badge badge-danger mr-2">Absent: <?= $absent_count ?></span>
                        <span class="badge badge-success mr-2">Attended: <?= $attend_count ?></span>
                        <span class="badge badge-primary mr-2" style="background-color: #007bff;">Pending: <?= $total_lessons - $total_recorded ?></span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Lessons Table -->
        <?php if ($total_filtered_lessons > 0): ?>
          <div class="col-md-12">
            <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title"><?= ucfirst($type) ?> Lesson Details</h4>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="lesson-details-toggle-btn">
                  <i class="fas fa-minus"></i>
                </button>
              </div>
              <div class="card-body" id="lesson-details-card-body">
                <div class="table-responsive">
                  <table id="lessons-table" class="table table-striped table-bordered">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Lesson</th>
                        <th>Date</th>
                        <th>Time</th>
                        <?php if ($type == 'past'): ?>
                          <th>Attendance</th>
                        <?php endif; ?>
                      </tr>
                    </thead>
                    <tbody>
                      <?php
                      $counter = 1;
                      $lessons_result->data_seek(0); // Reset pointer
                      while ($row = $lessons_result->fetch_assoc()):
                        $row_name = htmlspecialchars($row['student_lesson_name'] ?? 'Lesson ' . $counter);
                        $date_display = $row['date'] ? date('d M Y', strtotime($row['date'])) : 'Not Scheduled';
                        $time_display = ($row['start_time'] && $row['end_time']) ?
                          date('h:i A', strtotime($row['start_time'])) . ' - ' . date('h:i A', strtotime($row['end_time'])) :
                          'Not Scheduled';
                        $attendance_status = $row['attendance_status'] ?? 'Not Recorded';
                      ?>
                        <tr>
                          <td><?= $counter ?></td>
                          <td><?= $row_name ?></td>
                          <td><?= $date_display ?></td>
                          <td><?= $time_display ?></td>
                          <?php if ($type == 'past'): ?>
                            <td>
                              <span class="badge <?= $attendance_status === 'Attend' ? 'badge-success' : 'badge-danger' ?>">
                                <?= htmlspecialchars($attendance_status) ?>
                              </span>
                            </td>
                          <?php endif; ?>
                        </tr>
                      <?php
                        $counter++;
                      endwhile;
                      ?>
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include '../../../include/footer.html'; ?>

<!-- JavaScript for DataTables -->
<script>
  $(document).ready(function() {
    $('#lessons-table').DataTable({
      "order": []
    });
  });

  $(document).ready(function() {
    // Enable tooltips
    $('[data-toggle="tooltip"]').tooltip();
  });

  $(document).ready(function() {
    // Toggle student info card content visibility
    $('#student-info-toggle-btn').click(function() {
      var cardBody = $('#student-info-card-body');

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

  $(document).ready(function() {
    // Toggle lesson details card content visibility
    $('#lesson-details-toggle-btn').click(function() {
      var cardBody = $('#lesson-details-card-body');

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
  .card-header {
    padding: 0.75rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  
  .card-title {
    margin-bottom: 0;
  }

  .card {
    margin-bottom: 20px;
    transition: box-shadow 0.3s ease;
  }
  
  /* Fix for header interaction issues */
  .navbar .nav-link, .navbar .dropdown-item {
    z-index: 1000;
    position: relative;
  }
  
  /* Smooth transitions for card bodies */
  .card-body {
    transition: none;
  }
  
  .progress-card {
    margin-top: 5px;
  }
  
  .progress-status {
    display: flex;
    justify-content: space-between;
    margin-bottom: 5px;
  }
</style>