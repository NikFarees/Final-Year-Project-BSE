<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Fetch instructor data
$instructor_query = "
    SELECT 
        i.instructor_id, 
        u.name 
    FROM 
        instructors i
    JOIN 
        users u ON i.user_id = u.user_id
";
$instructor_result = $conn->query($instructor_query);

// Fetch student data
$student_query = "
    SELECT 
        s.student_id, 
        u.name 
    FROM 
        students s
    JOIN 
        users u ON s.user_id = u.user_id
";
$student_result = $conn->query($student_query);
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Manage Schedule</h4>
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
          <a href="#">User List</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h4 class="card-title">Instructor & Student List</h4>
          </div>
          <div class="card-body">
            <ul class="nav nav-tabs nav-line nav-color-secondary" id="line-tab" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="line-instructors-tab" data-bs-toggle="pill" href="#line-instructors" role="tab" aria-controls="line-instructors" aria-selected="true">Instructor List</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="line-students-tab" data-bs-toggle="pill" href="#line-students" role="tab" aria-controls="line-students" aria-selected="false">Student List</a>
              </li>
            </ul>
            <div class="tab-content mt-3 mb-3" id="line-tabContent">

              <!-- Instructor List -->
              <div class="tab-pane fade show active" id="line-instructors" role="tabpanel" aria-labelledby="line-instructors-tab">
                <div class="card mt-4">
                  <div class="card-body">
                    <div class="table-responsive">
                      <table id="instructors-table" class="table table-striped table-bordered table-hover">
                        <thead>
                          <tr>
                            <th>Instructor ID</th>
                            <th>Name</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          if ($instructor_result->num_rows > 0) {
                            while ($instructor = $instructor_result->fetch_assoc()) {
                              echo "<tr class='clickable-row' data-href='view_schedule_instructor.php?id=" . htmlspecialchars($instructor['instructor_id']) . "'>";
                              echo "<td>" . htmlspecialchars($instructor['instructor_id']) . "</td>";
                              echo "<td>" . htmlspecialchars($instructor['name']) . "</td>";
                              echo "</tr>";
                            }
                          } else {
                            echo "<tr><td colspan='2' class='text-center'>No instructors found.</td></tr>";
                          }
                          ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Student List -->
              <div class="tab-pane fade" id="line-students" role="tabpanel" aria-labelledby="line-students-tab">
                <div class="card mt-4">
                  <div class="card-body">
                    <div class="table-responsive">
                      <table id="students-table" class="table table-striped table-bordered table-hover">
                        <thead>
                          <tr>
                            <th>Student ID</th>
                            <th>Name</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          if ($student_result->num_rows > 0) {
                            while ($student = $student_result->fetch_assoc()) {
                              echo "<tr class='clickable-row' data-href='view_schedule_student.php?id=" . htmlspecialchars($student['student_id']) . "'>";
                              echo "<td>" . htmlspecialchars($student['student_id']) . "</td>";
                              echo "<td>" . htmlspecialchars($student['name']) . "</td>";
                              echo "</tr>";
                            }
                          } else {
                            echo "<tr><td colspan='2' class='text-center'>No students found.</td></tr>";
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
    </div>

  </div>
</div>

<?php
include '../../../include/footer.html';
?>

<script>
  $(document).ready(function() {
    $("#instructors-table").DataTable({});
    $("#students-table").DataTable({});

    // Make entire row clickable
    $(".clickable-row").click(function() {
      window.location = $(this).data("href");
    });
  });
</script>

<style>
  .clickable-row {
    cursor: pointer;
  }
</style>