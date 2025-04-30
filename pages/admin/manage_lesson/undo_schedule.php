<?php
include '../../../database/db_connection.php'; // Include DB connection

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lesson_id = $_POST['lesson_id'];
    
    // Make sure required values exist
    if ($lesson_id) {
        $query = "
            UPDATE student_lessons 
            SET date = NULL, start_time = NULL, end_time = NULL, schedule_status = 'Unassigned' 
            WHERE student_lesson_id = ?
        ";

        $stmt = $conn->prepare($query);
        $stmt->bind_param("s", $lesson_id);
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