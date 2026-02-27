<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();
require_once 'db_connect.php';
require_once 'session_helper.php';

requireStudent();

$user          = getLoggedInUser();
$student_email = $user['email'];
$student_name  = $user['name'];
$grade         = $user['grade'];
$institute_id  = $user['institute_id'];

// Get all subjects for this student's grade
$subjects_q = mysqli_query($conn, "SELECT DISTINCT subject_name FROM subjects WHERE grade='$grade' AND institute_id='$institute_id' ORDER BY subject_name ASC");
$subjects   = [];
while ($row = mysqli_fetch_assoc($subjects_q)) {
    $subjects[] = $row['subject_name'];
}

$selected_subject = isset($_GET['subject']) ? mysqli_real_escape_string($conn, $_GET['subject']) : ($subjects[0] ?? '');

$marks_data      = [];
$total_marks_sum = 0;
$obtained_sum    = 0;

if ($selected_subject) {
    $marks_q = mysqli_query($conn, "SELECT exam_type, marks_obtained, total_marks FROM marks WHERE student_email='$student_email' AND subject='$selected_subject' AND institute_id='$institute_id' ORDER BY created_at ASC");
    while ($row = mysqli_fetch_assoc($marks_q)) {
        $percent         = $row['total_marks'] > 0 ? round(($row['marks_obtained'] / $row['total_marks']) * 100) : 0;
        $row['percent']  = $percent;
        $marks_data[]    = $row;
        $total_marks_sum += $row['total_marks'];
        $obtained_sum    += $row['marks_obtained'];
    }
}

$overall_percent = $total_marks_sum > 0 ? round(($obtained_sum / $total_marks_sum) * 100) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Marks Page</title>
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
            font-size: 16px;
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
        
        .marks-summary {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .summary-card {
            flex: 1;
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        
        .summary-card.total {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .summary-card.obtained {
            background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%);
            color: white;
        }
        
        .summary-card.percentage {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            color: white;
        }
        
        .summary-card h3 {
            font-size: 16px;
            margin-bottom: 10px;
            opacity: 0.9;
        }
        
        .summary-card .value {
            font-size: 36px;
            font-weight: bold;
        }
        
        .marks-table-container {
            margin-top: 30px;
        }
        
        .section-title {
            font-size: 22px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .marks-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        
        .marks-table thead {
            background: #3498db;
            color: white;
        }
        
        .marks-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
        }
        
        .marks-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .marks-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .exam-type {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .marks-obtained {
            color: #27ae60;
            font-weight: bold;
        }
        
        .marks-total {
            color: #7f8c8d;
        }
        
        .percentage-cell {
            font-weight: bold;
        }
        
        .percentage-cell.excellent {
            color: #27ae60;
        }
        
        .percentage-cell.good {
            color: #f39c12;
        }
        
        .percentage-cell.average {
            color: #e67e22;
        }
        
        .percentage-cell.poor {
            color: #e74c3c;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: bold;
        }
        
        .status-badge.pass {
            background: #d4edda;
            color: #155724;
        }
        
        .status-badge.fail {
            background: #f8d7da;
            color: #721c24;
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
            margin-top: 20px;
            text-decoration: none;
            display: inline-block;
        }
        
        .btn-back:hover {
            background: #2980b9;
        }
        
        .overall-performance {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .overall-performance h3 {
            margin-bottom: 10px;
        }
        
        .performance-stats {
            display: flex;
            justify-content: space-around;
            margin-top: 15px;
        }
        
        .performance-stat {
            text-align: center;
        }
        
        .performance-stat .label {
            font-size: 14px;
            opacity: 0.9;
        }
        
        .performance-stat .value {
            font-size: 28px;
            font-weight: bold;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="frame">
        <!-- Header -->
        <div class="header">
            <div class="header-title">Marks / Results</div>
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
                    <a href="studentmarkspage.php?subject=<?php echo urlencode($subject); ?>"
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

                <!-- Overall Performance -->
                <div class="overall-performance">
                    <h3>Overall Performance</h3>
                    <div class="performance-stats">
                        <div class="performance-stat">
                            <div class="label">Total Marks</div>
                            <div class="value"><?php echo $total_marks_sum; ?></div>
                        </div>
                        <div class="performance-stat">
                            <div class="label">Obtained</div>
                            <div class="value"><?php echo $obtained_sum; ?></div>
                        </div>
                        <div class="performance-stat">
                            <div class="label">Percentage</div>
                            <div class="value"><?php echo $overall_percent; ?>%</div>
                        </div>
                    </div>
                </div>

                <!-- Marks Table -->
                <div class="marks-table-container">
                    <div class="section-title">📊 Exam-wise Breakdown</div>
                    <?php if (empty($marks_data)): ?>
                        <p style="color:#7f8c8d;">No marks recorded yet for this subject.</p>
                    <?php else: ?>
                        <table class="marks-table">
                            <thead>
                                <tr>
                                    <th>Exam Type</th>
                                    <th>Marks Obtained</th>
                                    <th>Total Marks</th>
                                    <th>Percentage</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($marks_data as $mark): ?>
                                    <?php
                                        $p = $mark['percent'];
                                        $p_class = $p >= 85 ? 'excellent' : ($p >= 70 ? 'good' : ($p >= 50 ? 'average' : 'poor'));
                                        $pass = $p >= 35;
                                    ?>
                                    <tr>
                                        <td class="exam-type"><?php echo htmlspecialchars($mark['exam_type']); ?></td>
                                        <td class="marks-obtained"><?php echo $mark['marks_obtained']; ?></td>
                                        <td class="marks-total"><?php echo $mark['total_marks']; ?></td>
                                        <td class="percentage-cell <?php echo $p_class; ?>"><?php echo $p; ?>%</td>
                                        <td>
                                            <span class="status-badge <?php echo $pass ? 'pass' : 'fail'; ?>">
                                                <?php echo $pass ? 'Pass' : 'Fail'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>

            <?php else: ?>
                <p style="color:#7f8c8d; font-size:18px; margin-top:20px;">No subjects found for your grade yet.</p>
            <?php endif; ?>

            <a href="studentdashboard.php" class="btn-back">← Back to Dashboard</a>

        </div>
    </div>
</body>
</html>