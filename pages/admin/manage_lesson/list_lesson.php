<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// UNASSIGNED LESSONS
$unassigned_query = "
    SELECT 
        sl.student_license_id,
        u.name AS student_name,
        l.license_name,
        COUNT(sls.student_lesson_id) AS total_lesson
    FROM student_licenses sl
    JOIN students s ON sl.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    JOIN licenses l ON sl.license_id = l.license_id
    LEFT JOIN student_lessons sls 
        ON sl.student_license_id = sls.student_license_id
        AND sls.status = 'Pending'
        AND sls.instructor_id IS NULL
        AND sls.date IS NULL
        AND sls.start_time IS NULL
        AND sls.end_time IS NULL
        AND sls.schedule_status = 'Unassigned'
    GROUP BY sl.student_license_id, u.name, l.license_name
    HAVING total_lesson > 0
";
$unassigned_result = $conn->query($unassigned_query);
$unassigned_count = $unassigned_result->num_rows;

// ASSIGNED LESSONS
$assigned_query = "
    SELECT 
        sl.student_license_id,
        u.name AS student_name,
        l.license_name,
        SUM(CASE 
            WHEN sls.status = 'Pending'
            AND sls.instructor_id IS NOT NULL
            AND sls.date IS NULL
            AND sls.start_time IS NULL
            AND sls.end_time IS NULL
            AND sls.schedule_status = 'Unassigned'
            THEN 1 ELSE 0 
        END) AS lesson_left,
        COUNT(sls.student_lesson_id) AS total_lesson
    FROM student_licenses sl
    JOIN students s ON sl.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    JOIN licenses l ON sl.license_id = l.license_id
    LEFT JOIN student_lessons sls 
        ON sl.student_license_id = sls.student_license_id
    GROUP BY sl.student_license_id, u.name, l.license_name
    HAVING lesson_left > 0
";
$assigned_result = $conn->query($assigned_query);
$assigned_count = $assigned_result->num_rows;

// UPCOMING LESSONS
$upcoming_query = "
    SELECT 
        sl.student_license_id, 
        u.name AS student_name, 
        l.license_name, 
        ins_user.name AS instructor_name,
        COUNT(*) AS lesson_left,
        (
            SELECT COUNT(*) 
            FROM student_lessons 
            WHERE student_license_id = sl.student_license_id
        ) AS total_lesson
    FROM student_licenses sl
    JOIN students s ON sl.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    JOIN licenses l ON sl.license_id = l.license_id
    JOIN student_lessons sls 
        ON sl.student_license_id = sls.student_license_id 
        AND sls.status = 'Pending'
        AND sls.instructor_id IS NOT NULL
        AND sls.date IS NOT NULL
        AND sls.start_time IS NOT NULL
        AND sls.end_time IS NOT NULL
        AND sls.schedule_status = 'Assigned'
    JOIN instructors ins ON sls.instructor_id = ins.instructor_id
    JOIN users ins_user ON ins.user_id = ins_user.user_id
    GROUP BY sl.student_license_id, u.name, l.license_name, ins_user.name
";
$upcoming_result = $conn->query($upcoming_query);
$upcoming_count = $upcoming_result->num_rows;

// PAST LESSONS
$past_query = "
    SELECT 
        sl.student_license_id, 
        u.name AS student_name, 
        l.license_name, 
        ins_user.name AS instructor_name,
        COUNT(*) AS lesson_done,
        (
            SELECT COUNT(*) 
            FROM student_lessons 
            WHERE student_license_id = sl.student_license_id
        ) AS total_lesson
    FROM student_licenses sl
    JOIN students s ON sl.student_id = s.student_id
    JOIN users u ON s.user_id = u.user_id
    JOIN licenses l ON sl.license_id = l.license_id
    JOIN student_lessons sls 
        ON sl.student_license_id = sls.student_license_id 
        AND sls.status = 'Completed'
        AND sls.instructor_id IS NOT NULL
        AND sls.date IS NOT NULL
        AND sls.start_time IS NOT NULL
        AND sls.end_time IS NOT NULL
        AND sls.schedule_status = 'Assigned'
    JOIN instructors ins ON sls.instructor_id = ins.instructor_id
    JOIN users ins_user ON ins.user_id = ins_user.user_id
    GROUP BY sl.student_license_id, u.name, l.license_name, ins_user.name
";
$past_result = $conn->query($past_query);
$past_count = $past_result->num_rows;

// Calculate total upcoming lessons
$total_upcoming_lessons = 0;
$upcoming_result->data_seek(0); // Reset pointer
while ($row = $upcoming_result->fetch_assoc()) {
  $total_upcoming_lessons += $row['lesson_left'];
}
$upcoming_result->data_seek(0); // Reset pointer for later use

// Calculate total assigned lessons
$total_assigned_lessons = 0;
$assigned_result->data_seek(0); // Reset pointer
while ($row = $assigned_result->fetch_assoc()) {
  $total_assigned_lessons += $row['lesson_left'];
}
$assigned_result->data_seek(0); // Reset pointer for later use

// Calculate total completed lessons
$total_completed_lessons = 0;
$past_result->data_seek(0); // Reset pointer
while ($row = $past_result->fetch_assoc()) {
  $total_completed_lessons += $row['lesson_done'];
}
$past_result->data_seek(0); // Reset pointer for later use
?>

<div class="container">
  <div class="page-inner">
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
          <a href="#">Lesson List</a>
        </li>
      </ul>
    </div>

    <div class="page-category">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title">Lesson List</h4>
            <!-- Add minimize button -->
            <button type="button" class="btn btn-sm btn-outline-secondary" id="lesson-toggle-btn">
              <i class="fas fa-minus"></i>
            </button>
          </div>
          <div class="card-body" id="lesson-card-body">
            <!-- Card Toggles -->
            <div class="row mb-4">
              <div class="col-md-3">
                <div class="card card-stats card-round toggle-card active" data-target="unassigned-table-container">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-5">
                        <div class="icon-big text-center">
                          <i class="fas fa-user-plus text-warning"></i>
                        </div>
                      </div>
                      <div class="col-7 col-stats">
                        <div class="numbers">
                          <p class="card-category">Unassigned</p>
                          <h4 class="card-title"><?php echo $unassigned_count; ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-3">
                <div class="card card-stats card-round toggle-card" data-target="assigned-table-container">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-5">
                        <div class="icon-big text-center">
                          <i class="fas fa-calendar-plus text-primary"></i>
                        </div>
                      </div>
                      <div class="col-7 col-stats">
                        <div class="numbers">
                          <p class="card-category">Assigned</p>
                          <h4 class="card-title"><?php echo $total_assigned_lessons; ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-3">
                <div class="card card-stats card-round toggle-card" data-target="upcoming-table-container">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-5">
                        <div class="icon-big text-center">
                          <i class="fas fa-calendar-alt text-success"></i>
                        </div>
                      </div>
                      <div class="col-7 col-stats">
                        <div class="numbers">
                          <p class="card-category">Upcoming</p>
                          <h4 class="card-title"><?php echo $total_upcoming_lessons; ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-3">
                <div class="card card-stats card-round toggle-card" data-target="past-table-container">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-5">
                        <div class="icon-big text-center">
                          <i class="fas fa-history text-info"></i>
                        </div>
                      </div>
                      <div class="col-7 col-stats">
                        <div class="numbers">
                          <p class="card-category">Completed</p>
                          <h4 class="card-title"><?php echo $total_completed_lessons; ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Table Containers -->
            <!-- Unassigned Lessons -->
            <div class="table-container" id="unassigned-table-container">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="fas fa-user-plus text-warning"></i> Unassigned Students</h4>
              </div>
              <div class="table-responsive">
                <table id="unassigned-table" class="table table-striped table-bordered">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Student Name</th>
                      <th>License</th>
                      <th>Total Lesson</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $counter = 1;
                    $unassigned_result->data_seek(0);
                    while ($row = $unassigned_result->fetch_assoc()) {
                      echo "<tr>
                            <td>{$counter}</td>
                            <td>" . htmlspecialchars($row['student_name']) . "</td>
                            <td>" . htmlspecialchars($row['license_name']) . "</td>
                            <td>" . $row['total_lesson'] . "</td>
                            <td class='action-buttons'>
                              <a href='assign_instructor.php?student_license_id=" . $row['student_license_id'] . "' class='btn btn-primary btn-sm'>
                                <i class='fas fa-user-check'></i> Assign Instructor
                              </a>
                            </td>
                          </tr>";
                      $counter++;
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Assigned Lessons -->
            <div class="table-container" id="assigned-table-container" style="display: none;">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="fas fa-calendar-plus text-primary"></i> Assigned Students (Unscheduled)</h4>
              </div>
              <div class="table-responsive">
                <table id="assigned-table" class="table table-striped table-bordered">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Student Name</th>
                      <th>License</th>
                      <th>Unscheduled Lesson</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $counter = 1;
                    $assigned_result->data_seek(0);
                    while ($row = $assigned_result->fetch_assoc()) {
                      echo "<tr>
                            <td>{$counter}</td>
                            <td>" . htmlspecialchars($row['student_name']) . "</td>
                            <td>" . htmlspecialchars($row['license_name']) . "</td>
                            <td>" . $row['lesson_left'] . " / " . $row['total_lesson'] . "</td>
                            <td class='action-buttons'>
                              <a href='schedule_lesson.php?student_license_id=" . $row['student_license_id'] . "' class='btn btn-success btn-sm'>
                                <i class='fas fa-calendar'></i> Schedule Lesson
                              </a>
                            </td>
                          </tr>";
                      $counter++;
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Upcoming Lessons -->
            <div class="table-container" id="upcoming-table-container" style="display: none;">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="fas fa-calendar-alt text-success"></i> Upcoming Lessons</h4>
              </div>
              <div class="table-responsive">
                <table id="upcoming-table" class="table table-striped table-bordered">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Student Name</th>
                      <th>License</th>
                      <th>Instructor Name</th>
                      <th>Upcoming Lesson</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $counter = 1;
                    $upcoming_result->data_seek(0);
                    while ($row = $upcoming_result->fetch_assoc()) {
                      echo "<tr>
                              <td>{$counter}</td>
                              <td>" . htmlspecialchars($row['student_name']) . "</td>
                              <td>" . htmlspecialchars($row['license_name']) . "</td>
                              <td>" . htmlspecialchars($row['instructor_name']) . "</td>
                              <td>" . $row['lesson_left'] . " / " . $row['total_lesson'] . "</td>
                              <td class='action-buttons'>
                                <a href='lesson_detail.php?student_license_id={$row['student_license_id']}&type=upcoming' class='btn btn-info btn-sm'>
                                  <i class='fas fa-eye'></i> View
                                </a>
                              </td>
                            </tr>";
                      $counter++;
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Past Lessons -->
            <div class="table-container" id="past-table-container" style="display: none;">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="fas fa-history text-info"></i> Completed Lessons</h4>
              </div>
              <div class="table-responsive">
                <table id="past-table" class="table table-striped table-bordered">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Student Name</th>
                      <th>License</th>
                      <th>Instructor Name</th>
                      <th>Past Lesson</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    $counter = 1;
                    $past_result->data_seek(0);
                    while ($row = $past_result->fetch_assoc()) {
                      echo "<tr>
                              <td>{$counter}</td>
                              <td>" . htmlspecialchars($row['student_name']) . "</td>
                              <td>" . htmlspecialchars($row['license_name']) . "</td>
                              <td>" . htmlspecialchars($row['instructor_name']) . "</td>
                              <td>" . $row['lesson_done'] . " / " . $row['total_lesson'] . "</td>
                              <td class='action-buttons'>
                                <a href='lesson_detail.php?student_license_id={$row['student_license_id']}&type=past' class='btn btn-info btn-sm'>
                                  <i class='fas fa-eye'></i> View
                                </a>
                              </td>
                            </tr>";
                      $counter++;
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
</div>

<?php include '../../../include/footer.html'; ?>

<script>
  $(document).ready(function() {
    $('#unassigned-table').DataTable();
  });

  $(document).ready(function() {
    $('#assigned-table').DataTable();
  });

  $(document).ready(function() {
    $('#upcoming-table').DataTable();
  });

  $(document).ready(function() {
    $('#past-table').DataTable();
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

  /* Button spacing */
  .mr-1 {
    margin-right: 0.25rem;
  }

  .mr-3 {
    margin-right: 1rem;
  }

  /* Card body transition handling */
  #lesson-card-body {
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