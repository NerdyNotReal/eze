<?php
ob_start(); // Start output buffering
session_start();
include('../db.php');
header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized', 401);
    }

    $userId = $_SESSION['user_id'];
    $workspaceId = $_POST['workspaceId'] ?? null;
    $title = $_POST['formTitle'] ?? '';
    $description = $_POST['formDescription'] ?? '';

    if (!$workspaceId || !$title) {
        throw new Exception('Missing required fields', 400);
    }

    // Check if user has access to create forms in this workspace
    $checkAccessQuery = "SELECT role FROM workspace_users 
                        WHERE workspace_id = ? 
                        AND user_id = ? 
                        AND role IN ('owner', 'admin', 'member')";
    
    $stmt = mysqli_prepare($conn, $checkAccessQuery);
    mysqli_stmt_bind_param($stmt, "ii", $workspaceId, $userId);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!mysqli_fetch_assoc($result)) {
        throw new Exception('You do not have permission to create forms in this workspace', 403);
    }

    // Create the form
    mysqli_begin_transaction($conn);

    $createFormQuery = "INSERT INTO forms (workspace_id, title, description, owner_id) 
                       VALUES (?, ?, ?, ?)";
    
    $stmt = mysqli_prepare($conn, $createFormQuery);
    mysqli_stmt_bind_param($stmt, "issi", $workspaceId, $title, $description, $userId);
    
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to create form: ' . mysqli_error($conn));
    }

    $formId = mysqli_insert_id($conn);
    mysqli_commit($conn);

    ob_clean(); // Clear output buffer
    echo json_encode([
        'success' => true,
        'message' => 'Form created successfully',
        'form' => [
            'id' => $formId,
            'title' => $title,
            'description' => $description,
            'workspace_id' => $workspaceId,
            'owner_id' => $userId,
            'status' => 'draft'
        ]
    ]);

} catch (Exception $e) {
    if (isset($conn) && mysqli_ping($conn)) {
        mysqli_rollback($conn);
    }
    
    $code = $e->getCode() ?: 500;
    http_response_code($code);
    ob_clean(); // Clear output buffer
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
    ob_end_flush(); // End output buffering
}
?>