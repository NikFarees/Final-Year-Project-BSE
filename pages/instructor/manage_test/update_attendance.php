<?php
include '../../../database/db_connection.php';

$data = json_decode(file_get_contents("php://input"), true);

$session_id = $data['session_id'] ?? null;
$attendance_status = $data['attendance_status'] ?? null;

if ($session_id && in_array($attendance_status, ['Attend', 'Absent'])) {
    $stmt = $conn->prepare("
        UPDATE student_test_sessions 
        SET attendance_status = ? 
        WHERE student_test_session_id = ?
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
