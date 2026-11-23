// assets/js/login.js

document.addEventListener('DOMContentLoaded', function() {
    const loginForm = document.getElementById('loginForm');
    const forgotPasswordButton = document.getElementById('forgotPasswordButton');
    let lockoutTimer = null;

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
                    if (lockoutTimer) {
                        clearInterval(lockoutTimer);
                        lockoutTimer = null;
                    }
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
                    if (data.locked_out) {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Access Restricted',
                            text: data.message,
                            confirmButtonColor: '#0D3B66'
                        });

                        submitBtn.disabled = true;
                        let remaining = parseInt(data.retry_after, 10) || 60;

                        if (lockoutTimer) {
                            clearInterval(lockoutTimer);
                        }

                        const updateCountdown = () => {
                            const mins = Math.floor(remaining / 60);
                            const secs = remaining % 60;
                            const secStr = secs < 10 ? '0' + secs : secs;
                            submitBtn.textContent = `LOCKED (${mins}m ${secStr}s)`;
                            if (remaining <= 0) {
                                clearInterval(lockoutTimer);
                                lockoutTimer = null;
                                submitBtn.disabled = false;
                                submitBtn.textContent = 'SECURE LOGIN';
                            }
                            remaining--;
                        };

                        updateCountdown();
                        lockoutTimer = setInterval(updateCountdown, 1000);
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

    if (forgotPasswordButton && loginForm) {
        forgotPasswordButton.addEventListener('click', async function() {
            const { value: recoveryDetails } = await Swal.fire({
                title: 'Reset Admin Password',
                input: 'password',
                inputPlaceholder: 'Recovery PIN',
                inputAttributes: {
                    inputmode: 'numeric',
                    autocomplete: 'one-time-code',
                    maxlength: '8'
                },
                showCancelButton: true,
                confirmButtonText: 'Reset password',
                confirmButtonColor: '#0D3B66',
                preConfirm: (pin) => {
                    if (!pin) {
                        Swal.showValidationMessage('Enter the recovery PIN.');
                        return false;
                    }

                    return pin;
                }
            });

            if (!recoveryDetails) {
                return;
            }

            forgotPasswordButton.disabled = true;

            try {
                const formData = new FormData();
                formData.set('recovery_pin', recoveryDetails);
                formData.set('csrf_token', loginForm.querySelector('input[name="csrf_token"]').value);

                const response = await fetch('controllers/login/forgot_password_process.php', {
                    method: 'POST',
                    body: formData
                });
                const data = await response.json();

                await Swal.fire({
                    icon: data.status === 'success' ? 'success' : 'error',
                    title: data.status === 'success' ? 'Password reset' : 'Reset failed',
                    html: data.status === 'success'
                        ? '<p>Username: <strong>admin</strong></p><p>Password: <strong>11021992</strong></p>'
                        : undefined,
                    text: data.status === 'success' ? undefined : data.message,
                    confirmButtonColor: '#0D3B66'
                });
            } catch (error) {
                console.error('Password reset error:', error);
                await Swal.fire({
                    icon: 'error',
                    title: 'Oops!',
                    text: 'Something went wrong. Please try again.',
                    confirmButtonColor: '#0D3B66'
                });
            } finally {
                forgotPasswordButton.disabled = false;
            }
        });
    }
});
