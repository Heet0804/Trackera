<?php
session_start();
require_once 'db_connect.php';
require_once 'session_helper.php';

// Only students allowed
requireStudent();

$user = getLoggedInUser();
$student_email = $user['email'];
$student_name  = $user['name'];
$grade         = $user['grade'];
$division      = $user['division'];
$roll_no       = $user['roll_no'];

// First name only for welcome
$first_name = explode(' ', $student_name)[0];

// --- Attendance % ---
$att_q = mysqli_query($conn, "SELECT COUNT(*) as total, SUM(status='Present') as present FROM attendance WHERE student_email='$student_email'");
$att = mysqli_fetch_assoc($att_q);
$total_classes = $att['total'] ?? 0;
$present       = $att['present'] ?? 0;
$att_percent   = $total_classes > 0 ? round(($present / $total_classes) * 100) : 0;

// --- Fees ---
$fees_q = mysqli_query($conn, "SELECT total_fees, paid_amount, pending_amount FROM fees WHERE student_email='$student_email' ORDER BY created_at DESC LIMIT 1");
$fees = mysqli_fetch_assoc($fees_q);

// --- Today's Schedule ---
$today = date('l'); // Monday, Tuesday etc.
$schedule_q = mysqli_query($conn, "SELECT subject, time_from, time_to, teacher_email FROM schedule WHERE grade='$grade' AND division='$division' AND day_name='$today' ORDER BY time_from ASC");

// --- Recent Notices (last 3) ---
$notices_q = mysqli_query($conn, "SELECT title, message, date FROM notices WHERE (grade='$grade' OR grade IS NULL) ORDER BY created_at DESC LIMIT 3");

// --- Language selection trigger check ---
$lang_trigger_q = mysqli_query($conn, "SELECT id FROM language_selection WHERE student_email='$student_email'");
$lang_already_selected = mysqli_num_rows($lang_trigger_q) > 0;

// Check if faculty has triggered language selection for this student
// We check via a flag - if grade is 7 and faculty has triggered (we use academic_year table or a simple check)
$show_language_btn = false;
if ($grade == 7 && !$lang_already_selected) {
    // Check if any language_selection record exists for grade 7 students (meaning faculty triggered it)
    $trigger_q = mysqli_query($conn, "SELECT id FROM language_selection WHERE grade=7 LIMIT 1");
    // Actually check via a dedicated trigger flag - for now show button if grade 7
    $show_language_btn = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
        }

        /* Header */
        .header {
            background-color: #2c3e50;
            height: 70px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0 30px;
            color: white;
        }

        .header h1 {
            font-size: 24px;
        }

        .user-section {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
        }

        .user-name {
            font-size: 16px;
            margin-bottom: 5px;
        }

        .logout-btn {
            background-color: #e74c3c;
            color: white;
            border: none;
            padding: 8px 24px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 12px;
            transition: background-color 0.3s;
        }

        .logout-btn:hover {
            background-color: #c0392b;
        }

        /* Main Container */
        .container {
            display: flex;
            min-height: calc(100vh - 70px);
        }

        /* Sidebar */
        .sidebar {
            width: 250px;
            background-color: #ecf0f1;
            padding: 20px 10px;
        }

        .menu-item {
            background-color: #95a5a6;
            color: white;
            padding: 15px 20px;
            margin-bottom: 10px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 16px;
            transition: all 0.3s;
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }

        .menu-item:hover {
            background-color: #7f8c8d;
            transform: translateX(5px);
        }

        .menu-item.active {
            background-color: #3498db;
        }

        /* Main Content */
        .main-content {
            flex: 1;
            padding: 30px;
            background-color: white;
        }

        .welcome-section h2 {
            color: #2c3e50;
            font-size: 28px;
            margin-bottom: 10px;
        }

        .welcome-section p {
            color: #7f8c8d;
            font-size: 16px;
            margin-bottom: 35px;
        }

        /* Stats Cards */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(270px, 1fr));
            gap: 30px;
            margin-bottom: 50px;
        }

        .stat-card {
            border-radius: 10px;
            padding: 30px;
            color: white;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
        }

        .stat-card.attendance {
            background-color: #3498db;
        }

        .stat-card.marks {
            background-color: #27ae60;
        }

        .stat-card.fees {
            background-color: #e67e22;
        }

        .stat-card h3 {
            font-size: 20px;
            margin-bottom: 20px;
        }

        .stat-value {
            font-size: 48px;
            font-weight: bold;
            margin-bottom: 10px;
        }

        .stat-detail {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Schedule Section */
        .schedule-section {
            margin-bottom: 40px;
        }

        .schedule-section h3 {
            color: #2c3e50;
            font-size: 22px;
            margin-bottom: 20px;
        }

        .schedule-table {
            width: 100%;
            border-collapse: collapse;
            background-color: white;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-radius: 5px;
            overflow: hidden;
        }

        .schedule-table thead {
            background-color: #ecf0f1;
        }

        .schedule-table th {
            padding: 15px;
            text-align: left;
            color: #2c3e50;
            font-weight: bold;
            font-size: 14px;
        }

        .schedule-table td {
            padding: 15px;
            border-top: 1px solid #bdc3c7;
            color: #34495e;
            font-size: 14px;
        }

        .schedule-table tbody tr {
            transition: background-color 0.2s;
        }

        .schedule-table tbody tr:hover {
            background-color: #f8f9fa;
        }

        /* Notices Section */
        .notices-section h3 {
            color: #2c3e50;
            font-size: 22px;
            margin-bottom: 15px;
        }

        .notice-box {
            background-color: #fff3cd;
            border: 2px solid #ffc107;
            border-radius: 5px;
            padding: 15px;
            color: #856404;
            font-size: 14px;
            margin-bottom: 10px;
        }

        /* Info card for unassigned division/roll */
        .info-card {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 12px 15px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            color: #1565c0;
        }

        /* Language selection button */
        .lang-btn {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 8px;
            cursor: pointer;
            font-size: 15px;
            font-weight: bold;
            margin-bottom: 20px;
            display: inline-block;
            text-decoration: none;
            animation: pulse 2s infinite;
        }

        @keyframes pulse {
            0%, 100% { box-shadow: 0 0 0 0 rgba(102,126,234,0.4); }
            50% { box-shadow: 0 0 0 10px rgba(102,126,234,0); }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
            }

            .sidebar {
                width: 100%;
            }

            .stats-grid {
                grid-template-columns: 1fr;
            }

            .header {
                padding: 0 15px;
            }

            .header h1 {
                font-size: 18px;
            }

            .main-content {
                padding: 15px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <div class="header">
        <h1>Student Dashboard</h1>
        <div class="user-section">
            <div class="user-name"><?php echo htmlspecialchars($student_name); ?></div>
            <button class="logout-btn" onclick="window.location.href='logout.php'">Logout</button>
        </div>
    </div>

    <!-- Main Container -->
    <div class="container">
        <!-- Sidebar -->
        <div class="sidebar">
            <a href="studentdashboard.php" class="menu-item active">
                <span>🏠</span><span>Home</span>
            </a>
            <a href="studentattendancepage.php" class="menu-item">
                <span>📊</span><span>View Attendance</span>
            </a>
            <a href="studentmarkspage.php" class="menu-item">
                <span>📝</span><span>View Marks</span>
            </a>
            <a href="#" class="menu-item">
                <span>💰</span><span>View Fees</span>
            </a>
            <a href="#" class="menu-item">
                <span>📅</span><span>View Schedule</span>
            </a>
            <a href="#" class="menu-item">
                <span>📢</span><span>View Notices</span>
            </a>
            <a href="resetpassword.php" class="menu-item">
                <span>🔒</span><span>Reset Password</span>
            </a>
        </div>

        <!-- Main Content -->
        <div class="main-content">

            <!-- Welcome Section -->
            <div class="welcome-section">
                <h2>Welcome, <?php echo htmlspecialchars($first_name); ?>! 👋</h2>
                <p>
                    Grade: <?php echo $grade; ?> |
                    Division: <?php echo $division ? $division : 'Not Assigned Yet'; ?> |
                    Roll No: <?php echo $roll_no ? $roll_no : 'Not Assigned Yet'; ?>
                </p>
            </div>

            <!-- Language Selection Button (only for Grade 7 if triggered) -->
            <?php if ($show_language_btn): ?>
                <a href="studentlanguageselection.php" class="lang-btn">
                    🌐 Select Your Language Subject for Grade 8 →
                </a>
            <?php endif; ?>

            <!-- Info if division not assigned -->
            <?php if (!$division): ?>
                <div class="info-card">
                    📌 Your division and roll number will be assigned by your faculty shortly.
                </div>
            <?php endif; ?>

            <!-- Stats Cards -->
            <div class="stats-grid">
                <div class="stat-card attendance">
                    <h3>Overall Attendance</h3>
                    <div class="stat-value"><?php echo $att_percent; ?>%</div>
                    <div class="stat-detail"><?php echo $present; ?> / <?php echo $total_classes; ?> classes</div>
                </div>

                <div class="stat-card fees">
                    <h3>Fees Status</h3>
                    <?php if ($fees): ?>
                        <div class="stat-value"><?php echo $fees['pending_amount'] > 0 ? 'Pending' : 'Paid ✓'; ?></div>
                        <div class="stat-detail">
                            Paid: ₹<?php echo number_format($fees['paid_amount']); ?> /
                            Total: ₹<?php echo number_format($fees['total_fees']); ?>
                        </div>
                    <?php else: ?>
                        <div class="stat-value" style="font-size:28px;">No Record</div>
                        <div class="stat-detail">No fees record found</div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Today's Schedule -->
            <div class="schedule-section">
                <h3>📅 Today's Schedule (<?php echo $today; ?>)</h3>
                <?php if (!$division): ?>
                    <p style="color:#7f8c8d;">Schedule will be available once your division is assigned.</p>
                <?php else: ?>
                    <table class="schedule-table">
                        <thead>
                            <tr>
                                <th>Time</th>
                                <th>Subject</th>
                                <th>Teacher</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (mysqli_num_rows($schedule_q) > 0): ?>
                                <?php while ($row = mysqli_fetch_assoc($schedule_q)): ?>
                                    <tr>
                                        <td><?php echo $row['time_from'] . ' - ' . $row['time_to']; ?></td>
                                        <td><?php echo htmlspecialchars($row['subject']); ?></td>
                                        <td><?php echo htmlspecialchars($row['teacher_email']); ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" style="text-align:center; color:#7f8c8d; padding:20px;">No classes scheduled for today.</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- Recent Notices -->
            <div class="notices-section">
                <h3>📢 Recent Notices</h3>
                <?php if (mysqli_num_rows($notices_q) > 0): ?>
                    <?php while ($notice = mysqli_fetch_assoc($notices_q)): ?>
                        <div class="notice-box">
                            📌 <strong><?php echo htmlspecialchars($notice['title']); ?></strong><br>
                            <?php echo htmlspecialchars($notice['message']); ?>
                            <br><small style="opacity:0.7;"><?php echo $notice['date']; ?></small>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <div class="notice-box">📌 No notices at the moment.</div>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
        window.addEventListener('load', () => {
            const statCards = document.querySelectorAll('.stat-card');
            statCards.forEach((card, index) => {
                setTimeout(() => {
                    card.style.opacity = '0';
                    card.style.transform = 'translateY(20px)';
                    card.style.transition = 'all 0.5s ease';
                    setTimeout(() => {
                        card.style.opacity = '1';
                        card.style.transform = 'translateY(0)';
                    }, 50);
                }, index * 100);
            });
        });
    </script>
</body>
</html>