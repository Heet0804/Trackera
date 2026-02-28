<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();
require_once 'db_connect.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email            = mysqli_real_escape_string($conn, strtolower(trim($_POST['email'])));
    $new_password     = $_POST['newPassword'];
    $confirm_password = $_POST['confirmPassword'];

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (strlen($new_password) < 8) {
        $error = "New password must be at least 8 characters.";
    } elseif ($new_password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {
        // Check if email exists in users table
        $result = mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email'");
        if (mysqli_num_rows($result) == 0) {
            $error = "No account found with this email address.";
        } else {
            $user    = mysqli_fetch_assoc($result);
            $hashed  = password_hash($new_password, PASSWORD_DEFAULT);
            $update  = "UPDATE users SET password='$hashed' WHERE email='$email'";
            if (mysqli_query($conn, $update)) {
                $success = "Password reset successful! Redirecting to login...";
                header("refresh:2;url=loginpage.php");
            } else {
                $error = "Something went wrong. Please try again.";
            }
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
        * { margin: 0; padding: 0; box-sizing: border-box; }

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
            overflow-x: hidden;
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
            position: absolute; border-radius: 50%; opacity: 0.1;
            animation: float 20s infinite ease-in-out;
        }

        .decoration-1 { width: 300px; height: 300px; background: white; top: -100px; left: -100px; animation-delay: 0s; }
        .decoration-2 { width: 200px; height: 200px; background: #3498db; bottom: -50px; right: 10%; animation-delay: 5s; }
        .decoration-3 { width: 150px; height: 150px; background: #e74c3c; top: 20%; right: -75px; animation-delay: 10s; }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25% { transform: translate(30px, -30px) scale(1.1); }
            50% { transform: translate(-20px, 20px) scale(0.9); }
            75% { transform: translate(20px, 30px) scale(1.05); }
        }

        .login-container {
            display: flex; width: 100%; max-width: 1400px;
            margin: auto; position: relative; z-index: 1;
        }

        .left-panel {
            flex: 1; display: flex; flex-direction: column;
            justify-content: center; align-items: center;
            padding: 60px; color: white; text-align: center;
            animation: slideInLeft 0.8s ease-out;
        }

        @keyframes slideInLeft {
            from { opacity: 0; transform: translateX(-50px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 72px; font-weight: 900; margin-bottom: 20px;
            text-shadow: 4px 4px 8px rgba(0,0,0,0.3); letter-spacing: -2px;
        }

        .tagline {
            font-size: 24px; font-weight: 300; opacity: 0.95;
            max-width: 400px; line-height: 1.6;
        }

        .features { margin-top: 50px; display: flex; flex-direction: column; gap: 20px; }

        .feature-item { display: flex; align-items: center; gap: 15px; font-size: 16px; font-weight: 300; }

        .feature-icon {
            font-size: 28px; background: rgba(255,255,255,0.2);
            padding: 10px; border-radius: 12px; backdrop-filter: blur(10px);
        }

        .right-panel {
            width: 520px; background: white; display: flex;
            flex-direction: column; justify-content: center;
            padding: 50px 45px 40px 45px; overflow-y: auto; height: 100vh;
        }

        .login-card {
            background: rgba(255,255,255,0.95); backdrop-filter: blur(20px);
            border-radius: 30px; padding: 50px 45px 40px 45px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
            width: 100%; max-width: 450px; position: relative; overflow: visible;
        }

        .login-card::before {
            content: ''; position: absolute; top: 0; left: 0; right: 0; height: 5px;
            background: linear-gradient(90deg, #3498db, #27ae60, #e67e22, #e74c3c);
            border-radius: 30px 30px 0 0;
        }

        .login-header { margin-bottom: 30px; }

        .login-header h2 {
            font-family: 'Playfair Display', serif; font-size: 36px;
            color: var(--primary); margin-bottom: 8px; font-weight: 700;
        }

        .login-header p { color: var(--text-muted); font-size: 15px; font-weight: 300; }

        .form-group { margin-bottom: 25px; position: relative; }

        .form-group label {
            display: block; margin-bottom: 8px; color: var(--text-dark);
            font-weight: 500; font-size: 14px;
        }

        .input-wrapper { position: relative; }

        .form-input {
            width: 100%; padding: 15px 45px 15px 45px;
            border: 2px solid #e0e0e0; border-radius: 12px;
            font-size: 15px; transition: all 0.3s; font-family: 'Poppins', sans-serif;
        }

        .form-input:focus {
            outline: none; border-color: var(--secondary);
            box-shadow: 0 0 0 4px rgba(52,152,219,0.1);
        }

        .input-icon {
            position: absolute; left: 15px; top: 50%;
            transform: translateY(-50%); font-size: 18px;
        }

        .password-toggle {
            position: absolute; right: 15px; top: 50%;
            transform: translateY(-50%); background: none;
            border: none; cursor: pointer; font-size: 18px; padding: 5px;
        }

        .btn {
            width: 100%; padding: 16px; border: none; border-radius: 12px;
            font-size: 16px; font-weight: 600; cursor: pointer;
            transition: all 0.3s; font-family: 'Poppins', sans-serif;
        }

        .btn-reset {
            background: linear-gradient(135deg, var(--warning), #d35400);
            color: white; box-shadow: 0 4px 15px rgba(230,126,34,0.3);
        }

        .btn-reset:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(230,126,34,0.4);
        }

        .divider {
            text-align: center; margin: 25px 0; color: var(--text-muted);
            font-size: 13px; position: relative;
        }

        .divider::before, .divider::after {
            content: ''; position: absolute; top: 50%;
            width: 38%; height: 1px; background: #e0e0e0;
        }

        .divider::before { left: 0; }
        .divider::after  { right: 0; }

        .back-link { text-align: center; font-size: 14px; color: var(--text-muted); }

        .back-link a {
            color: var(--secondary); text-decoration: none;
            font-weight: 600; transition: color 0.3s;
        }

        .back-link a:hover { color: var(--primary); }

        .msg-success {
            display: block; padding: 12px 15px; border-radius: 10px;
            margin-bottom: 20px; font-size: 14px; font-weight: 500;
            background: #d4edda; color: #155724; border: 1px solid #c3e6cb;
        }

        .msg-error {
            display: block; padding: 12px 15px; border-radius: 10px;
            margin-bottom: 20px; font-size: 14px; font-weight: 500;
            background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;
        }

        .password-strength {
            height: 4px; background: #ecf0f1; border-radius: 2px;
            margin-top: 8px; overflow: hidden; display: none;
        }

        .password-strength.show { display: block; }

        .password-strength-bar {
            height: 100%; width: 0%; transition: all 0.3s; border-radius: 2px;
        }

        .password-strength-bar.weak   { width: 33%; background: var(--accent); }
        .password-strength-bar.medium { width: 66%; background: var(--warning); }
        .password-strength-bar.strong { width: 100%; background: var(--success); }

        .error-message { color: var(--accent); font-size: 13px; margin-top: 5px; display: none; }
        .error-message.show { display: block; }

        @media (max-width: 1024px) {
            .left-panel { display: none; }
            .login-container { justify-content: center; }
        }

        @media (max-width: 480px) {
            .right-panel { padding: 20px; }
            .login-card { padding: 35px 25px; }
            .login-header h2 { font-size: 28px; }
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
            <p class="tagline">Reset your password and get back to managing your academics</p>
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
                    <h2>Forgot Password?</h2>
                    <p>Enter your email and set a new password</p>
                </div>

                <?php if ($error): ?>
                    <span class="msg-error">❌ <?php echo htmlspecialchars($error); ?></span>
                <?php endif; ?>
                <?php if ($success): ?>
                    <span class="msg-success">✅ <?php echo $success; ?></span>
                <?php endif; ?>

                <?php if (!$success): ?>
                <form method="POST" action="">

                    <!-- Email -->
                    <div class="form-group">
                        <label for="email">Your Registered Email</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email" class="form-input"
                                   placeholder="Enter your registered email" required
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <span class="input-icon">📧</span>
                        </div>
                    </div>

                    <!-- New Password -->
                    <div class="form-group">
                        <label for="newPassword">New Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="newPassword" name="newPassword" class="form-input"
                                   placeholder="Enter new password (min 8 chars)" required>
                            <span class="input-icon">🔒</span>
                            <button type="button" class="password-toggle" onclick="togglePassword('newPassword')">👁️</button>
                        </div>
                        <div class="password-strength" id="passwordStrength">
                            <div class="password-strength-bar" id="strengthBar"></div>
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="form-group">
                        <label for="confirmPassword">Confirm New Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="confirmPassword" name="confirmPassword" class="form-input"
                                   placeholder="Confirm your new password" required>
                            <span class="input-icon">✅</span>
                            <button type="button" class="password-toggle" onclick="togglePassword('confirmPassword')">👁️</button>
                        </div>
                        <div class="error-message" id="confirmError"></div>
                    </div>

                    <button type="submit" class="btn btn-reset">🔑 Reset Password</button>

                </form>
                <?php endif; ?>

                <div class="divider">or</div>

                <div class="back-link">
                    <a href="loginpage.php">← Back to Login</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const input  = document.getElementById(fieldId);
            const btn    = event.currentTarget;
            if (input.type === 'password') {
                input.type    = 'text';
                btn.textContent = '🙈';
            } else {
                input.type    = 'password';
                btn.textContent = '👁️';
            }
        }

        document.getElementById('newPassword').addEventListener('input', function() {
            const password         = this.value;
            const strengthBar      = document.getElementById('strengthBar');
            const strengthContainer = document.getElementById('passwordStrength');

            if (password.length === 0) { strengthContainer.classList.remove('show'); return; }
            strengthContainer.classList.add('show');

            let strength = 0;
            if (password.length >= 8) strength++;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength++;
            if (password.match(/[0-9]/)) strength++;
            if (password.match(/[^a-zA-Z0-9]/)) strength++;

            strengthBar.className = 'password-strength-bar';
            if (strength <= 2)      strengthBar.classList.add('weak');
            else if (strength === 3) strengthBar.classList.add('medium');
            else                     strengthBar.classList.add('strong');
        });

        document.getElementById('confirmPassword').addEventListener('input', function() {
            const newPassword     = document.getElementById('newPassword').value;
            const confirmPassword = this.value;
            const errorMsg        = document.getElementById('confirmError');

            if (confirmPassword && newPassword !== confirmPassword) {
                errorMsg.textContent = 'Passwords do not match';
                errorMsg.classList.add('show');
            } else {
                errorMsg.classList.remove('show');
            }
        });
    </script>
</body>
</html>