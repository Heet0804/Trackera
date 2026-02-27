<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();

// If already logged in redirect to dashboard
if (isset($_SESSION['role'])) {
    if ($_SESSION['role'] === 'student') {
        header("Location: studentdashboard.php");
    } else {
        header("Location: facultydashboard.php");
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Trackera — Smart School Management</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&family=Playfair+Display:wght@700;900&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Poppins', sans-serif;
            min-height: 100vh;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 25%, #f093fb 50%, #4facfe 100%);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
            display: flex;
            flex-direction: column;
            overflow-x: hidden;
        }

        @keyframes gradientShift {
            0%   { background-position: 0% 50%; }
            50%  { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }

        /* Floating decorations */
        .bg-decoration {
            position: fixed;
            border-radius: 50%;
            opacity: 0.08;
            animation: float 20s infinite ease-in-out;
            pointer-events: none;
        }

        .decoration-1 {
            width: 400px; height: 400px;
            background: white;
            top: -150px; left: -150px;
            animation-delay: 0s;
        }

        .decoration-2 {
            width: 300px; height: 300px;
            background: #3498db;
            bottom: -100px; right: 5%;
            animation-delay: 5s;
        }

        .decoration-3 {
            width: 200px; height: 200px;
            background: #e74c3c;
            top: 30%; right: -80px;
            animation-delay: 10s;
        }

        .decoration-4 {
            width: 150px; height: 150px;
            background: white;
            bottom: 20%; left: 5%;
            animation-delay: 7s;
        }

        @keyframes float {
            0%, 100% { transform: translate(0, 0) scale(1); }
            25%  { transform: translate(30px, -30px) scale(1.1); }
            50%  { transform: translate(-20px, 20px) scale(0.9); }
            75%  { transform: translate(20px, 30px) scale(1.05); }
        }

        /* Navbar */
        .navbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 60px;
            position: relative;
            z-index: 10;
        }

        .logo {
            font-family: 'Playfair Display', serif;
            font-size: 32px;
            font-weight: 900;
            color: white;
            letter-spacing: 2px;
        }

        .logo span { color: #ffd700; }

        .nav-btn {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid rgba(255,255,255,0.5);
            padding: 10px 28px;
            border-radius: 25px;
            font-size: 15px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .nav-btn:hover {
            background: white;
            color: #667eea;
            border-color: white;
        }

        /* Hero Section */
        .hero {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            text-align: center;
            padding: 60px 20px;
            position: relative;
            z-index: 10;
        }

        .hero-badge {
            background: rgba(255,255,255,0.2);
            color: white;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 13px;
            font-weight: 500;
            margin-bottom: 25px;
            border: 1px solid rgba(255,255,255,0.3);
            display: inline-block;
        }

        .hero h1 {
            font-family: 'Playfair Display', serif;
            font-size: 72px;
            font-weight: 900;
            color: white;
            line-height: 1.1;
            margin-bottom: 20px;
            text-shadow: 0 4px 20px rgba(0,0,0,0.2);
        }

        .hero h1 span { color: #ffd700; }

        .hero p {
            font-size: 20px;
            color: rgba(255,255,255,0.85);
            max-width: 600px;
            line-height: 1.7;
            margin-bottom: 50px;
            font-weight: 300;
        }

        .hero-buttons {
            display: flex;
            gap: 20px;
            flex-wrap: wrap;
            justify-content: center;
            margin-bottom: 70px;
        }

        .btn-primary {
            background: white;
            color: #667eea;
            padding: 16px 45px;
            border-radius: 35px;
            font-size: 17px;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s;
            box-shadow: 0 8px 25px rgba(0,0,0,0.2);
            font-family: 'Poppins', sans-serif;
        }

        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.3);
            background: #f8f9fa;
        }

        .btn-secondary {
            background: transparent;
            color: white;
            padding: 16px 45px;
            border-radius: 35px;
            font-size: 17px;
            font-weight: 600;
            text-decoration: none;
            border: 2px solid rgba(255,255,255,0.7);
            transition: all 0.3s;
            font-family: 'Poppins', sans-serif;
        }

        .btn-secondary:hover {
            background: rgba(255,255,255,0.15);
            border-color: white;
            transform: translateY(-3px);
        }

        /* Stats Row */
        .stats-row {
            display: flex;
            gap: 50px;
            justify-content: center;
            flex-wrap: wrap;
        }

        .stat {
            text-align: center;
            color: white;
        }

        .stat .num {
            font-size: 36px;
            font-weight: 700;
            display: block;
        }

        .stat .lbl {
            font-size: 13px;
            opacity: 0.8;
            font-weight: 300;
        }

        /* Features Section */
        .features {
            background: rgba(255,255,255,0.1);
            backdrop-filter: blur(10px);
            padding: 60px;
            position: relative;
            z-index: 10;
        }

        .features h2 {
            text-align: center;
            color: white;
            font-size: 36px;
            font-weight: 700;
            margin-bottom: 40px;
        }

        .features-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            max-width: 1000px;
            margin: 0 auto;
        }

        .feature-card {
            background: rgba(255,255,255,0.15);
            border: 1px solid rgba(255,255,255,0.2);
            border-radius: 15px;
            padding: 30px 25px;
            text-align: center;
            color: white;
            transition: all 0.3s;
        }

        .feature-card:hover {
            background: rgba(255,255,255,0.25);
            transform: translateY(-5px);
        }

        .feature-card .icon { font-size: 40px; margin-bottom: 15px; }
        .feature-card h3    { font-size: 18px; font-weight: 600; margin-bottom: 10px; }
        .feature-card p     { font-size: 13px; opacity: 0.8; line-height: 1.6; }

        /* Footer */
        .footer {
            text-align: center;
            padding: 25px;
            color: rgba(255,255,255,0.6);
            font-size: 13px;
            position: relative;
            z-index: 10;
        }

        @media (max-width: 768px) {
            .hero h1       { font-size: 42px; }
            .features-grid { grid-template-columns: 1fr; }
            .navbar        { padding: 20px 25px; }
            .features      { padding: 40px 25px; }
            .stats-row     { gap: 30px; }
        }
    </style>
</head>
<body>

    <!-- Background decorations -->
    <div class="bg-decoration decoration-1"></div>
    <div class="bg-decoration decoration-2"></div>
    <div class="bg-decoration decoration-3"></div>
    <div class="bg-decoration decoration-4"></div>

    <!-- Navbar -->
    <nav class="navbar">
        <div class="logo">Track<span>era</span></div>
        <a href="loginpage.php" class="nav-btn">Login</a>
    </nav>

    <!-- Hero -->
    <div class="hero">
        <div class="hero-badge">🎓 Smart School Management System</div>

        <h1>Manage Your <span>School</span><br>The Smart Way</h1>

        <p>Trackera brings together attendance, marks, fees, schedules and more — all in one place. Built for schools, colleges and tutorial classes.</p>

        <div class="hero-buttons">
            <a href="loginpage.php" class="btn-primary">🔐 Login to Portal</a>
            <a href="instituteregister.php" class="btn-secondary">🏫 Register Your Institute</a>
        </div>

        <div class="stats-row">
            <div class="stat">
                <span class="num">📊</span>
                <span class="lbl">Attendance Tracking</span>
            </div>
            <div class="stat">
                <span class="num">📝</span>
                <span class="lbl">Marks Management</span>
            </div>
            <div class="stat">
                <span class="num">💰</span>
                <span class="lbl">Fees Management</span>
            </div>
            <div class="stat">
                <span class="num">📅</span>
                <span class="lbl">Schedule Management</span>
            </div>
        </div>
    </div>

    <!-- Features -->
    <div class="features">
        <h2>Everything You Need 🚀</h2>
        <div class="features-grid">
            <div class="feature-card">
                <div class="icon">✅</div>
                <h3>Attendance Tracking</h3>
                <p>Mark and monitor student attendance subject-wise with detailed history.</p>
            </div>
            <div class="feature-card">
                <div class="icon">📝</div>
                <h3>Marks & Results</h3>
                <p>Enter exam marks, view performance and track student progress easily.</p>
            </div>
            <div class="feature-card">
                <div class="icon">💰</div>
                <h3>Fees Management</h3>
                <p>Track fee payments, pending amounts and generate receipts instantly.</p>
            </div>
            <div class="feature-card">
                <div class="icon">📅</div>
                <h3>Schedule Management</h3>
                <p>Create and manage class timetables visible to all students instantly.</p>
            </div>
            <div class="feature-card">
                <div class="icon">📢</div>
                <h3>Notice Board</h3>
                <p>Post announcements and notices to students grade-wise instantly.</p>
            </div>
            <div class="feature-card">
                <div class="icon">🎓</div>
                <h3>Student Promotion</h3>
                <p>Promote students to next grade automatically at the end of year.</p>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <div class="footer">
        © 2026 Trackera. Built for modern schools and tutorial classes.
    </div>

</body>
</html>