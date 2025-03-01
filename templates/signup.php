<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Signup</title>
    <?php include '../backend/link.php'; ?>
    <?php include '../components/nav.php'; ?>
</head>
<body>
    <div class="signup-container">
    <div class="signup">
        <div class="signup__header">
            <h2 class = "signup__header--title text-heading-medium">Create Account</h2>
            <p class="signup__header--info text-body-regular">Already have an account? <a href="login.php">Login</a></p>
            <div class="divider"></div>
        </div>
    
        <!-- Signup form -->
        <form class = "signup__form" id="signup">
            <div class = "signup__field">
                <label for="username" class = "text-body-regular">Username:</label>
                <input type="text" name="username" id="username" class = "signup__input" required placeholder="Enter your username">
            </div>
            <div class = "signup__field">
                <label for="email" class = "signup__label text-body-regular">Email:</label>
                <input type="email" name="email" id="email" class = "signup__input" required placeholder="Enter your email">
            </div>
            <div class = "signup__field">
                <label for="password" class = "text-body-regular">Password:</label>
                <div class="password-field">
                    <input type="password" name="password" id="password" class="signup__input" required placeholder="Enter your password">
                    <button type="button" class="password-toggle" aria-label="toggle password visibility">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>
            <div class = "signup__field">
                <label for="confirm_password" class = "text-body-regular">Confirm Password:</label>
                <div class="password-field">
                    <input type="password" name="confirm_password" id="confirm_password" class="signup__input" required placeholder="Enter your password">
                    <button type="button" class="password-toggle" aria-label="toggle password visibility">
                        <i class="fa-regular fa-eye"></i>
                    </button>
                </div>
            </div>
            <p id="signup_msg" class="text-body-small"></p>
            <div class="signup__submit">
                <div class="signup__submit--terms">
                    <input type="checkbox" id = "terms" >
                    <label for="terms" class = "text-body-small">I agree to the Terms of Service and Privacy Statement</label>
                </div>
                <button type="submit" name="submit" class = "btn btn__primary">
                    <span class="btn-text">Sign Up</span>
                    <span class="btn-spinner"></span>
                </button>
            </div>
        </form>


    </div>
    <div class="hero-image">
    </div>
    </div>

    <script src="../public/js/signup.js" defer></script>
    </body>
</html>
