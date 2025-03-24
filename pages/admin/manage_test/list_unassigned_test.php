<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Helper function to fetch unassigned tests based on test_id
function fetchUnassignedTests($conn, $test_id)
{
  $query = "
    SELECT 
        st.student_test_id,
        u.name AS student_name, 
        l.license_type,
        st.test_id,
        st.status
    FROM 
        student_tests AS st
    JOIN 
        student_licenses AS sl ON st.student_license_id = sl.student_license_id
    JOIN 
        students AS s ON sl.student_id = s.student_id
    JOIN 
        users AS u ON s.user_id = u.user_id
    JOIN 
        licenses AS l ON sl.license_id = l.license_id
    WHERE 
        st.schedule_status = 'Unassigned' AND st.status = 'Pending' AND st.test_id = '$test_id'
    ORDER BY 
        st.student_test_id ASC
    ";
  return mysqli_query($conn, $query);
}

$unassignedComputerTestResult = fetchUnassignedTests($conn, 'TES01');
$unassignedQtiTestResult = fetchUnassignedTests($conn, 'TES02');
$unassignedLitarTestResult = fetchUnassignedTests($conn, 'TES03');
$unassignedJalanRayaTestResult = fetchUnassignedTests($conn, 'TES04');
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Manage Test</h4>
      <ul class="breadcrumbs">
        <li class="nav-home">
          <a href="#">
            <i class="icon-home"></i>
          </a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="#">List Unassigned Test</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">

      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <h4 class="card-title">List Unassigned Test</h4>
          </div>
          <div class="card-body">
            <ul class="nav nav-pills nav-secondary" id="pills-tab" role="tablist">
              <li class="nav-item">
                <a class="nav-link active" id="pills-computer-test-tab" data-bs-toggle="pill" href="#pills-computer-test" role="tab" aria-controls="pills-computer-test" aria-selected="true">Computer Test</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="pills-qti-test-tab" data-bs-toggle="pill" href="#pills-qti-test" role="tab" aria-controls="pills-qti-test" aria-selected="false">QTI Test</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="pills-litar-test-tab" data-bs-toggle="pill" href="#pills-litar-test" role="tab" aria-controls="pills-litar-test" aria-selected="false">Litar Test</a>
              </li>
              <li class="nav-item">
                <a class="nav-link" id="pills-jalan-raya-test-tab" data-bs-toggle="pill" href="#pills-jalan-raya-test" role="tab" aria-controls="pills-jalan-raya-test" aria-selected="false">Jalan Raya Test</a>
              </li>
            </ul>
            <div class="tab-content mt-2 mb-3" id="pills-tabContent">
              <div class="tab-pane fade show active" id="pills-computer-test" role="tabpanel" aria-labelledby="pills-computer-test-tab">
                <div class="card mt-4">
                  <div class="card-body">
                    <div class="table-responsive">
                      <table id="computer-test-table" class="table table-bordered">
                        <thead>
                          <tr>
                            <th>Student Test ID</th>
                            <th>Student Name</th>
                            <th>License Type</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          if (mysqli_num_rows($unassignedComputerTestResult) > 0) {
                            while ($row = mysqli_fetch_assoc($unassignedComputerTestResult)) {
                              echo "<tr>
                                                <td>{$row['student_test_id']}</td>
                                                <td>{$row['student_name']}</td>
                                                <td>{$row['license_type']}</td>
                                                <td><a href='assign_test.php?student_test_id={$row['student_test_id']}' class='text-success'>Assign</a></td>
                                            </tr>";
                            }
                          } else {
                            echo "<tr><td colspan='4'>No unassigned computer tests found.</td></tr>";
                          }
                          ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              <div class="tab-pane fade" id="pills-qti-test" role="tabpanel" aria-labelledby="pills-qti-test-tab">
                <div class="card mt-4">
                  <div class="card-body">
                    <div class="table-responsive">
                      <table id="qti-test-table" class="table table-bordered">
                        <thead>
                          <tr>
                            <th>Student Test ID</th>
                            <th>Student Name</th>
                            <th>License Type</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          if (mysqli_num_rows($unassignedQtiTestResult) > 0) {
                            while ($row = mysqli_fetch_assoc($unassignedQtiTestResult)) {
                              echo "<tr>
                                                <td>{$row['student_test_id']}</td>
                                                <td>{$row['student_name']}</td>
                                                <td>{$row['license_type']}</td>
                                                <td><a href='assign_test.php?student_test_id={$row['student_test_id']}' class='text-success'>Assign</a></td>
                                            </tr>";
                            }
                          } else {
                            echo "<tr><td colspan='4'>No unassigned QTI tests found.</td></tr>";
                          }
                          ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              <div class="tab-pane fade" id="pills-litar-test" role="tabpanel" aria-labelledby="pills-litar-test-tab">
                <div class="card mt-4">
                  <div class="card-body">
                    <div class="table-responsive">
                      <table id="litar-test-table" class="table table-bordered">
                        <thead>
                          <tr>
                            <th>Student Test ID</th>
                            <th>Student Name</th>
                            <th>License Type</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          if (mysqli_num_rows($unassignedLitarTestResult) > 0) {
                            while ($row = mysqli_fetch_assoc($unassignedLitarTestResult)) {
                              echo "<tr>
                                                <td>{$row['student_test_id']}</td>
                                                <td>{$row['student_name']}</td>
                                                <td>{$row['license_type']}</td>
                                                <td><a href='assign_test.php?student_test_id={$row['student_test_id']}' class='text-success'>Assign</a></td>
                                            </tr>";
                            }
                          } else {
                            echo "<tr><td colspan='4'>No unassigned Litar tests found.</td></tr>";
                          }
                          ?>
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
              <div class="tab-pane fade" id="pills-jalan-raya-test" role="tabpanel" aria-labelledby="pills-jalan-raya-test-tab">
                <div class="card mt-4">
                  <div class="card-body">
                    <div class="table-responsive">
                      <table id="jalan-raya-test-table" class="table table-bordered">
                        <thead>
                          <tr>
                            <th>Student Test ID</th>
                            <th>Student Name</th>
                            <th>License Type</th>
                            <th>Action</th>
                          </tr>
                        </thead>
                        <tbody>
                          <?php
                          if (mysqli_num_rows($unassignedJalanRayaTestResult) > 0) {
                            while ($row = mysqli_fetch_assoc($unassignedJalanRayaTestResult)) {
                              echo "<tr>
                                                <td>{$row['student_test_id']}</td>
                                                <td>{$row['student_name']}</td>
                                                <td>{$row['license_type']}</td>
                                                <td><a href='assign_test.php?student_test_id={$row['student_test_id']}' class='text-success'>Assign</a></td>
                                            </tr>";
                            }
                          } else {
                            echo "<tr><td colspan='4'>No unassigned Jalan Raya tests found.</td></tr>";
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

  <?php
  include '../../../include/footer.html';
  ?>

<script>
  $(document).ready(function() {
    $("#computer-test-table").DataTable({});
    $("#qti-test-table").DataTable({});
    $("#litar-test-table").DataTable({});
    $("#jalan-raya-test-table").DataTable({});
  });
</script>