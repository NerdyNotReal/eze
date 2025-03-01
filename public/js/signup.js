// Handle Sign-Up
document.addEventListener('DOMContentLoaded', () => {
    // Add password toggle functionality for both password fields
    document.querySelectorAll('.password-toggle').forEach(toggle => {
        toggle.addEventListener('click', function() {
            const passwordInput = this.parentElement.querySelector('input');
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
    });
});

document.querySelector("#signup").addEventListener("submit", async (event) => {
    event.preventDefault();
    const username = document.querySelector("#username").value;
    const email = document.querySelector("#email").value;
    const password = document.querySelector("#password").value;
    const confirmPassword = document.querySelector("#confirm_password").value;
    const terms = document.querySelector("#terms").checked;
    const msg = document.getElementById('signup_msg');
    const button = document.querySelector('button[type="submit"]');
    const btnText = button.querySelector('.btn-text');
    const btnSpinner = button.querySelector('.btn-spinner');

    try {
        // Reset message
        msg.textContent = '';
        msg.classList.remove('success', 'error');

        // Validate form
        if (!username || !email || !password || !confirmPassword) {
            throw new Error('All fields are required');
        }

        if (!terms) {
            throw new Error('Please accept the Terms of Service');
        }

        if (password !== confirmPassword) {
            throw new Error('Passwords do not match');
        }

        // Get redirect parameter from URL if it exists
        const urlParams = new URLSearchParams(window.location.search);
        const redirect = urlParams.get('redirect');

        // Start loading state
        button.disabled = true;
        btnText.style.display = 'none';
        btnSpinner.style.display = 'block';

        const formData = new FormData();
        formData.append('username', username);
        formData.append('email', email);
        formData.append('password', password);
        formData.append('confirm_password', confirmPassword);
        if (redirect) {
            formData.append('redirect', redirect);
        }

        const response = await fetch('../backend/signup.php', {
            method: 'POST',
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            msg.textContent = data.message;
            msg.classList.add('success');

            // Redirect after a short delay to show success message
            setTimeout(() => {
                window.location.href = data.redirect;
            }, 1000);
        } else {
            throw new Error(data.message);
        }
    } catch (error) {
        msg.textContent = error.message;
        msg.classList.add('error');
        button.disabled = false;
        btnText.style.display = 'block';
        btnSpinner.style.display = 'none';
    }
});
