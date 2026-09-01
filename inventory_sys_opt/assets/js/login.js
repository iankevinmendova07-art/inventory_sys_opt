// assets/js/login.js

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');

    if (loginForm) {
        loginForm.addEventListener('submit', function(e) {
            // Stop the standard full-page reload
            e.preventDefault();

            const formData = new FormData(loginForm);
            const submitBtn = loginForm.querySelector('button[type="submit"]');
            
            // Temporarily disable button to prevent double-clicks
            submitBtn.disabled = true;
            submitBtn.textContent = 'LOGGING IN...';

            fetch('controllers/login/login_process.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.status === 'success') {
                    Swal.fire({
                        icon: 'success',
                        title: `WELCOME, ${data.admin_name}!`,
                        text: 'Login Successful!',
                        showConfirmButton: false,
                        timer: 1500
                    }).then(() => {
                        // Smooth transition to dashboard without previous reloads
                        window.location.href = 'index.php';
                    });
                } else {
                    Swal.fire({
                        icon: 'error',
                        title: 'Login Failed',
                        text: data.message,
                        confirmButtonColor: '#0D3B66'
                    });
                    // Re-enable button on failure
                    submitBtn.disabled = false;
                    submitBtn.textContent = 'SECURE LOGIN';
                }
            })
            .catch(error => {
                console.error('Error:', error);
                Swal.fire({
                    icon: 'error',
                    title: 'Oops!',
                    text: 'Something went wrong. Please try again.',
                    confirmButtonColor: '#0D3B66'
                });
                submitBtn.disabled = false;
                submitBtn.textContent = 'SECURE LOGIN';
            });
        });
    }
});