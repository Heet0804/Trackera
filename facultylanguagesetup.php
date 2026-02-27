<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();
require_once 'db_connect.php';
require_once 'session_helper.php';

requireFaculty();

$user         = getLoggedInUser();
$institute_id = $user['institute_id'];
$error        = '';
$success      = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['override'])) {
    $student_email = mysqli_real_escape_string($conn, $_POST['student_email']);
    $language      = mysqli_real_escape_string($conn, $_POST['language']);

    if (!$student_email || !$language) {
        $error = "Please select student and language!";
    } else {
        if ($language == 'Sanskrit')   $division = 'A';
        elseif ($language == 'French') $division = 'E';
        else                           $division = null;

        $div_val = $division ? "'$division'" : "NULL";

        mysqli_query($conn, "UPDATE users SET language='$language', division=$div_val WHERE email='$student_email' AND institute_id='$institute_id'");

        $check = mysqli_query($conn, "SELECT id FROM language_selection WHERE student_email='$student_email' AND institute_id='$institute_id'");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "UPDATE language_selection SET language='$language', created_at=NOW() WHERE student_email='$student_email' AND institute_id='$institute_id'");
        } else {
            $grade_q = mysqli_query($conn, "SELECT grade FROM users WHERE email='$student_email' AND institute_id='$institute_id'");
            $grade   = mysqli_fetch_assoc($grade_q)['grade'] ?? 7;
            mysqli_query($conn, "INSERT INTO language_selection (student_email, language, grade, institute_id, created_at) VALUES ('$student_email', '$language', '$grade', '$institute_id', NOW())");
        }
        $success = "Language override applied!";
    }
}

$students_q = mysqli_query($conn, "
    SELECT u.name, u.email, u.grade, u.division, u.language,
           ls.language AS ls_language
    FROM users u
    LEFT JOIN language_selection ls ON u.email = ls.student_email AND ls.institute_id='$institute_id'
    WHERE u.role='student' AND u.grade IN (7,8,9,10) AND u.institute_id='$institute_id'
    ORDER BY u.grade ASC, u.name ASC
");
$students = [];
while ($row = mysqli_fetch_assoc($students_q)) {
    $students[] = $row;
}

$total    = count($students);
$selected = count(array_filter($students, fn($s) => !empty($s['language'])));
$pending  = $total - $selected;

$g7_q        = mysqli_query($conn, "SELECT name, email FROM users WHERE role='student' AND grade=7 AND institute_id='$institute_id' ORDER BY name ASC");
$g7_students = [];
while ($row = mysqli_fetch_assoc($g7_q)) {
    $g7_students[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Language Setup</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f0f0f0; }

        .header {
            background: #3498db;
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
        }

        .header-title { color: white; font-size: 28px; font-weight: bold; }

        .btn-back {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 10px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
        }

        .content { padding: 30px; max-width: 1100px; margin: 0 auto; }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #1565c0;
        }

        .mapping-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .mapping-section h3 {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }

        .mapping-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
        }

        .language-card {
            border-radius: 12px;
            padding: 25px;
            text-align: center;
            border: 3px solid;
        }

        .language-card.sanskrit { border-color: #f39c12; background: #fef9e7; }
        .language-card.french   { border-color: #3498db; background: #ebf5fb; }
        .language-card.hindi    { border-color: #27ae60; background: #eafaf1; }

        .language-card .lang-icon { font-size: 40px; margin-bottom: 10px; }

        .language-card h4 { font-size: 20px; margin-bottom: 10px; }
        .language-card.sanskrit h4 { color: #f39c12; }
        .language-card.french h4   { color: #3498db; }
        .language-card.hindi h4    { color: #27ae60; }

        .division-badge {
            display: inline-block;
            padding: 8px 20px;
            border-radius: 20px;
            font-size: 18px;
            font-weight: bold;
            margin: 8px 5px;
        }

        .division-badge.sanskrit { background: #f39c12; color: white; }
        .division-badge.french   { background: #3498db; color: white; }
        .division-badge.hindi    { background: #27ae60; color: white; }

        .language-card p { font-size: 13px; color: #7f8c8d; margin-top: 10px; }

        .trigger-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .trigger-section h3 {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }

        .trigger-form {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            align-items: flex-end;
        }

        .form-group { }

        label {
            display: block;
            font-weight: 600;
            color: #34495e;
            margin-bottom: 8px;
            font-size: 14px;
        }

        select, input {
            width: 100%;
            padding: 11px 15px;
            border: 2px solid #bdc3c7;
            border-radius: 6px;
            font-size: 15px;
        }

        select:focus, input:focus { outline: none; border-color: #3498db; }

        .btn-trigger {
            background: #8e44ad;
            color: white;
            padding: 12px 20px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
            width: 100%;
        }

        .btn-trigger:hover { background: #7d3c98; }

        .pending-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .pending-section h3 {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        table { width: 100%; border-collapse: collapse; }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
            font-size: 14px;
        }

        th { background: #34495e; color: white; }
        tr:hover { background: #f8f9fa; }

        .badge-pending  { background: #fff3cd; color: #856404;  padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }
        .badge-selected { background: #d4edda; color: #155724;  padding: 4px 12px; border-radius: 12px; font-size: 12px; font-weight: 600; }

        .stats-row {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }

        .stat-box {
            flex: 1;
            background: #ecf0f1;
            border-radius: 8px;
            padding: 15px;
            text-align: center;
        }

        .stat-box .num { font-size: 28px; font-weight: bold; color: #3498db; }
        .stat-box .lbl { font-size: 13px; color: #7f8c8d; margin-top: 4px; }

        .msg-success {
            display: block; padding: 12px 15px; border-radius: 6px;
            margin-bottom: 20px; font-size: 14px; font-weight: 500;
            background: #d4edda; color: #155724; border: 1px solid #c3e6cb;
        }
        .msg-error {
            display: block; padding: 12px 15px; border-radius: 6px;
            margin-bottom: 20px; font-size: 14px; font-weight: 500;
            background: #f8d7da; color: #721c24; border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-title">🌐 Language Setup</div>
        <a href="facultydashboard.php" class="btn-back">← Back To Dashboard</a>
    </div>

    <div class="content">

        <?php if ($error): ?>
            <span class="msg-error"><?php echo htmlspecialchars($error); ?></span>
        <?php endif; ?>
        <?php if ($success): ?>
            <span class="msg-success"><?php echo $success; ?></span>
        <?php endif; ?>

        <div class="info-box">
            📌 This page is for managing language selection for Grade 7, 8, 9, and 10 students. Students in Grade 7 select their language for Grade 8. Based on their selection, divisions are automatically assigned: <strong>Sanskrit → Division A</strong>, <strong>French → Division E</strong>, <strong>Hindi → Division B, C, or D</strong>.
        </div>

        <!-- Division-Language Mapping -->
        <div class="mapping-section">
            <h3>📋 Division - Language Mapping (Grade 8, 9, 10)</h3>
            <div class="mapping-grid">
                <div class="language-card sanskrit">
                    <div class="lang-icon">🕉️</div>
                    <h4>Sanskrit</h4>
                    <div><span class="division-badge sanskrit">Division A</span></div>
                    <p>Students who choose Sanskrit are assigned to Division A across Grades 8, 9, and 10.</p>
                </div>
                <div class="language-card hindi">
                    <div class="lang-icon">🇮🇳</div>
                    <h4>Hindi</h4>
                    <div>
                        <span class="division-badge hindi">Division B</span>
                        <span class="division-badge hindi">Division C</span>
                        <span class="division-badge hindi">Division D</span>
                    </div>
                    <p>Students who choose Hindi are assigned to Division B, C, or D. Faculty can reassign within these divisions each year.</p>
                </div>
                <div class="language-card french">
                    <div class="lang-icon">🇫🇷</div>
                    <h4>French</h4>
                    <div><span class="division-badge french">Division E</span></div>
                    <p>Students who choose French are assigned to Division E across Grades 8, 9, and 10.</p>
                </div>
            </div>
        </div>

        <!-- Faculty Override Panel -->
        <div class="trigger-section">
            <h3>🔧 Faculty Override - Assign Language Manually</h3>
            <form method="POST" action="">
                <div class="trigger-form">
                    <div class="form-group">
                        <label>Grade 7 Student:</label>
                        <select name="student_email">
                            <option value="">-- Select Student --</option>
                            <?php foreach ($g7_students as $s): ?>
                                <option value="<?php echo htmlspecialchars($s['email']); ?>">
                                    <?php echo htmlspecialchars($s['name']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Assign Language:</label>
                        <select name="language">
                            <option value="">-- Select Language --</option>
                            <option value="Sanskrit">Sanskrit → Division A</option>
                            <option value="French">French → Division E</option>
                            <option value="Hindi">Hindi → Division B/C/D</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>&nbsp;</label>
                        <button type="submit" name="override" class="btn-trigger">🔧 Apply Override</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Status Table -->
        <div class="pending-section">
            <h3>📊 Language Selection Status (Grades 7-10)</h3>

            <div class="stats-row">
                <div class="stat-box">
                    <div class="num"><?php echo $total; ?></div>
                    <div class="lbl">Total Students</div>
                </div>
                <div class="stat-box">
                    <div class="num" style="color:#27ae60;"><?php echo $selected; ?></div>
                    <div class="lbl">Language Selected</div>
                </div>
                <div class="stat-box">
                    <div class="num" style="color:#e74c3c;"><?php echo $pending; ?></div>
                    <div class="lbl">Pending Selection</div>
                </div>
            </div>

            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Grade</th>
                        <th>Language Selected</th>
                        <th>Division</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($students)): ?>
                        <tr>
                            <td colspan="6" style="text-align:center; color:#7f8c8d; padding:30px;">No students found in Grades 7-10.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($students as $s): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($s['name']); ?></td>
                                <td><?php echo htmlspecialchars($s['email']); ?></td>
                                <td>Grade <?php echo $s['grade']; ?></td>
                                <td><?php echo $s['language'] ? htmlspecialchars($s['language']) : '--'; ?></td>
                                <td><?php echo $s['division'] ? 'Division ' . $s['division'] : '--'; ?></td>
                                <td>
                                    <?php if ($s['language']): ?>
                                        <span class="badge-selected">✅ Selected</span>
                                    <?php else: ?>
                                        <span class="badge-pending">⏳ Pending</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

    </div>
</body>
</html>