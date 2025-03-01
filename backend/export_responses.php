<?php
session_start();
include('db.php');

if (!isset($_SESSION['user_id'])) {
    die('Unauthorized');
}

$formId = isset($_GET['id']) ? $_GET['id'] : null;

if (!$formId) {
    die('Form ID is required');
}

// Get form details
$formSql = "SELECT f.*, u.username as creator FROM forms f 
            JOIN users u ON f.owner_id = u.id 
            WHERE f.id = '$formId' AND f.owner_id = '{$_SESSION['user_id']}'";
$formResult = mysqli_query($conn, $formSql);
$form = mysqli_fetch_assoc($formResult);

if (!$form) {
    die('Form not found or access denied');
}

// Get form elements
$elementsSql = "SELECT * FROM form_elements WHERE form_id = '$formId' ORDER BY position";
$elementsResult = mysqli_query($conn, $elementsSql);
$elements = mysqli_fetch_all($elementsResult, MYSQLI_ASSOC);

// Get responses
$responsesSql = "SELECT * FROM form_responses WHERE form_id = '$formId' ORDER BY created_at DESC";
$responsesResult = mysqli_query($conn, $responsesSql);
$responses = mysqli_fetch_all($responsesResult, MYSQLI_ASSOC);

// Set headers for CSV download
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="' . $form['title'] . '_responses_' . date('Y-m-d') . '.csv"');

// Create output stream
$output = fopen('php://output', 'w');

// Add UTF-8 BOM for Excel
fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));

// Write headers
$headers = ['Submission Date'];
foreach ($elements as $element) {
    $headers[] = $element['label'];
}
fputcsv($output, $headers);

// Write data rows
foreach ($responses as $response) {
    $row = [date('Y-m-d H:i:s', strtotime($response['submitted_at']))];
    $responseData = json_decode($response['response_data'], true);
    
    foreach ($elements as $element) {
        $elementId = "element_" . $element['id'];
        $value = isset($responseData[$elementId]) ? $responseData[$elementId] : '';
        if (is_array($value)) {
            $value = implode(', ', $value);
        }
        $row[] = $value;
    }
    
    fputcsv($output, $row);
}

fclose($output); 