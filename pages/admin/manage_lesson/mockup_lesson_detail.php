<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Check if student_license_id is provided
if (!isset($_GET['student_license_id']) || empty($_GET['student_license_id'])) {
    echo "<script>alert('Invalid request!'); window.location.href='dashboard.php';</script>";
    exit();
}

$student_license_id = $_GET['student_license_id'];

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
    echo "<script>alert('No records found!'); window.location.href='dashboard.php';</script>";
    exit();
}

$license_info = $license_result->fetch_assoc();
$total_lessons = $license_info['total_lessons'];
$completed_lessons = $license_info['completed_lessons'];
$remaining_lessons = $total_lessons - $completed_lessons;

// Fetch all lessons for this student license
$lessons_query = "SELECT stl.*, i.instructor_id, u_instructor.name as instructor_name,
                 CASE 
                    WHEN stl.status = 'Completed' THEN 'Past'
                    ELSE 'Upcoming'
                 END as lesson_type
                 FROM student_lessons stl
                 LEFT JOIN instructors i ON stl.instructor_id = i.instructor_id
                 LEFT JOIN users u_instructor ON i.user_id = u_instructor.user_id
                 WHERE stl.student_license_id = ?
                 ORDER BY stl.date ASC, stl.start_time ASC";

$stmt = $conn->prepare($lessons_query);
$stmt->bind_param("s", $student_license_id);
$stmt->execute();
$lessons_result = $stmt->get_result();

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
      <h4 class="page-title">Lesson Details</h4>
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
          <a href="#">Lessons</a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="#">Lesson Details</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      <div class="row">
        <!-- Student License Information Card -->
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="d-flex align-items-center">
                <h4 class="card-title">Student & License Information</h4>
              </div>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Student Name</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($license_info['student_name']); ?>" readonly>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>License Type</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($license_info['license_name']); ?>" readonly>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Lesson Name</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($license_info['lesson_name']); ?>" readonly>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Progress</label>
                    <div class="progress-card">
                      <div class="progress-status">
                        <span class="text-muted">Completed <?php echo $completed_lessons; ?> of <?php echo $total_lessons; ?> Lessons</span>
                        <span class="text-muted font-weight-bold"> <?php echo round(($completed_lessons/$total_lessons)*100); ?>%</span>
                      </div>
                      <div class="progress">
                        <div class="progress-bar progress-bar-striped bg-success" role="progressbar" style="width: <?php echo ($completed_lessons/$total_lessons)*100; ?>%" aria-valuenow="<?php echo $completed_lessons; ?>" aria-valuemin="0" aria-valuemax="<?php echo $total_lessons; ?>"></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Lessons Navigation -->
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <h4 class="card-title">Lessons</h4>
            </div>
            <div class="card-body">
              <ul class="nav nav-pills nav-secondary">
                <?php for ($i = 1; $i <= $total_lessons; $i++): ?>
                  <?php 
                    $lesson = isset($lessons_by_number[$i]) ? $lessons_by_number[$i] : null;
                    $lesson_status = $lesson ? $lesson['status'] : 'Ineligible';
                    $status_class = '';
                    
                    if ($lesson_status === 'Completed') {
                      $status_class = 'text-success';
                    } elseif ($lesson_status === 'Pending') {
                      $status_class = 'text-warning';
                    } else {
                      $status_class = 'text-muted';
                    }
                  ?>
                  <li class="nav-item">
                    <a class="nav-link <?php echo ($active_lesson_number === $i) ? 'active' : ''; ?>" 
                       href="lesson_detail.php?student_license_id=<?php echo $student_license_id; ?>&lesson=<?php echo $i; ?>">
                      <i class="fas fa-book-open <?php echo $status_class; ?>"></i> Lesson <?php echo $i; ?>
                    </a>
                  </li>
                <?php endfor; ?>
              </ul>
            </div>
          </div>
        </div>
        
        <!-- Active Lesson Details -->
        <?php if ($active_lesson): ?>
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <h4 class="card-title">
                Lesson <?php echo $active_lesson_number; ?> Details
                <?php if ($active_lesson['status'] === 'Completed'): ?>
                  <span class="badge badge-success">Completed</span>
                <?php elseif ($active_lesson['status'] === 'Pending'): ?>
                  <span class="badge badge-warning">Pending</span>
                <?php else: ?>
                  <span class="badge badge-secondary">Ineligible</span>
                <?php endif; ?>
              </h4>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Lesson Name</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($active_lesson['student_lesson_name'] ?? 'Lesson '.$active_lesson_number); ?>" readonly>
                  </div>
                </div>
                <div class="col-md-6">
                  <div class="form-group">
                    <label>Instructor</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($active_lesson['instructor_name'] ?? 'Not Assigned'); ?>" readonly>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Date</label>
                    <input type="text" class="form-control" value="<?php echo $active_lesson['date'] ? date('d/m/Y', strtotime($active_lesson['date'])) : 'Not Scheduled'; ?>" readonly>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Start Time</label>
                    <input type="text" class="form-control" value="<?php echo $active_lesson['start_time'] ? date('h:i A', strtotime($active_lesson['start_time'])) : 'Not Scheduled'; ?>" readonly>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>End Time</label>
                    <input type="text" class="form-control" value="<?php echo $active_lesson['end_time'] ? date('h:i A', strtotime($active_lesson['end_time'])) : 'Not Scheduled'; ?>" readonly>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Schedule Status</label>
                    <input type="text" class="form-control" value="<?php echo htmlspecialchars($active_lesson['schedule_status']); ?>" readonly>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Attendance</label>
                    <?php if ($active_lesson['status'] === 'Completed'): ?>
                      <input type="text" class="form-control" value="<?php echo htmlspecialchars($active_lesson['attendance_status']); ?>" readonly>
                    <?php else: ?>
                      <input type="text" class="form-control" value="Not Applicable" readonly>
                    <?php endif; ?>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="form-group">
                    <label>Created At</label>
                    <input type="text" class="form-control" value="<?php echo date('d/m/Y h:i A', strtotime($active_lesson['created_at'])); ?>" readonly>
                  </div>
                </div>
              </div>
              
              <?php if ($active_lesson['lesson_type'] === 'Upcoming' && $active_lesson['schedule_status'] === 'Assigned'): ?>
              <div class="row mt-3">
                <div class="col-md-12">
                  <button class="btn btn-success" onclick="markAttendance('<?php echo $active_lesson['student_lesson_id']; ?>', 'Attend')">
                    <i class="fas fa-check"></i> Mark as Attended
                  </button>
                  <button class="btn btn-danger" onclick="markAttendance('<?php echo $active_lesson['student_lesson_id']; ?>', 'Absent')">
                    <i class="fas fa-times"></i> Mark as Absent
                  </button>
                  <button class="btn btn-warning" onclick="rescheduleLesson('<?php echo $active_lesson['student_lesson_id']; ?>')">
                    <i class="fas fa-calendar-alt"></i> Reschedule
                  </button>
                </div>
              </div>
              <?php endif; ?>
              
              <?php if ($active_lesson['status'] === 'Ineligible' && $active_lesson_number <= ($completed_lessons + 1)): ?>
              <div class="row mt-3">
                <div class="col-md-12">
                  <button class="btn btn-primary" onclick="scheduleLesson('<?php echo $active_lesson['student_lesson_id']; ?>')">
                    <i class="fas fa-calendar-plus"></i> Schedule Lesson
                  </button>
                </div>
              </div>
              <?php endif; ?>
            </div>
          </div>
        </div>
        <?php else: ?>
        <div class="col-md-12">
          <div class="card">
            <div class="card-body">
              <div class="alert alert-info">
                No lesson information available. Please select a lesson from above.
              </div>
            </div>
          </div>
        </div>
        <?php endif; ?>
        
      </div>
    </div>
    
  </div>
</div>

<!-- JavaScript for actions -->
<script>
function markAttendance(lessonId, status) {
  // Here you would implement AJAX to update the attendance status
  alert("Marking lesson " + lessonId + " as " + status + ". This would be implemented with AJAX.");
}

function rescheduleLesson(lessonId) {
  // Here you would implement the rescheduling modal or redirect
  alert("Rescheduling lesson " + lessonId + ". This would open a modal or redirect to a scheduling page.");
}

function scheduleLesson(lessonId) {
  // Here you would implement the scheduling modal or redirect
  alert("Scheduling lesson " + lessonId + ". This would open a modal or redirect to a scheduling page.");
}
</script>

<?php
include '../../../include/footer.html';
?>