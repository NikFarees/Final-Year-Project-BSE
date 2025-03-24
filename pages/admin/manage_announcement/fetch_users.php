<?php
include '../../../database/db_connection.php';

$role = $_GET['role'];

// Validate the role parameter to ensure it is either 'instructor' or 'student'
if ($role !== 'instructor' && $role !== 'student') {
    echo json_encode([]);
    exit;
}

$query = "SELECT user_id, name FROM users WHERE role_id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("s", $role);
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    $users[] = $row;
}

echo json_encode($users);
?>