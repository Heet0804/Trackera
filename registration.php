<?php
session_start();
require_once 'db_connect.php';

// If already logged in, redirect
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['role'] === 'student') {
        header("Location: studentdashboard.php");
    } else {
        header("Location: facultydashboard.php");
    }
    exit();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') 
{
    $name     = mysqli_real_escape_string($conn, trim($_POST['fullname']));
    $email    = mysqli_real_escape_string($conn, strtolower(trim($_POST['email'])));
    $password = $_POST['password'];
    $grade    = (int)$_POST['grade'];
    $gender   = mysqli_real_escape_string($conn, $_POST['gender']);

    // Validate email format: must be name.surname08@school.ac.in
    if (!preg_match('/^[a-zA-Z]+\.[a-zA-Z]+08@school\.ac\.in$/', $email)) {
        $error = "Invalid email format! Must be name.surname08@school.ac.in (e.g. heet.lakhani08@school.ac.in)";
    }
    // Check duplicate email
    elseif (mysqli_num_rows(mysqli_query($conn, "SELECT user_id FROM users WHERE email='$email'")) > 0) {
        $error = "This email is already registered. Please login.";
    }
    // Validate name not empty
    elseif (empty($name)) {
        $error = "Please enter your full name.";
    }
    // Validate grade
    elseif ($grade < 1 || $grade > 10) {
        $error = "Please select a valid grade.";
    }
    // Validate gender
    elseif (!in_array($gender, ['Male', 'Female'])) {
        $error = "Please select a valid gender.";
    }
    else {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $division = null;

        // Auto-assign division ONLY for Grade 1 with gender balancing
        if ($grade == 1) {
            $divisions = ['A', 'B', 'C', 'D', 'E'];
            $assigned = false;

            foreach ($divisions as $div) {
                $total_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE grade=1 AND division='$div' AND role='student'");
                $total = mysqli_fetch_assoc($total_q)['cnt'];

                if ($total >= 42) continue;

                $same_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE grade=1 AND division='$div' AND gender='$gender' AND role='student'");
                $same_count = mysqli_fetch_assoc($same_q)['cnt'];
                $other_count = $total - $same_count;

                // Assign if gender balance is acceptable (tolerance of ±5)
                if ($total == 0 || $same_count <= $other_count + 5) {
                    $division = $div;
                    $assigned = true;
                    break;
                }
            }

            // Fallback: fill least full division if no balanced one found
            if (!$assigned) {
                foreach ($divisions as $div) {
                    $total_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE grade=1 AND division='$div' AND role='student'");
                    $total = mysqli_fetch_assoc($total_q)['cnt'];
                    if ($total < 42) { $division = $div; break; }
                }
            }
        }
        // Grade 2-7: NULL (faculty assigns manually)
        // Grade 8-10: NULL (assigned after language selection)

        $div_value = $division ? "'$division'" : "NULL";

        $insert = "INSERT INTO users (name, email, password, role, grade, division, gender, created_at)
                   VALUES ('$name', '$email', '$hashed_password', 'student', $grade, $div_value, '$gender', NOW())";

        if (mysqli_query($conn, $insert)) {
            $success = "Registration Successful! Redirecting to login...";
            header("refresh:2;url=loginpage.php");
        } else {
            $error = "Registration failed: " . mysqli_error($conn);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trackera - Registration</title>
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
            from { opacity: 0; transform: translateX(-50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 72px;
            font-weight: 900;
            margin-bottom: 20px;
            text-shadow: 4px 4px 8px rgba(0, 0, 0, 0.3);
            letter-spacing: -2px;
        }

        .tagline {
            font-size: 24px;
            font-weight: 300;
            opacity: 0.95;
            max-width: 400px;
            line-height: 1.6;
        }

        .features {
            margin-top: 50px;
            display: flex;
            flex-direction: column;
            gap: 20px;
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
            from { opacity: 0; transform: translateX(50px); }
            to { opacity: 1; transform: translateX(0); }
        }

        .login-card {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 30px;
            padding: 50px 45px;
            box-shadow: 0 30px 60px rgba(0, 0, 0, 0.3);
            width: 100%;
            max-width: 500px;
            position: relative;
            overflow: hidden;
            max-height: 90vh;
            overflow-y: auto;
        }

        .login-card::-webkit-scrollbar { width: 6px; }
        .login-card::-webkit-scrollbar-track { background: #f1f1f1; border-radius: 10px; }
        .login-card::-webkit-scrollbar-thumb { background: #3498db; border-radius: 10px; }

        .login-card::before {
            content: '';
            position: absolute;
            top: 0; left: 0; right: 0;
            height: 5px;
            background: linear-gradient(90deg, #3498db, #27ae60, #e67e22, #e74c3c);
        }

        .login-header {
            margin-bottom: 35px;
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
            margin-bottom: 22px;
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

        .form-input,
        .form-select {
            width: 100%;
            padding: 14px 45px 14px 45px;
            border: 2px solid #e0e0e0;
            border-radius: 12px;
            font-size: 15px;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
            background: white;
            appearance: none;
        }

        .form-input:focus,
        .form-select:focus {
            outline: none;
            border-color: var(--secondary);
            box-shadow: 0 0 0 4px rgba(52, 152, 219, 0.1);
        }

        .input-icon {
            position: absolute;
            left: 15px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 18px;
            pointer-events: none;
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

        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .info-note {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 22px;
            font-size: 13px;
            color: #1565c0;
        }

        .info-note strong {
            display: block;
            margin-bottom: 4px;
        }

        .btn {
            width: 100%;
            padding: 16px;
            border: none;
            border-radius: 12px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .btn-register {
            background: linear-gradient(135deg, var(--secondary) 0%, #2980b9 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(52, 152, 219, 0.3);
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(52, 152, 219, 0.4);
        }

        .divider {
            text-align: center;
            margin: 25px 0 15px;
            color: var(--text-muted);
            font-size: 14px;
            position: relative;
        }

        .divider::before,
        .divider::after {
            content: '';
            position: absolute;
            top: 50%;
            width: 35%;
            height: 1px;
            background: #e0e0e0;
        }

        .divider::before { left: 0; }
        .divider::after { right: 0; }

        .login-link {
            text-align: center;
        }

        .login-link a {
            color: var(--secondary);
            text-decoration: none;
            font-weight: 600;
            font-size: 15px;
            transition: color 0.3s;
        }

        .login-link a:hover { color: var(--primary); }

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
            .left-panel { display: none; }
            .login-container { justify-content: center; }
        }

        @media (max-width: 480px) {
            .right-panel { padding: 20px; }
            .login-card { padding: 35px 25px; }
            .login-header h2 { font-size: 28px; }
            .form-row { grid-template-columns: 1fr; }
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
            <p class="tagline">Join us today and experience seamless academic management</p>
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

                <?php if ($error): ?>
                    <span class="msg-error"><?php echo htmlspecialchars($error); ?></span>
                <?php endif; ?>
                <?php if ($success): ?>
                    <span class="msg-success"><?php echo $success; ?></span>
                <?php endif; ?>

                <div class="login-header">
                    <h2>Create Account</h2>
                    <p>Fill in your details to get started</p>
                </div>

                <div class="info-note">
                    <strong>📌 Note:</strong>
                    Division and Roll Number will be assigned to you by your faculty after registration.
                </div>

                <form method="POST" action="" id="registerForm">
                    <div class="form-group">
                        <label for="fullname">Full Name</label>
                        <div class="input-wrapper">
                            <input type="text" id="fullname" name="fullname" class="form-input" placeholder="Enter your full name" required value="<?php echo isset($_POST['fullname']) ? htmlspecialchars($_POST['fullname']) : ''; ?>">
                            <span class="input-icon">👤</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="email">Email Address</label>
                        <div class="input-wrapper">
                            <input type="email" id="email" name="email" class="form-input" placeholder="e.g. name.surname08@school.ac.in" required value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                            <span class="input-icon">📧</span>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <input
                                type="password"
                                id="password"
                                name="password"
                                class="form-input"
                                placeholder="Create a strong password"
                                required
                                pattern="(?=.*\d)(?=.*[a-z])(?=.*[A-Z])(?=.*[\W_]).{8,}"
                                title="Min 8 characters, include uppercase, lowercase, number and special character"
                            >
                            <span class="input-icon">🔒</span>
                            <button type="button" class="password-toggle" onclick="togglePassword()">👁️</button>
                        </div>
                    </div>

                    <div class="form-row">
                        <div class="form-group">
                            <label for="grade">Grade</label>
                            <div class="input-wrapper">
                                <select id="grade" name="grade" class="form-select" required>
                                    <option value="">Select Grade</option>
                                    <option value="1">Grade 1</option>
                                    <option value="2">Grade 2</option>
                                    <option value="3">Grade 3</option>
                                    <option value="4">Grade 4</option>
                                    <option value="5">Grade 5</option>
                                    <option value="6">Grade 6</option>
                                    <option value="7">Grade 7</option>
                                    <option value="8">Grade 8</option>
                                    <option value="9">Grade 9</option>
                                    <option value="10">Grade 10</option>
                                </select>
                                <span class="input-icon">📚</span>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="gender">Gender</label>
                            <div class="input-wrapper">
                                <select id="gender" name="gender" class="form-select" required>
                                    <option value="">Select Gender</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                                <span class="input-icon">🧑</span>
                            </div>
                        </div>
                    </div>

                    <div class="button-group">
                        <button type="submit" class="btn btn-register">Register</button>
                    </div>
                </form>

                <div class="divider">
                    <span>Already have an account?</span>
                </div>

                <div class="login-link">
                    <a href="loginpage.php">Login here</a>
                </div>
            </div>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleBtn = document.querySelector('.password-toggle');
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleBtn.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleBtn.textContent = '👁️';
            }
        }

        document.querySelectorAll('.form-input, .form-select').forEach(input => {
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