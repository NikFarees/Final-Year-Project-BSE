<?php
include '../../include/st_header.php';
include '../../database/db_connection.php';

// Assume $current_user_id is the ID of the currently logged-in user
$current_user_id = $_SESSION['user_id'];

// Fetch announcements for the current student
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
        (ra.role_id = 'student' OR ua.user_id = ?)
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

// Fetch license types from the database
$licenseQuery = "
    SELECT 
        license_id,
        license_name,
        license_type,
        license_fee
    FROM 
        licenses
";
$licenseResult = $conn->query($licenseQuery);
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
      <!-- Row 2: Notifications -->
      <div class="row mb-4">
        <!-- Column 1: Available License Types (slider) -->
        <div class="col-md-8">
          <div class="card">
            <div class="card-header">
              <div class="card-title">Available License Types</div>
              <div class="card-category">Swipe to view all license types</div>
            </div>
            <div class="card-body">
              <div class="owl-carousel license-carousel owl-theme">
                <?php
                if ($licenseResult->num_rows > 0) {
                  while ($license = $licenseResult->fetch_assoc()) {
                    // Determine the image based on license_type
                    $imagePath = "/assets/img/";
                    if ($license['license_type'] === 'D') {
                      $imagePath .= "car_d.png";
                    } elseif ($license['license_type'] === 'DA') {
                      $imagePath .= "car_da.png";
                    } else {
                      $imagePath .= "default-license.png"; // Fallback image
                    }
                ?>
                    <div class="item">
                      <img
                        class="card-img-top d-block mx-auto"
                        src="<?php echo $imagePath; ?>"
                        alt="License Image"
                        style="width: 300px; height: 300px;" />
                      <div class="separator-solid"></div>
                      <div class="text-center mt-3">
                        <h3 class="card-title"><?php echo htmlspecialchars($license['license_name']); ?> (<?php echo htmlspecialchars($license['license_type']); ?>)</h3>
                        <p class="date text-muted">License Fee: RM<?php echo number_format($license['license_fee'], 2); ?></p>
                        <a href="/pages/student/book_license/list_license.php" class="btn btn-sm btn-primary">View Detail</a>
                      </div>
                    </div>
                <?php
                  }
                } else {
                  echo "<p>No licenses available.</p>";
                }
                ?>
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
              <a href="/pages/student/view_announcement/view_announcement.php" class="btn btn-sm btn-primary">
                See More
              </a>
            </div>
          </div>
        </div>
      </div>

    </div>

    <!-- Row 3: Lesson Class Information and Test Information (both sliders) -->
    <div class="row mb-4">
      <!-- Column 1: Lesson Class Information (slider) -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <div class="card-title">Lesson Types</div>
            <div class="card-category">Swipe to view all lesson types</div>
          </div>
          <div class="card-body">
            <div class="owl-carousel lesson-carousel  owl-theme">
              <!-- Lesson Type Card 1 -->
              <div class="item">
                <div class="card card-success">
                  <div class="card-header">
                    <div class="card-title text-white">Basic Car Control</div>
                  </div>
                  <div class="card-body pb-0">
                    <div class="text-center">
                      <img src="../../../assets/img/lesson-basic.jpg" alt="Basic Car Control" class="img-fluid mb-3" style="height: 120px; object-fit: cover;">
                    </div>
                    <ul class="pl-3">
                      <li>Duration: 2 hours</li>
                      <li>Focus: Vehicle basics</li>
                      <li>Requirement: None</li>
                      <li>Status: <span class="badge badge-success">Completed</span></li>
                    </ul>
                    <div class="text-center mb-3">
                      <button class="btn btn-sm btn-primary">Review Materials</button>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Add more lesson type cards as needed -->
            </div>
          </div>
        </div>
      </div>

      <!-- Column 2: Test Information (slider) -->
      <div class="col-md-6">
        <div class="card">
          <div class="card-header">
            <div class="card-title">Test Type</div>
            <div class="card-category">Swipe to view all tests</div>
          </div>
          <div class="card-body">
            <div class="owl-carousel test-carousel  owl-theme">
              <!-- Test Card 1 -->
              <div class="item">
                <div class="card card-primary">
                  <div class="card-header">
                    <div class="card-title text-white">Theory Test</div>
                  </div>
                  <div class="card-body pb-0">
                    <div class="text-center">
                      <img src="../../../assets/img/test-theory.jpg" alt="Theory Test" class="img-fluid mb-3" style="height: 120px; object-fit: cover;">
                    </div>
                    <ul class="pl-3">
                      <li>Duration: 45 minutes</li>
                      <li>Total questions: 50</li>
                      <li>Pass mark: 43/50</li>
                      <li>Status: <span class="badge badge-primary">Scheduled</span></li>
                      <li>Date: March 25, 2025</li>
                    </ul>
                    <div class="text-center mb-3">
                      <button class="btn btn-sm btn-primary">Practice Test</button>
                      <button class="btn btn-sm btn-warning">Reschedule</button>
                    </div>
                  </div>
                </div>
              </div>
              <!-- Add more test cards as needed -->
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Row 4: Next Steps (for booked license) -->
    <div class="row mb-4">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <div class="card-title">Next Steps for Class B License</div>
            <div class="card-category">Your journey to getting licensed</div>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-12">
                <div class="timeline">
                  <div class="timeline-item">
                    <div class="timeline-badge"><i class="icon-note text-success"></i></div>
                    <div class="timeline-content">
                      <h6 class="text-success">Register for License</h6>
                      <p class="mb-0"><span class="badge badge-success">Completed</span> - March 1, 2025</p>
                    </div>
                  </div>
                  <div class="timeline-item">
                    <div class="timeline-badge"><i class="icon-doc text-success"></i></div>
                    <div class="timeline-content">
                      <h6 class="text-success">Submit Required Documents</h6>
                      <p class="mb-0"><span class="badge badge-success">Completed</span> - March 5, 2025</p>
                    </div>
                  </div>
                  <!-- Add more timeline items as needed -->
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Row 5: My Progress -->
    <div class="row">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header">
            <div class="card-title">My Progress</div>
            <div class="card-category">Track your learning journey</div>
          </div>
          <div class="card-body">
            <div class="row">
              <div class="col-md-6">
                <div class="form-group">
                  <label>Driving Hours</label>
                  <div class="progress-card">
                    <div class="d-flex justify-content-between mb-1">
                      <span class="text-muted">Completed Hours</span>
                      <span class="text-muted fw-bold"> 8/30</span>
                    </div>
                    <div class="progress mb-2" style="height: 7px;">
                      <div class="progress-bar bg-success" role="progressbar" style="width: 27%" aria-valuenow="8" aria-valuemin="0" aria-valuemax="30"></div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-group">
                  <label>Overall License Progress</label>
                  <div class="progress-card">
                    <div class="d-flex justify-content-between mb-1">
                      <span class="text-muted">Completed Steps</span>
                      <span class="text-muted fw-bold"> 2/7</span>
                    </div>
                    <div class="progress mb-2" style="height: 7px;">
                      <div class="progress-bar bg-primary" role="progressbar" style="width: 29%" aria-valuenow="2" aria-valuemin="0" aria-valuemax="7"></div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-md-12">
                <label>Skill Assessment</label>
              </div>
              <div class="col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-5">
                        <div class="icon-big text-center">
                          <i class="icon-control-play text-info"></i>
                        </div>
                      </div>
                      <div class="col-7 col-stats">
                        <div class="numbers">
                          <p class="card-category">Vehicle Control</p>
                          <h4 class="card-title">7/10</h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-5">
                        <div class="icon-big text-center">
                          <i class="icon-eye text-warning"></i>
                        </div>
                      </div>
                      <div class="col-7 col-stats">
                        <div class="numbers">
                          <p class="card-category">Road Awareness</p>
                          <h4 class="card-title">6/10</h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-5">
                        <div class="icon-big text-center">
                          <i class="icon-directions text-success"></i>
                        </div>
                      </div>
                      <div class="col-7 col-stats">
                        <div class="numbers">
                          <p class="card-category">Parking Skills</p>
                          <h4 class="card-title">5/10</h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div class="col-md-3">
                <div class="card card-stats card-round">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-5">
                        <div class="icon-big text-center">
                          <i class="icon-speedometer text-primary"></i>
                        </div>
                      </div>
                      <div class="col-7 col-stats">
                        <div class="numbers">
                          <p class="card-category">Theory Knowledge</p>
                          <h4 class="card-title">8/10</h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="row mt-3">
              <div class="col-md-12">
                <div class="card">
                  <div class="card-header">
                    <div class="card-title">Instructor Feedback</div>
                  </div>
                  <div class="card-body">
                    <div class="d-flex">
                      <div class="avatar">
                        <img src="../../../assets/img/instructor-avatar.jpg" alt="Instructor" class="avatar-img rounded-circle">
                      </div>
                      <div class="flex-1 ml-3 pt-1">
                        <h6 class="fw-bold mb-1">John Smith</h6>
                        <small class="text-muted">From your last lesson on March 10, 2025</small>
                      </div>
                    </div>
                    <div class="separator-dashed my-3"></div>
                    <p class="mb-0">Good progress on basic car control. Need to work on mirror checks and signaling before changing lanes. Parking skills require more practice, especially parallel parking. Overall, you're making good progress but need to focus on maintaining proper speed control.</p>
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
include '../../include/footer.html';
?>

<!-- Initialize the carousels -->
<script>
  $(document).ready(function() {
    $(".license-carousel, .lesson-carousel, .test-carousel").owlCarousel({
      loop: false, // Disable infinite looping
      margin: 10, // Space between items
      dots: true, // Enable dots for navigation
      nav: false, // Remove navigation arrows
      responsive: {
        0: {
          items: 1 // Show 1 item on small screens
        },
        768: {
          items: 1 // Show 1 item on medium screens
        },
        1000: {
          items: 1 // Show 1 item on large screens
        }
      }
    });
  });
</script>