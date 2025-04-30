<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Fetch instructor data
$instructor_query = "
    SELECT 
        i.instructor_id, 
        u.name, 
        GROUP_CONCAT(CONCAT(l.license_name, ' (', l.license_type, ')') SEPARATOR ', ') AS specialties
    FROM 
        instructors i
    JOIN 
        users u ON i.user_id = u.user_id
    LEFT JOIN 
        specialities s ON i.instructor_id = s.instructor_id
    LEFT JOIN 
        licenses l ON s.license_id = l.license_id
    GROUP BY 
        i.instructor_id, u.name
";
$instructor_result = $conn->query($instructor_query);
$instructors = $instructor_result->fetch_all(MYSQLI_ASSOC);

// Fetch student data
$student_query = "
    SELECT 
        s.student_id, 
        u.name, 
        GROUP_CONCAT(CONCAT(l.license_name, ' (', l.license_type, ')') SEPARATOR ', ') AS licenses_enrolled
    FROM 
        students s
    JOIN 
        users u ON s.user_id = u.user_id
    LEFT JOIN 
        student_licenses sl ON s.student_id = sl.student_id
    LEFT JOIN 
        licenses l ON sl.license_id = l.license_id
    GROUP BY 
        s.student_id, u.name
";
$student_result = $conn->query($student_query);
$students = $student_result->fetch_all(MYSQLI_ASSOC);
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Manage User</h4>
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
          <a href="#">User List</a>
        </li>
      </ul>
    </div>

    <!-- Inner page content -->
    <div class="page-category">
      <div class="col-md-12">
        <div class="card">
          <div class="card-header d-flex justify-content-between align-items-center">
            <div class="card-title">User List</div>
            <!-- Dynamic button -->
            <div class="d-flex align-items-center">
              <div id="dynamic-button-container" class="mr-3">
                <a href="add_instructor.php" class="btn btn-primary">Add Instructor</a>
              </div>
              <!-- Add minimize button -->
              <button type="button" class="btn btn-sm btn-outline-secondary" id="user-toggle-btn">
                <i class="fas fa-minus"></i>
              </button>
            </div>
          </div>
          <div class="card-body" id="user-card-body">
            <!-- Toggle Cards -->
            <div class="row mb-4">
              <div class="col-md-6">
                <div class="card card-stats card-round toggle-card active" data-target="instructors-table-container">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-5">
                        <div class="icon-big text-center">
                          <i class="fas fa-chalkboard-teacher text-primary"></i>
                        </div>
                      </div>
                      <div class="col-7 col-stats">
                        <div class="numbers">
                          <p class="card-category">Instructors</p>
                          <h4 class="card-title"><?php echo count($instructors); ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="card card-stats card-round toggle-card" data-target="students-table-container">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-5">
                        <div class="icon-big text-center">
                          <i class="fas fa-user-graduate text-success"></i>
                        </div>
                      </div>
                      <div class="col-7 col-stats">
                        <div class="numbers">
                          <p class="card-category">Students</p>
                          <h4 class="card-title"><?php echo count($students); ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Instructors Table -->
            <div class="table-container" id="instructors-table-container">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="fas fa-chalkboard-teacher text-primary"></i> Instructor List</h4>
              </div>
              <div class="table-responsive">
                <table id="instructors-table" class="table table-striped table-bordered ">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Name</th>
                      <th>Specialties</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if (count($instructors) > 0) {
                      $counter = 1;
                      foreach ($instructors as $instructor) {
                        echo "<tr>";
                        echo "<td>" . $counter++ . "</td>";
                        echo "<td>" . htmlspecialchars($instructor['name']) . "</td>";
                        echo "<td>" . (!empty($instructor['specialties']) ? htmlspecialchars($instructor['specialties']) : "None") . "</td>";
                        echo "<td class='action-buttons'>
                                <a href='view_instructor.php?id=" . htmlspecialchars($instructor['instructor_id']) . "' class='btn btn-primary btn-sm mr-1'><i class='fas fa-eye'></i> View</a>
                                <a href='edit_instructor.php?id=" . htmlspecialchars($instructor['instructor_id']) . "' class='btn btn-info btn-sm mr-1'><i class='fas fa-edit'></i> Edit</a>
                                <a href='delete_instructor.php?id=" . htmlspecialchars($instructor['instructor_id']) . "' class='btn btn-danger btn-sm'><i class='fas fa-trash'></i> Delete</a>
                              </td>";
                        echo "</tr>";
                      }
                    } else {
                      echo "<tr><td colspan='4' class='text-center'>No instructors found.</td></tr>";
                    }
                    ?>
                  </tbody>
                </table>
              </div>
            </div>

            <!-- Students Table -->
            <div class="table-container" id="students-table-container" style="display: none;">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="fas fa-user-graduate text-success"></i> Student List</h4>
              </div>
              <div class="table-responsive">
                <table id="students-table" class="table table-striped table-bordered ">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Name</th>
                      <th>Licenses Enrolled</th>
                      <th>Actions</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if (count($students) > 0) {
                      $counter = 1;
                      foreach ($students as $student) {
                        echo "<tr>";
                        echo "<td>" . $counter++ . "</td>";
                        echo "<td>" . htmlspecialchars($student['name']) . "</td>";
                        echo "<td>" . (!empty($student['licenses_enrolled']) ? htmlspecialchars($student['licenses_enrolled']) : "None") . "</td>";
                        echo "<td class='action-buttons'>
                                <a href='view_student.php?id=" . htmlspecialchars($student['student_id']) . "' class='btn btn-primary btn-sm mr-1'><i class='fas fa-eye'></i> View</a>
                                <a href='edit_student.php?id=" . htmlspecialchars($student['student_id']) . "' class='btn btn-info btn-sm mr-1'><i class='fas fa-edit'></i> Edit</a>
                                <a href='delete_student.php?id=" . htmlspecialchars($student['student_id']) . "' class='btn btn-danger btn-sm'><i class='fas fa-trash'></i> Delete</a>
                              </td>";
                        echo "</tr>";
                      }
                    } else {
                      echo "<tr><td colspan='4' class='text-center'>No students found.</td></tr>";
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

<?php
include '../../../include/footer.html';
?>

<script>
  $(document).ready(function() {
    $("#instructors-table").DataTable({});
  });

  $(document).ready(function() {
    $("#students-table").DataTable({});
  });

  $(document).ready(function() {
    // Add click event for the toggle cards
    $('.toggle-card').on('click', function() {
      // Remove active class from all cards
      $('.toggle-card').removeClass('active');

      // Add active class to clicked card
      $(this).addClass('active');

      // Hide all tables
      $('.table-container').hide();

      // Show the table corresponding to the clicked card
      $('#' + $(this).data('target')).show();
    });
  });

  $(document).ready(function() {
    // Add visual feedback when hovering over cards
    $('.toggle-card').hover(
      function() {
        if (!$(this).hasClass('active')) {
          $(this).css('cursor', 'pointer');
          $(this).addClass('shadow-sm');
        }
      },
      function() {
        $(this).removeClass('shadow-sm');
      }
    );
  });

  document.addEventListener('DOMContentLoaded', function() {
    // Update the dynamic button based on the active card
    document.querySelectorAll('.toggle-card').forEach(function(card) {
      card.addEventListener('click', function() {
        const dynamicButtonContainer = document.getElementById('dynamic-button-container');

        // Check which card is active and update the button
        if (this.getAttribute('data-target') === 'instructors-table-container') {
          dynamicButtonContainer.innerHTML = '<a href="add_instructor.php" class="btn btn-primary">Add Instructor</a>';
        } else if (this.getAttribute('data-target') === 'students-table-container') {
          dynamicButtonContainer.innerHTML = '<a href="add_student.php" class="btn btn-primary">Add Student</a>';
        }
      });
    });
  });

  $(document).ready(function() {
    // Toggle user card content visibility
    $('#user-toggle-btn').click(function() {
      var cardBody = $('#user-card-body');

      // Remove transition property to avoid conflicts
      cardBody.css('transition', 'none');

      // Use jQuery's slideToggle with a specified duration
      cardBody.slideToggle(300);

      // Toggle the icon
      var icon = $(this).find('i');
      if (icon.hasClass('fa-minus')) {
        icon.removeClass('fa-minus').addClass('fa-plus');
      } else {
        icon.removeClass('fa-plus').addClass('fa-minus');
      }
    });
  });
</script>

<style>
  .toggle-card {
    transition: all 0.3s ease;
    cursor: pointer;
  }

  .toggle-card.active {
    border-bottom: 3px solid #1572E8;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
  }

  .toggle-card:hover:not(.active) {
    transform: translateY(-5px);
  }

  .table-container {
    transition: all 0.3s ease;
  }
  
  /* Button spacing */
  .mr-1 {
    margin-right: 0.25rem;
  }
  
  .mr-3 {
    margin-right: 1rem;
  }
  
  /* Card body transition handling */
  #user-card-body {
    transition: none;
  }
  
  /* Card header styling */
  .card-header {
    padding: 0.75rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
  }
  
  .card-title {
    margin-bottom: 0;
  }
  
  /* Action buttons styling */
  .action-buttons {
    white-space: nowrap;
  }
</style>