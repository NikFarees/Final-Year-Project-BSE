<?php
include '../../../include/st_header.php';
include '../../../database/db_connection.php';

// Assume $current_user_id is the ID of the currently logged-in user
$current_user_id = $_SESSION['user_id'];

// Fetch announcements for the current student
$query = "
    SELECT 
        a.announcement_id,
        a.title,
        a.description,
        a.created_at,
        admin.name AS admin_name,
        admin.role_id AS admin_role_id
    FROM 
        announcements a
    LEFT JOIN 
        role_announcements ra ON a.announcement_id = ra.announcement_id
    LEFT JOIN 
        user_announcements ua ON a.announcement_id = ua.announcement_id
    LEFT JOIN 
        users admin ON a.created_by = admin.user_id
    WHERE 
        ra.role_id = 'student' OR ua.user_id = ?
    GROUP BY 
        a.announcement_id, a.title, a.description, a.created_at, admin.name, admin.role_id
";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $current_user_id);
$stmt->execute();
$result = $stmt->get_result();
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Announcement</h4>
      <ul class="breadcrumbs">
        <li class="nav-home">
          <a href="/pages/student/dashboard.php">
            <i class="icon-home"></i>
          </a>
        </li>
        <li class="separator">
          <i class="icon-arrow-right"></i>
        </li>
        <li class="nav-item">
          <a href="#">View Announcement</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      <div class="card">
        <div class="card-header">
          <h4 class="card-title">Announcements</h4>
        </div>
        <div class="card-body">
          <?php
          if ($result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
              echo '<div class="card mb-3 announcement-card" style="width: 80%; margin: 0 auto; background-color:rgb(243, 243, 243);">';
              echo '<div class="card-body">';
              echo '<div class="d-flex align-items-center mb-3">';
              echo '<div class="me-3" style="width: 40px; height: 40px; display: flex; align-items: center; justify-content: center; background-color: #e0e0e0; border-radius: 50%;">';
              echo '<i class="fas fa-user" style="font-size: 24px;"></i>';
              echo '</div>';
              echo '<div>';
              echo '<strong>' . htmlspecialchars($row['admin_name']) . '</strong><br>';
              echo htmlspecialchars($row['admin_role_id']);
              echo '</div>';
              echo '<div class="ms-auto">';
              echo '<small>' . htmlspecialchars($row['created_at']) . '</small>';
              echo '</div>';
              echo '</div>';
              echo '<h5 class="card-title">' . htmlspecialchars($row['title']) . '</h5>';
              echo '<p class="card-text">' . htmlspecialchars($row['description']) . '</p>';
              echo '</div>';
              echo '</div>';
              echo '<br>';
            }
          } else {
            echo '<p>No announcements found.</p>';
          }
          ?>
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