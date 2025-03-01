<?php
session_start();
include('../db.php');

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

$userId = $_SESSION['user_id'];

// Fetch all workspaces owned by or shared with the user
$sql = "SELECT 
            w.*, 
            COUNT(DISTINCT f.id) as form_count,
            CASE 
                WHEN w.owner_id = ? THEN 'owner'
                ELSE wu.role 
            END as user_role
        FROM workspaces w 
        LEFT JOIN forms f ON w.id = f.workspace_id 
        LEFT JOIN workspace_users wu ON w.id = wu.workspace_id AND wu.user_id = ?
        WHERE w.owner_id = ? OR wu.user_id = ?
        GROUP BY w.id 
        ORDER BY w.created_at DESC";

$stmt = mysqli_prepare($conn, $sql);
if (!$stmt) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to prepare statement']);
    exit();
}

mysqli_stmt_bind_param($stmt, "iiii", $userId, $userId, $userId, $userId);
if (!mysqli_stmt_execute($stmt)) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Failed to fetch workspaces']);
    exit();
}

$result = mysqli_stmt_get_result($stmt);
$workspaces = [];

while ($row = mysqli_fetch_assoc($result)) {
    $workspaces[] = [
        'id' => $row['id'],
        'name' => $row['name'],
        'description' => $row['description'],
        'created_at' => $row['created_at'],
        'form_count' => (int)$row['form_count'],
        'role' => $row['user_role']
    ];
}

echo json_encode([
    'success' => true,
    'workspaces' => $workspaces
]);

mysqli_close($conn);