<?php
ob_start();
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

function fetchTestTypes($conn)
{
    $query = "SELECT test_id, test_name FROM tests";
    return mysqli_query($conn, $query);
}

// New function to check for scheduling conflicts
function checkSchedulingConflicts($conn, $instructor_id, $test_date, $start_time, $end_time, $current_test_id)
{
    // Check for conflicts with other test sessions
    $test_conflict_sql = "
        SELECT ts.test_session_id, ts.test_date, ts.start_time, ts.end_time, t.test_name, t.test_id
        FROM test_sessions ts
        JOIN tests t ON ts.test_id = t.test_id
        WHERE ts.instructor_id = ? 
        AND ts.test_date = ?
        AND (
            (ts.start_time <= ? AND ts.end_time > ?) OR
            (ts.start_time < ? AND ts.end_time >= ?) OR
            (ts.start_time >= ? AND ts.end_time <= ?)
        )
    ";

    $test_stmt = $conn->prepare($test_conflict_sql);
    $test_stmt->bind_param("ssssssss", $instructor_id, $test_date, $end_time, $start_time, $end_time, $start_time, $start_time, $end_time);
    $test_stmt->execute();
    $test_result = $test_stmt->get_result();

    // Check for conflicts with lessons
    $lesson_conflict_sql = "
        SELECT sl.student_lesson_id, sl.date, sl.start_time, sl.end_time, sl.student_lesson_name
        FROM student_lessons sl
        WHERE sl.instructor_id = ? 
        AND sl.date = ?
        AND (
            (sl.start_time <= ? AND sl.end_time > ?) OR
            (sl.start_time < ? AND sl.end_time >= ?) OR
            (sl.start_time >= ? AND sl.end_time <= ?)
        )
    ";

    $lesson_stmt = $conn->prepare($lesson_conflict_sql);
    $lesson_stmt->bind_param("ssssssss", $instructor_id, $test_date, $end_time, $start_time, $end_time, $start_time, $start_time, $end_time);
    $lesson_stmt->execute();
    $lesson_result = $lesson_stmt->get_result();

    $conflicts = [];

    // Process test session conflicts, excluding TES03-TES04 conflicts
    while ($row = $test_result->fetch_assoc()) {
        // Special case: If current test is TES03 or TES04 and the conflicting test is the other one,
        // we allow this specific overlap
        if (
            ($current_test_id === 'TES03' && $row['test_id'] === 'TES04') ||
            ($current_test_id === 'TES04' && $row['test_id'] === 'TES03')
        ) {
            continue; // Skip this conflict - allow TES03 and TES04 to be scheduled together
        }

        $conflicts[] = [
            'type' => 'Test Session',
            'name' => $row['test_name'],
            'date' => $row['test_date'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time']
        ];
    }

    // Process lesson conflicts (no exceptions for lessons)
    while ($row = $lesson_result->fetch_assoc()) {
        $conflicts[] = [
            'type' => 'Lesson',
            'name' => $row['student_lesson_name'],
            'date' => $row['date'],
            'start_time' => $row['start_time'],
            'end_time' => $row['end_time']
        ];
    }

    return $conflicts;
}

$testTypesResult = fetchTestTypes($conn);

$errors = [];
$conflicts = [];
$successMessage = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $test_id = $_POST['testType'];
    $test_date = $_POST['testDate'];
    $start_time = $_POST['startTime'];
    $end_time = $_POST['endTime'];
    $capacity = $_POST['capacity'];
    $instructor_id = $_POST['instructor'];
    $eligible_students = isset($_POST['eligibleStudents']) ? $_POST['eligibleStudents'] : [];
    date_default_timezone_set('Asia/Kuala_Lumpur');
    $date_format = date("dmy");

    // Check for scheduling conflicts
    $conflicts = checkSchedulingConflicts($conn, $instructor_id, $test_date, $start_time, $end_time, $test_id);
    
    if (!empty($conflicts)) {
        // We have conflicts - don't proceed with saving
        $errors[] = "Scheduling conflict detected. The instructor is already assigned to another event at this time.";
    } else {
        try {
            // Generate new test_session_id
            $session_count_query = $conn->query("SELECT MAX(CAST(SUBSTRING(test_session_id, 5, 3) AS UNSIGNED)) AS max_id FROM test_sessions");
            $session_count_row = $session_count_query->fetch_assoc();
            $session_count = $session_count_row['max_id'] ? $session_count_row['max_id'] + 1 : 1;
            $test_session_id = sprintf('TSES%03d%s', $session_count, $date_format);

            // Insert test session
            $insert_session_sql = "INSERT INTO test_sessions (test_session_id, test_id, instructor_id, test_date, start_time, end_time, capacity_students, status) 
                               VALUES (?, ?, ?, ?, ?, ?, ?, 'Scheduled')";
            $insert_session_stmt = $conn->prepare($insert_session_sql);
            $insert_session_stmt->bind_param("ssssssi", $test_session_id, $test_id, $instructor_id, $test_date, $start_time, $end_time, $capacity);
            if (!$insert_session_stmt->execute()) {
                throw new Exception("Failed to insert test session. Error: " . $insert_session_stmt->error);
            }

            // Process eligible students 
            foreach ($eligible_students as $student_test_id) {
                // Update schedule_status to 'Assigned'
                $update_student_test_sql = "UPDATE student_tests SET schedule_status = 'Assigned' WHERE student_test_id = ?";
                $update_student_test_stmt = $conn->prepare($update_student_test_sql);
                $update_student_test_stmt->bind_param("s", $student_test_id);
                if (!$update_student_test_stmt->execute()) {
                    throw new Exception("Failed to update student_test_id: $student_test_id. Error: " . $update_student_test_stmt->error);
                }

                // Get numeric student_id (nnn part)
                $student_id_result = $conn->query("
                SELECT s.student_id FROM students s
                JOIN student_licenses sl ON sl.student_id = s.student_id
                JOIN student_tests st ON st.student_license_id = sl.student_license_id
                WHERE st.student_test_id = '$student_test_id' LIMIT 1
            ");
                $student_id_row = $student_id_result->fetch_assoc();
                $student_id_numeric = substr($student_id_row['student_id'], -3); // → "002"

                // Get increment for student_test_sessions (xxx part)
                $session_increment_result = $conn->query("
                SELECT MAX(CAST(SUBSTRING(student_test_session_id, 6, 3) AS UNSIGNED)) AS max_id 
                FROM student_test_sessions
            ");
                $session_increment_row = $session_increment_result->fetch_assoc();
                $session_increment = $session_increment_row['max_id'] ? $session_increment_row['max_id'] + 1 : 1;

                // Build student_test_session_id
                $student_test_session_id = sprintf(
                    'STSES%03d%03d%03d',
                    (int)$session_increment,
                    (int)$student_id_numeric,
                    (int)$session_count
                );

                // Insert into student_test_sessions
                $insert_student_session_sql = "INSERT INTO student_test_sessions (student_test_session_id, student_test_id, test_session_id) 
                                       VALUES (?, ?, ?)";
                $insert_student_session_stmt = $conn->prepare($insert_student_session_sql);
                $insert_student_session_stmt->bind_param("sss", $student_test_session_id, $student_test_id, $test_session_id);
                if (!$insert_student_session_stmt->execute()) {
                    throw new Exception("Failed to insert student_test_session_id: $student_test_session_id. Error: " . $insert_student_session_stmt->error);
                }
            }

            $successMessage = "Test session and student assignments added successfully.";
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
        }
    }
}

ob_end_flush();
?>

<!-- Rest of the HTML remains the same -->

<div class="container">
    <div class="page-inner">
        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Manage Tests</h4>
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
                    <a href="/pages/admin/manage_test/list_test.php">Test List</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Add Test</a>
                </li>
            </ul>
        </div>

        <div class="page-category">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header text-center">
                        <h3 class="fw-bold mb-3">Add Test</h3>
                        <p class="text-muted text-center">Fill in the details of the test</p>
                    </div>
                    <div class="card-body">
                        <form id="addTestForm" method="POST" action="">
                            <div class="row mb-3">
                                <!-- Test Type -->
                                <div class="col-md-6">
                                    <label for="testType">Test Type</label>
                                    <select id="testType" name="testType" class="form-control" required>
                                        <option value="">Select Test Type</option>
                                        <?php
                                        while ($row = mysqli_fetch_assoc($testTypesResult)) {
                                            echo "<option value='{$row['test_id']}'>{$row['test_name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>

                                <!-- Test Date -->
                                <div class="col-md-6">
                                    <label for="testDate">Test Date</label>
                                    <input type="date" id="testDate" name="testDate" class="form-control" required
                                        min="<?php echo date('Y-m-d', strtotime('+1 day')); ?>">
                                </div>
                            </div>

                            <div class="row mb-3">
                                <!-- Start Time -->
                                <div class="col-md-6">
                                    <label for="startTime">Start Time</label>
                                    <input type="time" id="startTime" name="startTime" class="form-control" required>
                                </div>

                                <!-- End Time -->
                                <div class="col-md-6">
                                    <label for="endTime">End Time</label>
                                    <input type="time" id="endTime" name="endTime" class="form-control" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <!-- Capacity -->
                                <div class="col-md-12">
                                    <label for="capacity">Capacity</label>
                                    <input type="number" id="capacity" name="capacity" class="form-control" min="1" required>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <!-- Instructor -->
                                <div class="col-md-12">
                                    <label for="instructor">Instructor</label>
                                    <select id="instructor" name="instructor" class="form-control" required>
                                        <option value="">Select Instructor</option>
                                        <?php
                                        // Fetch instructors from the database
                                        $instructorQuery = "SELECT i.instructor_id, u.name AS instructor_name FROM instructors i JOIN users u ON i.user_id = u.user_id";
                                        $instructorResult = mysqli_query($conn, $instructorQuery);

                                        while ($row = mysqli_fetch_assoc($instructorResult)) {
                                            echo "<option value='{$row['instructor_id']}'>{$row['instructor_name']}</option>";
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>

                            <div class="row mb-3">
                                <!-- Eligible Students -->
                                <div class="col-md-12">
                                    <label for="eligibleStudents">Eligible Students</label>
                                    <div class="input-group">
                                        <input type="text" id="searchStudent" class="form-control" placeholder="Search students...">
                                    </div>
                                    <div id="studentList" class="list-group mt-2">
                                        <p id="noStudentsMessage" class="text-muted">Select a test type to view eligible students.</p>
                                    </div>
                                    <small class="form-text text-muted">You can select up to the capacity limit.</small>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary mt-3 d-block mx-auto">Save Test</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const testTypeSelect = document.getElementById('testType');
        const studentList = document.getElementById('studentList');
        const noStudentsMessage = document.getElementById('noStudentsMessage');
        const searchStudent = document.getElementById('searchStudent');

        testTypeSelect.addEventListener('change', function() {
            const testId = testTypeSelect.value;

            if (testId) {
                fetch(`fetch_eligible_students.php?test_id=${testId}`)
                    .then(response => response.json())
                    .then(data => {
                        studentList.innerHTML = '';
                        if (data.length > 0) {
                            data.forEach(student => {
                                const studentItem = document.createElement('div');
                                studentItem.classList.add('list-group-item', 'd-flex', 'align-items-center', 'justify-content-between');
                                studentItem.innerHTML = `
                                <span>${student.student_name} (${student.license_name})</span>
                                <input type="checkbox" name="eligibleStudents[]" value="${student.student_test_id}" class="form-check-input select-student">
                            `;
                                studentList.appendChild(studentItem);
                            });
                            noStudentsMessage.style.display = 'none';
                        } else {
                            noStudentsMessage.textContent = 'No eligible students for this test.';
                            noStudentsMessage.style.display = 'block';
                        }
                    });
            } else {
                studentList.innerHTML = '';
                noStudentsMessage.textContent = 'Select a test type to view eligible students.';
                noStudentsMessage.style.display = 'block';
            }
        });

        // Search function for eligible students
        searchStudent.addEventListener('input', function() {
            const searchValue = searchStudent.value.toLowerCase();
            const studentItems = Array.from(studentList.querySelectorAll('.list-group-item'));

            // Filter and sort the student items
            const matchingItems = studentItems.filter(item => item.textContent.toLowerCase().includes(searchValue));
            const nonMatchingItems = studentItems.filter(item => !item.textContent.toLowerCase().includes(searchValue));

            // Clear the list and append matching items first, followed by non-matching items
            studentList.innerHTML = '';
            matchingItems.forEach(item => studentList.appendChild(item));
            nonMatchingItems.forEach(item => studentList.appendChild(item));
        });

        // Limit the number of selected students based on capacity
        const capacityInput = document.getElementById('capacity');
        capacityInput.addEventListener('input', function() {
            const capacity = parseInt(capacityInput.value);
            if (!isNaN(capacity)) {
                const studentCheckboxes = studentList.querySelectorAll('.select-student');

                studentCheckboxes.forEach(cb => cb.checked = false); // Reset all selections

                // Clear previous listeners by cloning the nodes (optional cleanup)
                studentCheckboxes.forEach(cb => {
                    const newCb = cb.cloneNode(true);
                    cb.parentNode.replaceChild(newCb, cb);
                });

                const updatedCheckboxes = studentList.querySelectorAll('.select-student');
                updatedCheckboxes.forEach(function(checkbox) {
                    checkbox.addEventListener('change', function() {
                        const selectedCount = studentList.querySelectorAll('.select-student:checked').length;
                        if (selectedCount > capacity) {
                            alert('You can only select up to ' + capacity + ' students.');
                            checkbox.checked = false;
                        }
                    });
                });
            }
        });

    });

    document.addEventListener('DOMContentLoaded', function() {
        <?php if (!empty($errors)): ?>
            Swal.fire({
                title: "Error!",
                html: "<?php echo implode('<br>', $errors); ?>",
                icon: "error"
            });
        <?php endif; ?>

        <?php if (!empty($successMessage)): ?>
            Swal.fire({
                title: "Success!",
                text: "<?php echo $successMessage; ?>",
                icon: "success",
                confirmButtonText: "OK"
            }).then(() => {
                window.location.href = 'list_test.php';
            });
        <?php endif; ?>
    });
</script>

<?php include '../../../include/footer.html'; ?>