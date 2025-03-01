<?php
session_start();
include('../db.php');

header('Content-Type: application/json');

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

// Check if it's a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Get the request body
$data = json_decode(file_get_contents('php://input'), true);

// Validate input
if (!isset($data['id']) || empty($data['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Workspace ID is required']);
    exit();
}

$userId = $_SESSION['user_id'];
$workspaceId = mysqli_real_escape_string($conn, $data['id']);

// First check if the workspace exists and belongs to the user
$checkSql = "SELECT id FROM workspaces WHERE id = '$workspaceId' AND owner_id = '$userId'";
$checkResult = mysqli_query($conn, $checkSql);

if (!$checkResult || mysqli_num_rows($checkResult) === 0) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Workspace not found or unauthorized']);
    exit();
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // Delete all forms in the workspace first
    $deleteFormsSql = "DELETE FROM forms WHERE workspace_id = '$workspaceId'";
    if (!mysqli_query($conn, $deleteFormsSql)) {
        throw new Exception('Failed to delete workspace forms: ' . mysqli_error($conn));
    }

    // Then delete the workspace
    $deleteWorkspaceSql = "DELETE FROM workspaces WHERE id = '$workspaceId' AND owner_id = '$userId'";
    if (!mysqli_query($conn, $deleteWorkspaceSql)) {
        throw new Exception('Failed to delete workspace: ' . mysqli_error($conn));
    }

    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode([
        'success' => true,
        'message' => 'Workspace deleted successfully'
    ]);

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

mysqli_close($conn); 