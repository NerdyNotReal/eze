<?php
session_start();

// Redirect to dashboard if already logged in
if (isset($_SESSION['user_id'])) {
    // Check if there's a pending invite
    if (isset($_SESSION['pending_invite_token'])) {
        $token = $_SESSION['pending_invite_token'];
        unset($_SESSION['pending_invite_token']);
        header("Location: join.php?token=" . urlencode($token));
    } else {
        header("Location: dashboard.php");
    }
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login</title>
    <?php include '../backend/link.php'; ?>
    <?php include '../components/nav.php'; ?>
</head>
<body>

    <div class="login-container">
        <div class="login">
            <div class="login__header">
                <h2 class="login__header--title text-heading-medium">Login to Your Account</h2>
                <p class="login__header--info text-body-regular">Don't have an account? <a href="signup.php">Create one</a></p>
                <div class="divider"></div>
            </div>
        
            <!-- Login form -->
            <form class="login__form" id="loginForm" autocomplete = "on">
                <div class="login__field">
                    <label for="username" class="text-body-regular">Username:</label>
                    <input type="text" name="username" id="username" class="login__input" required placeholder="Enter your username">
                </div>
                <!-- <div class="login__field">
                    <label for="email" class="login__label text-body-regular">Email:</label>
                    <input type="email" name="email" id="email" class="login__input" required placeholder="Enter your email">
                </div> -->
                <div class="login__field">
                    <label for="password" class="text-body-regular">Password:</label>
                    <div class="password-field">
                        <input type="password" name="password" id="password" class="login__input" required placeholder="Enter your password">
                        <button type = "button" class = "password-toggle" aria-label = "toggle password visibility">
                        <i class="fa-regular fa-eye"></i>
                        </button>
                    </div>
                </div>
                <p id="login_msg" class="text-body-small" style="color: red;"></p>

                <div class="login__options">
                    <div class="remember-me">
                        <input type="checkbox" id="rememberMe" name="rememberMe">
                        <label for="rememberMe" class = "text-body-small">Remember Me</label>
                    </div>
                    <a href="#" class="text-body-small text-link">Forgot password?</a>
                </div>

                <div class="login__submit">
                    <button type="submit" name="submit" class="btn btn__primary">
                        <span class = "btn-text">Login</span>
                        <span class="btn-spinner"></span>
                    </button>
                </div>
            </form>

        </div>
        <div class="hero-image">
        </div>
    </div>

    <script src="../public/js/login.js" defer></script>
</body>
</html>
