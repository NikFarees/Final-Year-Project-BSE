<?php
include '../../../include/in_header.php';
include '../../../database/db_connection.php';

// Get the current user_id from the session
$user_id = $_SESSION['user_id'] ?? null;

if (!$user_id) {
    echo '<div class="alert alert-danger">User is not logged in.</div>';
    include '../../../include/footer.html';
    exit;
}

// Fetch the instructor_id based on the user_id
$query = "SELECT instructor_id FROM instructors WHERE user_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $instructor = $result->fetch_assoc();
    $instructor_id = $instructor['instructor_id'];
} else {
    echo '<div class="alert alert-danger">Instructor not found for the current user.</div>';
    include '../../../include/footer.html';
    exit;
}
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">My Speciality</h4>
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
          <a href="#">Speciality List</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      <div class="card">
        <div class="card-header">
          <div class="card-title">License Specialities</div>
        </div>
        <div class="card-body">
          <div class="table-responsive">
            <table id="specialities-table" class="table table-bordered table-striped">
              <thead>
                <tr>
                  <th>License Code</th>
                  <th>License Name</th>
                  <th>Type</th>
                  <th>Description</th>
                </tr>
              </thead>
              <tbody>
                <?php
                // Fetch instructor specialities
                $query = "SELECT s.speciality_id, l.license_id, l.license_name, l.license_type, l.description 
                          FROM specialities s 
                          JOIN licenses l ON s.license_id = l.license_id 
                          WHERE s.instructor_id = ?";
                $stmt = $conn->prepare($query);
                $stmt->bind_param("s", $instructor_id);
                $stmt->execute();
                $specialities = $stmt->get_result();

                if ($specialities->num_rows > 0) {
                    while ($row = $specialities->fetch_assoc()) {
                        ?>
                        <tr>
                          <td><?php echo htmlspecialchars($row['license_id']); ?></td>
                          <td><?php echo htmlspecialchars($row['license_name']); ?></td>
                          <td>
                            <?php if ($row['license_type'] == 'Auto'): ?>
                              <span class="badge badge-primary">Auto</span>
                            <?php elseif ($row['license_type'] == 'Manual'): ?>
                              <span class="badge badge-info">Manual</span>
                            <?php else: ?>
                              <span class="badge badge-secondary"><?php echo htmlspecialchars($row['license_type']); ?></span>
                            <?php endif; ?>
                          </td>
                          <td><?php echo htmlspecialchars($row['description']); ?></td>
                        </tr>
                        <?php
                    }
                } else {
                    echo '<tr><td colspan="4" class="text-center">No specialities found</td></tr>';
                }
                ?>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Add Speciality Modal -->
      <div class="modal fade" id="addSpecialityModal" tabindex="-1" role="dialog">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Add License Speciality</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <form action="process_add_speciality.php" method="post">
              <div class="modal-body">
                <input type="hidden" name="instructor_id" value="<?php echo $instructor_id; ?>">
                <div class="form-group">
                  <label>Select License</label>
                  <select class="form-control" name="license_id" required>
                    <option value="">-- Select License --</option>
                    <?php
                    // Fetch all licenses
                    $query = "SELECT license_id, license_name, license_type FROM licenses ORDER BY license_name";
                    $result = $conn->query($query);
                    while ($license = $result->fetch_assoc()) {
                        echo '<option value="' . htmlspecialchars($license['license_id']) . '">' . htmlspecialchars($license['license_name']) . ' (' . htmlspecialchars($license['license_type']) . ')</option>';
                    }
                    ?>
                  </select>
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Add Speciality</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<?php include '../../../include/footer.html'; ?>

<script>
  $(document).ready(function() {
    $("#specialities-table").DataTable({});
  });
</script>