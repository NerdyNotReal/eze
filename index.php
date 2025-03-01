<!DOCTYPE html>
<html>
<head>
    <title>Signup Button</title>
    <script>
        function handleSignup() {
            window.location.href = 'templates/signup.php';
        }

        function handleLogin() {
            window.location.href = 'templates/login.php';
        }
    </script>
</head>
<body>
    <button onclick="handleSignup()">Signup</button>
    <button onclick="handleLogin()">Log in</button>
</body>
</html>
