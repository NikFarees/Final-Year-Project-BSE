<?php
include '../../include/ad_header.php';
include '../../database/db_connection.php'; // Include the database connection file

// Fetch the top 3 latest notifications
$notification_query = "
    SELECT 
        a.title, 
        a.description, 
        a.created_at, 
        u.name AS created_by 
    FROM 
        announcements AS a
    LEFT JOIN 
        users AS u ON a.created_by = u.user_id
    ORDER BY 
        a.created_at DESC 
    LIMIT 3
";
$notification_result = $conn->query($notification_query);

// Fetch the number of students
$student_count_query = "SELECT COUNT(*) AS student_count FROM students";
$student_count_result = $conn->query($student_count_query);
$student_count_row = $student_count_result->fetch_assoc();
$student_count = $student_count_row['student_count'];

// Fetch the number of instructors
$instructor_count_query = "SELECT COUNT(*) AS instructor_count FROM instructors";
$instructor_count_result = $conn->query($instructor_count_query);
$instructor_count_row = $instructor_count_result->fetch_assoc();
$instructor_count = $instructor_count_row['instructor_count'];
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

    <!-- Inner page content -->
    <div class="page-category">

      <!-- Stats Cards Row -->
      <div class="row">
        <div class="col-sm-6 col-md-3">
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
                    <p class="card-category">Students</p>
                    <h4 class="card-title"><?php echo $student_count; ?></h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-md-3">
          <div class="card card-stats card-info card-round">
            <div class="card-body">
              <div class="row">
                <div class="col-5">
                  <div class="icon-big text-center">
                    <i class="fas fa-user-tie"></i>
                  </div>
                </div>
                <div class="col-7 col-stats">
                  <div class="numbers">
                    <p class="card-category">Instructors</p>
                    <h4 class="card-title"><?php echo $instructor_count; ?></h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-md-3">
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
                    <p class="card-category">Vehicles (X)</p>
                    <h4 class="card-title">28</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
        <div class="col-sm-6 col-md-3">
          <div class="card card-stats card-warning card-round">
            <div class="card-body">
              <div class="row">
                <div class="col-5">
                  <div class="icon-big text-center">
                    <i class="fas fa-calendar-check"></i>
                  </div>
                </div>
                <div class="col-7 col-stats">
                  <div class="numbers">
                    <p class="card-category">Sessions Today (X)</p>
                    <h4 class="card-title">36</h4>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>


      <div class="row">


        <!-- Upcoming Sessions -->
        <div class="col-md-8">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Today's Sessions (X)</div>
              <div class="card-category">March 17, 2025</div>
            </div>
            <div class="card-body pb-0">
              <div class="table-responsive">
                <table class="table table-hover">
                  <thead>
                    <tr>
                      <th>Time</th>
                      <th>Student</th>
                      <th>Instructor</th>
                      <th>Vehicle</th>
                      <th>Status</th>
                    </tr>
                  </thead>
                  <tbody>
                    <tr>
                      <td>09:00 AM</td>
                      <td>James Wilson</td>
                      <td>Robert Clark</td>
                      <td>Toyota C-HR</td>
                      <td><span class="badge badge-success">Completed</span></td>
                    </tr>
                    <tr>
                      <td>10:30 AM</td>
                      <td>Emily Davis</td>
                      <td>Maria Rodriguez</td>
                      <td>Honda Civic</td>
                      <td><span class="badge badge-success">Completed</span></td>
                    </tr>
                    <tr>
                      <td>01:00 PM</td>
                      <td>Alex Johnson</td>
                      <td>David Smith</td>
                      <td>Ford Focus</td>
                      <td><span class="badge badge-info">In Progress</span></td>
                    </tr>
                    <tr>
                      <td>02:30 PM</td>
                      <td>Lisa Brown</td>
                      <td>John Anderson</td>
                      <td>Toyota Corolla</td>
                      <td><span class="badge badge-warning">Upcoming</span></td>
                    </tr>
                    <tr>
                      <td>04:00 PM</td>
                      <td>Mark Taylor</td>
                      <td>Sarah Miller</td>
                      <td>Hyundai i30</td>
                      <td><span class="badge badge-warning">Upcoming</span></td>
                    </tr>
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <!-- Notification Card -->
        <div class="col-md-4">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Recent Notifications</div>
            </div>
            <div class="card-body">
              <div class="notification-list">
                <?php
                if ($notification_result->num_rows > 0) {
                  while ($notification = $notification_result->fetch_assoc()) {
                    echo '<div class="notification-item">';
                    echo '<h6 class="notification-heading fw-bold mb-1">' . htmlspecialchars($notification['title']) . '</h6>';
                    echo '<div class="notification-time text-muted"><small>' . date('F j, Y, g:i a', strtotime($notification['created_at'])) . '</small></div>';
                    echo '<p class="notification-text">' . htmlspecialchars($notification['description']) . '</p>';
                    echo '</div>';
                    echo '<div class="separator-dashed"></div>';
                  }
                } else {
                  echo '<div class="notification-item">';
                  echo '<p class="notification-text">No announcements yet.</p>';
                  echo '</div>';
                }
                ?>
                <div class="d-flex justify-content-center mt-3">
                  <a href="/pages/admin/manage_announcement/list_announcement.php" class="btn btn-sm btn-primary">See More</a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Revenue & Student Progress -->
      <div class="row">
        <!-- Revenue Chart -->
        <div class="col-md-8">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Monthly Revenue (X)</div>
            </div>
            <div class="card-body">
              <div class="chart-container">
                <canvas id="revenueChart" style="width: 100%; height: 250px"></canvas>
              </div>
              <div class="mt-3 d-flex justify-content-between">
                <div>
                  <h6>Total This Month</h6>
                  <h4>$42,589</h4>
                </div>
                <div>
                  <h6>Compared to Last Month</h6>
                  <h4 class="text-success">+12.5%</h4>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Student Progress -->
        <div class="col-md-4">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Student Progress (X)</div>
            </div>
            <div class="card-body">
              <div class="progress-card">
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted">Theory Test Pass Rate</span>
                  <span class="text-muted fw-bold"> 78%</span>
                </div>
                <div class="progress mb-3" style="height: 7px;">
                  <div class="progress-bar bg-success" role="progressbar" style="width: 78%" aria-valuenow="78" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="78%"></div>
                </div>
              </div>
              <div class="progress-card">
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted">Practical Test Pass Rate</span>
                  <span class="text-muted fw-bold"> 62%</span>
                </div>
                <div class="progress mb-3" style="height: 7px;">
                  <div class="progress-bar bg-info" role="progressbar" style="width: 62%" aria-valuenow="62" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="62%"></div>
                </div>
              </div>
              <div class="progress-card">
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted">Course Completion Rate</span>
                  <span class="text-muted fw-bold"> 85%</span>
                </div>
                <div class="progress mb-3" style="height: 7px;">
                  <div class="progress-bar bg-primary" role="progressbar" style="width: 85%" aria-valuenow="85" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="85%"></div>
                </div>
              </div>
              <div class="progress-card">
                <div class="d-flex justify-content-between mb-1">
                  <span class="text-muted">Student Satisfaction</span>
                  <span class="text-muted fw-bold"> 94%</span>
                </div>
                <div class="progress mb-3" style="height: 7px;">
                  <div class="progress-bar bg-warning" role="progressbar" style="width: 94%" aria-valuenow="94" aria-valuemin="0" aria-valuemax="100" data-toggle="tooltip" data-placement="top" title="94%"></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <!-- Quick Access Buttons -->
      <div class="row">
        <div class="col-md-12">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Quick Access (X)</div>
            </div>
            <div class="card-body">
              <div class="row">
                <div class="col-6 col-md-3 mb-3">
                  <a href="#" class="btn btn-primary btn-block btn-round">
                    <i class="fas fa-user-plus mr-2"></i> New Student
                  </a>
                </div>
                <div class="col-6 col-md-3 mb-3">
                  <a href="#" class="btn btn-info btn-block btn-round">
                    <i class="fas fa-calendar-plus mr-2"></i> Schedule Session
                  </a>
                </div>
                <div class="col-6 col-md-3 mb-3">
                  <a href="#" class="btn btn-success btn-block btn-round">
                    <i class="fas fa-dollar-sign mr-2"></i> Process Payment
                  </a>
                </div>
                <div class="col-6 col-md-3 mb-3">
                  <a href="#" class="btn btn-warning btn-block btn-round">
                    <i class="fas fa-file-alt mr-2"></i> Generate Reports
                  </a>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>

<script>
  // Revenue Chart
  document.addEventListener("DOMContentLoaded", function() {
    var revenueCtx = document.getElementById('revenueChart').getContext('2d');
    var revenueChart = new Chart(revenueCtx, {
      type: 'line',
      data: {
        labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
        datasets: [{
          label: 'Revenue',
          data: [25000, 27500, 28900, 31200, 35600, 38200, 36800, 39500, 41200, 42500, 40800, 42589],
          borderColor: '#1572E8',
          backgroundColor: 'rgba(21, 114, 232, 0.1)',
          borderWidth: 2,
          fill: true,
          tension: 0.4
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: {
            display: false
          }
        },
        scales: {
          y: {
            beginAtZero: false,
            grid: {
              drawBorder: false
            },
            ticks: {
              callback: function(value) {
                return '$' + value.toLocaleString();
              }
            }
          },
          x: {
            grid: {
              display: false
            }
          }
        }
      }
    });
  });
</script>

<?php
include '../../include/footer.html';
?>