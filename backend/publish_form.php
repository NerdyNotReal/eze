<?php
header('Content-Type: application/json');
exit();
error_reporting(E_ALL);
ini_set('display_errors', '1');
session_start();
include('db.php');

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$formId = $data['formId'];
$formElements = $data['formElements'];

try {
    // Verify user has access to the form
    $checkAccessSql = "SELECT f.id 
                      FROM forms f 
                      LEFT JOIN form_collaborators fc ON f.id = fc.form_id AND fc.user_id = ?
                      LEFT JOIN workspace_users wu ON f.workspace_id = wu.workspace_id AND wu.user_id = ?
                      WHERE f.id = ? 
                      AND (f.owner_id = ? 
                           OR fc.role IN ('editor', 'admin')
                           OR wu.role IN ('owner', 'admin', 'member'))";
    $stmt = mysqli_prepare($conn, $checkAccessSql);
    mysqli_stmt_bind_param($stmt, "iiii", $_SESSION['user_id'], $_SESSION['user_id'], $formId, $_SESSION['user_id']);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    if (!mysqli_fetch_assoc($result)) {
        throw new Exception('Access denied. You do not have permission to modify this form.');
    }

    // Start transaction
    mysqli_begin_transaction($conn);

    // Update form status to published
    $updateFormSql = "UPDATE forms SET status = 'published' WHERE id = ?";
    $stmt = mysqli_prepare($conn, $updateFormSql);
    mysqli_stmt_bind_param($stmt, "i", $formId);
    mysqli_stmt_execute($stmt);

    // Delete existing form elements
    $deleteElementsSql = "DELETE FROM form_elements WHERE form_id = ?";
    $stmt = mysqli_prepare($conn, $deleteElementsSql);
    mysqli_stmt_execute($stmt);

    // Insert new form elements
    $insertElementSql = "INSERT INTO form_elements (form_id, element_type, element_data, position) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $insertElementSql);

    foreach ($formElements as $index => $element) {
        $elementType = $element['type'];
        $elementData = [
            'label' => $element['label'],
            'required' => $element['required'] ?? false,
            'properties' => $element['properties'] ?? []
        ];
        $position = $index;
        $elementDataJson = json_encode($elementData);

        mysqli_stmt_bind_param($stmt, "issi", $formId, $elementType, $elementDataJson, $position);
        mysqli_stmt_execute($stmt);
    }

    // Commit transaction
    mysqli_commit($conn);

    // Generate the public URL
    $publicUrl = sprintf(
        "%s://%s/templates/form.php?id=%s",
        isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' ? 'https' : 'http',
        $_SERVER['SERVER_NAME'],
        $formId
    );

    echo json_encode([
        'success' => true,
        'message' => 'Form published successfully',
        'publicUrl' => $publicUrl
    ]);

} catch (Exception $e) {
    mysqli_rollback($conn);
    http_response_code(500);
    echo json_encode(['error' => 'Failed to publish form: ' . $e->getMessage()]);
}

mysqli_close($conn);
