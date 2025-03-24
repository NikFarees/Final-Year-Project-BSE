<?php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php';

// Fetch student_id from URL parameter
if (!isset($_GET['id'])) {
    die("Student ID is not set.");
}
$student_id = $_GET['id'];

// Fetch student details
$studentDetailsQuery = "
    SELECT 
        s.student_id, 
        u.name 
    FROM 
        students AS s
    JOIN 
        users AS u ON s.user_id = u.user_id 
    WHERE 
        s.student_id = ?
";
$studentDetailsStmt = $conn->prepare($studentDetailsQuery);
$studentDetailsStmt->bind_param("s", $student_id);
$studentDetailsStmt->execute();
$studentDetailsResult = $studentDetailsStmt->get_result();
$student = $studentDetailsResult->fetch_assoc();

// Check if student details were found
if (!$student) {
    die("Student not found.");
}

// Fetch lessons assigned to the student
$lessonsQuery = "
    SELECT 
        stl.student_lesson_id,
        stl.student_lesson_name,
        stl.date,
        stl.start_time,
        stl.end_time,
        u.name AS instructor_name
    FROM 
        student_lessons AS stl
    JOIN 
        student_licenses AS sl ON stl.student_license_id = sl.student_license_id
    JOIN 
        students AS s ON sl.student_id = s.student_id
    JOIN 
        instructors AS i ON stl.instructor_id = i.instructor_id
    JOIN 
        users AS u ON i.user_id = u.user_id
    WHERE 
        sl.student_id = ? AND stl.schedule_status = 'Assigned';
";
$lessonsStmt = $conn->prepare($lessonsQuery);
$lessonsStmt->bind_param("s", $student_id);
$lessonsStmt->execute();
$lessonsResult = $lessonsStmt->get_result();
$lessons = [];
while ($row = $lessonsResult->fetch_assoc()) {
    $lessons[] = $row;
}
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
                    <a href="/pages/admin/manage_schedule/list_student.php">Student List</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Student Schedule</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">
            <div class="card">
                <div class="card-header">
                    <h4><?php echo $student['student_id'] . ' - ' . $student['name']; ?> Schedule</h4>
                </div>
                <div class="card-body">
                    <div id="calendar"></div>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
include '../../../include/footer.html';
?>

<!-- Include necessary JS libraries for calendar -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" />

<style>
    .fc-agenda-allday .fc-day {
        background-color: #BDE7BD;
        /* Default background color for all-day slot */
    }
</style>

<script>
    $(document).ready(function() {
        // Initialize calendar
        $('#calendar').fullCalendar({
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'agendaWeek'
            },
            defaultView: 'agendaWeek',
            validRange: {
                start: moment().format('YYYY-MM-DD')
            },
            minTime: "08:00:00", // Earliest time displayed on the calendar
            maxTime: "18:00:00", // Latest time displayed on the calendar
            slotDuration: "00:30:00", // Set slot intervals to 30 minutes
            scrollTime: "08:00:00", // Auto-scroll to 8 AM
            height: 'auto', // Automatically adjust the height of the calendar
            allDaySlot: false, // Disable the all-day slot
            events: [
                <?php foreach ($lessons as $lesson) { ?> {
                        title: '<?php echo $lesson['student_lesson_name']; ?>',
                        start: '<?php echo $lesson['date'] . 'T' . $lesson['start_time']; ?>',
                        end: '<?php echo $lesson['date'] . 'T' . $lesson['end_time']; ?>',
                        description: 'Instructor Name: <?php echo $lesson['instructor_name']; ?>',
                        color: '#FDFD97', // Color for student's schedule
                        student_license_id: '<?php echo $lesson['student_lesson_id']; ?>'
                    },
                <?php } ?>
            ],
            eventRender: function(event, element) {
                // Modify the event's details to exclude time information and display only the lesson name and instructor name
                var description = event.description ? event.description.split("<br>")[0] : '';
                element.find('.fc-title').append("<br/>" + description);
                element.find('.fc-time').remove(); // Remove the time display
            }
        });

        // Add a legend for session preference colors
        var legend = `
            <div id="legend" class="mt-3">
                <strong>Legend:</strong>
                <span style="display: inline-block; width: 20px; height: 20px; background-color: #FDFD97; margin-left: 10px;"></span> Slot Occupied
            </div>
        `;
        $(".fc-toolbar").after(legend); // Append legend below the calendar toolbar
    });
</script>

