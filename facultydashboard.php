<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();
require_once 'db_connect.php';
require_once 'session_helper.php';

requireFaculty();

$user         = getLoggedInUser();
$faculty_name = $user['name'];
$first_name   = explode(' ', $faculty_name)[0];

// --- Stats from DB ---
// Total students
$students_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role='student'");
$total_students = mysqli_fetch_assoc($students_q)['cnt'];

// Classes today
$today = date('l');
$classes_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM schedule WHERE day_name='$today'");
$classes_today = mysqli_fetch_assoc($classes_q)['cnt'];

// Active notices (posted in last 7 days)
$notices_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM notices WHERE date >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)");
$active_notices = mysqli_fetch_assoc($notices_q)['cnt'];

// Total subjects
$subjects_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM subjects");
$total_subjects = mysqli_fetch_assoc($subjects_q)['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: #f0f0f0;
            min-height: 100vh;
        }

        .frame {
            width: 100vw;
            height: 100vh;
            background: white;
            position: relative;
            overflow: hidden;
        }

        /* Header */
        .header {
            position: absolute;
            top: 0; left: 0;
            width: 100%;
            height: 70px;
            background: #3498db;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
        }

        .header-title {
            color: white;
            font-size: 24px;
            font-weight: bold;
        }

        .header-right {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            gap: 5px;
        }

        .faculty-name {
            color: white;
            font-size: 16px;
            font-weight: 600;
        }

        .btn-logout {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 14px;
        }

        .btn-logout:hover { background: #c0392b; }

        /* Sidebar */
        .sidebar {
            position: absolute;
            top: 70px; left: 0;
            width: 250px;
            height: calc(100vh - 70px);
            background: #ecf0f1;
            padding: 15px 10px;
            overflow-y: auto;
        }

        .sidebar-section {
            margin-bottom: 10px;
        }

        .sidebar-section-title {
            font-size: 11px;
            font-weight: 700;
            color: #95a5a6;
            text-transform: uppercase;
            letter-spacing: 1px;
            padding: 5px 10px;
            margin-bottom: 5px;
        }

        .sidebar-btn {
            width: 230px;
            height: 50px;
            margin-bottom: 8px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-align: left;
            padding-left: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: white;
        }

        .sidebar-btn.active {
            background: #3498db;
            box-shadow: 0 4px 8px rgba(52, 152, 219, 0.3);
        }

        .sidebar-btn:not(.active) {
            background: #95a5a6;
        }

        .sidebar-btn:not(.active):hover {
            background: #7f8c8d;
            transform: translateX(5px);
        }

        .sidebar-btn.admin-btn:not(.active) {
            background: #8e44ad;
        }

        .sidebar-btn.admin-btn:not(.active):hover {
            background: #7d3c98;
            transform: translateX(5px);
        }

        /* Main Content */
        .main-content {
            position: absolute;
            top: 70px;
            left: 250px;
            width: calc(100vw - 250px);
            height: calc(100vh - 70px);
            background: white;
            padding: 30px;
            overflow-y: auto;
        }

        .welcome-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            border-radius: 15px;
            margin-bottom: 30px;
        }

        .welcome-section h2 {
            font-size: 32px;
            margin-bottom: 8px;
        }

        .welcome-section p {
            font-size: 16px;
            opacity: 0.9;
        }

        .quick-actions {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .action-card {
            background: white;
            padding: 25px;
            border-radius: 12px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-decoration: none;
            color: inherit;
            display: block;
        }

        .action-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 20px rgba(0,0,0,0.15);
        }

        .action-card.attendance { border-left: 5px solid #3498db; }
        .action-card.marks { border-left: 5px solid #2ecc71; }
        .action-card.notices { border-left: 5px solid #f39c12; }

        .action-card .icon { font-size: 40px; margin-bottom: 12px; }
        .action-card h3 { font-size: 18px; color: #2c3e50; margin-bottom: 8px; }
        .action-card p { font-size: 13px; color: #7f8c8d; }

        .stats-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .stats-section h3 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }

        .stat-item {
            text-align: center;
            padding: 20px;
            background: #ecf0f1;
            border-radius: 8px;
        }

        .stat-item .number {
            font-size: 36px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 5px;
        }

        .stat-item .label {
            font-size: 13px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="frame">
        <!-- Header -->
        <div class="header">
            <div class="header-title">Faculty Dashboard</div>
            <div class="header-right">
                <div class="faculty-name"><?php echo htmlspecialchars($faculty_name); ?></div>
                <button class="btn-logout" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Teaching Section -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">Teaching</div>
                <a href="facultydashboard.php" class="sidebar-btn active">🏠 Home</a>
                <a href="facultymarksattendance.php" class="sidebar-btn">✅ Mark Attendance</a>
                <a href="facultyentermarks.php" class="sidebar-btn">📝 Enter Marks</a>
                <a href="facultypostnotice.php" class="sidebar-btn">📢 Post Notices</a>
                <a href="facultymanageschedule.php" class="sidebar-btn">📅 Manage Schedule</a>
                <a href="facultyviewstudents.php" class="sidebar-btn">👥 View Students</a>
            </div>

            <!-- Administration Section -->
            <div class="sidebar-section">
                <div class="sidebar-section-title">Administration</div>
                <a href="facultyassigndivision.php" class="sidebar-btn admin-btn">🏷️ Assign Divisions</a>
                <a href="facultyassignrollnumbers.php" class="sidebar-btn admin-btn">🔢 Assign Roll Numbers</a>
                <a href="facultypromotestudents.php" class="sidebar-btn admin-btn">🎓 Promote Students</a>
                <a href="facultymanagesubjects.php" class="sidebar-btn admin-btn">📚 Manage Subjects</a>
                <a href="facultylanguagesetup.php" class="sidebar-btn admin-btn">🌐 Language Setup</a>
                <a href="facultymanagefees.php" class="sidebar-btn admin-btn">💰 Manage Fees</a>
                <a href="resetpassword.php" class="sidebar-btn admin-btn">🔒 Reset Password</a>
            </div>
        </div>

        <!-- Main Content -->
        <div class="main-content">

            <div class="welcome-section">
                <h2>Welcome back, <?php echo htmlspecialchars($first_name); ?>! 👋</h2>
                <p>Here's what's happening with your classes today (<?php echo date('l, d M Y'); ?>)</p>
            </div>

            <!-- Quick Actions -->
            <div class="quick-actions">
                <a href="facultymarksattendance.php" class="action-card attendance">
                    <div class="icon">✅</div>
                    <h3>Mark Attendance</h3>
                    <p>Take today's attendance</p>
                </a>
                <a href="facultyentermarks.php" class="action-card marks">
                    <div class="icon">📝</div>
                    <h3>Enter Marks</h3>
                    <p>Add exam marks</p>
                </a>
                <a href="facultypostnotice.php" class="action-card notices">
                    <div class="icon">📢</div>
                    <h3>Post Notice</h3>
                    <p>Announce to students</p>
                </a>
            </div>

            <!-- Stats -->
            <div class="stats-section">
                <h3>📊 Overview</h3>
                <div class="stats-grid">
                    <div class="stat-item">
                        <div class="number"><?php echo $total_students; ?></div>
                        <div class="label">Total Students</div>
                    </div>
                    <div class="stat-item">
                        <div class="number"><?php echo $classes_today; ?></div>
                        <div class="label">Classes Today</div>
                    </div>
                    <div class="stat-item">
                        <div class="number"><?php echo $total_subjects; ?></div>
                        <div class="label">Total Subjects</div>
                    </div>
                    <div class="stat-item">
                        <div class="number"><?php echo $active_notices; ?></div>
                        <div class="label">Active Notices</div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</body>
</html>