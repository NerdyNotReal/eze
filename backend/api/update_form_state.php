<?php
session_start();
include('../db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$formId = $data['formId'] ?? null;
$state = $data['state'] ?? null;

if (!$formId || !$state || !in_array($state, ['draft', 'published'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Invalid form ID or state']);
    exit;
}

// Update form state
$sql = "UPDATE forms SET status = ? WHERE id = ? AND owner_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "sii", $state, $formId, $_SESSION['user_id']);

if (mysqli_stmt_execute($stmt)) {
    echo json_encode(['success' => true]);
} else {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to update form state']);
}

mysqli_close($conn);