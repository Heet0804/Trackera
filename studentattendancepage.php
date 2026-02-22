<?php
session_start();
require_once 'db_connect.php';
require_once 'session_helper.php';

// Only students allowed
requireStudent();

$user          = getLoggedInUser();
$student_email = $user['email'];
$student_name  = $user['name'];
$grade         = $user['grade'];
$division      = $user['division'];

// Get all subjects for this student's grade
$subjects_q = mysqli_query($conn, "SELECT DISTINCT subject_name FROM subjects WHERE grade='$grade' ORDER BY subject_name ASC");
$subjects = [];
while ($row = mysqli_fetch_assoc($subjects_q)) {
    $subjects[] = $row['subject_name'];
}

// Get selected subject from URL (default to first subject)
$selected_subject = isset($_GET['subject']) ? mysqli_real_escape_string($conn, $_GET['subject']) : ($subjects[0] ?? '');

// Get attendance data for selected subject
$att_data = [];
$total = 0;
$present = 0;
$absent = 0;

if ($selected_subject) {
    $att_q = mysqli_query($conn, "SELECT date, status FROM attendance WHERE student_email='$student_email' AND subject='$selected_subject' ORDER BY date DESC");
    while ($row = mysqli_fetch_assoc($att_q)) {
        $att_data[] = $row;
        $total++;
        if ($row['status'] == 'Present') $present++;
        else $absent++;
    }
}

$percent = $total > 0 ? round(($present / $total) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Attendance Dashboard</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
    font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
    background: #f0f0f0;
    min-height: 100vh;
    margin: 0;
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
    top: 0;
    left: 0;
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
        
        .student-name {
            color: white;
            font-size: 16px;
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
        
        .btn-logout:hover {
            background: #c0392b;
        }
        
        /* Sidebar */
        .sidebar {
    position: absolute;
    top: 70px;
    left: 0;
    width: 250px;
    height: calc(100vh - 70px);
    background: #ecf0f1;
    padding: 20px 10px;
    overflow-y: auto;
}

        
        .sidebar-btn {
            width: 230px;
            height: 50px;
            margin-bottom: 10px;
            border: none;
            border-radius: 8px;
            font-size: 15px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.3s;
            text-align: left;
            padding-left: 20px;
            display: block;
            text-decoration: none;
            line-height: 50px;
        }
        
        .sidebar-btn.active {
            background: #3498db;
            color: white;
        }
        
        .sidebar-btn:not(.active) {
            background: #95a5a6;
            color: white;
        }
        
        .sidebar-btn:not(.active):hover {
            background: #7f8c8d;
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

        
        .subject-name {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .info-row {
            font-size: 18px;
            color: #34495e;
            margin-bottom: 15px;
            padding: 10px;
            background: #f8f9fa;
            border-radius: 5px;
        }
        
        .info-label {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .percentage {
            font-size: 48px;
            font-weight: bold;
            color: #27ae60;
            margin: 30px 0;
        }
        
        .status {
            display: inline-block;
            padding: 10px 20px;
            border-radius: 25px;
            font-weight: bold;
            font-size: 16px;
        }
        
        .status.good {
            background: #d4edda;
            color: #155724;
        }
        
        .status.warning {
            background: #fff3cd;
            color: #856404;
        }
        
        .progress-bar-container {
            width: 100%;
            height: 30px;
            background: #ecf0f1;
            border-radius: 15px;
            overflow: hidden;
            margin: 20px 0;
        }
        
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #3498db, #2ecc71);
            transition: width 0.5s;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: bold;
        }
        
        .btn-back {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            font-weight: bold;
            margin-top: 30px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-back:hover {
            background: #2980b9;
        }
        
        .lecture-details {
            margin-top: 30px;
            border-top: 2px solid #ecf0f1;
            padding-top: 20px;
        }
        
        .lecture-details h3 {
            color: #2c3e50;
            margin-bottom: 15px;
        }

        /* Attendance History Table */
        .attendance-history {
            margin-top: 40px;
            border-top: 2px solid #ecf0f1;
            padding-top: 20px;
        }

        .attendance-history h3 {
            color: #2c3e50;
            margin-bottom: 20px;
            font-size: 20px;
        }

        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            border-radius: 8px;
            overflow: hidden;
        }

        .attendance-table thead {
            background: #3498db;
            color: white;
        }

        .attendance-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 14px;
        }

        .attendance-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ecf0f1;
            font-size: 14px;
            color: #2c3e50;
        }

        .attendance-table tbody tr:hover {
            background: #f8f9fa;
        }

        .attendance-table tbody tr:last-child td {
            border-bottom: none;
        }

        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 13px;
            font-weight: 600;
        }

        .status-badge.present {
            background: #d4edda;
            color: #155724;
        }

        .status-badge.absent {
            background: #f8d7da;
            color: #721c24;
        }
    </style>
</head>
<body>
    <div class="frame">
        <!-- Header -->
        <div class="header">
            <div class="header-title">Attendance</div>
            <div class="header-right">
                <div class="student-name"><?php echo htmlspecialchars($student_name); ?></div>
                <button class="btn-logout" onclick="window.location.href='logout.php'">Logout</button>
            </div>
        </div>

        <!-- Sidebar with subjects from DB -->
        <div class="sidebar">
            <?php if (empty($subjects)): ?>
                <div style="color:#7f8c8d; padding:10px; font-size:14px;">No subjects found for your grade.</div>
            <?php else: ?>
                <?php foreach ($subjects as $subject): ?>
                    <a href="studentattendancepage.php?subject=<?php echo urlencode($subject); ?>"
                       class="sidebar-btn <?php echo $subject === $selected_subject ? 'active' : ''; ?>">
                        <?php echo htmlspecialchars($subject); ?>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Main Content Area -->
        <div class="main-content">

            <?php if ($selected_subject): ?>

                <div class="subject-name">📚 <?php echo htmlspecialchars($selected_subject); ?></div>

                <div class="info-row">
                    <span class="info-label">Total Classes:</span> <?php echo $total; ?>
                </div>

                <div class="info-row">
                    <span class="info-label">Attended:</span> <?php echo $present; ?>
                </div>

                <div class="info-row">
                    <span class="info-label">Absent:</span> <?php echo $absent; ?>
                </div>

                <div class="percentage" style="color: <?php echo $percent >= 75 ? '#27ae60' : '#e74c3c'; ?>">
                    <?php echo $percent; ?>%
                </div>

                <div class="progress-bar-container">
                    <div class="progress-bar" style="width: <?php echo $percent; ?>%;"></div>
                </div>

                <span class="status <?php echo $percent >= 75 ? 'good' : 'warning'; ?>">
                    <?php echo $percent >= 75 ? '✅ Good Standing' : '⚠️ Below 75% - Attendance Low'; ?>
                </span>

                <div class="lecture-details">
                    <h3>📊 Attendance Breakdown</h3>
                    <div class="info-row">
                        <span class="info-label">Present:</span> <?php echo $present; ?> days
                    </div>
                    <div class="info-row">
                        <span class="info-label">Absent:</span> <?php echo $absent; ?> days
                    </div>
                    <div class="info-row">
                        <span class="info-label">Minimum Required:</span> 75% (<?php echo ceil($total * 0.75); ?> classes)
                    </div>
                </div>

                <!-- Attendance History -->
                <div class="attendance-history">
                    <h3>📅 Attendance History</h3>
                    <?php if (empty($att_data)): ?>
                        <p style="color:#7f8c8d;">No attendance records found for this subject.</p>
                    <?php else: ?>
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Day</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($att_data as $record): ?>
                                    <tr>
                                        <td><?php echo date('F j, Y', strtotime($record['date'])); ?></td>
                                        <td><?php echo date('l', strtotime($record['date'])); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $record['status'] == 'Present' ? 'present' : 'absent'; ?>">
                                                <?php echo $record['status'] == 'Present' ? '✓ Present' : '✗ Absent'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <p style="color:#7f8c8d; font-size:18px; margin-top:20px;">No subjects found for your grade yet. Please check back later.</p>
            <?php endif; ?>

            <a href="studentdashboard.php" class="btn-back">← Back to Dashboard</a>

        </div>
    </div>
</body>
</html>