<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();
require_once 'db_connect.php';

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name        = mysqli_real_escape_string($conn, trim($_POST['institute_name']));
    $email       = mysqli_real_escape_string($conn, strtolower(trim($_POST['email'])));
    $phone       = mysqli_real_escape_string($conn, trim($_POST['phone']));
    $address     = mysqli_real_escape_string($conn, trim($_POST['address']));
    $type        = mysqli_real_escape_string($conn, $_POST['type']);
    $email_domain = mysqli_real_escape_string($conn, strtolower(trim($_POST['email_domain'])));
    $password    = $_POST['password'];
    $confirm     = $_POST['confirm_password'];

    if (empty($name)) {
        $error = "Please enter your institute name.";
    } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Please enter a valid email address.";
    } elseif (empty($phone)) {
        $error = "Please enter a contact number.";
    } elseif (empty($email_domain)) {
        $error = "Please enter your institute's email domain.";
    } elseif (!preg_match('/^[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/', $email_domain)) {
        $error = "Please enter a valid email domain (e.g. school.ac.in)";
    } elseif (!in_array($type, ['School', 'College', 'Tutorial'])) {
        $error = "Please select a valid institute type.";
    } elseif (strlen($password) < 6) {
        $error = "Password must be at least 6 characters.";
    } elseif ($password !== $confirm) {
        $error = "Passwords do not match!";
    } else {
        // Check duplicate email
        $check = mysqli_query($conn, "SELECT id FROM institutes WHERE email='$email'");
        if (mysqli_num_rows($check) > 0) {
            $error = "This email is already registered!";
        } else {
            // Check duplicate domain
            $check_domain = mysqli_query($conn, "SELECT id FROM institutes WHERE email_domain='$email_domain'");
            if (mysqli_num_rows($check_domain) > 0) {
                $error = "This email domain is already registered!";
            } else {
                $hashed = password_hash($password, PASSWORD_DEFAULT);
                $ins = "INSERT INTO institutes (name, email, password, phone, address, type, email_domain, created_at)
                        VALUES ('$name', '$email', '$hashed', '$phone', '$address', '$type', '$email_domain', NOW())";
                if (mysqli_query($conn, $ins)) {
                    $success = "Institute registered successfully! Your students and faculty can now select <strong>$name</strong> during login/registration.";
                } else {
                    $error = "Something went wrong: " . mysqli_error($conn);
                }
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
    <title>Trackera — Register Your Institute</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        :root {
            --primary: #2c3e50;
            --secondary: #3498db;
            --accent: #e74c3c;
            --success: #27ae60;
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
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        .bg-decoration {
            position: absolute;
            border-radius: 50%;
            opacity: 0.1;
            animation: float 20s infinite ease-in-out;
        }

        .decoration-1 {
            width: 300px; height: 300px;
            background: white;
            top: -100px; left: -100px;
            animation-delay: 0s;
        }

        .decoration-2 {
            width: 200px; height: 200px;
            background: #3498db;
            bottom: -50px; right: 10%;
            animation-delay: 5s;
        }

        .decoration-3 {
            width: 150px; height: 150px;
            background: #e74c3c;
            top: 20%; right: -75px;
            animation-delay: 10s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25%  { transform: translate(30px, -30px) scale(1.1); }
            50%  { transform: translate(-20px, 20px) scale(0.9); }
            75%  { transform: translate(20px, 30px) scale(1.05); }
        }

        .container {
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
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 48px;
            font-weight: 900;
            margin-bottom: 10px;
            letter-spacing: 2px;
        }

        .logo span { color: #ffd700; }

        .tagline {
            font-size: 16px;
            opacity: 0.8;
            margin-bottom: 50px;
            font-weight: 300;
        }

        .left-panel h2 {
            font-size: 32px;
            font-weight: 700;
            margin-bottom: 15px;
            line-height: 1.3;
        }

        .left-panel p {
            font-size: 15px;
            opacity: 0.85;
            line-height: 1.8;
            margin-bottom: 40px;
            font-weight: 300;
        }

        .feature-list {
            list-style: none;
            text-align: left;
            width: 100%;
            max-width: 320px;
        }

        .feature-list li {
            padding: 10px 0;
            font-size: 14px;
            opacity: 0.9;
            border-bottom: 1px solid rgba(255,255,255,0.15);
            display: flex;
            align-items: center;
            gap: 12px;
        }

        .feature-list li:last-child { border-bottom: none; }

        .right-panel {
            width: 520px;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding: 50px 45px;
            overflow-y: auto;
            max-height: 100vh;
        }

        .right-panel h3 {
            font-size: 26px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 5px;
        }

        .right-panel .subtitle {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 30px;
        }

        .form-group { margin-bottom: 18px; }

        .form-group label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 8px;
        }

        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 13px 18px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 14px;
            font-family: 'Poppins', sans-serif;
            transition: border-color 0.3s;
            color: var(--text-dark);
        }

        .form-group input:focus,
        .form-group select:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: var(--secondary);
        }

        .form-group textarea {
            resize: none;
            height: 80px;
        }

        .domain-input-wrapper {
            display: flex;
            align-items: center;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            overflow: hidden;
            transition: border-color 0.3s;
        }

        .domain-input-wrapper:focus-within {
            border-color: var(--secondary);
        }

        .domain-prefix {
            background: #f8f9fa;
            padding: 13px 15px;
            font-size: 14px;
            color: var(--text-muted);
            border-right: 2px solid #e0e0e0;
            white-space: nowrap;
            font-family: 'Poppins', sans-serif;
        }

        .domain-input-wrapper input {
            border: none !important;
            border-radius: 0 !important;
            flex: 1;
            padding: 13px 15px !important;
        }

        .domain-input-wrapper input:focus {
            outline: none;
            border: none !important;
        }

        .domain-hint {
            font-size: 12px;
            color: var(--text-muted);
            margin-top: 6px;
        }

        .input-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn-register {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
            margin-top: 5px;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102,126,234,0.4);
        }

        .divider {
            text-align: center;
            margin: 20px 0;
            color: var(--text-muted);
            font-size: 13px;
            position: relative;
        }

        .divider::before, .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 40%;
            height: 1px;
            background: #e0e0e0;
        }

        .divider::before { left: 0; }
        .divider::after  { right: 0; }

        .back-link {
            text-align: center;
            font-size: 14px;
            color: var(--text-muted);
        }

        .back-link a {
            color: var(--secondary);
            font-weight: 600;
            text-decoration: none;
        }

        .back-link a:hover { text-decoration: underline; }

        .msg-success {
            display: block; padding: 12px 15px; border-radius: 10px;
            margin-bottom: 20px; font-size: 13px; font-weight: 500;
            background: #d4edda; color: #155724; border: 1px solid #c3e6cb;
        }

        .msg-error {
            display: block; padding: 12px 15px; border-radius: 10px;
            margin-bottom: 20px; font-size: 13px; font-weight: 500;
            background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;
        }

        .type-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 10px;
        }

        .type-option {
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            padding: 12px 8px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            font-size: 13px;
            font-weight: 500;
            color: var(--text-muted);
        }

        .type-option:hover {
            border-color: #667eea;
            color: #667eea;
        }

        .type-option.selected {
            border-color: #667eea;
            background: #f0f2ff;
            color: #667eea;
            font-weight: 600;
        }

        .type-option .type-icon { font-size: 24px; margin-bottom: 5px; }

        @media (max-width: 900px) {
            .left-panel  { display: none; }
            .right-panel { width: 100%; max-width: 500px; margin: auto; }
        }
    </style>
</head>
<body>

    <div class="bg-decoration decoration-1"></div>
    <div class="bg-decoration decoration-2"></div>
    <div class="bg-decoration decoration-3"></div>

    <div class="container">

        <!-- Left Panel -->
        <div class="left-panel">
            <div class="logo">Track<span>era</span></div>
            <div class="tagline">Smart School Management System</div>

            <h2>Join Trackera Today 🎓</h2>
            <p>Register your institute and get access to a complete school management system — attendance, marks, fees, schedules and more!</p>

            <ul class="feature-list">
                <li>✅ Attendance tracking subject-wise</li>
                <li>📝 Marks and results management</li>
                <li>💰 Fees tracking and receipts</li>
                <li>📅 Class schedule management</li>
                <li>📢 Notice board for students</li>
                <li>🎓 Student promotion system</li>
                <li>🌐 Language subject selection</li>
                <li>🔒 Secure login for all users</li>
            </ul>
        </div>

        <!-- Right Panel -->
        <div class="right-panel">
            <h3>Register Your Institute</h3>
            <p class="subtitle">Fill in the details below to get started for free!</p>

            <?php if ($error): ?>
                <span class="msg-error">❌ <?php echo htmlspecialchars($error); ?></span>
            <?php endif; ?>

            <?php if ($success): ?>
                <span class="msg-success">✅ <?php echo $success; ?></span>
            <?php endif; ?>

            <?php if (!$success): ?>
            <form method="POST" action="">

                <!-- Institute Type -->
                <div class="form-group">
                    <label>Institute Type:</label>
                    <div class="type-grid">
                        <div class="type-option <?php echo (isset($_POST['type']) && $_POST['type']=='School') ? 'selected' : ''; ?>"
                             onclick="selectType('School')">
                            <div class="type-icon">🏫</div>
                            School
                        </div>
                        <div class="type-option <?php echo (isset($_POST['type']) && $_POST['type']=='College') ? 'selected' : ''; ?>"
                             onclick="selectType('College')">
                            <div class="type-icon">🎓</div>
                            College
                        </div>
                        <div class="type-option <?php echo (isset($_POST['type']) && $_POST['type']=='Tutorial') ? 'selected' : ''; ?>"
                             onclick="selectType('Tutorial')">
                            <div class="type-icon">📚</div>
                            Tutorial
                        </div>
                    </div>
                    <input type="hidden" name="type" id="typeInput"
                           value="<?php echo isset($_POST['type']) ? htmlspecialchars($_POST['type']) : ''; ?>">
                </div>

                <!-- Institute Name -->
                <div class="form-group">
                    <label>Institute Name:</label>
                    <input type="text" name="institute_name"
                           placeholder="e.g. Sunshine Academy"
                           value="<?php echo isset($_POST['institute_name']) ? htmlspecialchars($_POST['institute_name']) : ''; ?>"
                           required>
                </div>

                <!-- Email Domain -->
                <div class="form-group">
                    <label>Student Email Domain:</label>
                    <div class="domain-input-wrapper">
                        <span class="domain-prefix">studentname08@</span>
                        <input type="text" name="email_domain"
                               placeholder="school.ac.in"
                               value="<?php echo isset($_POST['email_domain']) ? htmlspecialchars($_POST['email_domain']) : ''; ?>">
                    </div>
                    <div class="domain-hint">
                        💡 e.g. if student email is <strong>name.surname08@sunshine.ac.in</strong> then enter <strong>sunshine.ac.in</strong>
                    </div>
                </div>

                <!-- Email and Phone -->
                <div class="input-row">
                    <div class="form-group">
                        <label>Contact Email:</label>
                        <input type="email" name="email"
                               placeholder="institute@email.com"
                               value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>"
                               required>
                    </div>
                    <div class="form-group">
                        <label>Phone Number:</label>
                        <input type="text" name="phone"
                               placeholder="e.g. 9876543210"
                               value="<?php echo isset($_POST['phone']) ? htmlspecialchars($_POST['phone']) : ''; ?>"
                               required>
                    </div>
                </div>

                <!-- Address -->
                <div class="form-group">
                    <label>Address:</label>
                    <textarea name="address"
                              placeholder="Institute address..."><?php echo isset($_POST['address']) ? htmlspecialchars($_POST['address']) : ''; ?></textarea>
                </div>

                <!-- Password -->
                <div class="input-row">
                    <div class="form-group">
                        <label>Password:</label>
                        <input type="password" name="password"
                               placeholder="Min 6 characters" required>
                    </div>
                    <div class="form-group">
                        <label>Confirm Password:</label>
                        <input type="password" name="confirm_password"
                               placeholder="Repeat password" required>
                    </div>
                </div>

                <button type="submit" class="btn-register">🏫 Register Institute</button>

            </form>
            <?php endif; ?>

            <div class="divider">or</div>

            <div class="back-link">
                Already registered? <a href="landingpage.php">← Back to Home</a>
            </div>

        </div>
    </div>

    <script>
        function selectType(type) {
            document.querySelectorAll('.type-option').forEach(el => el.classList.remove('selected'));
            document.getElementById('typeInput').value = type;
            event.currentTarget.classList.add('selected');
        }
    </script>

</body>
</html>