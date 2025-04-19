<?php
include '../../../include/in_header.php';
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Test Management</h4>
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
          <a href="#">Instructor</a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="#">Test Sessions</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      <!-- Test Sessions Overview -->
      <div class="card">
        <div class="card-header">
          <div class="card-title">My Test Sessions</div>
          <div class="card-category">View and manage your assigned test sessions</div>
        </div>
        <div class="card-body">
          <!-- Filter Options -->
          <div class="row mb-3">
            <div class="col-md-3">
              <div class="form-group">
                <label for="test-type">Test Type</label>
                <select class="form-control" id="test-type">
                  <option value="">All Tests</option>
                  <option value="computer">Computer Test</option>
                  <option value="qti">QTI Test</option>
                  <option value="circuit">Circuit Test</option>
                  <option value="on-road">On-Road Test</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="test-date">Date</label>
                <input type="date" class="form-control" id="test-date">
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label for="test-status">Status</label>
                <select class="form-control" id="test-status">
                  <option value="">All Status</option>
                  <option value="upcoming">Upcoming</option>
                  <option value="ongoing">Ongoing</option>
                  <option value="completed">Completed</option>
                  <option value="cancelled">Cancelled</option>
                </select>
              </div>
            </div>
            <div class="col-md-3">
              <div class="form-group">
                <label>&nbsp;</label>
                <button class="btn btn-primary btn-block">Filter</button>
              </div>
            </div>
          </div>

          <!-- Sessions Table -->
          <div class="table-responsive">
            <table class="table table-hover">
              <thead>
                <tr>
                  <th>Test ID</th>
                  <th>Type</th>
                  <th>Date</th>
                  <th>Time</th>
                  <th>Capacity</th>
                  <th>Enrolled</th>
                  <th>Status</th>
                  <th>Actions</th>
                </tr>
              </thead>
              <tbody>
                <!-- Mock data -->
                <tr>
                  <td>TST-10045</td>
                  <td><span class="badge badge-info">Computer Test</span></td>
                  <td>12 Apr 2025</td>
                  <td>09:00 - 11:00</td>
                  <td>20</td>
                  <td>18</td>
                  <td><span class="badge badge-success">Upcoming</span></td>
                  <td>
                    <button class="btn btn-link btn-sm" onclick="viewDetails('TST-10045')">
                      <i class="icon-eye"></i> Details
                    </button>
                  </td>
                </tr>
                <tr>
                  <td>TST-10046</td>
                  <td><span class="badge badge-primary">QTI Test</span></td>
                  <td>12 Apr 2025</td>
                  <td>13:00 - 15:00</td>
                  <td>15</td>
                  <td>15</td>
                  <td><span class="badge badge-warning">Ongoing</span></td>
                  <td>
                    <button class="btn btn-link btn-sm" onclick="viewDetails('TST-10046')">
                      <i class="icon-eye"></i> Details
                    </button>
                  </td>
                </tr>
                <tr>
                  <td>TST-10040</td>
                  <td><span class="badge badge-warning">Circuit Test</span></td>
                  <td>11 Apr 2025</td>
                  <td>10:00 - 12:30</td>
                  <td>10</td>
                  <td>9</td>
                  <td><span class="badge badge-success">Completed</span></td>
                  <td>
                    <button class="btn btn-link btn-sm" onclick="viewDetails('TST-10040')">
                      <i class="icon-eye"></i> Details
                    </button>
                  </td>
                </tr>
                <tr>
                  <td>TST-10039</td>
                  <td><span class="badge badge-danger">On-Road Test</span></td>
                  <td>10 Apr 2025</td>
                  <td>14:00 - 17:00</td>
                  <td>8</td>
                  <td>8</td>
                  <td><span class="badge badge-success">Completed</span></td>
                  <td>
                    <button class="btn btn-link btn-sm" onclick="viewDetails('TST-10039')">
                      <i class="icon-eye"></i> Details
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <!-- Test Details Modal -->
      <div class="modal fade" id="testDetailsModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="modalTitle">Test Session Details</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <!-- Test Details -->
              <div class="row">
                <div class="col-md-6">
                  <h6>Test Information</h6>
                  <div class="table-responsive">
                    <table class="table table-bordered">
                      <tr>
                        <th>Test ID:</th>
                        <td id="detail-test-id">TST-10045</td>
                      </tr>
                      <tr>
                        <th>Type:</th>
                        <td id="detail-test-type">Computer Test</td>
                      </tr>
                      <tr>
                        <th>Date:</th>
                        <td id="detail-date">12 Apr 2025</td>
                      </tr>
                      <tr>
                        <th>Time:</th>
                        <td id="detail-time">09:00 - 11:00</td>
                      </tr>
                      <tr>
                        <th>Location:</th>
                        <td id="detail-location">Lab Room 305</td>
                      </tr>
                    </table>
                  </div>
                </div>
                <div class="col-md-6">
                  <h6>Status Information</h6>
                  <div class="table-responsive">
                    <table class="table table-bordered">
                      <tr>
                        <th>Status:</th>
                        <td id="detail-status"><span class="badge badge-success">Upcoming</span></td>
                      </tr>
                      <tr>
                        <th>Capacity:</th>
                        <td id="detail-capacity">20</td>
                      </tr>
                      <tr>
                        <th>Enrolled:</th>
                        <td id="detail-enrolled">18</td>
                      </tr>
                      <tr>
                        <th>Instructor:</th>
                        <td id="detail-instructor">John Smith (YOU)</td>
                      </tr>
                      <tr>
                        <th>Actions:</th>
                        <td id="detail-actions">
                          <button class="btn btn-sm btn-warning">Mark Attendance</button>
                          <button class="btn btn-sm btn-info">Start Test</button>
                        </td>
                      </tr>
                    </table>
                  </div>
                </div>
              </div>

              <!-- Student List -->
              <div class="row mt-3">
                <div class="col-md-12">
                  <h6>Student List</h6>
                  <div class="table-responsive">
                    <table class="table table-hover">
                      <thead>
                        <tr>
                          <th>Student ID</th>
                          <th>Name</th>
                          <th>Attendance</th>
                          <th>Score</th>
                          <th>Status</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody id="detail-student-list">
                        <!-- Student list will be populated here -->
                        <tr>
                          <td>STD-2345</td>
                          <td>Jane Cooper</td>
                          <td><span class="badge badge-success">Present</span></td>
                          <td>85/100</td>
                          <td><span class="badge badge-success">Passed</span></td>
                          <td>
                            <button class="btn btn-sm btn-primary" onclick="editScore('STD-2345')">Edit Score</button>
                          </td>
                        </tr>
                        <tr>
                          <td>STD-2346</td>
                          <td>Robert Johnson</td>
                          <td><span class="badge badge-success">Present</span></td>
                          <td>-</td>
                          <td><span class="badge badge-warning">Pending</span></td>
                          <td>
                            <button class="btn btn-sm btn-primary" onclick="editScore('STD-2346')">Add Score</button>
                          </td>
                        </tr>
                        <tr>
                          <td>STD-2347</td>
                          <td>Emily Davis</td>
                          <td><span class="badge badge-danger">Absent</span></td>
                          <td>-</td>
                          <td><span class="badge badge-danger">Absent</span></td>
                          <td>
                            <button class="btn btn-sm btn-primary" disabled>Add Score</button>
                          </td>
                        </tr>
                        <tr>
                          <td>STD-2348</td>
                          <td>Michael Wilson</td>
                          <td><span class="badge badge-success">Present</span></td>
                          <td>62/100</td>
                          <td><span class="badge badge-danger">Failed</span></td>
                          <td>
                            <button class="btn btn-sm btn-primary" onclick="editScore('STD-2348')">Edit Score</button>
                          </td>
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
              <button type="button" class="btn btn-success" id="saveAllScores">Save All Scores</button>
            </div>
          </div>
        </div>
      </div>

      <!-- Score Edit Modal -->
      <div class="modal fade" id="scoreEditModal" tabindex="-1" role="dialog" aria-hidden="true">
        <div class="modal-dialog" role="document">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Edit Student Score</h5>
              <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
              </button>
            </div>
            <div class="modal-body">
              <form id="scoreForm">
                <div class="form-group">
                  <label>Student ID:</label>
                  <input type="text" class="form-control" id="score-student-id" readonly>
                </div>
                <div class="form-group">
                  <label>Student Name:</label>
                  <input type="text" class="form-control" id="score-student-name" readonly>
                </div>

                <!-- Different score forms based on test type -->
                <!-- Computer Test Scoring -->
                <div id="computer-test-scoring">
                  <div class="form-group">
                    <label>Total Score:</label>
                    <input type="number" class="form-control" id="score-total" min="0" max="100">
                  </div>
                  <div class="form-group">
                    <label>Comments:</label>
                    <textarea class="form-control" id="score-comments" rows="3"></textarea>
                  </div>
                </div>

                <!-- Circuit Test Scoring -->
                <div id="circuit-test-scoring" style="display: none;">
                  <div class="form-group">
                    <label>Maneuver Score (0-40):</label>
                    <input type="number" class="form-control" id="circuit-maneuver" min="0" max="40">
                  </div>
                  <div class="form-group">
                    <label>Safety Score (0-30):</label>
                    <input type="number" class="form-control" id="circuit-safety" min="0" max="30">
                  </div>
                  <div class="form-group">
                    <label>Time Score (0-30):</label>
                    <input type="number" class="form-control" id="circuit-time" min="0" max="30">
                  </div>
                  <div class="form-group">
                    <label>Total Score:</label>
                    <input type="number" class="form-control" id="circuit-total" readonly>
                  </div>
                  <div class="form-group">
                    <label>Comments:</label>
                    <textarea class="form-control" id="circuit-comments" rows="3"></textarea>
                  </div>
                </div>

                <!-- On-Road Test Scoring -->
                <div id="on-road-test-scoring" style="display: none;">
                  <div class="form-group">
                    <label>Driving Skills (0-25):</label>
                    <input type="number" class="form-control" id="road-skills" min="0" max="25">
                  </div>
                  <div class="form-group">
                    <label>Traffic Rules (0-25):</label>
                    <input type="number" class="form-control" id="road-rules" min="0" max="25">
                  </div>
                  <div class="form-group">
                    <label>Safety Awareness (0-25):</label>
                    <input type="number" class="form-control" id="road-safety" min="0" max="25">
                  </div>
                  <div class="form-group">
                    <label>Parking (0-25):</label>
                    <input type="number" class="form-control" id="road-parking" min="0" max="25">
                  </div>
                  <div class="form-group">
                    <label>Total Score:</label>
                    <input type="number" class="form-control" id="road-total" readonly>
                  </div>
                  <div class="form-group">
                    <label>Comments:</label>
                    <textarea class="form-control" id="road-comments" rows="3"></textarea>
                  </div>
                </div>

                <!-- Pass/Fail Status -->
                <div class="form-group">
                  <label>Status:</label>
                  <select class="form-control" id="score-status">
                    <option value="passed">Passed</option>
                    <option value="failed">Failed</option>
                    <option value="pending">Pending</option>
                  </select>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancel</button>
              <button type="button" class="btn btn-primary" id="saveScore">Save Score</button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- JavaScript for functionality -->
<script>
  // View test details
  function viewDetails(testId) {
    // In a real application, this would fetch data from the server
    // For this mockup, we'll just show the modal with static data

    // Determine test type to show appropriate scoring form
    let testType = '';
    if (testId === 'TST-10045') testType = 'computer';
    else if (testId === 'TST-10046') testType = 'qti';
    else if (testId === 'TST-10040') testType = 'circuit';
    else if (testId === 'TST-10039') testType = 'on-road';

    // Update modal title with test ID
    $('#modalTitle').text('Test Session Details - ' + testId);

    // Store test type in data attribute for score editing
    $('#testDetailsModal').data('test-type', testType);

    // Show the modal
    $('#testDetailsModal').modal('show');
  }

  // Edit student score
  function editScore(studentId) {
    // Get test type from the details modal
    let testType = $('#testDetailsModal').data('test-type');

    // Find student name (in a real app, would fetch from database)
    let studentName = 'Student Name';
    if (studentId === 'STD-2345') studentName = 'Jane Cooper';
    else if (studentId === 'STD-2346') studentName = 'Robert Johnson';
    else if (studentId === 'STD-2347') studentName = 'Emily Davis';
    else if (studentId === 'STD-2348') studentName = 'Michael Wilson';

    // Set values in the score edit form
    $('#score-student-id').val(studentId);
    $('#score-student-name').val(studentName);

    // Show appropriate scoring form based on test type
    $('#computer-test-scoring, #circuit-test-scoring, #on-road-test-scoring').hide();

    if (testType === 'computer' || testType === 'qti') {
      $('#computer-test-scoring').show();

      // Pre-fill values if student already has a score
      if (studentId === 'STD-2345') {
        $('#score-total').val(85);
        $('#score-status').val('passed');
      } else if (studentId === 'STD-2348') {
        $('#score-total').val(62);
        $('#score-status').val('failed');
      } else {
        $('#score-total').val('');
        $('#score-status').val('pending');
      }
    } else if (testType === 'circuit') {
      $('#circuit-test-scoring').show();

      // Pre-fill circuit test scores
      if (studentId === 'STD-2345') {
        $('#circuit-maneuver').val(35);
        $('#circuit-safety').val(25);
        $('#circuit-time').val(25);
        $('#circuit-total').val(85);
        $('#score-status').val('passed');
      } else {
        $('#circuit-maneuver').val('');
        $('#circuit-safety').val('');
        $('#circuit-time').val('');
        $('#circuit-total').val('');
        $('#score-status').val('pending');
      }
    } else if (testType === 'on-road') {
      $('#on-road-test-scoring').show();

      // Pre-fill on-road test scores
      if (studentId === 'STD-2345') {
        $('#road-skills').val(22);
        $('#road-rules').val(23);
        $('#road-safety').val(20);
        $('#road-parking').val(20);
        $('#road-total').val(85);
        $('#score-status').val('passed');
      } else {
        $('#road-skills').val('');
        $('#road-rules').val('');
        $('#road-safety').val('');
        $('#road-parking').val('');
        $('#road-total').val('');
        $('#score-status').val('pending');
      }
    }

    // Show the scoring modal
    $('#scoreEditModal').modal('show');
  }

  // Calculate total scores for different test types
  $(document).ready(function() {
    // Circuit test auto-calculation
    $('#circuit-maneuver, #circuit-safety, #circuit-time').on('input', function() {
      let maneuver = parseInt($('#circuit-maneuver').val()) || 0;
      let safety = parseInt($('#circuit-safety').val()) || 0;
      let time = parseInt($('#circuit-time').val()) || 0;

      let total = maneuver + safety + time;
      $('#circuit-total').val(total);

      // Auto-set pass/fail status
      if (total >= 70) {
        $('#score-status').val('passed');
      } else if (total > 0) {
        $('#score-status').val('failed');
      } else {
        $('#score-status').val('pending');
      }
    });

    // On-road test auto-calculation
    $('#road-skills, #road-rules, #road-safety, #road-parking').on('input', function() {
      let skills = parseInt($('#road-skills').val()) || 0;
      let rules = parseInt($('#road-rules').val()) || 0;
      let safety = parseInt($('#road-safety').val()) || 0;
      let parking = parseInt($('#road-parking').val()) || 0;

      let total = skills + rules + safety + parking;
      $('#road-total').val(total);

      // Auto-set pass/fail status
      if (total >= 70) {
        $('#score-status').val('passed');
      } else if (total > 0) {
        $('#score-status').val('failed');
      } else {
        $('#score-status').val('pending');
      }
    });

    // Computer test auto-set pass/fail
    $('#score-total').on('input', function() {
      let total = parseInt($('#score-total').val()) || 0;

      if (total >= 70) {
        $('#score-status').val('passed');
      } else if (total > 0) {
        $('#score-status').val('failed');
      } else {
        $('#score-status').val('pending');
      }
    });

    // Save score button
    $('#saveScore').click(function() {
      // In a real application, this would save to the database
      alert('Score saved successfully!');
      $('#scoreEditModal').modal('hide');

      // Update the score in the student list (just for demo)
      // In a real application, you would update the UI based on the saved data
    });

    // Save all scores button
    $('#saveAllScores').click(function() {
      // In a real application, this would save all scores to the database
      alert('All scores saved successfully!');
    });
  });
</script>

<?php
include '../../../include/footer.html';
?>