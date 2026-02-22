<?php
session_start();
require_once 'db_connect.php';

// Must be logged in to reset password
if (!isset($_SESSION['user_id'])) {
    header("Location: loginpage.php");
    exit();
}

$error = '';
$success = '';
$user_id = $_SESSION['user_id'];
$user_email = $_SESSION['email'];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $old_password    = $_POST['oldPassword'];
    $new_password    = $_POST['newPassword'];
    $confirm_password = $_POST['confirmPassword'];

    // Fetch current password from DB
    $result = mysqli_query($conn, "SELECT password FROM users WHERE user_id='$user_id'");
    $user = mysqli_fetch_assoc($result);

    if (!password_verify($old_password, $user['password'])) {
        $error = "Old password is incorrect.";
    }
    elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters.";
    }
    elseif ($new_password !== $confirm_password) {
        $error = "New passwords do not match.";
    }
    elseif ($old_password === $new_password) {
        $error = "New password must be different from old password.";
    }
    else {
        $hashed = password_hash($new_password, PASSWORD_DEFAULT);
        $update = "UPDATE users SET password='$hashed' WHERE user_id='$user_id'";
        if (mysqli_query($conn, $update)) {
            $success = "Password reset successful! Redirecting to login...";
            // Destroy session and redirect to login
            session_unset();
            session_destroy();
            header("refresh:2;url=loginpage.php");
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trackera - Reset Password</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --success: #27ae60;
            --warning: #e67e22;
            --light: #ecf0f1;
            --text-dark: #2c3e50;
            --text-muted: #7f8c8d;
        }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            display: flex;
            position: relative;
            overflow: hidden;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #4facfe 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }

        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .bg-decoration {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
            animation: float 20s infinite ease-in-out;
        }

        .decoration-1 {
            width: 300px;
            height: 300px;
            background: white;
            top: -100px;
            left: -100px;
            animation-delay: 0s;
        }

        .decoration-2 {
            width: 200px;
            height: 200px;
            background: #3498db;
            bottom: -50px;
            right: 10%;
            animation-delay: 5s;
        }

        .decoration-3 {
            width: 150px;
            height: 150px;
            background: #e74c3c;
            top: 20%;
            right: -75px;
            animation-delay: 10s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(30px, -30px) scale(1.1); }
            50% { transform: translate(-20px, 20px) scale(0.9); }
            75% { transform: translate(20px, 30px) scale(1.05); }
        }

        .login-container {
            display: flex;
            width: 100%;
            max-width: 1400px;
            margin: auto;
            position: relative;
            z-index: 1;
        }

        .left-panel {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            padding: 60px;
            color: white;
            text-align: center;
            animation: slideInLeft 0.8s ease-out;
        }

        @keyframes slideInLeft {
            from {
                opacity: 0;
                transform: translateX(-50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 72px;
            font-weight: 900;
            margin-bottom: 20px;
            text-shadow: 4px 4px 8px rgba(0, 0, 0, 0.3);
            letter-spacing: -2px;
            animation: fadeInScale 1s ease-out 0.3s both;
        }

        @keyframes fadeInScale {
            from {
                opacity: 0;
                transform: scale(0.9);
            }
            to {
                opacity: 1;
                transform: scale(1);
            }
        }

        .tagline {
            font-size: 24px;
            font-weight: 300;
            opacity: 0.95;
            max-width: 400px;
            line-height: 1.6;
            animation: fadeInUp 1s ease-out 0.5s both;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .features {
            margin-top: 50px;
            display: flex;
            flex-direction: column;
            gap: 20px;
            animation: fadeInUp 1s ease-out 0.7s both;
        }

        .feature-item {
            display: flex;
            align-items: center;
            gap: 15px;
            font-size: 16px;
            font-weight: 300;
        }

        .feature-icon {
            font-size: 28px;
            background: rgba(255, 255, 255, 0.2);
            padding: 10px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .right-panel {
            flex: 1;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 40px;
            animation: slideInRight 0.8s ease-out;
        }

        @keyframes slideInRight {
            from {
                opacity: 0;
                transform: translateX(50px);
            }
            to {
                opacity: 1;
                transform: translateX(0);
            }
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 50px 45px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 5px;
            background: linear-gradient(90deg, #3498db, #27ae60, #e67e22, #e74c3c);
        }

        .login-header {
            margin-bottom: 40px;
        }

        .login-header h2 {
            font-family: 'Playfair Display', serif;
            font-size: 36px;
            color: var(--primary);
            margin-bottom: 8px;
            font-weight: 700;
        }

        .login-header p {
            color: var(--text-muted);
            font-size: 15px;
            font-weight: 300;
        }

        .form-group {
            margin-bottom: 25px;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: var(--text-dark);
            font-weight: 500;
            font-size: 14px;
            letter-spacing: 0.3px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-icon {
            position: absolute;
            left: 18px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 20px;
            color: var(--text-muted);
            transition: color 0.3s;
        }

        .form-input {
            width: 100%;
            padding: 16px 20px 16px 55px;
            border: 2px solid #e0e0e0;
            border-radius: 14px;
            font-size: 15px;
            font-family: 'Poppins', sans-serif;
            transition: all 0.3s ease;
            background: white;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
        }

        .form-input:focus + .input-icon {
            color: var(--secondary);
        }

        .password-toggle {
            position: absolute;
            right: 18px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 20px;
            color: var(--text-muted);
            transition: color 0.3s;
        }

        .password-toggle:hover {
            color: var(--secondary);
        }

        .error-message {
            color: var(--accent);
            font-size: 13px;
            margin-top: 5px;
            display: none;
        }

        .error-message.show {
            display: block;
        }

        .button-group {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        .btn {
            padding: 16px 32px;
            border: none;
            border-radius: 14px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            font-family: 'Poppins', sans-serif;
            position: relative;
            overflow: hidden;
        }

        .btn::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 0;
            height: 0;
            border-radius: 50%;
            background: rgba(255, 255, 255, 0.3);
            transform: translate(-50%, -50%);
            transition: width 0.6s, height 0.6s;
        }

        .btn:active::before {
            width: 300px;
            height: 300px;
        }

        .btn-reset {
            background: linear-gradient(135deg, var(--warning), #d35400);
            color: white;
            box-shadow: 0 8px 20px rgba(230, 126, 34, 0.3);
        }

        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 28px rgba(230, 126, 34, 0.4);
        }

        .divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 30px 0 20px 0;
            color: var(--text-muted);
            font-size: 14px;
        }

        .divider::before,
        .divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid #e0e0e0;
        }

        .divider span {
            padding: 0 15px;
            font-weight: 300;
        }

        .back-link {
            text-align: center;
            color: var(--text-muted);
            font-size: 14px;
        }

        .back-link a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
        }

        .back-link a:hover {
            text-decoration: underline;
        }

        .msg-success {
            display: block;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .msg-error {
            display: block;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        @media (max-width: 1024px) {
            .left-panel {
                display: none;
            }
            .login-container {
                justify-content: center;
            }
        }

        @media (max-width: 480px) {
            .right-panel {
                padding: 20px;
            }
            .login-card {
                padding: 35px 25px;
            }
            .login-header h2 {
                font-size: 28px;
            }
            .logo {
                font-size: 48px;
            }
        }

        .btn.loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .btn.loading::after {
            content: '';
            position: absolute;
            width: 16px;
            height: 16px;
            top: 50%;
            left: 50%;
            margin-left: -8px;
            margin-top: -8px;
            border: 2px solid white;
            border-radius: 50%;
            border-top-color: transparent;
            animation: spin 0.6s linear infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        .password-strength {
            height: 4px;
            background: #ecf0f1;
            border-radius: 2px;
            margin-top: 8px;
            overflow: hidden;
            display: none;
        }

        .password-strength.show {
            display: block;
        }

        .password-strength-bar {
            height: 100%;
            width: 0%;
            transition: all 0.3s;
            border-radius: 2px;
        }

        .password-strength-bar.weak {
            width: 33%;
            background: var(--accent);
        }

        .password-strength-bar.medium {
            width: 66%;
            background: var(--warning);
        }

        .password-strength-bar.strong {
            width: 100%;
            background: var(--success);
        }
    </style>
</head>
<body>
    <div class="bg-decoration decoration-1"></div>
    <div class="bg-decoration decoration-2"></div>
    <div class="bg-decoration decoration-3"></div>

    <div class="login-container">
        <div class="left-panel">
            <div class="logo">Trackera</div>
            <p class="tagline">Reset your password securely and get back to managing your academics</p>
            <div class="features">
                <div class="feature-item">
                    <div class="feature-icon">🔒</div>
                    <span>Secure password reset process</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">✅</div>
                    <span>Password strength validation</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">⚡</div>
                    <span>Instant access restoration</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">🛡️</div>
                    <span>Your data stays protected</span>
                </div>
            </div>
        </div>

        <div class="right-panel">
            <div class="login-card">
                <div class="login-header">
                    <h2>Reset Password</h2>
                    <p>Enter your current and new password</p>
                </div>

                <?php if ($error): ?>
                    <span class="msg-error"><?php echo htmlspecialchars($error); ?></span>
                <?php endif; ?>
                <?php if ($success): ?>
                    <span class="msg-success"><?php echo $success; ?></span>
                <?php endif; ?>

                <form method="POST" action="" id="resetForm">
                    <div class="form-group">
                        <label for="oldPassword">Old Password</label>
                        <div class="input-wrapper">
                            <input
                                type="password"
                                id="oldPassword"
                                name="oldPassword"
                                class="form-input"
                                placeholder="Enter your old password"
                                required
                            >
                            <span class="input-icon">🔑</span>
                            <button type="button" class="password-toggle" onclick="togglePassword('oldPassword')">👁️</button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <div class="input-wrapper">
                            <input
                                type="password"
                                id="newPassword"
                                name="newPassword"
                                class="form-input"
                                placeholder="Enter your new password"
                                required
                            >
                            <span class="input-icon">🔒</span>
                            <button type="button" class="password-toggle" onclick="togglePassword('newPassword')">👁️</button>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                        <div class="error-message" id="passwordError"></div>
                    </div>

                    <div class="form-group">
                        <label for="confirmPassword">Confirm New Password</label>
                        <div class="input-wrapper">
                            <input
                                type="password"
                                id="confirmPassword"
                                name="confirmPassword"
                                class="form-input"
                                placeholder="Confirm your new password"
                                required
                            >
                            <span class="input-icon">✓</span>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">👁️</button>
                        </div>
                        <div class="error-message" id="confirmError"></div>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn btn-reset">Reset Password</button>
                    </div>
                </form>

                <div class="divider">
                    <span>Remember your password?</span>
                </div>

                <div class="back-link">
                    <a href="<?php echo isset($_SESSION['role']) && $_SESSION['role'] === 'student' ? 'studentdashboard.php' : 'facultydashboard.php'; ?>">Back to Dashboard</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleBtn = event.currentTarget;
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }

        document.getElementById('newPassword').addEventListener('input', function() {
            const password = this.value;
            const strengthBar = document.getElementById('strengthBar');
            const strengthContainer = document.getElementById('passwordStrength');

            if (password.length === 0) {
                strengthContainer.classList.remove('show');
                return;
            }

            strengthContainer.classList.add('show');

            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;

            strengthBar.className = 'password-strength-bar';
            if (strength <= 2) {
                strengthBar.classList.add('weak');
            } else if (strength === 3) {
                strengthBar.classList.add('medium');
            } else {
                strengthBar.classList.add('strong');
            }
        });

        document.getElementById('confirmPassword').addEventListener('input', function() {
            const newPassword = document.getElementById('newPassword').value;
            const confirmPassword = this.value;
            const errorMsg = document.getElementById('confirmError');

            if (confirmPassword && newPassword !== confirmPassword) {
                errorMsg.textContent = 'Passwords do not match';
                errorMsg.classList.add('show');
            } else {
                errorMsg.classList.remove('show');
            }
        });

        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('focus', function() {
                this.parentElement.style.transform = 'translateY(-2px)';
            });
            input.addEventListener('blur', function() {
                this.parentElement.style.transform = 'translateY(0)';
            });
        });
    </script>
</body>
</html>