<?php
include '../../include/in_header.php';
include '../../database/db_connection.php';

// Assume $current_user_id is the ID of the currently logged-in instructor
$current_user_id = $_SESSION['user_id'];

// Fetch announcements for the current instructor
$query = "
    SELECT 
        a.announcement_id,
        a.title,
        a.description,
        a.created_at
    FROM 
        announcements a
    LEFT JOIN 
        role_announcements ra ON a.announcement_id = ra.announcement_id
    LEFT JOIN 
        user_announcements ua ON a.announcement_id = ua.announcement_id
    WHERE 
        (ra.role_id = 'instructor' OR ua.user_id = ?)
    ORDER BY 
        a.created_at DESC
    LIMIT 3
";

$stmt = $conn->prepare($query);
if (!$stmt) {
  die("Query preparation failed: " . $conn->error);
}

$stmt->bind_param("s", $current_user_id);
$stmt->execute();
$notification_result = $stmt->get_result();
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Dashboard</h4>
      <ul class="breadcrumbs">
        <li class="nav-home">
          <a href="#">
            <i class="icon-home"></i>
          </a>
        </li>
      </ul>
    </div>

    <!-- Stats Cards -->
    <div class="row">
      <div class="col-md-3">
        <div class="card card-stats card-primary card-round">
          <div class="card-body">
            <div class="row">
              <div class="col-5">
                <div class="icon-big text-center">
                  <i class="fas fa-users"></i>
                </div>
              </div>
              <div class="col-7 col-stats">
                <div class="numbers">
                  <p class="card-category">My Students</p>
                  <h4 class="card-title">12</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card card-stats card-info card-round">
          <div class="card-body">
            <div class="row">
              <div class="col-5">
                <div class="icon-big text-center">
                  <i class="far fa-calendar-check"></i>
                </div>
              </div>
              <div class="col-7 col-stats">
                <div class="numbers">
                  <p class="card-category">Sessions Today</p>
                  <h4 class="card-title">5</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card card-stats card-success card-round">
          <div class="card-body">
            <div class="row">
              <div class="col-5">
                <div class="icon-big text-center">
                  <i class="fas fa-car"></i>
                </div>
              </div>
              <div class="col-7 col-stats">
                <div class="numbers">
                  <p class="card-category">Available Vehicles</p>
                  <h4 class="card-title">8</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-3">
        <div class="card card-stats card-warning card-round">
          <div class="card-body">
            <div class="row">
              <div class="col-5">
                <div class="icon-big text-center">
                  <i class="far fa-clock"></i>
                </div>
              </div>
              <div class="col-7 col-stats">
                <div class="numbers">
                  <p class="card-category">Working Hours</p>
                  <h4 class="card-title">24/40</h4>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Today's Sessions -->
    <div class="row">
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">
            <div class="card-title">Today's Sessions</div>
            <div class="card-subtitle">March 19, 2025</div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>TIME</th>
                    <th>STUDENT</th>
                    <th>VEHICLE</th>
                    <th>LESSON TYPE</th>
                    <th>STATUS</th>
                    <th>ACTION</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>09:00 AM</td>
                    <td>James Wilson</td>
                    <td>Toyota C-HR</td>
                    <td>Highway Driving</td>
                    <td><span class="badge badge-success">Completed</span></td>
                    <td>
                      <button class="btn btn-primary btn-xs"><i class="fa fa-eye"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>10:30 AM</td>
                    <td>Emily Davis</td>
                    <td>Honda Civic</td>
                    <td>Parallel Parking</td>
                    <td><span class="badge badge-success">Completed</span></td>
                    <td>
                      <button class="btn btn-primary btn-xs"><i class="fa fa-eye"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>01:00 PM</td>
                    <td>Alex Johnson</td>
                    <td>Ford Focus</td>
                    <td>City Driving</td>
                    <td><span class="badge badge-info">In Progress</span></td>
                    <td>
                      <button class="btn btn-primary btn-xs"><i class="fa fa-eye"></i></button>
                    </td>
                  </tr>
                  <tr>
                    <td>02:30 PM</td>
                    <td>Lisa Brown</td>
                    <td>Toyota Corolla</td>
                    <td>Defensive Driving</td>
                    <td><span class="badge badge-warning">Upcoming</span></td>
                    <td>
                      <div class="btn-group">
                        <button class="btn btn-success btn-xs"><i class="fa fa-check"></i></button>
                        <button class="btn btn-danger btn-xs"><i class="fa fa-times"></i></button>
                      </div>
                    </td>
                  </tr>
                  <tr>
                    <td>04:00 PM</td>
                    <td>Mark Taylor</td>
                    <td>Hyundai i30</td>
                    <td>Test Preparation</td>
                    <td><span class="badge badge-warning">Upcoming</span></td>
                    <td>
                      <div class="btn-group">
                        <button class="btn btn-success btn-xs"><i class="fa fa-check"></i></button>
                        <button class="btn btn-danger btn-xs"><i class="fa fa-times"></i></button>
                      </div>
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card">
          <div class="card-header">
            <div class="card-title">Notifications</div>
          </div>
          <div class="card-body">
            <div class="list-group">
              <?php if ($notification_result->num_rows > 0): ?>
                <?php while ($notification = $notification_result->fetch_assoc()): ?>
                  <div class="notification-item">
                    <h6 class="notification-heading fw-bold mb-1">
                      <?= htmlspecialchars($notification['title']) ?>
                    </h6>
                    <div class="notification-time text-muted">
                      <small><?= date('F j, Y, g:i a', strtotime($notification['created_at'])) ?></small>
                    </div>
                    <p class="notification-text">
                      <?= nl2br(htmlspecialchars($notification['description'])) ?>
                    </p>
                  </div>
                  <div class="separator-dashed"></div>
                <?php endwhile; ?>
              <?php else: ?>
                <div class="notification-item">
                  <p class="notification-text">No announcements yet.</p>
                </div>
              <?php endif; ?>
            </div>
            <div class="text-center mt-3">
              <a href="/pages/instructor/view_announcement/view_announcement.php" class="btn btn-sm btn-primary">
                See More
              </a>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Student Performance and Working Hours -->
    <div class="row">
      <div class="col-md-8">
        <div class="card">
          <div class="card-header">
            <div class="card-title">Student Performance</div>
          </div>
          <div class="card-body">
            <div class="chart-container">
              <canvas id="studentPerformanceChart"></canvas>
            </div>
            <div class="row mt-4">
              <div class="col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="numbers text-center">
                      <p class="card-category">Pass Rate</p>
                      <h4 class="card-title">78%</h4>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="numbers text-center">
                      <p class="card-category">Practical Skills</p>
                      <h4 class="card-title">83%</h4>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="numbers text-center">
                      <p class="card-category">Theory Knowledge</p>
                      <h4 class="card-title">91%</h4>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="numbers text-center">
                      <p class="card-category">Satisfaction</p>
                      <h4 class="card-title">94%</h4>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
      <div class="col-md-4">
        <div class="card">
          <div class="card-header">
            <div class="card-title">My Working Hours</div>
          </div>
          <div class="card-body">
            <div class="d-flex justify-content-between">
              <p>This Week:</p>
              <p><strong>24 / 40 hours</strong></p>
            </div>
            <div class="progress mb-4">
              <div class="progress-bar bg-success" role="progressbar" style="width: 60%" aria-valuenow="60" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="d-flex justify-content-between">
              <p>This Month:</p>
              <p><strong>86 / 160 hours</strong></p>
            </div>
            <div class="progress mb-4">
              <div class="progress-bar bg-info" role="progressbar" style="width: 53.75%" aria-valuenow="53.75" aria-valuemin="0" aria-valuemax="100"></div>
            </div>
            <div class="table-responsive mt-3">
              <table class="table table-bordered">
                <thead>
                  <tr>
                    <th>Day</th>
                    <th>Hours</th>
                    <th>Sessions</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>Monday</td>
                    <td>4.5</td>
                    <td>3</td>
                  </tr>
                  <tr>
                    <td>Tuesday</td>
                    <td>6</td>
                    <td>4</td>
                  </tr>
                  <tr>
                    <td>Wednesday</td>
                    <td>5.5</td>
                    <td>4</td>
                  </tr>
                  <tr>
                    <td>Today</td>
                    <td>8</td>
                    <td>5</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Student Attendance -->
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <div class="card-title">Student Attendance</div>
          </div>
          <div class="card-body">
            <div class="table-responsive">
              <table class="table table-hover">
                <thead>
                  <tr>
                    <th>STUDENT</th>
                    <th>TOTAL SESSIONS</th>
                    <th>ATTENDED</th>
                    <th>MISSED</th>
                    <th>ATTENDANCE RATE</th>
                    <th>LAST SESSION</th>
                    <th>NEXT SESSION</th>
                  </tr>
                </thead>
                <tbody>
                  <tr>
                    <td>James Wilson</td>
                    <td>12</td>
                    <td>12</td>
                    <td>0</td>
                    <td>
                      <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                      </div>
                      <span class="text-success">100%</span>
                    </td>
                    <td>Today, 09:00 AM</td>
                    <td>Mar 22, 10:00 AM</td>
                  </tr>
                  <tr>
                    <td>Emily Davis</td>
                    <td>8</td>
                    <td>7</td>
                    <td>1</td>
                    <td>
                      <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 87.5%"></div>
                      </div>
                      <span class="text-success">87.5%</span>
                    </td>
                    <td>Today, 10:30 AM</td>
                    <td>Mar 21, 02:00 PM</td>
                  </tr>
                  <tr>
                    <td>Alex Johnson</td>
                    <td>15</td>
                    <td>12</td>
                    <td>3</td>
                    <td>
                      <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 80%"></div>
                      </div>
                      <span class="text-warning">80%</span>
                    </td>
                    <td>Today, 01:00 PM</td>
                    <td>Mar 25, 11:30 AM</td>
                  </tr>
                  <tr>
                    <td>Lisa Brown</td>
                    <td>6</td>
                    <td>6</td>
                    <td>0</td>
                    <td>
                      <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-success" role="progressbar" style="width: 100%"></div>
                      </div>
                      <span class="text-success">100%</span>
                    </td>
                    <td>Mar 15, 03:45 PM</td>
                    <td>Today, 02:30 PM</td>
                  </tr>
                  <tr>
                    <td>Mark Taylor</td>
                    <td>10</td>
                    <td>8</td>
                    <td>2</td>
                    <td>
                      <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: 80%"></div>
                      </div>
                      <span class="text-warning">80%</span>
                    </td>
                    <td>Mar 18, 09:15 AM</td>
                    <td>Today, 04:00 PM</td>
                  </tr>
                </tbody>
              </table>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Quick Access -->
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <div class="card-title">Quick Access</div>
          </div>
          <div class="card-body text-center">
            <div class="row">
              <div class="col-md-3">
                <button class="btn btn-primary btn-lg btn-block">
                  <i class="fas fa-calendar-plus mr-2"></i>Schedule Session
                </button>
              </div>
              <div class="col-md-3">
                <button class="btn btn-info btn-lg btn-block">
                  <i class="fas fa-file-alt mr-2"></i>Submit Report
                </button>
              </div>
              <div class="col-md-3">
                <button class="btn btn-success btn-lg btn-block">
                  <i class="fas fa-car-alt mr-2"></i>Vehicle Check
                </button>
              </div>
              <div class="col-md-3">
                <button class="btn btn-warning btn-lg btn-block">
                  <i class="fas fa-chart-line mr-2"></i>View Statistics
                </button>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

  </div>
</div>

<!-- Initialize Charts -->
<script>
  $(document).ready(function() {
    // Student Performance Chart
    var ctx = document.getElementById('studentPerformanceChart').getContext('2d');
    var studentChart = new Chart(ctx, {
      type: 'bar',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun'],
        datasets: [{
          label: 'Pass Rate',
          data: [72, 75, 78, 80, 82, 85],
          backgroundColor: '#1572E8',
          borderWidth: 0
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        scales: {
          y: {
            beginAtZero: true,
            max: 100
          }
        }
      }
    });
  });
</script>

<?php
include '../../include/footer.html';
?>