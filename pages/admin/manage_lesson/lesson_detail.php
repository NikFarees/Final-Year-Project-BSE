<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Check if student_license_id is provided
if (!isset($_GET['student_license_id']) || empty($_GET['student_license_id'])) {
  echo "<script>alert('Invalid request!'); window.location.href='list_lesson.php';</script>";
  exit();
}

$student_license_id = $_GET['student_license_id'];
$type = isset($_GET['type']) ? $_GET['type'] : 'upcoming'; // Default to upcoming if not specified

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
                  GROUP BY sl.student_license_id";

$stmt = $conn->prepare($license_query);
$stmt->bind_param("s", $student_license_id);
$stmt->execute();
$license_result = $stmt->get_result();

if ($license_result->num_rows === 0) {
  echo "<script>alert('No records found!'); window.location.href='list_lesson.php';</script>";
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
                 WHERE stl.student_license_id = ? ";

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
$stmt->bind_param("s", $student_license_id);
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
          <a href="/pages/admin/dashboard.php">
            <i class="icon-home"></i>
          </a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="list_lesson.php">Lesson List</a>
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
              <button type="button" class="btn btn-sm btn-outline-secondary" id="info-toggle-btn">
                <i class="fas fa-minus"></i>
              </button>
            </div>
            <div class="card-body" id="info-card-body">
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
                                          WHERE student_license_id = ? AND status = 'Completed'";

                      $stmt = $conn->prepare($attendance_query);
                      $stmt->bind_param("s", $student_license_id);
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
                        <span class="badge badge-purple mr-2" style="background-color: #007bff;">Pending: <?= $total_lessons - $total_recorded ?></span>
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
                <button type="button" class="btn btn-sm btn-outline-secondary" id="lessons-toggle-btn">
                  <i class="fas fa-minus"></i>
                </button>
              </div>
              <div class="card-body" id="lessons-card-body">
                <div class="table-responsive">
                  <table id="lessons-table" class="table table-striped table-bordered">
                    <thead>
                      <tr>
                        <th>#</th>
                        <th>Lesson</th>
                        <th>Date</th>
                        <th>Time</th>
                        <?= $type == 'past' ? '<th>Attendance</th>' : '' ?>
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
                      ?>
                        <tr>
                          <td><?= $counter ?></td>
                          <td><?= $row_name ?></td>
                          <td><?= $date_display ?></td>
                          <td><?= $time_display ?></td>
                          <?= $type == 'past' ? '<td>' . htmlspecialchars($row['attendance_status'] ?? 'Not Recorded') . '</td>' : '' ?>
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

          <!-- Active Lesson Details -->
          <?php
          if ($active_lesson):
            $lesson_name = htmlspecialchars($active_lesson['student_lesson_name'] ?? 'Lesson ' . $active_lesson_number);
            $instructor_name = htmlspecialchars($active_lesson['instructor_name'] ?? 'Not Assigned');
            $date_display = $active_lesson['date'] ? date('d M Y', strtotime($active_lesson['date'])) : 'Not Scheduled';
            $time_display = ($active_lesson['start_time'] && $active_lesson['end_time']) ?
              date('h:i A', strtotime($active_lesson['start_time'])) . ' - ' . date('h:i A', strtotime($active_lesson['end_time'])) :
              'Not Scheduled';
            $created_at = date('d M Y h:i A', strtotime($active_lesson['created_at']));
          ?>
          <div class="col-md-12">
            <div class="card">
              <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="card-title">Active Lesson Details</h4>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="active-toggle-btn">
                  <i class="fas fa-minus"></i>
                </button>
              </div>
              <div class="card-body" id="active-card-body">
                <div class="row">
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Lesson Name</label>
                      <input type="text" class="form-control" value="<?= $lesson_name ?>" readonly>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Instructor Name</label>
                      <input type="text" class="form-control" value="<?= $instructor_name ?>" readonly>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Date</label>
                      <input type="text" class="form-control" value="<?= $date_display ?>" readonly>
                    </div>
                  </div>
                  <div class="col-md-6">
                    <div class="form-group">
                      <label>Time</label>
                      <input type="text" class="form-control" value="<?= $time_display ?>" readonly>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<?php include '../../../include/footer.html'; ?>

<!-- JavaScript for actions -->
<script>
  $(document).ready(function() {
    $('#lessons-table').DataTable({
      "order": []
    });
    
    // Initialize tooltips
    $('[data-toggle="tooltip"]').tooltip();
    
    // Toggle info card content visibility
    $('#info-toggle-btn').click(function() {
      var cardBody = $('#info-card-body');
      cardBody.css('transition', 'none');
      cardBody.slideToggle(300);
      
      var icon = $(this).find('i');
      if (icon.hasClass('fa-minus')) {
        icon.removeClass('fa-minus').addClass('fa-plus');
      } else {
        icon.removeClass('fa-plus').addClass('fa-minus');
      }
    });
    
    // Toggle lessons card content visibility
    $('#lessons-toggle-btn').click(function() {
      var cardBody = $('#lessons-card-body');
      cardBody.css('transition', 'none');
      cardBody.slideToggle(300);
      
      var icon = $(this).find('i');
      if (icon.hasClass('fa-minus')) {
        icon.removeClass('fa-minus').addClass('fa-plus');
      } else {
        icon.removeClass('fa-plus').addClass('fa-minus');
      }
    });
    
    // Toggle active lesson card content visibility
    $('#active-toggle-btn').click(function() {
      var cardBody = $('#active-card-body');
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

  function markAttendance(lessonId, status) {
    // Here you would implement AJAX to update the attendance status
    alert("Marking lesson " + lessonId + " as " + status + ". This would be implemented with AJAX.");
  }

  function rescheduleLesson(lessonId) {
    // Here you would implement the rescheduling modal or redirect
    alert("Rescheduling lesson " + lessonId + ". This would open a modal or redirect to a scheduling page.");
  }
</script>

<style>
  /* Button spacing */
  .mr-1 {
    margin-right: 0.25rem;
  }
  
  .mr-3 {
    margin-right: 1rem;
  }
  
  /* Card body transition handling */
  #info-card-body,
  #lessons-card-body,
  #active-card-body {
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

</style>

