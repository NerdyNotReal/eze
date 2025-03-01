<?php
session_start();
include('../db.php');

header('Content-Type: application/json');

try {
    if (!isset($_SESSION['user_id'])) {
        throw new Exception('Unauthorized', 401);
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        throw new Exception('Method not allowed', 405);
    }

    $workspaceId = $_GET['id'] ?? null;
    $userId = $_SESSION['user_id'];

    if (!$workspaceId) {
        throw new Exception('Workspace ID is required', 400);
    }

    // Check if user has access to workspace and get their role
    $accessQuery = "SELECT w.*, wu.role, 
                    CASE 
                        WHEN w.owner_id = ? THEN 'owner'
                        ELSE wu.role 
                    END as user_role 
                   FROM workspaces w 
                   INNER JOIN workspace_users wu ON w.id = wu.workspace_id 
                   WHERE w.id = ? AND wu.user_id = ?";
    
    $accessStmt = mysqli_prepare($conn, $accessQuery);
    mysqli_stmt_bind_param($accessStmt, "iii", $userId, $workspaceId, $userId);
    mysqli_stmt_execute($accessStmt);
    $result = mysqli_stmt_get_result($accessStmt);
    $workspace = mysqli_fetch_assoc($result);

    if (!$workspace) {
        throw new Exception('Workspace not found or access denied', 404);
    }

    // Get forms for this workspace
    $formsQuery = "SELECT * FROM forms WHERE workspace_id = ? ORDER BY created_at DESC";
    $formsStmt = mysqli_prepare($conn, $formsQuery);
    mysqli_stmt_bind_param($formsStmt, "i", $workspaceId);
    mysqli_stmt_execute($formsStmt);
    $formsResult = mysqli_stmt_get_result($formsStmt);
    $forms = [];
    while ($form = mysqli_fetch_assoc($formsResult)) {
        $forms[] = $form;
    }

    // Include user_role in the response
    $workspace['role'] = $workspace['user_role'];
    unset($workspace['user_role']); // Remove duplicate field

    echo json_encode([
        'success' => true,
        'workspace' => $workspace,
        'forms' => $forms
    ]);

} catch (Exception $e) {
    $code = $e->getCode() ?: 500;
    http_response_code($code);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
} finally {
    if (isset($accessStmt)) mysqli_stmt_close($accessStmt);
    if (isset($formsStmt)) mysqli_stmt_close($formsStmt);
    if (isset($conn)) mysqli_close($conn);
}
?>