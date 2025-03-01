<?php
session_start();
include('../db.php');
header('Content-Type: application/json'); // for json response

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["success" => false, "message" => "User not logged in"]);
    exit(); // Stop further execution
}

$userid = $_SESSION['user_id'];
$sql = "SELECT id, title, description FROM forms WHERE owner_id = '$userid'";
if (mysqli_query($conn, $sql)) {
    $result = mysqli_query($conn, $sql);
    $forms = [];
    
    while ($row = mysqli_fetch_assoc($result)) {
        $forms[] = $row;
    }

    echo json_encode(["success" => true, "data" => $forms]);
} else {
    echo json_encode(["success" => false, "message" => "Error fetching forms"]);
}

?>

