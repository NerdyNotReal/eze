<?php
session_start();
require 'db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = mysqli_real_escape_string($conn, $_POST['username']);
    $password = mysqli_real_escape_string($conn, $_POST['password']);
    $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : null;

    // Validation
    if (empty($username) || empty($password)) {
        die("Username and password are required.");
    }

    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = mysqli_query($conn, $sql);

    if (mysqli_num_rows($result) > 0) {
        $user = mysqli_fetch_assoc($result);

        if (password_verify($password, $user['password_hash'])) {
            // Password is correct
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];

            // Handle redirect for invite links
            if ($redirect && strpos($redirect, 'join.php') !== false) {
                // Extract token from redirect URL
                parse_str(parse_url($redirect, PHP_URL_QUERY), $params);
                if (isset($params['token'])) {
                    $redirect = '../templates/join.php?token=' . urlencode($params['token']);
                }
            } else {
                $redirect = '../templates/dashboard.php';
            }

            echo json_encode([
                'success' => true,
                'message' => 'Login successful',
                'redirect' => $redirect
            ]);
        } else {
            // Password is incorrect
            echo json_encode([
                'success' => false,
                'message' => 'Invalid username or password'
            ]);
        }
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'User not found'
        ]);
    }
} else {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method'
    ]);
}
?>
