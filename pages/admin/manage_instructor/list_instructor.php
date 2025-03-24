<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// SQL query for fetching instructor data
$sql = "SELECT u.user_id, i.instructor_id, u.name, u.email, u.phone, 
            GROUP_CONCAT(CONCAT(l.license_name, ' (', l.license_type, ')') SEPARATOR ', ') AS specialties
        FROM instructors i
        JOIN users u ON i.user_id = u.user_id
        LEFT JOIN specialities s ON i.instructor_id = s.instructor_id
        LEFT JOIN licenses l ON s.license_id = l.license_id
        GROUP BY i.instructor_id, u.user_id, u.name, u.email, u.phone";

$result = $conn->query($sql);
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Manage Instructor</h4>
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
          <a href="#">Instructor List</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      <!-- Card Structure with Table -->
      <div class="card">
        <div class="card-header d-flex justify-content-between align-items-center">
          <h4 class="card-title">Instructor List</h4>
          <div class="ms-md-auto py-2 py-md-0">
            <a href="add_instructor.php" class="btn btn-primary btn-round">Add Instructor</a>
          </div>
        </div>
        <div class="card-body">
          <!-- Table Section -->
          <div class="table-responsive">
            <table id="basic-datatables" class="table table-bordered table-striped table-hover">
              <thead>
                <tr>
                  <th>Instructor ID</th>
                  <th>Name</th>
                  <th>Specialties</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php
                if ($result->num_rows > 0) {
                  while ($row = $result->fetch_assoc()) {
                    echo "<tr class='clickable-row' data-href='view_instructor.php?id=" . $row['instructor_id'] . "'>";
                    echo "<td>" . $row['instructor_id'] . "</td>";
                    echo "<td>" . $row['name'] . "</td>";
                    echo "<td>" . ($row['specialties'] ? $row['specialties'] : "None") . "</td>";
                    echo "<td>";
                    echo "<a href='edit_instructor.php?id=" . $row['instructor_id'] . "' class='text-dark me-3'>Edit</a>";
                    echo "<a href='delete_instructor.php?id=" . $row['instructor_id'] . "' class='text-danger'>Delete</a>";
                    echo "</td>";
                    echo "</tr>";
                  }
                } else {
                  echo "<tr><td colspan='4'>No instructors found</td></tr>";
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