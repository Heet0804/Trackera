<?php
session_start();
require_once 'db_connect.php';
require_once 'session_helper.php';

requireFaculty();

$user          = getLoggedInUser();
$faculty_email = $user['email'];

$error   = '';
$success = '';

// Get academic year
$yr_q = mysqli_query($conn, "SELECT year FROM academic_year WHERE is_current=1 LIMIT 1");
$yr   = mysqli_fetch_assoc($yr_q);
$academic_year = $yr ? $yr['year'] : date('Y') . '-' . (date('Y') + 1);

// Handle Save Marks
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {
    $grade     = mysqli_real_escape_string($conn, $_POST['grade']);
    $division  = mysqli_real_escape_string($conn, $_POST['division']);
    $subject   = mysqli_real_escape_string($conn, $_POST['subject']);
    $exam_type = mysqli_real_escape_string($conn, $_POST['exam_type']);

    if (!$grade || !$division || !$subject || !$exam_type) {
        $error = "Please select all fields!";
    } elseif (!isset($_POST['marks_obtained']) || empty($_POST['marks_obtained'])) {
        $error = "No student marks to save!";
    } else {
        $saved = 0;
        foreach ($_POST['marks_obtained'] as $student_email => $obtained) {
            $obtained = trim($obtained);
            if ($obtained === '') continue;

            $student_email = mysqli_real_escape_string($conn, $student_email);
            $obtained      = (float) $obtained;
            $total         = isset($_POST['total_marks'][$student_email]) ? (float) $_POST['total_marks'][$student_email] : 100;

            if ($obtained > $total) continue; // skip invalid

            // Check if marks already exist
            $check = mysqli_query($conn, "SELECT id FROM marks WHERE student_email='$student_email' AND subject='$subject' AND exam_type='$exam_type' AND academic_year='$academic_year'");
            if (mysqli_num_rows($check) > 0) {
                mysqli_query($conn, "UPDATE marks SET marks_obtained='$obtained', total_marks='$total', teacher_email='$faculty_email', created_at=NOW() WHERE student_email='$student_email' AND subject='$subject' AND exam_type='$exam_type' AND academic_year='$academic_year'");
            } else {
                mysqli_query($conn, "INSERT INTO marks (student_email, subject, exam_type, marks_obtained, total_marks, teacher_email, academic_year, created_at)
                    VALUES ('$student_email', '$subject', '$exam_type', '$obtained', '$total', '$faculty_email', '$academic_year', NOW())");
            }
            $saved++;
        }
        $success = "Marks saved successfully for $saved students!";
    }
}

// Filters
$sel_grade    = isset($_POST['grade'])      ? mysqli_real_escape_string($conn, $_POST['grade'])      : '';
$sel_division = isset($_POST['division'])   ? mysqli_real_escape_string($conn, $_POST['division'])   : '';
$sel_subject  = isset($_POST['subject'])    ? mysqli_real_escape_string($conn, $_POST['subject'])    : '';
$sel_exam     = isset($_POST['exam_type'])  ? mysqli_real_escape_string($conn, $_POST['exam_type'])  : '';

// Load students
$students = [];
if ($sel_grade && $sel_division) {
    $st_q = mysqli_query($conn, "SELECT user_id, name, email, roll_no FROM users WHERE role='student' AND grade='$sel_grade' AND division='$sel_division' ORDER BY roll_no ASC, name ASC");
    while ($row = mysqli_fetch_assoc($st_q)) {
        $students[] = $row;
    }
}

// Load existing marks for selected subject+exam
$existing_marks = [];
if ($sel_subject && $sel_exam && !empty($students)) {
    foreach ($students as $s) {
        $em = mysqli_real_escape_string($conn, $s['email']);
        $mq = mysqli_query($conn, "SELECT marks_obtained, total_marks FROM marks WHERE student_email='$em' AND subject='$sel_subject' AND exam_type='$sel_exam' AND academic_year='$academic_year'");
        if (mysqli_num_rows($mq) > 0) {
            $existing_marks[$s['email']] = mysqli_fetch_assoc($mq);
        }
    }
}

// Get subjects for selected grade
$subjects = [];
if ($sel_grade) {
    $sub_q = mysqli_query($conn, "SELECT subject_name FROM subjects WHERE grade='$sel_grade' ORDER BY subject_name ASC");
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
    <title>Faculty Enter Marks</title>
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
            align-items: flex-end;
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
        
        .input-group select {
            padding: 12px;
            border: 2px solid #bdc3c7;
            border-radius: 5px;
            font-size: 15px;
            background: white;
        }
        
        .input-group select:focus {
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
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            padding: 12px 15px;
            margin-bottom: 15px;
            border-radius: 4px;
            font-size: 14px;
            color: #856404;
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
        
        .name-cell {
            font-weight: 600;
            color: #2c3e50;
        }
        
        .marks-input {
            padding: 8px 10px;
            border: 2px solid #bdc3c7;
            border-radius: 5px;
            font-size: 14px;
            width: 80px;
            text-align: center;
        }
        
        .marks-input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .marks-input.valid {
            border-color: #27ae60;
            background: #d5f4e6;
        }
        
        .marks-input.invalid {
            border-color: #e74c3c;
            background: #fadbd8;
        }
        
        .total-marks {
            font-weight: bold;
            color: #7f8c8d;
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
        
        .summary .filled-count {
            color: #27ae60;
        }
        
        .summary .pending-count {
            color: #e67e22;
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
            <div class="header-title">📝 Enter Marks</div>
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
                    <h3>📌 Select Class & Exam Details</h3>
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
                            <label>Exam Type:</label>
                            <select name="exam_type" id="examTypeSelect">
                                <option value="">-- Select Exam Type --</option>
                                <?php foreach (['Unit Test 1','Unit Test 2','Mid-Term Exam','Final Exam','Assignment','Oral'] as $et): ?>
                                    <option value="<?php echo $et; ?>" <?php echo $sel_exam == $et ? 'selected' : ''; ?>><?php echo $et; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" name="load" class="btn-load">Load Students</button>
                    </div>
                </div>

                <!-- Students Table -->
                <div class="table-panel">
                    <h3>👥 Enter Student Marks</h3>
                    <div class="info-banner">
                        <strong>Note:</strong> Enter marks obtained and total marks for each student. Marks will be validated automatically.
                    </div>

                    <table class="students-table">
                        <thead>
                            <tr>
                                <th style="width: 350px;">Student Name</th>
                                <th style="width: 120px;">Grade</th>
                                <th style="width: 120px;">Division</th>
                                <th style="width: 150px;">Roll Number</th>
                                <th style="width: 150px;">Marks Obtained</th>
                                <th style="width: 150px;">Total Marks</th>
                                <th style="width: 120px;">Percentage</th>
                            </tr>
                        </thead>
                        <tbody id="studentTableBody">
                            <?php if (empty($students)): ?>
                                <tr>
                                    <td colspan="7" style="text-align:center; color:#7f8c8d; padding:30px;">
                                        Select Grade, Division, Subject and Exam Type then click Load Students.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($students as $s): ?>
                                    <?php
                                        $ex = $existing_marks[$s['email']] ?? null;
                                        $ob = $ex ? $ex['marks_obtained'] : '';
                                        $tm = $ex ? $ex['total_marks']    : '';
                                        $pct = ($ob !== '' && $tm && $tm > 0) ? round(($ob / $tm) * 100, 2) : null;
                                        $has_marks = $ob !== '';
                                    ?>
                                    <tr>
                                        <td class="name-cell"><?php echo htmlspecialchars($s['name']); ?></td>
                                        <td><?php echo $sel_grade; ?></td>
                                        <td><?php echo $sel_division; ?></td>
                                        <td><?php echo $s['roll_no'] ? $s['roll_no'] : '-'; ?></td>
                                        <td>
                                            <input type="number"
                                                class="marks-input <?php echo $has_marks ? 'valid' : ''; ?>"
                                                name="marks_obtained[<?php echo htmlspecialchars($s['email']); ?>]"
                                                value="<?php echo $ob; ?>"
                                                min="0" placeholder="0"
                                                onchange="calculatePercentage(this)">
                                        </td>
                                        <td>
                                            <input type="number"
                                                class="marks-input <?php echo $has_marks ? 'valid' : ''; ?>"
                                                name="total_marks[<?php echo htmlspecialchars($s['email']); ?>]"
                                                value="<?php echo $tm; ?>"
                                                min="0" placeholder="100"
                                                onchange="calculatePercentage(this)">
                                        </td>
                                        <td class="percentage-cell" style="color: <?php
                                            if ($pct === null) echo '#7f8c8d';
                                            elseif ($pct >= 85) echo '#27ae60';
                                            elseif ($pct >= 70) echo '#f39c12';
                                            elseif ($pct >= 50) echo '#e67e22';
                                            else echo '#e74c3c';
                                        ?>">
                                            <?php echo $pct !== null ? $pct . '%' : '-'; ?>
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
                        Marks Filled: <span class="filled-count" id="filledCount">0</span> |
                        Pending: <span class="pending-count" id="pendingCount">0</span>
                    </div>
                    <div class="action-buttons">
                        <button type="button" class="btn-clear" onclick="clearAll()">Clear All</button>
                        <button type="submit" name="save" class="btn-save">✓ Save Marks</button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <script>
        function calculatePercentage(input) {
            const row = input.closest('tr');
            const marksInputs = row.querySelectorAll('.marks-input');
            const obtainedInput = marksInputs[0];
            const totalInput = marksInputs[1];
            const percentageCell = row.querySelector('.percentage-cell');

            const obtained = parseFloat(obtainedInput.value);
            const total = parseFloat(totalInput.value);

            if (obtained > total) {
                obtainedInput.classList.add('invalid');
                obtainedInput.classList.remove('valid');
                percentageCell.textContent = 'Invalid';
                percentageCell.style.color = '#e74c3c';
                return;
            }

            if (obtained >= 0 && total && total > 0) {
                obtainedInput.classList.add('valid');
                obtainedInput.classList.remove('invalid');
                totalInput.classList.add('valid');

                const percentage = ((obtained / total) * 100).toFixed(2);
                percentageCell.textContent = percentage + '%';

                if (percentage >= 85)      percentageCell.style.color = '#27ae60';
                else if (percentage >= 70) percentageCell.style.color = '#f39c12';
                else if (percentage >= 50) percentageCell.style.color = '#e67e22';
                else                       percentageCell.style.color = '#e74c3c';
            } else {
                percentageCell.textContent = '-';
                percentageCell.style.color = '#7f8c8d';
            }

            updateSummary();
        }

        function updateSummary() {
            const rows = document.querySelectorAll('#studentTableBody tr');
            let filled = 0, pending = 0;
            rows.forEach(row => {
                const inputs = row.querySelectorAll('.marks-input');
                if (inputs.length >= 2 && inputs[0].value !== '' && inputs[1].value !== '') filled++;
                else if (inputs.length >= 2) pending++;
            });
            document.getElementById('filledCount').textContent = filled;
            document.getElementById('pendingCount').textContent = pending;
        }

        function clearAll() {
            document.querySelectorAll('.marks-input').forEach(input => {
                input.value = '';
                input.classList.remove('valid', 'invalid');
            });
            document.querySelectorAll('.percentage-cell').forEach(cell => {
                cell.textContent = '-';
                cell.style.color = '#7f8c8d';
            });
            updateSummary();
        }

        updateSummary();
    </script>
</body>
</html>