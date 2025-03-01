<?php
session_start();
require_once('../db.php');

// Enable error logging
error_log("Starting form submission process");
error_log("POST data: " . print_r($_POST, true));
error_log("FILES data: " . print_r($_FILES, true));

header('Content-Type: application/json');

// Check if form_id is provided
if (!isset($_POST['form_id'])) {
    error_log("Form ID not provided");
    echo json_encode(['success' => false, 'error' => 'Form ID is required']);
    exit;
}

$formId = $_POST['form_id'];

try {
    // Get form details and check if it exists
    $formSql = "SELECT f.*, u.username as creator FROM forms f 
                JOIN users u ON f.owner_id = u.id 
                WHERE f.id = ?";
    $stmt = mysqli_prepare($conn, $formSql);
    mysqli_stmt_bind_param($stmt, "i", $formId);
    mysqli_stmt_execute($stmt);
    $formResult = mysqli_stmt_get_result($stmt);
    $form = mysqli_fetch_assoc($formResult);

    if (!$form) {
        error_log("Form not found: " . $formId);
        echo json_encode(['success' => false, 'error' => 'Form not found']);
        exit;
    }

    // Get form elements
    $elementsSql = "SELECT id, element_type, element_data FROM form_elements WHERE form_id = ? ORDER BY position";
    $stmt = mysqli_prepare($conn, $elementsSql);
    mysqli_stmt_bind_param($stmt, "i", $formId);
    mysqli_stmt_execute($stmt);
    $elementsResult = mysqli_stmt_get_result($stmt);
    $elements = mysqli_fetch_all($elementsResult, MYSQLI_ASSOC);

    error_log("Found " . count($elements) . " form elements");

    // Start transaction
    mysqli_begin_transaction($conn);

    // Process each form element
    foreach ($elements as $element) {
        $elementId = $element['id'];
        $elementType = $element['element_type'];
        $elementData = json_decode($element['element_data'], true);

        error_log("Processing element: " . $elementType . " (ID: " . $elementId . ")");

        // Get submitted value
        $submittedValue = null;
        $fieldName = "element_" . $elementId;

        // Handle different element types
        switch ($elementType) {
            case 'checkbox':
            case 'checkbox-group':
            case 'multi-select':
                // Handle multiple values
                $submittedValue = isset($_POST[$fieldName]) ? json_encode($_POST[$fieldName]) : null;
                break;

            case 'file-upload':
            case 'image-upload':
            case 'document-upload':
                // Handle file uploads
                if (isset($_FILES[$fieldName]) && $_FILES[$fieldName]['error'] === UPLOAD_ERR_OK) {
                    $uploadDir = '../uploads/';
                    if (!file_exists($uploadDir)) {
                        mkdir($uploadDir, 0777, true);
                    }

                    $fileName = uniqid() . '_' . basename($_FILES[$fieldName]['name']);
                    $filePath = $uploadDir . $fileName;

                    if (move_uploaded_file($_FILES[$fieldName]['tmp_name'], $filePath)) {
                        $submittedValue = $fileName;
                    } else {
                        error_log("Failed to move uploaded file: " . $fieldName);
                        throw new Exception('Failed to upload file');
                    }
                }
                break;

            default:
                // Handle single value fields
                $submittedValue = isset($_POST[$fieldName]) ? $_POST[$fieldName] : null;
                break;
        }

        // Validate required fields
        if (($elementData['required'] ?? false) && 
            ($submittedValue === null || $submittedValue === '' || 
             (is_array($submittedValue) && count($submittedValue) === 0))) {
            error_log("Required field empty: " . $fieldName);
            throw new Exception('Required field is empty: ' . ($elementData['label'] ?? 'Unknown field'));
        }

        // Save response
        $responseSql = "INSERT INTO form_responses (form_id, element_id, response_value) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $responseSql);
        mysqli_stmt_bind_param($stmt, "iis", $formId, $elementId, $submittedValue);
        
        if (!mysqli_stmt_execute($stmt)) {
            error_log("Failed to save response for element: " . $elementId);
            throw new Exception('Failed to save response');
        }
    }

    // Commit transaction
    mysqli_commit($conn);
    error_log("Form submission successful");
    echo json_encode(['success' => true]);

} catch (Exception $e) {
    // Rollback transaction on error
    mysqli_rollback($conn);
    error_log("Form submission failed: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

mysqli_close($conn); 