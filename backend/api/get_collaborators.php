<?php
session_start();
include('../db.php');
header('Content-Type: application/json');
try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized');
    }
    $userId = $_SESSION['user_id'];
    $workspaceId = $_GET['workspaceId'] ?? null;
    if (!$workspaceId) {
        throw new Exception('Missing workspace ID');
    }

    // First check if the current user has access to this workspace
    $accessSql = "SELECT role FROM workspace_users WHERE workspace_id = ? AND user_id = ?";
    $accessStmt = mysqli_prepare($conn, $accessSql);
    if (!$accessStmt) {
        throw new Exception('Failed to prepare access check statement: ' . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($accessStmt, "ii", $workspaceId, $userId);
    if (!mysqli_stmt_execute($accessStmt)) {
        throw new Exception('Failed to check workspace access: ' . mysqli_stmt_error($accessStmt));
    }
    $accessResult = mysqli_stmt_get_result($accessStmt);
    if (mysqli_num_rows($accessResult) === 0) {
        throw new Exception('Access denied to this workspace');
    }

    // Get workspace members
    $sql = "SELECT 
                u.id as user_id,
                u.email,
                CASE 
                    WHEN w.owner_id = u.id THEN 'owner'
                    ELSE wu.role 
                END as role,
                w.owner_id = u.id as is_owner
            FROM users u
            LEFT JOIN workspace_users wu ON u.id = wu.user_id AND wu.workspace_id = ?
            LEFT JOIN workspaces w ON w.id = ?
            WHERE u.id = w.owner_id OR wu.workspace_id = ?
            ORDER BY is_owner DESC, u.email";
            
    $stmt = mysqli_prepare($conn, $sql);
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
    }
    mysqli_stmt_bind_param($stmt, "iii", $workspaceId, $workspaceId, $workspaceId);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to execute query: ' . mysqli_stmt_error($stmt));
    }
    $result = mysqli_stmt_get_result($stmt);
    if ($result === false) {
        throw new Exception('Failed to get query result: ' . mysqli_error($conn));
    }
    
    $collaborators = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $collaborators[] = [
            'user_id' => $row['user_id'],
            'email' => $row['email'],
            'role' => $row['role'],
            'is_owner' => (bool)$row['is_owner']
        ];
    }
    
    echo json_encode([
        'success' => true,
        'collaborators' => $collaborators
    ]);
} catch (Exception $e) {
    http_response_code($e->getMessage() === 'Unauthorized' ? 401 : 400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($accessStmt)) {
        mysqli_stmt_close($accessStmt);
    }
    if (isset($stmt)) {
        mysqli_stmt_close($stmt);
    }
    if (isset($conn)) {
        mysqli_close($conn);
    }
}