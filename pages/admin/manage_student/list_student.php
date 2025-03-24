<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// SQL query for fetching student data
$sql = "SELECT u.user_id, s.student_id, u.name, 
            GROUP_CONCAT(CONCAT(l.license_name, ' (', l.license_type, ')') SEPARATOR ', ') AS licenses_enrolled
        FROM students s
        JOIN users u ON s.user_id = u.user_id
        LEFT JOIN student_licenses sl ON s.student_id = sl.student_id
        LEFT JOIN licenses l ON sl.license_id = l.license_id
        GROUP BY s.student_id, u.user_id, u.name";

$result = $conn->query($sql);
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Manage Student</h4>
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
      <!-- Card Structure with Table -->
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="card-title">Student List</h4>
          <div class="ms-md-auto py-2 py-md-0">
            <a href="add_student.php" class="btn btn-primary btn-round">Add Student</a>
          </div>
        </div>
        <div class="card-body">
          <!-- Table Section -->
          <div class="table-responsive">
            <table id="basic-datatables" class="table table-bordered table-striped table-hover">
              <thead>
                <tr>
                  <th>Student ID</th>
                  <th>Name</th>
                  <th>Licenses Enrolled</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                if ($result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                    echo "<tr class='clickable-row' data-href='view_student.php?id=" . $row['student_id'] . "'>";
                    echo "<td>" . $row['student_id'] . "</td>";
                    echo "<td>" . $row['name'] . "</td>";
                    echo "<td>" . ($row['licenses_enrolled'] ? $row['licenses_enrolled'] : "None") . "</td>";
                    echo "<td>";
                    echo "<a href='edit_student.php?id=" . $row['student_id'] . "' class='text-dark me-3'>Edit</a>";
                    echo "<a href='delete_student.php?id=" . $row['student_id'] . "' class='text-danger'>Delete</a>";
                    echo "</td>";
                    echo "</tr>";
                  }
                } else {
                  echo "<tr><td colspan='4'>No students found</td></tr>";
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