<?php
session_start();
require '../db.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    echo json_encode(['success' => false, 'error' => 'Invalid request method']);
    exit;
}

$formId = $_GET['formId'] ?? null;

if (!$formId) {
    echo json_encode(['success' => false, 'error' => 'Form ID is required']);
    exit;
}

try {
    $sql = "SELECT id, element_type, element_data, position FROM form_elements WHERE form_id = ? ORDER BY position ASC";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $formId);
    
    if (mysqli_stmt_execute($stmt)) {
        $result = mysqli_stmt_get_result($stmt);
        $elements = [];
        
        while ($row = mysqli_fetch_assoc($result)) {
            $row['element_data'] = json_decode($row['element_data'], true);
            $elements[] = $row;
        }
        
        echo json_encode([
            'success' => true,
            'elements' => $elements
        ]);
    } else {
        throw new Exception(mysqli_error($conn));
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => 'Failed to get form elements: ' . $e->getMessage()
    ]);
}

mysqli_close($conn); 