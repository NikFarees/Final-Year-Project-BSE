<?php
include '../../../database/db_connection.php';

$data = json_decode(file_get_contents("php://input"), true);

$session_id = $data['session_id'] ?? null;
$attendance_status = $data['attendance_status'] ?? null;
$session_type = $data['session_type'] ?? 'lesson'; // Default to 'lesson'

if ($session_id && in_array($attendance_status, ['Attend', 'Absent']) && $session_type === 'lesson') {
    // Update attendance for lessons
    $stmt = $conn->prepare("
        UPDATE student_lessons 
        SET attendance_status = ? 
        WHERE student_lesson_id = ?
    ");
    $stmt->bind_param("ss", $attendance_status, $session_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'error' => $stmt->error]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Invalid input']);
}
?>