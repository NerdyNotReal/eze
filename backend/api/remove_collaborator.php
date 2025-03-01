<?php
session_start();
include('../db.php');
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized', 401);
    }

    $userId = $_SESSION['user_id'];
    $data = json_decode(file_get_contents('php://input'), true);
    $workspaceId = $data['workspaceId'] ?? null;
    $targetUserId = $data['userId'] ?? null;

    if (!$workspaceId || !$targetUserId) {
        throw new Exception('Missing required fields', 400);
    }

    // Check if target user is not the workspace owner
    $checkOwnerSql = "SELECT owner_id FROM workspaces WHERE id = ? AND owner_id = ?";
    $stmt = mysqli_prepare($conn, $checkOwnerSql);
    mysqli_stmt_bind_param($stmt, "ii", $workspaceId, $targetUserId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    if (mysqli_num_rows($result) > 0) {
        throw new Exception('Cannot remove the workspace owner', 403);
    }

    // Check if current user has permission (owner or admin)
    $checkPermissionSql = "SELECT wu.role 
                          FROM workspace_users wu
                          JOIN workspaces w ON w.id = wu.workspace_id
                          WHERE wu.workspace_id = ? 
                          AND wu.user_id = ? 
                          AND (wu.role IN ('owner', 'admin') OR w.owner_id = ?)";
    
    $stmt = mysqli_prepare($conn, $checkPermissionSql);
    mysqli_stmt_bind_param($stmt, "iii", $workspaceId, $userId, $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (mysqli_num_rows($result) === 0) {
        throw new Exception('Not authorized to remove users from this workspace', 403);
    }

    // Remove the user
    $removeUserSql = "DELETE FROM workspace_users WHERE workspace_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $removeUserSql);
    mysqli_stmt_bind_param($stmt, "ii", $workspaceId, $targetUserId);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to remove user: ' . mysqli_error($conn), 500);
    }

    if (mysqli_affected_rows($conn) === 0) {
        throw new Exception('User not found in workspace', 404);
    }

    echo json_encode([
        'success' => true,
        'message' => 'User removed successfully'
    ]);

} catch (Exception $e) {
    http_response_code($e->getCode() ?: 500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    if (isset($conn)) {
        mysqli_close($conn);
    }
}