<?php
header('Content-Type: application/json');
require_once('../db.php');
session_start();

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit();
}

try {
    // Get JSON data
    $jsonData = file_get_contents('php://input');
    $data = json_decode($jsonData, true);
    
    if (!isset($data['workspaceId']) || !isset($data['role'])) {
        throw new Exception('Missing required parameters');
    }

    $workspaceId = intval($data['workspaceId']);
    $role = mysqli_real_escape_string($conn, $data['role']);
    $userId = $_SESSION['user_id'];

    // Verify user has permission to generate invite links
    $checkPermissionQuery = "SELECT 1 FROM workspace_users 
                            WHERE workspace_id = ? 
                            AND user_id = ? 
                            AND (role = 'owner' OR role = 'admin')";
    
    $stmt = mysqli_prepare($conn, $checkPermissionQuery);
    mysqli_stmt_bind_param($stmt, "ii", $workspaceId, $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!mysqli_fetch_assoc($result)) {
        throw new Exception('You do not have permission to generate invite links');
    }

    // Generate a unique token
    $token = bin2hex(random_bytes(32));
    $expiresAt = date('Y-m-d H:i:s', strtotime('+7 days')); // Link expires in 7 days

    // Start transaction
    mysqli_begin_transaction($conn);

    // Insert the invite
    $insertQuery = "INSERT INTO workspace_invites (workspace_id, token, role, created_by, expires_at) 
                   VALUES (?, ?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $insertQuery);
    mysqli_stmt_bind_param($stmt, "issis", $workspaceId, $token, $role, $userId, $expiresAt);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to generate invite: ' . mysqli_error($conn));
    }

    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'token' => $token
    ]);

} catch (Exception $e) {
    if (isset($conn) && mysqli_ping($conn)) {
        mysqli_rollback($conn);
    }
    
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>