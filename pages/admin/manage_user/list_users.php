<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Fetch instructor data
$instructor_query = "
    SELECT 
        i.instructor_id, 
        u.name, 
        GROUP_CONCAT(CONCAT(l.license_name, ' (', l.license_type, ')') SEPARATOR ', ') AS specialties
    FROM 
        instructors i
    JOIN 
        users u ON i.user_id = u.user_id
    LEFT JOIN 
        specialities s ON i.instructor_id = s.instructor_id
    LEFT JOIN 
        licenses l ON s.license_id = l.license_id
    GROUP BY 
        i.instructor_id, u.name
";
$instructor_result = $conn->query($instructor_query);

// Fetch student data
$student_query = "
    SELECT 
        s.student_id, 
        u.name, 
        GROUP_CONCAT(CONCAT(l.license_name, ' (', l.license_type, ')') SEPARATOR ', ') AS licenses_enrolled
    FROM 
        students s
    JOIN 
        users u ON s.user_id = u.user_id
    LEFT JOIN 
        student_licenses sl ON s.student_id = sl.student_id
    LEFT JOIN 
        licenses l ON sl.license_id = l.license_id
    GROUP BY 
        s.student_id, u.name
";
$student_result = $conn->query($student_query);
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Manage User</h4>
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
          <div class="card-header d-flex justify-content-between align-items-center">
            <h4 class="card-title">User List</h4>
            <div id="dynamic-buttons"></div>
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
                            <th>#</th>
                            <th>Name</th>
                            <th>Specialties</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          if ($instructor_result->num_rows > 0) {
                            $counter = 1; // Initialize counter
                            while ($instructor = $instructor_result->fetch_assoc()) {
                              echo "<tr class='clickable-row' data-href='view_instructor.php?id=" . $instructor['instructor_id'] . "'>";
                              echo "<td>" . $counter++ . "</td>"; // Display counter instead of instructor_id
                              echo "<td>" . htmlspecialchars($instructor['name']) . "</td>";
                              echo "<td>" . (!empty($instructor['specialties']) ? htmlspecialchars($instructor['specialties']) : "None") . "</td>";
                              echo "<td>
                                      <a href='edit_instructor.php?id=" . htmlspecialchars($instructor['instructor_id']) . "' class='text-dark me-3'>Edit</a>
                                      <a href='delete_instructor.php?id=" . htmlspecialchars($instructor['instructor_id']) . "' class='text-danger'>Delete</a>
                                    </td>";
                              echo "</tr>";
                            }
                          } else {
                            echo "<tr><td colspan='4' class='text-center'>No instructors found.</td></tr>";
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
                            <th>#</th>
                            <th>Name</th>
                            <th>Licenses Enrolled</th>
                            <th>Actions</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          if ($student_result->num_rows > 0) {
                            $counter = 1; // Initialize counter
                            while ($student = $student_result->fetch_assoc()) {
                              echo "<tr class='clickable-row' data-href='view_student.php?id=" . $student['student_id'] . "'>";
                              echo "<td>" . $counter++ . "</td>"; // Display counter instead of student_id
                              echo "<td>" . htmlspecialchars($student['name']) . "</td>";
                              echo "<td>" . (!empty($student['licenses_enrolled']) ? htmlspecialchars($student['licenses_enrolled']) : "None") . "</td>";
                              echo "<td>
                                      <a href='edit_student.php?id=" . htmlspecialchars($student['student_id']) . "' class='text-dark me-3'>Edit</a>
                                      <a href='delete_student.php?id=" . htmlspecialchars($student['student_id']) . "' class='text-danger'>Delete</a>
                                    </td>";
                              echo "</tr>";
                            }
                          } else {
                            echo "<tr><td colspan='4' class='text-center'>No students found.</td></tr>";
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

    // Dynamically show buttons based on active tab
    function updateButtons() {
      const activeTab = $(".nav-link.active").attr("id");
      const buttonContainer = $("#dynamic-buttons");
      buttonContainer.empty();

      if (activeTab === "line-instructors-tab") {
        buttonContainer.append('<a href="add_instructor.php" class="btn btn-primary btn-round">Add Instructor</a>');
      } else if (activeTab === "line-students-tab") {
        buttonContainer.append('<a href="add_student.php" class="btn btn-primary btn-round">Add Student</a>');
      }
    }

    // Initial button update
    updateButtons();

    // Update buttons on tab change
    $(".nav-link").on("shown.bs.tab", function() {
      updateButtons();
    });
  });
</script>

<style>
  .clickable-row {
    cursor: pointer;
  }
</style>