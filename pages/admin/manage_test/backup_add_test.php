<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Fetch Test Types
function fetchTestTypes($conn)
{
    $query = "SELECT test_id, test_name FROM tests";
    return mysqli_query($conn, $query);
}

$testTypesResult = fetchTestTypes($conn);
?>

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
                        <form id="addTestForm" method="POST" action="save_test.php">
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
                                    <input type="date" id="testDate" name="testDate" class="form-control" required>
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
                                <input type="checkbox" name="eligibleStudents[]" value="${student.user_id}" class="form-check-input select-student">
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
                studentCheckboxes.forEach(function(checkbox) {
                    checkbox.checked = false; // Uncheck all checkboxes
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
</script>

<?php include '../../../include/footer.html'; ?>