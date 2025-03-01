<?php
session_start();
require '../db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

// Handle both JSON and form-urlencoded data
$contentType = $_SERVER["CONTENT_TYPE"] ?? '';
if (strpos($contentType, 'application/json') !== false) {
    $data = json_decode(file_get_contents('php://input'), true);
} else {
    $data = $_POST;
}

$formId = $data['formId'] ?? null;
$elementId = $data['elementId'] ?? null;

error_log("Received delete request - formId: " . $formId . ", elementId: " . $elementId);

if (!$formId || !$elementId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Missing required fields']);
    exit;
}

// First, check if the form exists and get workspace info
$basicCheck = "SELECT f.*, w.id as workspace_id 
               FROM forms f 
               INNER JOIN workspaces w ON f.workspace_id = w.id 
               WHERE f.id = ?";
$stmt = mysqli_prepare($conn, $basicCheck);
mysqli_stmt_bind_param($stmt, "i", $formId);
mysqli_stmt_execute($stmt);
$basicResult = mysqli_stmt_get_result($stmt);
$formInfo = mysqli_fetch_assoc($basicResult);

error_log("Form and workspace info: " . print_r($formInfo, true));

if (!$formInfo) {
    http_response_code(404);
    echo json_encode(['success' => false, 'error' => 'Form not found']);
    exit;
}

// Check workspace permissions
$workspaceCheck = "SELECT wu.role as workspace_role
                   FROM workspace_users wu
                   WHERE wu.workspace_id = ? AND wu.user_id = ?";
$stmt = mysqli_prepare($conn, $workspaceCheck);
mysqli_stmt_bind_param($stmt, "ii", $formInfo['workspace_id'], $_SESSION['user_id']);
mysqli_stmt_execute($stmt);
$workspaceResult = mysqli_stmt_get_result($stmt);
$workspaceInfo = mysqli_fetch_assoc($workspaceResult);

error_log("Workspace permissions: " . print_r($workspaceInfo, true));

// Grant access if user:
// 1. Is the workspace owner/admin
// 2. Is the form owner
// 3. Is a workspace member and created the element
$hasAccess = false;

if ($workspaceInfo && in_array($workspaceInfo['workspace_role'], ['owner', 'admin'])) {
    error_log("Access granted - User is workspace owner/admin");
    $hasAccess = true;
} elseif ($formInfo['owner_id'] == $_SESSION['user_id']) {
    error_log("Access granted - User is form owner");
    $hasAccess = true;
}

error_log("Final access decision: " . ($hasAccess ? "Granted" : "Denied"));

if (!$hasAccess) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Access denied']);
    exit;
}

try {
    // Delete the element
    $sql = "DELETE FROM form_elements WHERE id = ? AND form_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $elementId, $formId);
    
    if (mysqli_stmt_execute($stmt)) {
        if (mysqli_affected_rows($conn) > 0) {
            // Update positions of remaining elements
            $updateSql = "UPDATE form_elements SET position = position - 1 WHERE form_id = ? AND position > (SELECT position FROM (SELECT position FROM form_elements WHERE id = ?) AS temp)";
            $updateStmt = mysqli_prepare($conn, $updateSql);
            mysqli_stmt_bind_param($updateStmt, "ii", $formId, $elementId);
            mysqli_stmt_execute($updateStmt);
            
            echo json_encode([
                'success' => true,
                'message' => 'Form element deleted successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'error' => 'Element not found or already deleted'
            ]);
        }
    } else {
        throw new Exception(mysqli_error($conn));
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to delete form element: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
