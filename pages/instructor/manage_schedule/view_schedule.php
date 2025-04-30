<?php
include '../../../include/in_header.php';
include '../../../database/db_connection.php';

// Fetch user_id from session
$user_id = $_SESSION['user_id'];

// Get instructor_id from the database
$instructorQuery = "
    SELECT instructor_id 
    FROM instructors 
    WHERE user_id = ?;
";
$instructorStmt = $conn->prepare($instructorQuery);
$instructorStmt->bind_param("s", $user_id);
$instructorStmt->execute();
$instructorResult = $instructorStmt->get_result();
$instructorRow = $instructorResult->fetch_assoc();
$instructor_id = $instructorRow['instructor_id'];

// Fetch lessons assigned to the instructor
$lessonsQuery = "
    SELECT 
        stl.student_lesson_id,
        stl.student_lesson_name,
        stl.date,
        stl.start_time,
        stl.end_time,
        u.name AS student_name,
        'lesson' AS event_type
    FROM 
        student_lessons AS stl
    JOIN 
        student_licenses AS sl ON stl.student_license_id = sl.student_license_id
    JOIN 
        students AS s ON sl.student_id = s.student_id
    JOIN 
        users AS u ON s.user_id = u.user_id
    WHERE 
        stl.instructor_id = ? AND stl.schedule_status = 'Assigned';
";
$lessonsStmt = $conn->prepare($lessonsQuery);
$lessonsStmt->bind_param("s", $instructor_id);
$lessonsStmt->execute();
$lessonsResult = $lessonsStmt->get_result();
$events = [];
while ($row = $lessonsResult->fetch_assoc()) {
    $events[] = $row;
}

// Fetch test sessions assigned to the instructor
$testSessionsQuery = "
    SELECT 
        ts.test_session_id,
        t.test_name,
        ts.test_date AS date,
        ts.start_time,
        ts.end_time,
        ts.capacity_students,
        ts.status,
        'test_session' AS event_type
    FROM 
        test_sessions AS ts
    JOIN 
        tests AS t ON ts.test_id = t.test_id
    WHERE 
        ts.instructor_id = ? AND ts.status = 'Scheduled' OR ts.status = 'Completed';
";
$testSessionsStmt = $conn->prepare($testSessionsQuery);
$testSessionsStmt->bind_param("s", $instructor_id);
$testSessionsStmt->execute();
$testSessionsResult = $testSessionsStmt->get_result();
while ($row = $testSessionsResult->fetch_assoc()) {
    $events[] = $row;
}

// Fetch availability of the instructor
$availabilityQuery = "
    SELECT 
        availability_id,
        date,
        start_time,
        end_time,
        status,
        'availability' AS event_type
    FROM 
        availability
    WHERE 
        instructor_id = ?;
";
$availabilityStmt = $conn->prepare($availabilityQuery);
$availabilityStmt->bind_param("s", $instructor_id);
$availabilityStmt->execute();
$availabilityResult = $availabilityStmt->get_result();
while ($row = $availabilityResult->fetch_assoc()) {
    $events[] = $row;
}
?>

<div class="container">
    <div class="page-inner">

        <!-- Breadcrumbs -->
        <div class="page-header">
            <h4 class="page-title">Instructor Schedule</h4>
            <ul class="breadcrumbs">
                <li class="nav-home">
                    <a href="/pages/instructor/dashboard.php">
                        <i class="icon-home"></i>
                    </a>
                </li>
                <li class="separator">
                    <i class="icon-arrow-right"></i>
                </li>
                <li class="nav-item">
                    <a href="#">Schedule</a>
                </li>
            </ul>
        </div>

        <!-- Inner page content -->
        <div class="page-category">
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h4 class="card-title">My Schedule</h4>
                            <button type="button" class="btn btn-sm btn-outline-secondary" id="schedule-toggle-btn">
                                <i class="fas fa-minus"></i>
                            </button>
                        </div>
                        <div class="card-body" id="schedule-card-body">
                            <div id="calendar"></div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event Details Modal -->
            <div class="modal fade" id="eventModal" tabindex="-1" role="dialog" aria-labelledby="eventModalLabel" aria-hidden="true">
                <div class="modal-dialog" role="document">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="eventModalLabel">Event Details</h5>
                            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                        <div class="modal-body" id="eventModalBody">
                            <!-- Event details will be populated here -->
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

<!-- Include necessary JS libraries for calendar -->
<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/moment.js/2.29.1/moment.min.js"></script>
<!-- Add Bootstrap JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.js"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/fullcalendar/3.10.2/fullcalendar.min.css" />

<style>
    /* Custom styles for the calendar */
    .fc-time-grid .fc-event {
        border-left: 4px solid;
    }

    .lesson-event {
        background-color: #FDFD97 !important;
        border-color: #FDAA33 !important;
    }

    .test-event {
        background-color: #A0C4FF !important;
        border-color: #0066CC !important;
    }

    .available-event {
        background-color: #BDE7BD !important;
        border-color: #28A745 !important;
        opacity: 0.7;
    }

    .unavailable-event {
        background-color: #FFB6B3 !important;
        border-color: #DC3545 !important;
        opacity: 0.7;
    }

    #legend {
        margin-top: 15px;
        padding: 10px;
        border-radius: 4px;
        background-color: #f8f9fa;
    }

    .legend-item {
        display: inline-block;
        margin-right: 15px;
    }

    .color-box {
        display: inline-block;
        width: 16px;
        height: 16px;
        margin-right: 5px;
        vertical-align: middle;
        border-radius: 3px;
    }

    .view-buttons {
        margin-bottom: 15px;
    }

    /* New styles for event formatting */
    .fc-content {
        text-align: left !important;
        padding-left: 5px !important;
    }

    .custom-event-title {
        font-weight: bold;
    }

    .custom-event-detail {
        display: block;
        margin-top: 2px;
    }
    
    /* Transition settings for smooth toggle */
    #schedule-card-body {
        transition: all 0.3s ease;
    }
</style>

<script>
    $(document).ready(function() {
        // Initialize calendar
        var calendar = $('#calendar').fullCalendar({
            header: {
                left: 'prev,next today',
                center: 'title',
                right: 'agendaDay,agendaWeek,month'
            },
            defaultView: 'agendaWeek',
            minTime: "07:00:00", // Earliest time displayed on the calendar
            maxTime: "20:00:00", // Latest time displayed on the calendar
            slotDuration: "00:30:00", // Set slot intervals to 30 minutes
            scrollTime: "08:00:00", // Auto-scroll to 8 AM
            height: 'auto', // Automatically adjust the height of the calendar
            allDaySlot: false, // Disable the all-day slot
            eventLimit: true, // Allow "more" link when too many events
            navLinks: true, // Can click day/week names to navigate views
            slotEventOverlap: false, // Prevent events from overlapping
            events: [
                <?php foreach ($events as $event) {
                    $color = '';
                    $id = '';
                    $title = '';
                    $description = '';

                    if ($event['event_type'] === 'lesson') {
                        $color = '#FDFD97';
                        $id = $event['student_lesson_id'];
                        $title = $event['student_lesson_name'];
                        $description = $event['student_name'];
                        $className = 'lesson-event';
                    } else if ($event['event_type'] === 'test_session') {
                        $color = '#A0C4FF';
                        $id = $event['test_session_id'];
                        $title = $event['test_name'];
                        $description = '';
                        $className = 'test-event';
                    } else if ($event['event_type'] === 'availability') {
                        $className = $event['status'] === 'Available' ? 'available-event' : 'unavailable-event';
                        $color = $event['status'] === 'Available' ? '#BDE7BD' : '#FFB6B3';
                        $id = $event['availability_id'];
                        $title = $event['status'] . ' Time';
                        $description = '';
                    }
                ?> {
                        id: '<?php echo $id; ?>',
                        title: "<?php echo addslashes($title); ?>",
                        start: '<?php echo $event['date'] . 'T' . $event['start_time']; ?>',
                        end: '<?php echo $event['date'] . 'T' . $event['end_time']; ?>',
                        description: "<?php echo addslashes($description); ?>",
                        color: '<?php echo $color; ?>',
                        type: '<?php echo $event['event_type']; ?>',
                        className: '<?php echo $className; ?>'
                    },
                <?php } ?>
            ],
            eventClick: function(calEvent, jsEvent, view) {
                // Show event details in modal
                var modalContent = '';

                if (calEvent.type === 'lesson') {
                    modalContent = `
                        <div class="alert alert-warning">
                            <h5>Student Lesson</h5>
                        </div>
                        <p><strong>Lesson:</strong> ${calEvent.title}</p>
                        <p><strong>Student:</strong> ${calEvent.description}</p>
                        <p><strong>Date:</strong> ${moment(calEvent.start).format('MMMM D, YYYY')}</p>
                        <p><strong>Time:</strong> ${moment(calEvent.start).format('h:mm A')} - ${moment(calEvent.end).format('h:mm A')}</p>
                    `;
                } else if (calEvent.type === 'test_session') {
                    modalContent = `
                        <div class="alert alert-info">
                            <h5>Test Session</h5>
                        </div>
                        <p><strong>${calEvent.title}</strong></p>
                        <p><strong>Date:</strong> ${moment(calEvent.start).format('MMMM D, YYYY')}</p>
                        <p><strong>Time:</strong> ${moment(calEvent.start).format('h:mm A')} - ${moment(calEvent.end).format('h:mm A')}</p>
                    `;
                } else if (calEvent.type === 'availability') {
                    modalContent = `
                        <div class="alert ${calEvent.className.includes('available') ? 'alert-success' : 'alert-danger'}">
                            <h5>${calEvent.title}</h5>
                        </div>
                        <p><strong>Date:</strong> ${moment(calEvent.start).format('MMMM D, YYYY')}</p>
                        <p><strong>Time:</strong> ${moment(calEvent.start).format('h:mm A')} - ${moment(calEvent.end).format('h:mm A')}</p>
                    `;
                }

                $('#eventModalLabel').text(calEvent.title);
                $('#eventModalBody').html(modalContent);
                $('#eventModal').modal('show');
            },
            eventRender: function(event, element) {
                // Custom rendering for events based on type
                var content = '';

                if (event.type === 'lesson') {
                    content = `
                        <div class="fc-content">
                            <span class="custom-event-title">lesson: ${event.title}</span>
                            <span class="custom-event-detail">${event.description}</span>
                        </div>
                    `;
                } else if (event.type === 'test_session') {
                    content = `
                        <div class="fc-content">
                            <span class="custom-event-title">test: ${event.title}</span>
                        </div>
                    `;
                } else if (event.type === 'availability') {
                    content = `
                        <div class="fc-content">
                            <span class="custom-event-title">${event.title}</span>
                        </div>
                    `;
                }

                element.html(content);
            }
        });

        // Add a legend below the calendar
        var legend = `
            <div id="legend">
                <h6>Legend:</h6>
                <div class="legend-item">
                    <span class="color-box" style="background-color: #FDFD97; border-left: 4px solid #FDAA33;"></span>
                    <span>Student Lessons</span>
                </div>
                <div class="legend-item">
                    <span class="color-box" style="background-color: #A0C4FF; border-left: 4px solid #0066CC;"></span>
                    <span>Test Sessions</span>
                </div>
                <div class="legend-item">
                    <span class="color-box" style="background-color: #BDE7BD; border-left: 4px solid #28A745;"></span>
                    <span>Available Time</span>
                </div>
                <div class="legend-item">
                    <span class="color-box" style="background-color: #FFB6B3; border-left: 4px solid #DC3545;"></span>
                    <span>Unavailable Time</span>
                </div>
            </div>
        `;
        $('.fc-toolbar').after(legend);

        // Button actions for view changes
        $('#viewDay').click(function() {
            calendar.fullCalendar('changeView', 'agendaDay');
            $('.btn-group .btn').removeClass('active');
            $(this).addClass('active');
        });

        $('#viewWeek').click(function() {
            calendar.fullCalendar('changeView', 'agendaWeek');
            $('.btn-group .btn').removeClass('active');
            $(this).addClass('active');
        });

        $('#viewMonth').click(function() {
            calendar.fullCalendar('changeView', 'month');
            $('.btn-group .btn').removeClass('active');
            $(this).addClass('active');
        });

        // Add explicit handlers for the modal close buttons
        $('.modal .close, .modal .btn-secondary').on('click', function() {
            $('#eventModal').modal('hide');
        });
        
        // Toggle schedule card content visibility
        $('#schedule-toggle-btn').click(function() {
            var cardBody = $('#schedule-card-body');
            
            // Remove transition property to avoid conflicts
            cardBody.css('transition', 'none');
            
            // Use jQuery's slideToggle with a specified duration
            cardBody.slideToggle(300, function() {
                // After toggle is complete, refresh the calendar to ensure proper rendering
                if ($(this).is(':visible')) {
                    calendar.fullCalendar('render');
                }
            });
            
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