<?php
session_start();
include('../db.php');

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized', 401);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Method not allowed', 405);
    }

    $userId = $_SESSION['user_id'];
    $name = $_POST['workspaceName'] ?? '';
    $description = $_POST['workspaceDescription'] ?? '';

    if (empty($name)) {
        throw new Exception('Workspace name is required', 400);
    }

    // Start transaction
    mysqli_begin_transaction($conn);

    // Create workspace
    $createWorkspaceSql = "INSERT INTO workspaces (name, description, owner_id, created_at) VALUES (?, ?, ?, NOW())";
    $createWorkspaceStmt = mysqli_prepare($conn, $createWorkspaceSql);
    
    if (!$createWorkspaceStmt) {
        throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($createWorkspaceStmt, "ssi", $name, $description, $userId);

    if (!mysqli_stmt_execute($createWorkspaceStmt)) {
        throw new Exception('Failed to create workspace: ' . mysqli_error($conn));
    }

    $workspaceId = mysqli_insert_id($conn);

    // Add owner to workspace_users
    $addUserSql = "INSERT INTO workspace_users (workspace_id, user_id, role, created_at) VALUES (?, ?, 'owner', NOW())";
    $addUserStmt = mysqli_prepare($conn, $addUserSql);
    
    if (!$addUserStmt) {
        throw new Exception('Failed to prepare statement: ' . mysqli_error($conn));
    }

    mysqli_stmt_bind_param($addUserStmt, "ii", $workspaceId, $userId);

    if (!mysqli_stmt_execute($addUserStmt)) {
        throw new Exception('Failed to add owner to workspace: ' . mysqli_error($conn));
    }

    // Commit transaction
    mysqli_commit($conn);

    echo json_encode([
        'success' => true,
        'message' => 'Workspace created successfully',
        'workspace' => [
            'id' => $workspaceId,
            'name' => $name,
            'description' => $description
        ]
    ]);

} catch (Exception $e) {
    if (isset($conn)) {
        mysqli_rollback($conn);
    }
    $code = $e->getCode() ?: 500;
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($createWorkspaceStmt)) {
        mysqli_stmt_close($createWorkspaceStmt);
    }
    if (isset($addUserStmt)) {
        mysqli_stmt_close($addUserStmt);
    }
    if (isset($conn)) {
        mysqli_close($conn);
    }
}
