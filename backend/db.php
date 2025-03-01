<?php 
$host = "localhost";
$username = "root";
$password = "";
$dbname = "ezepze_db";

$conn = mysqli_connect($host, $username, $password, $dbname);

if (!$conn) {
    die ("Database connection failed: ". mysqli_connect_error());
}
?>