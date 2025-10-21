<?php
require_once __DIR__ . '/../includes/auth.php';
requireRole('doctor');

header('Content-Type: application/json');

$conn = getDBConnection();
$user_id = getCurrentUserId();

// Get doctor info
$stmt = $conn->prepare("SELECT doctor_id FROM doctors WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$doctor_result = $stmt->get_result();

if ($doctor_result->num_rows === 0) {
    echo json_encode([]);
    exit();
}

$doctor_id = $doctor_result->fetch_assoc()['doctor_id'];

// Get patient_id and doctor_id from query params
$patient_id = isset($_GET['patient_id']) ? intval($_GET['patient_id']) : 0;
$request_doctor_id = isset($_GET['doctor_id']) ? intval($_GET['doctor_id']) : 0;

// Verify doctor_id matches
if ($doctor_id !== $request_doctor_id || $patient_id === 0) {
    echo json_encode([]);
    exit();
}

// Get appointments for this patient with this doctor
$stmt = $conn->prepare("
    SELECT appointment_id, appointment_date, appointment_time, status
    FROM appointments
    WHERE patient_id = ? AND doctor_id = ?
    ORDER BY appointment_date DESC, appointment_time DESC
");
$stmt->bind_param("ii", $patient_id, $doctor_id);
$stmt->execute();
$result = $stmt->get_result();

$appointments = [];
while ($row = $result->fetch_assoc()) {
    $appointments[] = [
        'appointment_id' => $row['appointment_id'],
        'appointment_date' => date('M d, Y', strtotime($row['appointment_date'])),
        'appointment_time' => date('h:i A', strtotime($row['appointment_time'])),
        'status' => ucfirst($row['status'])
    ];
}

echo json_encode($appointments);

$stmt->close();
closeDBConnection($conn);
