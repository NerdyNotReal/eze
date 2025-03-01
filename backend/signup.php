<?php
session_start();
require 'db.php';
require 'helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $email = $_POST['email'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : null;

    // validate empty fields 
    if (empty($username) || empty($email) || empty($password) || empty($confirm_password)) {
        echo json_encode(["success" => false, "message" => "All fields are required."]);
        exit;
    }

    //username validation
    $usernameValidation = isValidUsername($username);
    if (!$usernameValidation['valid']) {
        echo json_encode(["success" => false, "message" => $usernameValidation['message']]);
        exit;
    }

    // check if username already taken by other user
    if (isUsernameTaken($conn, $username)) {
        echo json_encode(["success" => false, "message" => "Username is already taken."]);
        exit;
    }

    // check if password matches
    if ($password !== $confirm_password) {
        echo json_encode(["success" => false, "message" => "Passwords do not match."]);
        exit;
    }

    // Email validation
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(["success" => false, "message" => "Invalid email format."]);
        exit;
    }

    // Start transaction
    mysqli_begin_transaction($conn);

    try {
        //sanitize inputs for better security
        $username = mysqli_real_escape_string($conn, $username);
        $email = mysqli_real_escape_string($conn, $email);
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // insert user in database
        $sql = "INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)";
        $stmt = mysqli_prepare($conn, $sql);
        mysqli_stmt_bind_param($stmt, "sss", $username, $email, $password_hash);
        
        if (!mysqli_stmt_execute($stmt)) {
            throw new Exception("Failed to create user: " . mysqli_error($conn));
        }

        $userId = mysqli_insert_id($conn);
        $_SESSION['user_id'] = $userId;
        $_SESSION['username'] = $username;

        // Handle redirect for invite links
        if ($redirect && strpos($redirect, 'join.php') !== false) {
            // Extract token from redirect URL
            parse_str(parse_url($redirect, PHP_URL_QUERY), $params);
            if (isset($params['token'])) {
                $token = $params['token'];
                
                // Verify and process the invite
                $inviteQuery = "SELECT * FROM workspace_invites 
                              WHERE token = ? 
                              AND used_at IS NULL 
                              AND (expires_at IS NULL OR expires_at > NOW())";
                
                $inviteStmt = mysqli_prepare($conn, $inviteQuery);
                mysqli_stmt_bind_param($inviteStmt, "s", $token);
                mysqli_stmt_execute($inviteStmt);
                $inviteResult = mysqli_stmt_get_result($inviteStmt);
                
                if ($invite = mysqli_fetch_assoc($inviteResult)) {
                    // Add user to workspace
                    $addUserQuery = "INSERT INTO workspace_users (workspace_id, user_id, role) 
                                   VALUES (?, ?, ?)";
                    $addUserStmt = mysqli_prepare($conn, $addUserQuery);
                    mysqli_stmt_bind_param($addUserStmt, "iis", 
                        $invite['workspace_id'], 
                        $userId, 
                        $invite['role']
                    );
                    
                    if (!mysqli_stmt_execute($addUserStmt)) {
                        throw new Exception("Failed to add user to workspace: " . mysqli_error($conn));
                    }

                    // Mark invite as used
                    $updateInviteQuery = "UPDATE workspace_invites 
                                        SET used_at = NOW(), used_by = ? 
                                        WHERE id = ?";
                    $updateInviteStmt = mysqli_prepare($conn, $updateInviteQuery);
                    mysqli_stmt_bind_param($updateInviteStmt, "ii", $userId, $invite['id']);
                    
                    if (!mysqli_stmt_execute($updateInviteStmt)) {
                        throw new Exception("Failed to update invite status: " . mysqli_error($conn));
                    }

                    $redirect = "../templates/workspace.php?id=" . $invite['workspace_id'];
                } else {
                    $redirect = "../templates/dashboard.php";
                }
            }
        } else {
            $redirect = "../templates/dashboard.php";
        }

        mysqli_commit($conn);
        
        echo json_encode([
            "success" => true, 
            "message" => "User registered successfully.",
            "redirect" => $redirect
        ]);

    } catch (Exception $e) {
        mysqli_rollback($conn);
        echo json_encode([
            "success" => false, 
            "message" => $e->getMessage()
        ]);
    } finally {
        if (isset($stmt)) mysqli_stmt_close($stmt);
        if (isset($inviteStmt)) mysqli_stmt_close($inviteStmt);
        if (isset($addUserStmt)) mysqli_stmt_close($addUserStmt);
        if (isset($updateInviteStmt)) mysqli_stmt_close($updateInviteStmt);
    }
} else {
    echo json_encode(["success" => false, "message" => "Invalid request method."]);
}

mysqli_close($conn);
?>