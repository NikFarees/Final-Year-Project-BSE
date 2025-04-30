<?php
session_start();
include __DIR__ . '/../config.php'; // Include the configuration file
include BASE_DIR . '/database/db_connection.php'; // Use BASE_DIR to include the database connection file

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
  header("Location: /authentication/login_frontend.php");
  exit;
}

// Assuming the user's ID is stored in the session
$user_id = $_SESSION['user_id'];

// Fetch user details and role description from the database
$sql = "SELECT u.name, u.email, r.description AS role_description, u.role_id 
        FROM users u 
        JOIN roles r ON u.role_id = r.role_id 
        WHERE u.user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_id); // Use 's' for string binding as user_id is VARCHAR
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Check if user role is 'instructor' (assuming role_id for instructor is 'instructor')
if ($user['role_id'] != 'instructor') {
  // Log out the user and redirect to login page
  session_destroy();
  header("Location: ../../authentication/login_frontend.php");
  exit;
}

// User data is available for use
$name = htmlspecialchars($user['name']);
$email = htmlspecialchars($user['email']);
$role_description = htmlspecialchars($user['role_description']);

// Close the database connection
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <title>Kaiadmin - Bootstrap 5 Admin Dashboard</title>
  <meta
    content="width=device-width, initial-scale=1.0, shrink-to-fit=no"
    name="viewport" />
  <link
    rel="icon"
    href="/assets/img/kaiadmin/favicon.ico"
    type="image/x-icon" />

  <!-- Fonts and icons -->
  <script src="/assets/js/plugin/webfont/webfont.min.js"></script>
  <script>
    WebFont.load({
      google: {
        families: ["Public Sans:300,400,500,600,700"]
      },
      custom: {
        families: [
          "Font Awesome 5 Solid",
          "Font Awesome 5 Regular",
          "Font Awesome 5 Brands",
          "simple-line-icons",
        ],
        urls: ["/assets/css/fonts.min.css"],
      },
      active: function() {
        sessionStorage.fonts = true;
      },
    });
  </script>

  <!-- CSS Files -->
  <link rel="stylesheet" href="/assets/css/bootstrap.min.css" />
  <link rel="stylesheet" href="/assets/css/plugins.min.css" />
  <link rel="stylesheet" href="/assets/css/kaiadmin.min.css" />
</head>

<body>
  <div class="wrapper">
    <!-- Sidebar -->
    <div class="sidebar" data-background-color="dark">
      <div class="sidebar-logo">
        <!-- Logo Header -->
        <div class="logo-header" data-background-color="dark">
          <a href="index.html" class="logo">
            <img
              src="/assets/img/kaiadmin/logo_light.svg"
              alt="navbar brand"
              class="navbar-brand"
              height="20" />
          </a>
          <div class="nav-toggle">
            <button class="btn btn-toggle toggle-sidebar">
              <i class="gg-menu-right"></i>
            </button>
            <button class="btn btn-toggle sidenav-toggler">
              <i class="gg-menu-left"></i>
            </button>
          </div>
          <button class="topbar-toggler more">
            <i class="gg-more-vertical-alt"></i>
          </button>
        </div>
        <!-- End Logo Header -->
      </div>
      <div class="sidebar-wrapper scrollbar scrollbar-inner">
        <div class="sidebar-content">
          <ul class="nav nav-secondary">
            <li class="nav-item <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
              <a href="/pages/instructor/dashboard.php">
                <i class="fas fa-home"></i>
                <p>Dashboard</p>
              </a>
            </li>

            <li class="nav-section">
              <span class="sidebar-mini-icon">
                <i class="fa fa-ellipsis-h"></i>
              </span>
              <h4 class="text-section">Components</h4>
            </li>

            <!-- Manage Student -->
            <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/pages/instructor/manage_student/') !== false ? 'active' : ''; ?>">
              <a href="/pages/instructor/manage_student/list_student.php">
                <i class="fas fa-layer-group"></i>
                <p>Manage Student</p>
              </a>
            </li>

            <!-- Manage Lesson -->
            <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/pages/instructor/manage_lesson/') !== false ? 'active' : ''; ?>">
              <a href="/pages/instructor/manage_lesson/list_lesson.php">
                <i class="fas fa-layer-group"></i>
                <p>Manage Lesson</p>
              </a>
            </li>

            <!-- Manage Test -->
            <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/pages/instructor/manage_test/') !== false ? 'active' : ''; ?>">
              <a href="/pages/instructor/manage_test/list_test.php">
                <i class="fas fa-layer-group"></i>
                <p>Manage Test</p>
              </a>
            </li>

            <!-- My Schedule -->
            <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/pages/instructor/manage_schedule/') !== false ? 'active' : ''; ?>">
              <a href="/pages/instructor/manage_schedule/view_schedule.php">
                <i class="fas fa-layer-group"></i>
                <p>My Schedule</p>
              </a>
            </li>

            <!-- My Speciality -->
            <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/pages/instructor/manage_speciality/') !== false ? 'active' : ''; ?>">
              <a href="/pages/instructor/manage_speciality/view_speciality.php">
                <i class="fas fa-layer-group"></i>
                <p>My Speciality</p>
              </a>
            </li>

            <!-- My Feedback -->
            <li class="nav-item <?php echo strpos($_SERVER['PHP_SELF'], '/pages/instructor/manage_feedback/') !== false ? 'active' : ''; ?>">
              <a href="/pages/instructor/manage_feedback/list_feedback.php">
                <i class="fas fa-layer-group"></i>
                <p>My Feedback</p>
              </a>
            </li>
          </ul>
        </div>
      </div>
    </div>
    <!-- End Sidebar -->

    <div class="main-panel">
      <div class="main-header">
        <div class="main-header-logo">
          <!-- Logo Header -->
          <div class="logo-header" data-background-color="dark">
            <a href="index.html" class="logo">
              <img
                src="assets/img/logo_KMSE.jpeg"
                alt="navbar brand"
                class="navbar-brand"
                height="20" />
            </a>
            <div class="nav-toggle">
              <button class="btn btn-toggle toggle-sidebar">
                <i class="gg-menu-right"></i>
              </button>
              <button class="btn btn-toggle sidenav-toggler">
                <i class="gg-menu-left"></i>
              </button>
            </div>
            <button class="topbar-toggler more">
              <i class="gg-more-vertical-alt"></i>
            </button>
          </div>
          <!-- End Logo Header -->
        </div>
        <!-- Navbar Header -->
        <nav
          class="navbar navbar-header navbar-header-transparent navbar-expand-lg border-bottom">
          <div class="container-fluid">
            <ul class="navbar-nav topbar-nav ms-md-auto align-items-center">
              <li class="nav-item topbar-user dropdown hidden-caret">
                <a
                  class="dropdown-toggle profile-pic"
                  data-bs-toggle="dropdown"
                  href="#"
                  aria-expanded="false">
                  <div class="avatar-sm">
                    <img
                      src="/assets/img/profile.jpg"
                      alt="..."
                      class="avatar-img rounded-circle" />
                  </div>
                  <span class="profile-username">
                    <span class="op-7">Hi instructor,</span>
                    <span class="fw-bold"><?php echo $name; ?></span>
                  </span>
                </a>
                <ul class="dropdown-menu dropdown-user animated fadeIn">
                  <div class="dropdown-user-scroll scrollbar-outer">
                    <li>
                      <div class="user-box">
                        <div class="avatar-lg">
                          <img
                            src="/assets/img/profile.jpg"
                            alt="image profile"
                            class="avatar-img rounded" />
                        </div>
                        <div class="u-text">
                          <h4><?php echo $name; ?></h4>
                          <p class="text-muted"><?php echo $email; ?></p>
                        </div>
                      </div>
                    </li>
                    <li>
                      <div class="dropdown-divider"></div>
                      <a class="dropdown-item" href="/pages/instructor/manage_profile/view_profile.php">Profile</a>
                      <div class="dropdown-divider"></div>
                      <a class="dropdown-item" href="/pages/instructor/manage_announcement/list_announcement.php">Announcement</a>
                      <div class="dropdown-divider"></div>
                      <a class="dropdown-item" href="/authentication/logout.php">Logout</a>
                    </li>
                  </div>
                </ul>
              </li>
            </ul>
          </div>
        </nav>
        <!-- End Navbar -->
      </div>