<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="icon" type="image/png" href="/assets/img/logo.png">
    <title>Reset Password - Employee Management System</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script src="https://code.iconify.design/3/3.2.0/iconify.min.js"></script>
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body class="bg-gradient-to-br from-blue-500 to-blue-600 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-md">
    <!-- Reset Password Card -->
    <div class="bg-white rounded-lg shadow-xl p-8">
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-800">Reset Password</h2>
            <p class="text-gray-600 text-sm mt-2">Enter your new password below.</p>
        </div>

        <!-- Alert Messages -->
        <div id="alert" class="hidden mb-4 p-4 rounded text-sm font-medium"></div>

        <?php
        $token = $_GET['token'] ?? '';
        $email = $_GET['email'] ?? '';
        
        if (empty($token) || empty($email)): ?>
            <div class="bg-red-100 border border-red-400 text-red-700 p-4 rounded text-sm font-medium mb-6">
                Invalid or expired reset link.
            </div>
            <a href="/forgot-password.php" class="block w-full text-center bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-200">
                Request New Link
            </a>
        <?php else: ?>
            <!-- Reset Password Form -->
            <form id="resetPasswordForm">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">

                <!-- New Password Field -->
                <div class="mb-4">
                    <label for="password" class="block text-gray-700 font-semibold mb-2">
                        <span class="iconify inline mr-2" data-icon="mdi:lock"></span>
                        New Password
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="password"
                            name="password"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Min. 8 characters"
                            minlength="8"
                        >
                        <button
                            type="button"
                            onclick="togglePasswordVisibility('password')"
                            class="absolute right-3 top-2 text-gray-500 hover:text-gray-700"
                        >
                            <span class="iconify" data-icon="mdi:eye"></span>
                        </button>
                    </div>
                </div>


                <!-- Confirm Password Field -->
                <div class="mb-6">
                    <label for="confirm_password" class="block text-gray-700 font-semibold mb-2">
                        <span class="iconify inline mr-2" data-icon="mdi:lock-check"></span>
                        Confirm Password
                    </label>
                    <div class="relative">
                        <input
                            type="password"
                            id="confirm_password"
                            name="confirm_password"
                            required
                            class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                            placeholder="Re-enter password"
                        >
                        <button
                            type="button"
                            onclick="togglePasswordVisibility('confirm_password')"
                            class="absolute right-3 top-2 text-gray-500 hover:text-gray-700"
                        >
                            <span class="iconify" data-icon="mdi:eye"></span>
                        </button>
                    </div>
                </div>

                <ul id="pwRequirements" class="text-xs text-gray-500 mb-4 space-y-1">
                    <li id="req-length">• At least 8 characters</li>
                    <li id="req-upper">• At least 1 uppercase letter</li>
                    <li id="req-lower">• At least 1 lowercase letter</li>
                    <li id="req-number">• At least 1 number</li>
                    <li id="req-special">• At least 1 special character (e.g. @, #, $, %, !)</li>
                </ul>

                <!-- Submit Button -->
                <button
                    type="submit"
                    id="submitBtn"
                    class="w-full bg-blue-600 text-white font-bold py-2 px-4 rounded-lg hover:bg-blue-700 transition duration-200 flex items-center justify-center gap-2 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                    <span class="iconify" data-icon="mdi:lock-reset"></span>
                    Update Password
                </button>
            </form>
        <?php endif; ?>

        <!-- Back to Login -->
        <div class="mt-6 text-center">
            <a href="/login.php" class="text-sm text-blue-600 hover:underline">
                Back to Sign In
            </a>
        </div>
    </div>

    <!-- Footer -->
    <p class="text-center text-blue-100 mt-6 text-sm">
        © 2026 Employee Management System. All rights reserved.
    </p>
</div>

<!-- Script -->
<script>
const resetPasswordForm = document.getElementById('resetPasswordForm');
const submitBtn = document.getElementById('submitBtn');
const alertBox = document.getElementById('alert');
const passwordInput = document.getElementById('password');

function togglePasswordVisibility(id) {
    const input = document.getElementById(id);
    const button = input.nextElementSibling;
    const icon = button.querySelector('.iconify');
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.setAttribute('data-icon', 'mdi:eye-off');
    } else {
        input.type = 'password';
        icon.setAttribute('data-icon', 'mdi:eye');
    }
    if (window.Iconify) window.Iconify.build();
}

function showAlert(message, type) {
    alertBox.textContent = message;
    alertBox.className = type === 'success' 
        ? 'bg-green-100 border border-green-400 text-green-700 p-4 rounded text-sm font-medium' 
        : 'bg-red-100 border border-red-400 text-red-700 p-4 rounded text-sm font-medium';
    alertBox.classList.remove('hidden');
}

// Mirrors App\Helpers\PasswordPolicy::validate() on the backend
function checkPasswordRequirements(password) {
    const checks = {
        length:  password.length >= 8,
        upper:   /[A-Z]/.test(password),
        lower:   /[a-z]/.test(password),
        number:  /[0-9]/.test(password),
        special: /[^A-Za-z0-9]/.test(password),
    };

    for (const [key, passed] of Object.entries(checks)) {
        const el = document.getElementById(`req-${key}`);
        if (!el) continue;
        el.classList.toggle('text-green-600', passed);
        el.classList.toggle('text-gray-500', !passed);
    }

    return Object.values(checks).every(Boolean);
}

function getPasswordError(password) {
    if (password.length < 8) return 'Password must be at least 8 characters long.';
    if (!/[A-Z]/.test(password)) return 'Password must contain at least 1 uppercase letter.';
    if (!/[a-z]/.test(password)) return 'Password must contain at least 1 lowercase letter.';
    if (!/[0-9]/.test(password)) return 'Password must contain at least 1 number.';
    if (!/[^A-Za-z0-9]/.test(password)) return 'Password must contain at least 1 special character (e.g. @, #, $, %, !).';
    return null;
}

if (passwordInput) {
    passwordInput.addEventListener('input', () => {
        checkPasswordRequirements(passwordInput.value);
    });
}

if (resetPasswordForm) {
    resetPasswordForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        alertBox.classList.add('hidden');

        const formData = new FormData(resetPasswordForm);
        const password = formData.get('password');
        const confirmPassword = formData.get('confirm_password');

        const pwError = getPasswordError(password);
        if (pwError) {
            showAlert(pwError, 'error');
            return;
        }

        if (password !== confirmPassword) {
            showAlert('Passwords do not match.', 'error');
            return;
        }

        submitBtn.disabled = true;
        const originalHTML = submitBtn.innerHTML;
        submitBtn.innerHTML = '<span class="iconify animate-spin" data-icon="mdi:loading"></span> Updating...';
        if (window.Iconify) window.Iconify.build();

        try {
            const response = await fetch('/reset-password-process.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: new URLSearchParams(formData).toString()
            });

            const data = await response.json();

            if (data.success) {
                showAlert('Password updated successfully! Redirecting to login...', 'success');
                setTimeout(() => {
                    window.location.href = '/login.php';
                }, 2000);
            } else {
                showAlert(data.message || 'An error occurred.', 'error');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalHTML;
                if (window.Iconify) window.Iconify.build();
            }
        } catch (error) {
            console.error('Error:', error);
            showAlert('Connection error. Please try again.', 'error');
            submitBtn.disabled = false;
            submitBtn.innerHTML = originalHTML;
            if (window.Iconify) window.Iconify.build();
        }
    });
}
</script>

</body>
</html>
