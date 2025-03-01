document.addEventListener("DOMContentLoaded", () => {
    // Handle saved credentials
    const savedUsername = localStorage.getItem("rememberedUsername");
    const savedPassword = localStorage.getItem("rememberedPassword");
    
    if (savedUsername && savedPassword) {
        document.querySelector("#username").value = savedUsername;
        document.querySelector("#password").value = savedPassword;
        document.querySelector("#rememberMe").checked = true;
    }
});

// Add password toggle functionality
document.querySelector('.password-toggle').addEventListener('click', function() {
    const passwordInput = document.querySelector('#password');
    const eyeIcon = this.querySelector('i');
    
    if (passwordInput.type === 'password') {
        passwordInput.type = 'text';
        eyeIcon.classList.remove('fa-eye');
        eyeIcon.classList.add('fa-eye-slash');
    } else {
        passwordInput.type = 'password';
        eyeIcon.classList.remove('fa-eye-slash');
        eyeIcon.classList.add('fa-eye');
    }
});

document.querySelector('#loginForm').addEventListener('submit', async (event) => {
    event.preventDefault();
    const username = document.querySelector("#username").value;
    const password = document.querySelector("#password").value;
    const rememberMe = document.querySelector("#rememberMe").checked;
    const msg = document.getElementById('login_msg');
    const button = document.querySelector('button[type="submit"]');
    const btnText = button.querySelector('.btn-text');
    const btnSpinner = button.querySelector('.btn-spinner');
    
    try {
        button.disabled = true;
        btnText.style.display = 'none';
        btnSpinner.style.display = 'block';
        msg.textContent = '';
        msg.classList.remove('success', 'error');

        // Get redirect parameter from URL if it exists
        const urlParams = new URLSearchParams(window.location.search);
        const redirect = urlParams.get('redirect');

        const formData = new FormData();
        formData.append("username", username);
        formData.append("password", password);
        if (redirect) {
            formData.append("redirect", redirect);
        }

        const response = await fetch("../backend/login.php", {
            method: "POST",
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            // Handle remember me
            if (rememberMe) {
                localStorage.setItem("rememberedUsername", username);
                localStorage.setItem("rememberedPassword", password);
            } else {
                localStorage.removeItem("rememberedUsername");
                localStorage.removeItem("rememberedPassword");
            }

            msg.textContent = data.message;
            msg.classList.add('success');

            // Redirect after a short delay to show the success message
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1000);
        } else {
            msg.textContent = data.message;
            msg.classList.add('error');
            button.disabled = false;
            btnText.style.display = 'block';
            btnSpinner.style.display = 'none';
        }
    } catch (error) {
        console.error('Error:', error);
        msg.textContent = "An error occurred. Please try again.";
        msg.classList.add('error');
        button.disabled = false;
        btnText.style.display = 'block';
        btnSpinner.style.display = 'none';
    }
});