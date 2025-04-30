<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Fetch instructor data
$instructor_query = "
    SELECT 
        i.instructor_id, 
        u.name 
    FROM 
        instructors i
    JOIN 
        users u ON i.user_id = u.user_id
";
$instructor_result = $conn->query($instructor_query);

// Fetch student data
$student_query = "
    SELECT 
        s.student_id, 
        u.name 
    FROM 
        students s
    JOIN 
        users u ON s.user_id = u.user_id
";
$student_result = $conn->query($student_query);

// Count number of records in each category
$instructorCount = mysqli_num_rows($instructor_result);
$studentCount = mysqli_num_rows($student_result);
?>

<div class="container">
  <div class="page-inner">

    <!-- Breadcrumbs -->
    <div class="page-header">
      <h4 class="page-title">Manage Schedule</h4>
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
            <button type="button" class="btn btn-sm btn-outline-secondary" id="user-toggle-btn">
              <i class="fas fa-minus"></i>
            </button>
          </div>
          <div class="card-body" id="user-card-body">
            <!-- Toggle Cards -->
            <div class="row mb-4">
              <div class="col-md-6">
                <div class="card card-stats card-round toggle-card active" data-target="instructors-container">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-5">
                        <div class="icon-big text-center">
                          <i class="fas fa-chalkboard-teacher text-primary"></i>
                        </div>
                      </div>
                      <div class="col-7 col-stats">
                        <div class="numbers">
                          <p class="card-category">Instructor List</p>
                          <h4 class="card-title"><?php echo $instructorCount; ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div class="col-md-6">
                <div class="card card-stats card-round toggle-card" data-target="students-container">
                  <div class="card-body">
                    <div class="row">
                      <div class="col-5">
                        <div class="icon-big text-center">
                          <i class="fas fa-user-graduate text-success"></i>
                        </div>
                      </div>
                      <div class="col-7 col-stats">
                        <div class="numbers">
                          <p class="card-category">Student List</p>
                          <h4 class="card-title"><?php echo $studentCount; ?></h4>
                        </div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <!-- Instructor List -->
            <div class="table-container" id="instructors-container">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="fas fa-chalkboard-teacher text-primary"></i> Instructor List</h4>
              </div>
              <div class="table-responsive">
                <table id="instructors-table" class="table table-striped table-bordered ">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Instructor ID</th>
                      <th>Name</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if ($instructor_result->num_rows > 0) {
                      mysqli_data_seek($instructor_result, 0); // Reset pointer
                      $counter = 1; // Initialize counter
                      while ($instructor = $instructor_result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $counter++ . "</td>"; // Counter column
                        echo "<td>" . htmlspecialchars($instructor['instructor_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($instructor['name']) . "</td>";
                        echo "<td>
                                <a href='view_schedule_instructor.php?id=" . htmlspecialchars($instructor['instructor_id']) . "' class='btn btn-sm btn-primary'>
                                  <i class='fas fa-calendar-alt mr-1'></i> View Schedule
                                </a>
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

            <!-- Student List -->
            <div class="table-container" id="students-container" style="display: none;">
              <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0"><i class="fas fa-user-graduate text-success"></i> Student List</h4>
              </div>
              <div class="table-responsive">
                <table id="students-table" class="table table-striped table-bordered ">
                  <thead>
                    <tr>
                      <th>#</th>
                      <th>Student ID</th>
                      <th>Name</th>
                      <th>Action</th>
                    </tr>
                  </thead>
                  <tbody>
                    <?php
                    if ($student_result->num_rows > 0) {
                      mysqli_data_seek($student_result, 0); // Reset pointer
                      $counter = 1; // Initialize counter
                      while ($student = $student_result->fetch_assoc()) {
                        echo "<tr>";
                        echo "<td>" . $counter++ . "</td>"; // Counter column
                        echo "<td>" . htmlspecialchars($student['student_id']) . "</td>";
                        echo "<td>" . htmlspecialchars($student['name']) . "</td>";
                        echo "<td>
                                <a href='view_schedule_student.php?id=" . htmlspecialchars($student['student_id']) . "' class='btn btn-sm btn-success'>
                                  <i class='fas fa-calendar-alt mr-1'></i> View Schedule
                                </a>
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
    // Initialize DataTables
    $("#instructors-table").DataTable({});
    $("#students-table").DataTable({});
    
    // Toggle card content visibility
    $('#user-toggle-btn').click(function() {
      var cardBody = $('#user-card-body');
      cardBody.css('transition', 'none');
      cardBody.slideToggle(300);
      
      var icon = $(this).find('i');
      if (icon.hasClass('fa-minus')) {
        icon.removeClass('fa-minus').addClass('fa-plus');
      } else {
        icon.removeClass('fa-plus').addClass('fa-minus');
      }
    });

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
  /* Button spacing */
  .mr-1 {
    margin-right: 0.25rem;
  }
  
  /* Card body transition handling */
  #user-card-body {
    transition: none;
  }
</style>