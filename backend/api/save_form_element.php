<?php
session_start();
require '../db.php';

error_log("Session user_id: " . ($_SESSION['user_id'] ?? 'not set'));
error_log("POST data: " . print_r($_POST, true));
error_log("Raw input: " . file_get_contents('php://input'));

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

$data = json_decode(file_get_contents('php://input'), true);
$formId = $data['formId'] ?? null;
$elementType = $data['type'] ?? null;
$elementData = $data['data'] ?? null;
$position = $data['position'] ?? 0;

if (!$formId || !$elementType || !$elementData) {
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

// Allow saving if user:
// 1. Is the workspace owner/admin
// 2. Is the form owner
// 3. Is a workspace member
$hasAccess = false;

if ($workspaceInfo) {
    if (in_array($workspaceInfo['workspace_role'], ['owner', 'admin', 'member'])) {
        error_log("Access granted - User has workspace role: " . $workspaceInfo['workspace_role']);
        $hasAccess = true;
    }
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
    // Prepare the element data for storage
    $processedData = [
        'label' => $elementData['label'] ?? '',
        'placeholder' => $elementData['placeholder'] ?? '',
        'description' => $elementData['description'] ?? '',
        'tooltip' => $elementData['tooltip'] ?? '',
        'required' => $elementData['required'] ?? false,
        'attributes' => $elementData['attributes'] ?? [],
        'type' => $elementData['type'] ?? $elementType
    ];

    // Add specific handling for different element types
    switch($elementType) {
        case 'dropdown':
        case 'radio-button':
        case 'checkbox':
        case 'multi-select':
            $processedData['options'] = $elementData['options'] ?? [];
            break;
        case 'date-picker':
        case 'date-range':
            $processedData['format'] = $elementData['format'] ?? 'YYYY-MM-DD';
            $processedData['min_date'] = $elementData['min_date'] ?? null;
            $processedData['max_date'] = $elementData['max_date'] ?? null;
            break;
        case 'number-field':
        case 'price-field':
            $processedData['min'] = $elementData['min'] ?? null;
            $processedData['max'] = $elementData['max'] ?? null;
            $processedData['step'] = $elementData['step'] ?? '1';
            break;
        case 'file-upload':
        case 'image-upload':
        case 'document-upload':
            $processedData['max_size'] = $elementData['max_size'] ?? '5MB';
            $processedData['allowed_types'] = $elementData['allowed_types'] ?? [];
            break;
    }

    $sql = "INSERT INTO form_elements (form_id, element_type, element_data, position) VALUES (?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    $elementDataJson = json_encode($processedData);

    mysqli_stmt_bind_param($stmt, "issi", $formId, $elementType, $elementDataJson, $position);
    
    if (mysqli_stmt_execute($stmt)) {
        $elementId = mysqli_insert_id($conn);
        echo json_encode([
            'success' => true,
            'elementId' => $elementId,
            'message' => 'Form element saved successfully'
        ]);
    } else {
        throw new Exception(mysqli_error($conn));
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to save form element: ' . $e->getMessage()
    ]);
}

mysqli_close($conn);
?>
