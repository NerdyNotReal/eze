<?php
session_start();
include('../db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];
$workspaceId = mysqli_real_escape_string($conn, $_GET['workspace_id']);

// Verify workspace exists and user has access
$sql = "SELECT id FROM workspaces WHERE id = '$workspaceId' AND owner_id = '$userId'";
$result = mysqli_query($conn, $sql);

if (!$result || mysqli_num_rows($result) === 0) {
    http_response_code(403);
    echo json_encode(['error' => 'Access denied']);
    exit();
}

// Fetch forms
$sql = "SELECT * FROM forms WHERE workspace_id = '$workspaceId'";
$result = mysqli_query($conn, $sql);

$forms = [];
while ($row = mysqli_fetch_assoc($result)) {
    $forms[] = [
        'id' => $row['id'],
        'title' => $row['title'],
        'description' => $row['description'],
        'status' => $row['status']
    ];
}

echo json_encode(['forms' => $forms]);
mysqli_close($conn); 