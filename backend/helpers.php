<?php 


function isValidUsername($username) {
    // length must be (3 - 20)
    if (strlen($username) < 3 || strlen($username) > 20) {
        return ["valid" => false, "message" => "Username must be between 3 and 20 characters"];
    } 

    // must start with letter
    if(!preg_match('/^[a-zA-Z]/', $username)) {
        return ["valid" => false, "message" => "Username must start with letter"];
    }

    // only allow letters, numbers, underscores and dots
    if (!preg_match('/^[a-zA-Z][a-zA-Z0-9._]+[a-zA-Z0-9]$/', $username)) {
        return ["valid" => false, "message" => "Username can only contain letters, numbers, dots, and underscores."];
    }

    // No consecutive dots or underscores(2 or more) for eg: [__] & [..] 
    if (preg_match('/[._]{2,}/', $username)) {
        return ["valid" => false, "message" => "Username cannot contain consecutive dots or underscores."];
    }

    // Cannot end with dot or underscore
    if (preg_match('/[._]$/', $username)) {
        return ["valid" => false, "message" => "Username cannot end with a dot or underscore."];
    }

    return ["valid" => true, "message" => "Valid username"];
}

function containsForbiden($username) {
    $forbissen_words = ['badword1', 'badword2', 'offensive'];

    $username_lower = strtolower($username);
    foreach ($forbissen_words as $word) {
        if (strpos($username_lower, $word) !== false) {
            return true;
        }
    }
    return false;
}

function isUsernameTaken($conn, $username) {
    $username = mysqli_real_escape_string($conn, strtolower($username));
    $sql = "SELECT id FROM users WHERE LOWER(username) = '$username'";
    $result = mysqli_query($conn, $sql);
    return mysqli_num_rows($result) > 0;
}

?>