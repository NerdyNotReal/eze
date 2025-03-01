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

// Get JSON data from request body
$data = json_decode(file_get_contents('php://input'), true);
$formId = $data['id'] ?? null;

if (!$formId) {
    http_response_code(400);
    echo json_encode(['success' => false, 'error' => 'Form ID is required']);
    exit();
}

// Start transaction
mysqli_begin_transaction($conn);

try {
    // First verify that the user has access to this form
    $checkSql = "SELECT f.id 
                FROM forms f 
                INNER JOIN workspaces w ON f.workspace_id = w.id 
                WHERE f.id = ? AND w.owner_id = ?";
    
    $stmt = mysqli_prepare($conn, $checkSql);
    mysqli_stmt_bind_param($stmt, "ii", $formId, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);

    if (!$result || mysqli_num_rows($result) === 0) {
        throw new Exception('Form not found or unauthorized');
    }

    // Delete form elements first (due to foreign key constraint)
    $deleteElementsSql = "DELETE FROM form_elements WHERE form_id = ?";
    $stmt = mysqli_prepare($conn, $deleteElementsSql);
    mysqli_stmt_bind_param($stmt, "i", $formId);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to delete form elements');
    }

    // Delete form responses
    $deleteResponsesSql = "DELETE FROM form_responses WHERE form_id = ?";
    $stmt = mysqli_prepare($conn, $deleteResponsesSql);
    mysqli_stmt_bind_param($stmt, "i", $formId);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to delete form responses');
    }

    // Finally delete the form
    $deleteFormSql = "DELETE FROM forms WHERE id = ?";
    $stmt = mysqli_prepare($conn, $deleteFormSql);
    mysqli_stmt_bind_param($stmt, "i", $formId);
    if (!mysqli_stmt_execute($stmt)) {
        throw new Exception('Failed to delete form');
    }

    // Commit transaction
    mysqli_commit($conn);
    
    echo json_encode(['success' => true, 'message' => 'Form deleted successfully']);

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

mysqli_close($conn);