<?php 
session_start();

include('../db.php');

header('Content-Type: application/json'); //for  json response

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (!isset($_SESSION['user_id'])) {
        echo json_encode(['success' => false, 'message' => 'User not logged in']);
        exit;
    }

    $userid = $_SESSION['user_id']; 
    $title = $_POST['formTitle']; 
    $description = $_POST['formDescription']; 

    $title = mysqli_real_escape_string($conn, $title);
    $description = mysqli_real_escape_string($conn, $description);

    $sql =  "INSERT INTO forms (title, description, owner_id, status) VALUES ('$title', '$description', '$userid', 'draft')";
    
    if (mysqli_query($conn, $sql)) {
        $form_id = mysqli_insert_id($conn);
        $response = [
            'success' => true,
            'message' => 'Form created successfully!',
            'form' => [
                'id' => $form_id,
                'title' => $title,
                'description' => $description,
                'owner_id' => $userid,
                'status' => 'draft'
            ]
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'Error creating form: ' . mysqli_error($conn)
        ];
    }

    echo json_encode($response);
}
?>