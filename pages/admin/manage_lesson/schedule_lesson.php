<?php
// filepath: c:\Users\nikfa\Desktop\UniKL\BSE\SEM 6\Final Year Project 2\KMSE_Driveflow\pages\admin\manage_lesson\schedule_lesson.php
include '../../../include/ad_header.php';
include '../../../database/db_connection.php'; // Include the database connection file

// Get student license ID from query parameter
$student_license_id = isset($_GET['student_license_id']) ? $conn->real_escape_string($_GET['student_license_id']) : '';

// Fetch instructor details for the student
$instructorQuery = "
    SELECT i.instructor_id, u.name AS instructor_name
    FROM student_lessons AS sl
    JOIN instructors AS i ON sl.instructor_id = i.instructor_id
    JOIN users AS u ON i.user_id = u.user_id
    WHERE sl.student_license_id = ? AND sl.instructor_id IS NOT NULL
    LIMIT 1;
";
$instructorStmt = $conn->prepare($instructorQuery);
$instructorStmt->bind_param("s", $student_license_id);
$instructorStmt->execute();
$instructorResult = $instructorStmt->get_result();
$instructor = $instructorResult->fetch_assoc();
$instructor_name = $instructor['instructor_name'] ?? 'Not Assigned';

// Fetch unassigned lessons for the student
$unassignedQuery = "
    SELECT 
        sl.student_lesson_id, 
        sl.student_lesson_name, 
        u.name AS student_name,
        slc.lesson_id
    FROM student_lessons AS sl
    JOIN student_licenses AS slc ON sl.student_license_id = slc.student_license_id
    JOIN students AS s ON slc.student_id = s.student_id
    JOIN users AS u ON s.user_id = u.user_id
    WHERE sl.student_license_id = ? AND sl.schedule_status = 'Unassigned';
";

$unassignedStmt = $conn->prepare($unassignedQuery);
$unassignedStmt->bind_param("s", $student_license_id);
$unassignedStmt->execute();
$unassignedResult = $unassignedStmt->get_result();
$unassignedLessons = [];
$student_name = 'Unknown';
while ($row = $unassignedResult->fetch_assoc()) {
    $unassignedLessons[] = $row;
    $student_name = $row['student_name'];
}

// Fetch assigned lessons for the student
$assignedQuery = "
    SELECT 
        sl.student_lesson_id, 
        sl.student_lesson_name, 
        sl.date, 
        sl.start_time, 
        sl.end_time
    FROM student_lessons AS sl
    WHERE sl.student_license_id = ? AND sl.schedule_status = 'Assigned'
    ORDER BY sl.date ASC, sl.start_time ASC;
";
$assignedStmt = $conn->prepare($assignedQuery);
$assignedStmt->bind_param("s", $student_license_id);
$assignedStmt->execute();
$assignedResult = $assignedStmt->get_result();
$assignedLessons = [];
while ($row = $assignedResult->fetch_assoc()) {
    $assignedLessons[] = $row;
}

// Get the latest assigned lesson (the lesson with the latest date and time)
$latestAssignedLesson = null;
$secondLatestAssignedLesson = null;

if (count($assignedLessons) > 0) {
    // Sort by date and time in descending order
    usort($assignedLessons, function ($a, $b) {
        $dateA = strtotime($a['date'] . ' ' . $a['end_time']);
        $dateB = strtotime($b['date'] . ' ' . $b['end_time']);
        return $dateB - $dateA; // Sort in descending order
    });

    // The first item is the latest lesson
    $latestAssignedLesson = $assignedLessons[0];

    // The second item (if exists) is the second latest lesson
    if (count($assignedLessons) > 1) {
        $secondLatestAssignedLesson = $assignedLessons[1];
    }

    // Resort the array back to ascending order for display
    usort($assignedLessons, function ($a, $b) {
        $dateA = strtotime($a['date'] . ' ' . $a['start_time']);
        $dateB = strtotime($b['date'] . ' ' . $b['start_time']);
        return $dateA - $dateB; // Sort in ascending order
    });
}

// Fetch instructor's schedule
$instructorScheduleQuery = "
    SELECT 
        sl.student_lesson_name, 
        sl.date, 
        sl.start_time, 
        sl.end_time
    FROM student_lessons AS sl
    WHERE sl.instructor_id = ? AND sl.schedule_status = 'Assigned';
";
$instructorScheduleStmt = $conn->prepare($instructorScheduleQuery);
$instructorScheduleStmt->bind_param("s", $instructor['instructor_id']);
$instructorScheduleStmt->execute();
$instructorScheduleResult = $instructorScheduleStmt->get_result();
$instructorSchedule = [];
while ($row = $instructorScheduleResult->fetch_assoc()) {
    $instructorSchedule[] = $row;
}

// Fetch the latest assigned lesson for the student
$latestAssignedQuery = "
    SELECT 
        MAX(CONCAT(sl.date, ' ', sl.end_time)) AS latest_assigned_datetime
    FROM student_lessons AS sl
    WHERE sl.student_license_id = ? AND sl.schedule_status = 'Assigned';
";
$latestAssignedStmt = $conn->prepare($latestAssignedQuery);
$latestAssignedStmt->bind_param("s", $student_license_id);
$latestAssignedStmt->execute();
$latestAssignedResult = $latestAssignedStmt->get_result();
$latestAssignedRow = $latestAssignedResult->fetch_assoc();
$latestAssignedDatetime = $latestAssignedRow['latest_assigned_datetime'] ?? null;

// Fetch test sessions for the instructor
$testSessionQuery = "
    SELECT 
        ts.test_session_id,
        t.test_name,
        ts.test_date,
        ts.start_time,
        ts.end_time
    FROM test_sessions AS ts
    JOIN tests AS t ON ts.test_id = t.test_id
    WHERE ts.instructor_id = ? AND ts.status = 'Scheduled'
    LIMIT 1; -- Only fetch one test session if multiple exist
";
$testSessionStmt = $conn->prepare($testSessionQuery);
$testSessionStmt->bind_param("s", $instructor['instructor_id']);
$testSessionStmt->execute();
$testSessionResult = $testSessionStmt->get_result();
$testSessions = [];
while ($row = $testSessionResult->fetch_assoc()) {
    $testSessions[] = $row;
}

function getSessionPreference($lesson_id)
{
    // You could expand this if more types are added
    $weekdayLessons = ['LES01', 'LES03'];
    $weekendLessons = ['LES02', 'LES04'];

    if (in_array($lesson_id, $weekdayLessons)) {
        return 'Weekday';
    } elseif (in_array($lesson_id, $weekendLessons)) {
        return 'Weekend';
    } else {
        return 'Unknown';
    }
}

// Function to check if a lesson is in the past or currently ongoing
function isLessonPastOrCurrent($date, $startTime, $endTime) {
    $now = new DateTime();
    $lessonStart = new DateTime($date . ' ' . $startTime);
    $lessonEnd = new DateTime($date . ' ' . $endTime);
    
    // If lesson end time is in the past or lesson is currently ongoing
    return ($lessonEnd < $now || ($lessonStart <= $now && $lessonEnd >= $now));
}

?>

<div class="container">
    <div class="page-inner">
        <div class="page-header">
            <h4 class="page-title">Manage Lesson</h4>
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
                    <a href="/pages/admin/manage_lesson/list_lesson.php">Lesson List</a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Schedule Lesson</a>
                </li>
            </ul>
        </div>

        <div class="page-category">
            <!-- Card Header -->
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="fw-bold">Schedule Lesson</h3>
                        <p class="text-muted mb-0">Please drag the card in "Unassigned Lessons" to the calendar to assign a lesson.</p>
                    </div>
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="schedule-toggle-btn">
                        <i class="fas fa-minus"></i>
                    </button>
                </div>

                <!-- Card Body -->
                <div class="card-body" id="schedule-card-body">
                    <div class="row">
                        <!-- Unassigned Lessons Card -->
                        <div class="col-md-4">
                            <div class="card shadow-dark">
                                <div class="card-header">
                                    <h5 class="card-title">Unassigned Lesson</h5>
                                </div>
                                <div class="card-body">
                                    <?php if (!empty($unassignedLessons)): ?>
                                        <div class="info">
                                            <p><strong>Student Name:</strong> <?php echo $student_name; ?></p>
                                            <p><strong>Instructor Assigned:</strong> <?php echo $instructor_name; ?></p>
                                            <p><strong>Latest Assigned Datetime:</strong>
                                                <?php echo $latestAssignedDatetime ? date('F j, Y, g:i A', strtotime($latestAssignedDatetime)) : 'No assigned lessons yet'; ?>
                                            </p>
                                        </div>
                                        <div id="lesson-cards">
                                            <?php foreach ($unassignedLessons as $lesson): ?>
                                                <?php $sessionPref = getSessionPreference($lesson['lesson_id']); ?>
                                                <div class="card draggable mb-2" data-lesson-id="<?php echo $lesson['student_lesson_id']; ?>" data-session="<?php echo $sessionPref; ?>">
                                                    <div class="card-body">
                                                        <p class="card-text mb-1"><strong><?php echo $lesson['student_lesson_name']; ?></strong></p>
                                                        <p class="text-muted small">Session: <?php echo $sessionPref; ?></p>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php else: ?>
                                        <p class="text-muted">✅ All lessons have already been assigned for this student.</p>
                                    <?php endif; ?>

                                    <!-- Assigned Lessons List with Undo Buttons -->
                                    <?php if (!empty($assignedLessons)): ?>
                                        <div class="mt-4">
                                            <h6 class="font-weight-bold">Assigned Lessons:</h6>
                                            <div class="list-group">
                                                <?php foreach ($assignedLessons as $index => $lesson): ?>
                                                    <?php
                                                    $isLatest = ($latestAssignedLesson && $lesson['student_lesson_id'] == $latestAssignedLesson['student_lesson_id']);
                                                    $isSecondLatest = ($secondLatestAssignedLesson && $lesson['student_lesson_id'] == $secondLatestAssignedLesson['student_lesson_id']);
                                                    $isPastOrCurrent = isLessonPastOrCurrent($lesson['date'], $lesson['start_time'], $lesson['end_time']);
                                                    ?>
                                                    <div class="list-group-item mb-2 d-flex justify-content-between align-items-center">
                                                        <div>
                                                            <strong><?php echo $lesson['student_lesson_name']; ?></strong><br>
                                                            <small><?php echo date('F j, Y', strtotime($lesson['date'])); ?><br>
                                                                <?php echo date('g:i A', strtotime($lesson['start_time'])); ?> -
                                                                <?php echo date('g:i A', strtotime($lesson['end_time'])); ?></small>
                                                                <?php if ($isPastOrCurrent): ?>
                                                                    <span class="badge badge-warning">In progress/past</span>
                                                                <?php endif; ?>
                                                        </div>
                                                        <?php if (($isLatest || $isSecondLatest) && !$isPastOrCurrent): ?>
                                                            <button
                                                                class="btn btn-sm btn-danger undo-lesson"
                                                                data-lesson-id="<?php echo $lesson['student_lesson_id']; ?>"
                                                                data-date="<?php echo $lesson['date']; ?>"
                                                                data-start-time="<?php echo $lesson['start_time']; ?>"
                                                                data-end-time="<?php echo $lesson['end_time']; ?>"
                                                                <?php if (!$isLatest): ?>
                                                                data-depends-on="<?php echo $latestAssignedLesson['student_lesson_id']; ?>"
                                                                disabled
                                                                <?php endif; ?>>
                                                                Undo
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Calendar View Card -->
                        <div class="col-md-8">
                            <div class="card shadow-dark">
                                <div class="card-header">
                                    <h5 class="card-title">Schedule Calendar</h5>
                                </div>
                                <!-- Replace the current legend with this improved version -->
                                <div class="card mb-3">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3 col-sm-6 mb-2">
                                                <div class="d-flex align-items-center">
                                                    <div style="width: 20px; height: 20px; background-color:#97D4FD; margin-right: 10px; border-radius: 4px;"></div>
                                                    <span>Current Student Lessons</span>
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-sm-6 mb-2">
                                                <div class="d-flex align-items-center">
                                                    <div style="width: 20px; height: 20px; background-color:#FDFD97; margin-right: 10px; border-radius: 4px;"></div>
                                                    <span>Instructor's Existing Schedule</span>
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-sm-6 mb-2">
                                                <div class="d-flex align-items-center">
                                                    <div style="width: 20px; height: 20px; background-color:#BDE7BD; margin-right: 10px; border-radius: 4px;"></div>
                                                    <span>Available Slots</span>
                                                </div>
                                            </div>
                                            <div class="col-md-3 col-sm-6 mb-2">
                                                <div class="d-flex align-items-center">
                                                    <div style="width: 20px; height: 20px; background-color:#FFB6B3; margin-right: 10px; border-radius: 4px;"></div>
                                                    <span>Non-Preferred Slots</span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <div id="calendar"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../../include/footer.html'; ?>

<!-- Include necessary JS libraries -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jqueryui/1.12.1/jquery-ui.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" />
<!-- Add SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<script>
    $(document).ready(function() {
        // Toggle schedule card content visibility
        $('#schedule-toggle-btn').click(function() {
            var cardBody = $('#schedule-card-body');
            cardBody.css('transition', 'none');
            cardBody.slideToggle(300);
            
            var icon = $(this).find('i');
            if (icon.hasClass('fa-minus')) {
                icon.removeClass('fa-minus').addClass('fa-plus');
            } else {
                icon.removeClass('fa-plus').addClass('fa-minus');
            }
        });
    
        // Make only the first unassigned lesson card draggable
        $(".draggable").each(function(index) {
            if (index === 0) {
                $(this).draggable({
                    revert: "invalid",
                    helper: "clone"
                });
            } else {
                $(this).addClass("disabled").css("opacity", "0.5");
            }
        });

        // Initialize the calendar
        $('#calendar').fullCalendar({
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'agendaWeek'
            },
            defaultView: 'agendaWeek',
            minTime: "08:00:00", // Earliest time displayed on the calendar
            maxTime: "17:00:00", // Latest time displayed on the calendar
            slotDuration: "00:30:00", // Set slot intervals to 30 minutes
            scrollTime: "08:00:00", // Auto-scroll to 8 AM
            height: 'auto', // Automatically adjust the height of the calendar
            // Restrict viewable and selectable dates
            validRange: {
                start: moment().add(1, 'days').format('YYYY-MM-DD') // Block today and all dates before today
            },

            events: function(start, end, timezone, callback) {
                var events = [];

                // Add assigned lessons for this student
                <?php foreach ($assignedLessons as $lesson): 
                    // Check if the lesson is past or current
                    $isPastOrCurrent = isLessonPastOrCurrent($lesson['date'], $lesson['start_time'], $lesson['end_time']);
                ?>
                    var studentLesson = {
                        id: '<?php echo $lesson['student_lesson_id']; ?>',
                        title: '<?php echo $lesson['student_lesson_name']; ?>',
                        start: '<?php echo $lesson['date'] . 'T' . $lesson['start_time']; ?>',
                        end: '<?php echo $lesson['date'] . 'T' . $lesson['end_time']; ?>',
                        color: '#97D4FD',
                        textColor: '#000',
                        isLatestLesson: <?php echo ($latestAssignedLesson && $lesson['student_lesson_id'] == $latestAssignedLesson['student_lesson_id']) ? 'true' : 'false'; ?>,
                        isSecondLatestLesson: <?php echo ($secondLatestAssignedLesson && $lesson['student_lesson_id'] == $secondLatestAssignedLesson['student_lesson_id']) ? 'true' : 'false'; ?>,
                        isPastOrCurrent: <?php echo $isPastOrCurrent ? 'true' : 'false'; ?>,
                        lessonDate: '<?php echo $lesson['date']; ?>',
                        lessonStartTime: '<?php echo $lesson['start_time']; ?>',
                        lessonEndTime: '<?php echo $lesson['end_time']; ?>'
                    };
                    events.push(studentLesson);
                <?php endforeach; ?>

                // Add instructor's other lessons
                <?php foreach ($instructorSchedule as $schedule):
                    // Check if this event is already in the student's schedule (to avoid duplication)
                    $isDuplicate = false;
                    foreach ($assignedLessons as $studentLesson) {
                        if (
                            $studentLesson['date'] == $schedule['date'] &&
                            $studentLesson['start_time'] == $schedule['start_time'] &&
                            $studentLesson['end_time'] == $schedule['end_time']
                        ) {
                            $isDuplicate = true;
                            break;
                        }
                    }
                    if (!$isDuplicate):
                ?>
                        events.push({
                            title: '<?php echo $schedule['student_lesson_name']; ?>',
                            start: '<?php echo $schedule['date'] . 'T' . $schedule['start_time']; ?>',
                            end: '<?php echo $schedule['date'] . 'T' . $schedule['end_time']; ?>',
                            color: '#FDFD97',
                            textColor: '#000'
                        });
                <?php endif;
                endforeach; ?>

                // Add test sessions
                <?php foreach ($testSessions as $testSession): ?>
                    events.push({
                        id: '<?php echo $testSession['test_session_id']; ?>',
                        title: '<?php echo $testSession['test_name']; ?>',
                        start: '<?php echo $testSession['test_date'] . 'T' . $testSession['start_time']; ?>',
                        end: '<?php echo $testSession['test_date'] . 'T' . $testSession['end_time']; ?>',
                        color: '#FFB6B3', // Highlight test sessions in red
                        textColor: '#000'
                    });
                <?php endforeach; ?>

                callback(events);
            },

            droppable: true,

            eventRender: function(event, element) {
                // Add undo button for the latest assigned lesson that is not past or current
                if ((event.isLatestLesson || event.isSecondLatestLesson) && !event.isPastOrCurrent) {
                    // Only allow undoing the latest lesson, or the second latest if the latest is undone
                    if (event.isLatestLesson) {
                        element.append('<div class="undo-button" data-lesson-id="' + event.id +
                            '" data-date="' + event.lessonDate + 
                            '" data-start-time="' + event.lessonStartTime + 
                            '" data-end-time="' + event.lessonEndTime + 
                            '"><i class="fa fa-undo"></i> Undo</div>');
                    }
                }
                
                // For past or current lessons, add a visual indicator
                if (event.isPastOrCurrent) {
                    element.find('.fc-title').after('<span style="margin-left:5px;font-size:0.8em;color:#f90;">[In progress/past]</span>');
                }
            },

            dayRender: function(date, cell) {
                // Get all draggable lessons and their session preferences
                $(".draggable").each(function() {
                    var sessionPreference = $(this).data('session');
                    var day = date.day(); // Sunday = 0, Saturday = 6

                    // Apply colors based on session preference
                    if (sessionPreference === 'Weekday') {
                        if (day === 0 || day === 6) {
                            cell.css("background-color", "#FFB6B3"); // Red for weekends
                        } else {
                            cell.css("background-color", "#BDE7BD"); // Green for weekdays
                        }
                    } else if (sessionPreference === 'Weekend') {
                        if (day === 0 || day === 6) {
                            cell.css("background-color", "#BDE7BD"); // Green for weekends
                        } else {
                            cell.css("background-color", "#FFB6B3"); // Red for weekdays
                        }
                    }
                });
            },

            drop: function(date, jsEvent, ui) {
                var lessonId = $(ui.helper).data('lesson-id');
                var sessionPreference = $(ui.helper).data('session');
                var day = date.day(); // Sunday = 0, Saturday = 6
                var studentLicenseId = '<?php echo $student_license_id; ?>';

                if (!date.hasTime()) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Action',
                        text: 'Dropping lessons on all-day sessions is not allowed.'
                    });
                    return;
                }

                if ((sessionPreference === 'Weekday' && (day === 0 || day === 6)) ||
                    (sessionPreference === 'Weekend' && day >= 1 && day <= 5)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Day',
                        text: 'Invalid day for this session. Please assign only on ' + sessionPreference + 's.'
                    });
                    return;
                }

                var now = moment().startOf('day');
                var lessonDay = moment(date).startOf('day');

                if (lessonDay.isSame(now) || lessonDay.isBefore(now)) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Invalid Date',
                        text: 'Lessons can only be scheduled starting from tomorrow onward.'
                    });
                    return;
                }

                var startDate = date.format('YYYY-MM-DD');
                var startTime = date.format('HH:mm:ss');
                var endTime = moment(date).add(2, 'hours').format('HH:mm:ss');

                // Prevent drop on test sessions
                var isOnTestSession = false;
                $('#calendar').fullCalendar('clientEvents', function(event) {
                    if (event.color === '#FFB6B3') { // This color identifies test sessions
                        var eventStart = moment(event.start);
                        var eventEnd = moment(event.end);

                        if (
                            date.isBetween(eventStart, eventEnd, null, '[)') ||
                            moment(date).add(2, 'hours').isBetween(eventStart, eventEnd, null, '(]') ||
                            (date.isSame(eventStart) && moment(date).add(2, 'hours').isSame(eventEnd))
                        ) {
                            isOnTestSession = true;
                            return true;
                        }
                    }
                });

                if (isOnTestSession) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Test Session Conflict',
                        text: 'Cannot schedule lessons during a test session.'
                    });
                    return;
                }

                // Enforce order of lessons
                var latestLessonEnd = null;
                $('#calendar').fullCalendar('clientEvents', function(event) {
                    if (event.color === '#97D4FD') {
                        var eventEnd = moment(event.end);
                        if (!latestLessonEnd || eventEnd.isAfter(latestLessonEnd)) {
                            latestLessonEnd = eventEnd;
                        }
                    }
                    return false;
                });

                if (latestLessonEnd) {
                    const latestLessonDate = latestLessonEnd.clone().startOf('day');
                    const intendedLessonDate = lessonDay.clone().startOf('day');

                    if (!intendedLessonDate.isAfter(latestLessonDate)) {
                        Swal.fire({
                            icon: 'error',
                            title: 'Invalid Sequence',
                            text: 'Please schedule lessons in the correct order. The next lesson must be at least one day after the latest assigned lesson.'
                        });
                        return;
                    }
                }

                // Conflict check
                var hasConflict = false;
                $('#calendar').fullCalendar('clientEvents', function(event) {
                    var eventStart = moment(event.start);
                    var eventEnd = moment(event.end);

                    if (lessonDay.isBetween(eventStart, eventEnd, null, '[)') ||
                        moment(date).add(2, 'hours').isBetween(eventStart, eventEnd, null, '(]') ||
                        (lessonDay.isSame(eventStart) && moment(date).add(2, 'hours').isSame(eventEnd))) {
                        hasConflict = true;
                        return true;
                    }
                });

                if (hasConflict) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Time Conflict',
                        text: 'This time slot is already occupied. Please choose a different time.'
                    });
                    return;
                }

                // AJAX schedule call
                $.ajax({
                    url: 'update_schedule.php',
                    type: 'POST',
                    data: {
                        lesson_id: lessonId,
                        date: startDate,
                        start_time: startTime,
                        end_time: endTime
                    },
                    success: function(response) {
                        if (response === 'success') {
                            Swal.fire({
                                icon: 'success',
                                title: 'Success!',
                                text: 'Lesson scheduled successfully!',
                                timer: 1500
                            }).then(() => {
                                location.reload();
                            });
                        } else {
                            Swal.fire({
                                icon: 'error',
                                title: 'Error',
                                text: 'Failed to schedule lesson.'
                            });
                        }
                    }
                });
            }

        });

        // Handle undo button clicks from calendar
        $(document).on('click', '.undo-button', function() {
            var lessonId = $(this).data('lesson-id');
            var date = $(this).data('date');
            var startTime = $(this).data('start-time');
            var endTime = $(this).data('end-time');
            
            // Check if the lesson is past or current
            checkAndUndoLesson(lessonId, date, startTime, endTime);
        });

        // Handle undo button clicks from list
        $(document).on('click', '.undo-lesson', function() {
            var lessonId = $(this).data('lesson-id');
            var date = $(this).data('date');
            var startTime = $(this).data('start-time');
            var endTime = $(this).data('end-time');
            
            // Check if the lesson is past or current
            checkAndUndoLesson(lessonId, date, startTime, endTime);
        });

        // Function to check if lesson is past or current before undoing
        function checkAndUndoLesson(lessonId, date, startTime, endTime) {
            // Convert to moment objects for comparison
            var now = moment();
            var lessonStart = moment(date + ' ' + startTime);
            var lessonEnd = moment(date + ' ' + endTime);
            
            // If lesson is past or current, don't allow undo
            if (lessonEnd < now || (lessonStart <= now && lessonEnd >= now)) {
                Swal.fire({
                    icon: 'error',
                    title: 'Cannot Undo',
                    text: 'You cannot undo lessons that are in progress or have already passed.'
                });
                return;
            }
            
            // If it's future lesson, proceed with undo
            undoLessonAssignment(lessonId);
        }

        // Function to undo a lesson assignment
        function undoLessonAssignment(lessonId) {
            Swal.fire({
                title: 'Are you sure?',
                text: "Do you want to undo this lesson scheduling?",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#3085d6',
                cancelButtonColor: '#d33',
                confirmButtonText: 'Yes, undo it!'
            }).then((result) => {
                if (result.isConfirmed) {
                    $.ajax({
                        url: 'undo_schedule.php',
                        type: 'POST',
                        data: {
                            lesson_id: lessonId
                        },
                        success: function(response) {
                            if (response === 'success') {
                                Swal.fire({
                                    icon: 'success',
                                    title: 'Success!',
                                    text: 'Lesson scheduling undone successfully!',
                                    timer: 1500
                                }).then(() => {
                                    location.reload();
                                });
                            } else {
                                Swal.fire({
                                    icon: 'error',
                                    title: 'Error',
                                    text: 'Failed to undo lesson scheduling.'
                                });
                            }
                        }
                    });
                }
            });
        }

        // Enable the second latest lesson's undo button when latest is undone
        function updateUndoButtonsState() {
            var latestUndone = false;
            $('.undo-lesson').each(function() {
                var dependsOn = $(this).data('depends-on');
                if (dependsOn) {
                    // Check if the lesson it depends on has been undone
                    var dependentElement = $('.undo-lesson[data-lesson-id="' + dependsOn + '"]');
                    if (dependentElement.length === 0 || latestUndone) {
                        $(this).prop('disabled', false);
                    }
                }
            });
        }
    });
</script>

<style>
    .draggable {
        cursor: move;
    }

    .disabled {
        pointer-events: none;
        opacity: 0.5;
    }

    .shadow-dark {
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.3);
        /* Darker shadow */
        border-radius: 8px;
        /* Optional: Rounded corners */
    }

    .undo-button {
        position: absolute;
        bottom: 0;
        right: 0;
        background: rgba(255, 0, 0, 0.7);
        color: white;
        padding: 2px 5px;
        font-size: 10px;
        border-top-left-radius: 3px;
        cursor: pointer;
    }

    .undo-button:hover {
        background: rgba(255, 0, 0, 0.9);
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
    
    /* Small button styling */
    .btn-sm {
        padding: 0.25rem 0.5rem;
        font-size: 0.875rem;
        line-height: 1.5;
        border-radius: 0.2rem;
    }
    
    /* Badge styling */
    .badge-warning {
        background-color: #ffc107;
        color: #212529;
        padding: 0.25em 0.4em;
        font-size: 75%;
        font-weight: 700;
        border-radius: 0.25rem;
        margin-left: 5px;
    }
</style>