<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

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
          <a href="#">Student List</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Student List</h4>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="basic-datatables" class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>Student ID</th>
                  <th>Name</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php
                if ($student_result->num_rows > 0) {
                  while ($student = $student_result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($student['student_id']) . '</td>';
                    echo '<td>' . htmlspecialchars($student['name']) . '</td>';
                    echo '<td><a href="view_schedule_student.php?id=' . htmlspecialchars($student['student_id']) . '" class="btn btn-primary btn-sm">View</a></td>';
                    echo '</tr>';
                  }
                } else {
                  echo '<tr>';
                  echo '<td colspan="3" class="text-center">No students found.</td>';
                  echo '</tr>';
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

<script>
    $(document).ready(function() {
        $("#basic-datatables").DataTable({});
    });
</script>