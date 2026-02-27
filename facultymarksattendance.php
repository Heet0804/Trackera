<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();
require_once 'db_connect.php';
require_once 'session_helper.php';

requireFaculty();

$user          = getLoggedInUser();
$faculty_email = $user['email'];
$institute_id  = $user['institute_id'];
$error         = '';
$success       = '';

$yr_q          = mysqli_query($conn, "SELECT year FROM academic_year WHERE is_current=1 AND institute_id='$institute_id' LIMIT 1");
$yr            = mysqli_fetch_assoc($yr_q);
$academic_year = $yr ? $yr['year'] : date('Y') . '-' . (date('Y') + 1);

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {
    $grade    = mysqli_real_escape_string($conn, $_POST['grade']);
    $division = mysqli_real_escape_string($conn, $_POST['division']);
    $subject  = mysqli_real_escape_string($conn, $_POST['subject']);
    $date     = mysqli_real_escape_string($conn, $_POST['date']);

    if (!$grade || !$division || !$subject || !$date) {
        $error = "Please select Grade, Division, Subject and Date!";
    } elseif (!isset($_POST['attendance']) || empty($_POST['attendance'])) {
        $error = "No students found!";
    } else {
        $saved = 0;
        foreach ($_POST['attendance'] as $student_email => $status) {
            $student_email = mysqli_real_escape_string($conn, $student_email);
            $status_val    = $status == 'P' ? 'Present' : 'Absent';

            $check = mysqli_query($conn, "SELECT id FROM attendance WHERE student_email='$student_email' AND subject='$subject' AND date='$date' AND institute_id='$institute_id'");
            if (mysqli_num_rows($check) > 0) {
                mysqli_query($conn, "UPDATE attendance SET status='$status_val', teacher_email='$faculty_email' WHERE student_email='$student_email' AND subject='$subject' AND date='$date' AND institute_id='$institute_id'");
            } else {
                mysqli_query($conn, "INSERT INTO attendance (student_email, subject, date, status, teacher_email, academic_year, institute_id, created_at)
                    VALUES ('$student_email', '$subject', '$date', '$status_val', '$faculty_email', '$academic_year', '$institute_id', NOW())");
            }
            $saved++;
        }
        $success = "Attendance saved for $saved students!";
    }
}

$sel_grade    = isset($_POST['grade'])    ? mysqli_real_escape_string($conn, $_POST['grade'])    : '';
$sel_division = isset($_POST['division']) ? mysqli_real_escape_string($conn, $_POST['division']) : '';
$sel_subject  = isset($_POST['subject'])  ? mysqli_real_escape_string($conn, $_POST['subject'])  : '';
$sel_date     = isset($_POST['date'])     ? mysqli_real_escape_string($conn, $_POST['date'])     : date('Y-m-d');

$students = [];
if ($sel_grade && $sel_division) {
    $st_q = mysqli_query($conn, "SELECT user_id, name, email, roll_no FROM users WHERE role='student' AND grade='$sel_grade' AND division='$sel_division' AND institute_id='$institute_id' ORDER BY roll_no ASC, name ASC");
    while ($row = mysqli_fetch_assoc($st_q)) {
        $students[] = $row;
    }
}

$existing_att = [];
if ($sel_subject && $sel_date && !empty($students)) {
    foreach ($students as $s) {
        $em  = mysqli_real_escape_string($conn, $s['email']);
        $att = mysqli_query($conn, "SELECT status FROM attendance WHERE student_email='$em' AND subject='$sel_subject' AND date='$sel_date' AND institute_id='$institute_id'");
        if (mysqli_num_rows($att) > 0) {
            $existing_att[$s['email']] = mysqli_fetch_assoc($att)['status'];
        }
    }
}

$subjects = [];
if ($sel_grade) {
    $sub_q = mysqli_query($conn, "SELECT subject_name FROM subjects WHERE grade='$sel_grade' AND institute_id='$institute_id' ORDER BY subject_name ASC");
    while ($row = mysqli_fetch_assoc($sub_q)) {
        $subjects[] = $row['subject_name'];
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Mark Attendance</title>
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
        .content {
    position: absolute;
    top: 70px;
    left: 0;
    width: 100%;
    height: calc(100vh - 70px);
    background: white;
    padding: 30px;
    overflow-y: auto;
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
            font-size: 28px;
            font-weight: bold;
        }
        
        .btn-back {
            background: #e74c3c;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            text-decoration: none;
        }
        
        .btn-back:hover {
            background: #c0392b;
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
        
        /* Selection Panel */
        .selection-panel {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .selection-panel h3 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .input-row {
            display: flex;
            gap: 20px;
            margin-bottom: 15px;
            align-items: center;
        }
        
        .input-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .input-group label {
            font-size: 14px;
            font-weight: 600;
            color: #34495e;
            margin-bottom: 8px;
        }
        
        .input-group select,
        .input-group input {
            padding: 12px;
            border: 2px solid #bdc3c7;
            border-radius: 5px;
            font-size: 15px;
            background: white;
        }
        
        .input-group select:focus,
        .input-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .btn-load {
            background: #3498db;
            color: white;
            border: none;
            padding: 12px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            margin-top: 24px;
        }
        
        .btn-load:hover {
            background: #2980b9;
        }
        
        /* Students Table */
        .table-panel {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .table-panel h3 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 15px;
        }
        
        .info-banner {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 12px 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 14px;
            color: #1976d2;
        }
        
        .students-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        .students-table thead {
            background: #34495e;
            color: white;
        }
        
        .students-table th {
            padding: 15px;
            text-align: left;
            font-weight: 600;
            font-size: 15px;
        }
        
        .students-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ecf0f1;
            font-size: 14px;
        }
        
        .students-table tbody tr:hover {
            background: #f8f9fa;
        }
        
        .students-table tbody tr:nth-child(even) {
            background: #fafafa;
        }
        
        .students-table tbody tr:nth-child(even):hover {
            background: #f8f9fa;
        }
        
        .prn-cell {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .name-cell {
            color: #34495e;
        }
        
        .status-select {
            padding: 8px 12px;
            border: 2px solid #bdc3c7;
            border-radius: 5px;
            font-size: 14px;
            cursor: pointer;
            min-width: 120px;
        }
        
        .status-select.present {
            border-color: #27ae60;
            background: #d5f4e6;
            color: #27ae60;
            font-weight: bold;
        }
        
        .status-select.absent {
            border-color: #e74c3c;
            background: #fadbd8;
            color: #e74c3c;
            font-weight: bold;
        }
        
        /* Action Buttons */
        .action-panel {
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: white;
            padding: 20px 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .summary {
            font-size: 16px;
            color: #34495e;
        }
        
        .summary span {
            font-weight: bold;
            margin: 0 5px;
        }
        
        .summary .present-count {
            color: #27ae60;
        }
        
        .summary .absent-count {
            color: #e74c3c;
        }
        
        .action-buttons {
            display: flex;
            gap: 15px;
        }
        
        .btn-save {
            background: #27ae60;
            color: white;
            border: none;
            padding: 14px 40px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
        }
        
        .btn-save:hover {
            background: #229954;
        }
        
        .btn-clear {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 14px 30px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
        }
        
        .btn-clear:hover {
            background: #7f8c8d;
        }

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
    <div class="frame">
        <!-- Header -->
        <div class="header">
            <div class="header-title">📋 Mark Attendance</div>
            <a href="facultydashboard.php" class="btn-back">← Back to Dashboard</a>
        </div>

        <!-- Content -->
        <div class="content">

            <?php if ($error): ?>
                <span class="msg-error"><?php echo htmlspecialchars($error); ?></span>
            <?php endif; ?>
            <?php if ($success): ?>
                <span class="msg-success"><?php echo $success; ?></span>
            <?php endif; ?>

            <form method="POST" action="">
                <!-- Selection Panel -->
                <div class="selection-panel">
                    <h3>📌 Select Class Details</h3>
                    <div class="input-row">
                        <div class="input-group">
                            <label>Grade:</label>
                            <select name="grade" id="gradeSelect">
                                <option value="">-- Select Grade --</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $sel_grade == $i ? 'selected' : ''; ?>>Grade <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="input-group">
                            <label>Division:</label>
                            <select name="division" id="divisionSelect">
                                <option value="">-- Select Division --</option>
                                <?php foreach (['A','B','C','D','E'] as $d): ?>
                                    <option value="<?php echo $d; ?>" <?php echo $sel_division == $d ? 'selected' : ''; ?>>Division <?php echo $d; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="input-group">
                            <label>Subject:</label>
                            <select name="subject" id="subjectSelect">
                                <option value="">-- Select Subject --</option>
                                <?php foreach ($subjects as $sub): ?>
                                    <option value="<?php echo htmlspecialchars($sub); ?>" <?php echo $sel_subject == $sub ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sub); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="input-group">
                            <label>Date:</label>
                            <input type="date" name="date" id="dateInput" value="<?php echo $sel_date; ?>">
                        </div>

                        <button type="submit" name="load" class="btn-load">Load Students</button>
                    </div>
                </div>

                <!-- Students Table -->
                <div class="table-panel">
                    <h3>👥 Student Attendance List</h3>
                    <div class="info-banner">
                        <strong>Note:</strong> Select Present (P) or Absent (A) for each student. Changes are color-coded for easy identification.
                    </div>

                    <table class="students-table">
                        <thead>
                            <tr>
                                <th style="width: 100px;">Roll No</th>
                                <th style="width: 300px;">Student Name</th>
                                <th style="width: 150px;">Grade</th>
                                <th style="width: 150px;">Division</th>
                                <th style="width: 200px;">Status</th>
                            </tr>
                        </thead>
                        <tbody id="studentTableBody">
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="5" style="text-align:center; color:#7f8c8d; padding:30px;">
                                        Select Grade, Division, Subject and Date then click Load Students.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $s): ?>
                                    <?php
                                        $att_status = $existing_att[$s['email']] ?? 'Present';
                                        $is_present = $att_status == 'Present';
                                    ?>
                                    <tr>
                                        <td class="prn-cell"><?php echo $s['roll_no'] ? $s['roll_no'] : '-'; ?></td>
                                        <td class="name-cell"><?php echo htmlspecialchars($s['name']); ?></td>
                                        <td><?php echo $sel_grade; ?></td>
                                        <td><?php echo $sel_division; ?></td>
                                        <td>
                                            <select class="status-select <?php echo $is_present ? 'present' : 'absent'; ?>"
                                                    name="attendance[<?php echo htmlspecialchars($s['email']); ?>]"
                                                    onchange="updateStatus(this)">
                                                <option value="P" <?php echo $is_present ? 'selected' : ''; ?>>Present</option>
                                                <option value="A" <?php echo !$is_present ? 'selected' : ''; ?>>Absent</option>
                                            </select>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Action Panel -->
                <div class="action-panel">
                    <div class="summary">
                        Total Students: <span><?php echo count($students); ?></span> |
                        Present: <span class="present-count" id="presentCount">0</span> |
                        Absent: <span class="absent-count" id="absentCount">0</span>
                    </div>
                    <div class="action-buttons">
                        <button type="button" class="btn-clear" onclick="clearAll()">Clear All</button>
                        <button type="submit" name="save" class="btn-save">✓ Save Attendance</button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <script>
        function updateStatus(select) {
            if (select.value === 'P') {
                select.className = 'status-select present';
            } else {
                select.className = 'status-select absent';
            }
            updateSummary();
        }

        function updateSummary() {
            const selects = document.querySelectorAll('.status-select');
            let presentCount = 0;
            let absentCount  = 0;
            selects.forEach(select => {
                if (select.value === 'P') presentCount++;
                else absentCount++;
            });
            document.getElementById('presentCount').textContent = presentCount;
            document.getElementById('absentCount').textContent  = absentCount;
        }

        function clearAll() {
            const selects = document.querySelectorAll('.status-select');
            selects.forEach(select => {
                select.value = 'P';
                select.className = 'status-select present';
            });
            updateSummary();
        }

        updateSummary();
    </script>
</body>
</html>