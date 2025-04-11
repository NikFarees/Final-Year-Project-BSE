<?php
include '../../../database/db_connection.php';

$testId = $_GET['test_id'] ?? null;

if ($testId) {
    $query = "
        SELECT 
            st.student_test_id,
            u.name AS student_name,
            l.license_name
        FROM 
            student_tests AS st
        JOIN 
            student_licenses AS sl ON st.student_license_id = sl.student_license_id
        JOIN 
            licenses AS l ON sl.license_id = l.license_id
        JOIN 
            students AS s ON sl.student_id = s.student_id
        JOIN 
            users AS u ON s.user_id = u.user_id
        WHERE 
            st.status = 'Pending' AND st.schedule_status = 'Unassigned' AND st.test_id = '$testId'
    ";

    $result = mysqli_query($conn, $query);
    $students = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $students[] = $row;
    }

    echo json_encode($students);
}
?>
