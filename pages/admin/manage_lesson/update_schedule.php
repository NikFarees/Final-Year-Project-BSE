<?php
include '../../../database/db_connection.php'; // Include DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lesson_id = $_POST['lesson_id'];
    $date = $_POST['date'];
    $start_time = $_POST['start_time'];
    $end_time = $_POST['end_time'];

    // Make sure required values exist
    if ($lesson_id && $date && $start_time && $end_time) {
        $query = "
            UPDATE student_lessons 
            SET date = ?, start_time = ?, end_time = ?, schedule_status = 'Assigned' 
            WHERE student_lesson_id = ?
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("ssss", $date, $start_time, $end_time, $lesson_id);
        $result = $stmt->execute();

        if ($result) {
            echo "success";
        } else {
            echo "error";
        }
    } else {
        echo "invalid";
    }
} else {
    echo "invalid request";
}
?>
