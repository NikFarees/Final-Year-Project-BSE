<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

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
          <a href="#">Instructor List </a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Instructor List</h4>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="basic-datatables" class="table table-striped table-bordered">
              <thead>
                <tr>
                  <th>Instructor ID</th>
                  <th>Name</th>
                  <th>Action</th>
                </tr>
              </thead>
              <tbody>
                <?php
                if ($instructor_result->num_rows > 0) {
                  while ($instructor = $instructor_result->fetch_assoc()) {
                    echo '<tr>';
                    echo '<td>' . htmlspecialchars($instructor['instructor_id']) . '</td>';
                    echo '<td>' . htmlspecialchars($instructor['name']) . '</td>';
                    echo '<td><a href="view_schedule_instructor.php?id=' . htmlspecialchars($instructor['instructor_id']) . '" class="btn btn-primary btn-sm">View</a></td>';
                    echo '</tr>';
                  }
                } else {
                  echo '<tr>';
                  echo '<td colspan="3" class="text-center">No instructors found.</td>';
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