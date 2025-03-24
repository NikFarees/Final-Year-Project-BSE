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

// Fetch available licenses from the database
$sql = "SELECT license_id, license_name, license_type, description, license_fee FROM licenses";
$result = $conn->query($sql);

// Store licenses in an array
$licenses = [];
while ($row = $result->fetch_assoc()) {
    $licenses[] = $row;
}

// Check if user role is 'student' (assuming role_id for student is 'student')
if ($user['role_id'] != 'student') {
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
    <link rel="stylesheet" href="/assets/css/plugins.css" />
    <link rel="stylesheet" href="/assets/css/kaiadmin.min.css" />

    <style>
        /* Make indicators (bullet points) bigger and visible */
        .carousel-indicators button {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background-color: #007bff;
            border: none;
        }

        .carousel-indicators .active {
            background-color: #0056b3;
        }
    </style>
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
                            <a href="/pages/student/dashboard.php">
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

                        <li class="nav-item <?php echo in_array(basename($_SERVER['PHP_SELF']), ['list_license.php']) ? 'active' : ''; ?>">
                            <a href="/pages/student/book_license/list_license.php">
                                <i class="fas fa-layer-group"></i>
                                <p>Book License</p>
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
                                        <span class="op-7">Hi,</span>
                                        <span class="fw-bold">Hizrian</span>
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
                                                    <h4>Hizrian</h4>
                                                    <p class="text-muted">hello@example.com</p>
                                                </div>
                                            </div>
                                        </li>
                                        <li>
                                            <div class="dropdown-divider"></div>
                                            <a class="dropdown-item" href="#">Account Setting</a>
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


                        <div class="container mt-4">
                            <div class="row justify-content-center">
                                <div class="col-lg-8">
                                    <div class="card shadow-lg">
                                        <div class="card-header bg-primary text-white text-center">
                                            <h4>Available Licenses</h4>
                                        </div>
                                        <div class="card-body">
                                            <!-- Bootstrap Carousel -->
                                            <div id="licenseCarousel" class="carousel slide" data-bs-ride="false">
                                                <!-- Indicators (Bullet Points) -->
                                                <div class="carousel-indicators">
                                                    <?php foreach ($licenses as $index => $license): ?>
                                                        <button type="button" data-bs-target="#licenseCarousel" data-bs-slide-to="<?php echo $index; ?>" class="<?php echo $index === 0 ? 'active' : ''; ?>"></button>
                                                    <?php endforeach; ?>
                                                </div>

                                                <div class="carousel-inner">
                                                    <?php if (count($licenses) > 0): ?>
                                                        <?php foreach ($licenses as $index => $license): ?>
                                                            <div class="carousel-item <?php echo $index === 0 ? 'active' : ''; ?>">
                                                                <div class="text-center p-4">
                                                                    <h5 class="text-primary"><?php echo htmlspecialchars($license['license_name']); ?></h5>
                                                                    <p><strong>Type:</strong> <?php echo htmlspecialchars($license['license_type']); ?></p>
                                                                    <p><?php echo htmlspecialchars($license['description']); ?></p>
                                                                    <p><strong>Fee:</strong> $<?php echo number_format($license['license_fee'], 2); ?></p>
                                                                </div>
                                                            </div>
                                                        <?php endforeach; ?>
                                                    <?php else: ?>
                                                        <div class="carousel-item active">
                                                            <div class="text-center p-4">
                                                                <h5 class="text-danger">No Licenses Available</h5>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="card-footer text-center text-muted">
                                            <small>Swipe left/right to browse licenses</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        








                    </div>

                </div>
            </div>


            <footer class="footer">
                <div class="container-fluid d-flex justify-content-between">
                    <nav class="pull-left">
                        <ul class="nav">
                            <li class="nav-item">
                                <a class="nav-link" href="http://www.themekita.com">
                                    ThemeKita
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#"> Help </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="#"> Licenses </a>
                            </li>
                        </ul>
                    </nav>
                    <div class="copyright">
                        2024, made with <i class="fa fa-heart heart text-danger"></i> by
                        <a href="http://www.themekita.com">Nik Farees</a>
                    </div>
                    <div>
                        Made for
                        <a target="_blank" href="https://themewagon.com/">UniKL MIIT</a>.
                    </div>
                </div>
            </footer>
        </div>
    </div>

    <!-- Add jQuery and touchSwipe plugin -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery.touchswipe/1.6.19/jquery.touchSwipe.min.js"></script>

    <script>
                            $(document).ready(function() {
                                $("#licenseCarousel").swipe({
                                    swipeLeft: function() {
                                        $("#licenseCarousel").carousel("next");
                                    },
                                    swipeRight: function() {
                                        $("#licenseCarousel").carousel("prev");
                                    },
                                    threshold: 50
                                });
                            });
                        </script>

    <!--   Core JS Files   -->
    <script src="/assets/js/core/jquery-3.7.1.min.js"></script>
    <script src="/assets/js/core/popper.min.js"></script>
    <script src="/assets/js/core/bootstrap.min.js"></script>

    <!-- Owl Carousel JS -->
    <script src="/assets/js/plugin/owl-carousel/owl.carousel.min.js"></script>

    <!-- jQuery Scrollbar -->
    <script src="/assets/js/plugin/jquery-scrollbar/jquery.scrollbar.min.js"></script>

    <!-- Chart JS -->
    <script src="/assets/js/plugin/chart.js/chart.min.js"></script>

    <!-- jQuery Sparkline -->
    <script src="/assets/js/plugin/jquery.sparkline/jquery.sparkline.min.js"></script>

    <!-- Chart Circle -->
    <script src="/assets/js/plugin/chart-circle/circles.min.js"></script>

    <!-- Datatables -->
    <script src="/assets/js/plugin/datatables/datatables.min.js"></script>

    <!-- Bootstrap Notify -->
    <script src="/assets/js/plugin/bootstrap-notify/bootstrap-notify.min.js"></script>

    <!-- jQuery Vector Maps -->
    <script src="/assets/js/plugin/jsvectormap/jsvectormap.min.js"></script>
    <script src="/assets/js/plugin/jsvectormap/world.js"></script>

    <!-- Google Maps Plugin -->
    <script src="/assets/js/plugin/gmaps/gmaps.js"></script>

    <!-- Sweet Alert -->
    <script src="/assets/js/plugin/sweetalert/sweetalert.min.js"></script>

    <!-- Kaiadmin JS -->
    <script src="/assets/js/kaiadmin.min.js"></script>
</body>

</html>