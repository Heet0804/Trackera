<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();
require_once 'db_connect.php';

$error = '';

if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'student') {
        header("Location: studentdashboard.php");
    } else {
        header("Location: facultydashboard.php");
    }
    exit();
}

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $email    = mysqli_real_escape_string($conn, strtolower(trim($_POST['email'])));
    $password = $_POST['password'];

    // Extract domain from email
    $email_parts  = explode('@', $email);
    $email_prefix = $email_parts[0];
    $email_domain = $email_parts[1] ?? '';

    // Find institute by domain
    $inst_q = mysqli_query($conn, "SELECT id, name, student_email_format, faculty_email_format FROM institutes WHERE email_domain='$email_domain'");

    if (mysqli_num_rows($inst_q) == 0) {
        $error = "No institute found for this email domain! Please ask your institute to register on Trackera first.";
    } else {
        $institute            = mysqli_fetch_assoc($inst_q);
        $institute_id         = $institute['id'];
        $institute_name       = $institute['name'];
        $student_email_format = $institute['student_email_format'];
        $faculty_email_format = $institute['faculty_email_format'];

        // Extract prefixes from stored format examples
        $student_prefix = explode('@', $student_email_format)[0]; // e.g. "gch123" or "name.surname08"
        $faculty_prefix = explode('@', $faculty_email_format)[0]; // e.g. "smrit.mandhana"

        // Find common base between student and faculty prefix from the start
        $common_base = '';
        for ($i = 0; $i < min(strlen($student_prefix), strlen($faculty_prefix)); $i++) {
            if ($student_prefix[$i] === $faculty_prefix[$i]) {
                $common_base .= $student_prefix[$i];
            } else {
                break;
            }
        }

        $student_suffix = substr($student_prefix, strlen($common_base)); // e.g. "123" or "08"
        $faculty_suffix = substr($faculty_prefix, strlen($common_base)); // e.g. "" or "ndhana"

        $is_student = false;

        if (empty($common_base)) {
            // No common base — completely different formats e.g. gch123 vs smrit.mandhana
            // Check if login email starts with same letters as student example
            $student_letters = preg_replace('/[^a-zA-Z]/', '', $student_prefix); // e.g. "gch"
            $student_alpha   = substr($student_letters, 0, 3); // first 3 letters e.g. "gch"
            $is_student      = (strpos($email_prefix, $student_alpha) === 0);
        } elseif (empty($faculty_suffix)) {
    // Student has extra suffix e.g. 08 — check if login email ends with that suffix
    $is_student = (substr($email_prefix, -strlen($student_suffix)) === $student_suffix);
}   else {
            // Both differ after common base — student ends with student_suffix
            $is_student = (substr($email_prefix, -strlen($student_suffix)) === $student_suffix);
        }

        // Check if user exists
        $result = mysqli_query($conn, "SELECT * FROM users WHERE email='$email' AND institute_id='$institute_id'");

        if ($result && mysqli_num_rows($result) > 0) {
            $user = mysqli_fetch_assoc($result);

            if (password_verify($password, $user['password'])) {
                $_SESSION['user_id']        = $user['user_id'];
                $_SESSION['name']           = $user['name'];
                $_SESSION['email']          = $user['email'];
                $_SESSION['role']           = $is_student ? 'student' : 'faculty';
                $_SESSION['grade']          = $user['grade'];
                $_SESSION['division']       = $user['division'];
                $_SESSION['roll_no']        = $user['roll_no'];
                $_SESSION['institute_id']   = $institute_id;
                $_SESSION['institute_name'] = $institute_name;

                if ($is_student) {
                    header("Location: studentdashboard.php");
                } else {
                    header("Location: facultydashboard.php");
                }
                exit();
            } else {
                $error = "Invalid credentials. Please enter the correct password.";
            }
        } else {
            if ($is_student) {
                $error = "Account not found. Redirecting to registration...";
                header("refresh:2;url=registration.php");
            } else {
                // Faculty first time login — auto create account
                $name_words     = explode('.', $email_prefix);
                $formatted_name = ucwords(implode(' ', $name_words));
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);

                $insert_query = "INSERT INTO users (name, email, password, role, institute_id, created_at)
                                 VALUES ('$formatted_name', '$email', '$hashed_password', 'faculty', '$institute_id', NOW())";

                if (mysqli_query($conn, $insert_query)) {
                    $user_id = mysqli_insert_id($conn);

                    $_SESSION['user_id']        = $user_id;
                    $_SESSION['name']           = $formatted_name;
                    $_SESSION['email']          = $email;
                    $_SESSION['role']           = 'faculty';
                    $_SESSION['institute_id']   = $institute_id;
                    $_SESSION['institute_name'] = $institute_name;

                    header("Location: facultydashboard.php");
                    exit();
                } else {
                    $error = "Error creating faculty account: " . mysqli_error($conn);
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
    <title>Student Portal - Login</title>
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
            from { opacity: 0; transform: translateX(-50px); }
            to   { opacity: 1; transform: translateX(0); }
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 72px;
            font-weight: 900;
            margin-bottom: 20px;
            text-shadow: 4px 4px 8px rgba(0,0,0,0.3);
            letter-spacing: -2px;
            animation: fadeInScale 1s ease-out 0.3s both;
        }

        @keyframes fadeInScale {
            from { opacity: 0; transform: scale(0.9); }
            to   { opacity: 1; transform: scale(1); }
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
            from { opacity: 0; transform: translateY(20px); }
            to   { opacity: 1; transform: translateY(0); }
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
            background: rgba(255,255,255,0.2);
            padding: 10px;
            border-radius: 12px;
            backdrop-filter: blur(10px);
        }

        .right-panel {
            width: 520px;
            background: white;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            padding: 50px 45px 40px 45px;
            overflow-y: auto;
            height: 100vh;
        }

        .login-card {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 50px 45px 40px 45px;
            box-shadow: 0 30px 60px rgba(0,0,0,0.3);
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: visible;
        }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 5px;
            background: linear-gradient(90deg, #3498db, #27ae60, #e67e22, #e74c3c);
            border-radius: 30px 30px 0 0;
        }

        .login-header { margin-bottom: 40px; }

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

        .error-message {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c33;
            font-size: 14px;
        }

        .success-message {
            background: #d4edda;
            color: #155724;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #28a745;
            font-size: 14px;
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
        }

        .input-wrapper { position: relative; }

        .form-input {
            width: 100%;
            padding: 15px 45px 15px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .form-input:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 4px rgba(52,152,219,0.1);
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
        }

        .password-toggle {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            font-size: 18px;
            padding: 5px;
        }

        .form-options {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            font-size: 13px;
        }

        .remember-me {
            display: flex;
            align-items: center;
            gap: 8px;
            cursor: pointer;
            color: var(--text-dark);
        }

        .remember-me input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
        }

        .forgot-password {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 500;
            transition: color 0.3s;
        }

        .forgot-password:hover { color: var(--primary); }

        .button-group {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .btn {
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-login {
            background: linear-gradient(135deg, var(--secondary) 0%, #2980b9 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(52,152,219,0.3);
        }

        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52,152,219,0.4);
        }

        .btn-register {
            background: white;
            color: var(--secondary);
            border: 2px solid var(--secondary);
        }

        .btn-register:hover {
            background: var(--secondary);
            color: white;
        }

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
            <p class="tagline">Your gateway to academic excellence and seamless learning management</p>
            <div class="features">
                <div class="feature-item">
                    <div class="feature-icon">📊</div>
                    <span>Track your attendance in real-time</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">📝</div>
                    <span>Access grades and performance metrics</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">💰</div>
                    <span>Manage fees and payments easily</span>
                </div>
                <div class="feature-item">
                    <div class="feature-icon">📅</div>
                    <span>Stay updated with your schedule</span>
                </div>
            </div>
        </div>

        <div class="right-panel">
            <div class="login-card">
                <div class="login-header">
                    <h2>Welcome Back!</h2>
                    <p>Please login to access your dashboard</p>
                </div>

                <?php if ($error): ?>
                    <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email"
                                   class="form-input" placeholder="Enter your email" required>
                            <span class="input-icon">📧</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input type="password" id="password" name="password"
                                   class="form-input" placeholder="Enter your password" required>
                            <span class="input-icon">🔒</span>
                            <button type="button" class="password-toggle" onclick="togglePassword()">👁️</button>
                        </div>
                    </div>

                    <div class="form-options">
                        <label class="remember-me">
                            <input type="checkbox" id="remember">
                            <span>Remember me</span>
                        </label>
                        <a href="resetpassword.php" class="forgot-password">Forgot Password?</a>
                    </div>

                    <div class="button-group">
    <button type="submit" class="btn btn-login">Login</button>
    <button type="button" class="btn btn-register" onclick="window.location.href='registration.php'">
        Create Student Account
    </button>
    <button type="button" class="btn btn-register" onclick="window.location.href='landingpage.php'">
        🏠 Back to Home
    </button>
</div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn     = document.querySelector('.password-toggle');
            if (passwordInput.type === 'password') {
                passwordInput.type    = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordInput.type    = 'password';
                toggleBtn.textContent = '👁️';
            }
        }
    </script>
</body>
</html>