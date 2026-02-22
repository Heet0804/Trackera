<?php
session_start();
require_once 'db_connect.php';
require_once 'session_helper.php';

requireFaculty();

$user          = getLoggedInUser();
$faculty_email = $user['email'];

$error   = '';
$success = '';

// Fixed periods with time slots
$periods = [
    1  => ['time_from' => '09:00', 'time_to' => '10:00'],
    2  => ['time_from' => '10:00', 'time_to' => '11:00'],
    3  => ['time_from' => '11:15', 'time_to' => '12:15'],
    4  => ['time_from' => '12:15', 'time_to' => '13:15'],
    5  => ['time_from' => '14:00', 'time_to' => '15:00'],
    6  => ['time_from' => '15:00', 'time_to' => '16:00'],
    7  => ['time_from' => '16:00', 'time_to' => '17:00'],
    8  => ['time_from' => '17:00', 'time_to' => '18:00'],
    9  => ['time_from' => '18:00', 'time_to' => '19:00'],
    10 => ['time_from' => '19:00', 'time_to' => '20:00'],
];

// Get academic year
$yr_q = mysqli_query($conn, "SELECT year FROM academic_year WHERE is_current=1 LIMIT 1");
$yr   = mysqli_fetch_assoc($yr_q);
$academic_year = $yr ? $yr['year'] : date('Y') . '-' . (date('Y') + 1);

// Handle Save
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {
    $grade    = mysqli_real_escape_string($conn, $_POST['grade']);
    $division = mysqli_real_escape_string($conn, $_POST['division']);
    $day      = mysqli_real_escape_string($conn, $_POST['day']);

    if (!$grade || !$division || !$day) {
        $error = "Please select Grade, Division and Day!";
    } else {
        // Delete existing schedule for this grade+division+day
        mysqli_query($conn, "DELETE FROM schedule WHERE grade='$grade' AND division='$division' AND day_name='$day' AND academic_year='$academic_year'");

        // Insert new periods
        foreach ($periods as $p_num => $time) {
            $subject = isset($_POST["period$p_num"]) ? mysqli_real_escape_string($conn, $_POST["period$p_num"]) : '';
            if (!empty($subject)) {
                mysqli_query($conn, "INSERT INTO schedule (grade, division, day_name, subject, time_from, time_to, teacher_email, academic_year, created_at)
                    VALUES ('$grade', '$division', '$day', '$subject', '{$time['time_from']}', '{$time['time_to']}', '$faculty_email', '$academic_year', NOW())");
            }
        }
        $success = "Schedule saved successfully for Grade $grade-$division, $day!";
    }
}

// Load existing schedule if grade+division+day selected
$sel_grade    = isset($_POST['grade'])    ? mysqli_real_escape_string($conn, $_POST['grade'])    : '';
$sel_division = isset($_POST['division']) ? mysqli_real_escape_string($conn, $_POST['division']) : '';
$sel_day      = isset($_POST['day'])      ? mysqli_real_escape_string($conn, $_POST['day'])      : '';

$existing = [];
if ($sel_grade && $sel_division && $sel_day) {
    $ex_q = mysqli_query($conn, "SELECT time_from, subject FROM schedule WHERE grade='$sel_grade' AND division='$sel_division' AND day_name='$sel_day' AND academic_year='$academic_year'");
    while ($row = mysqli_fetch_assoc($ex_q)) {
        $existing[$row['time_from']] = $row['subject'];
    }
}

// Get subjects from DB for selected grade
$subjects = [];
if ($sel_grade) {
    $sub_q = mysqli_query($conn, "SELECT subject_name FROM subjects WHERE grade='$sel_grade' ORDER BY subject_name ASC");
    while ($row = mysqli_fetch_assoc($sub_q)) {
        $subjects[] = $row['subject_name'];
    }
}
// Fallback default subjects if none in DB
if (empty($subjects)) {
    $subjects = ['English', 'Mathematics', 'Science', 'Social Studies', 'Hindi', 'Computer', 'Physical Education', 'Art'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Manage Schedule</title>
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
        
        /* Schedule Panel */
        .schedule-panel {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .schedule-panel h3 {
            font-size: 22px;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .info-banner {
            background: #e8f5e9;
            border-left: 4px solid #4caf50;
            padding: 12px 15px;
            margin-bottom: 25px;
            border-radius: 4px;
            font-size: 14px;
            color: #2e7d32;
        }
        
        .period-grid {
            display: grid;
            grid-template-columns: 150px 1fr;
            gap: 15px;
            align-items: center;
            margin-bottom: 15px;
        }
        
        .period-label {
            font-size: 16px;
            font-weight: 600;
            color: #34495e;
            background: #ecf0f1;
            padding: 12px;
            border-radius: 5px;
            text-align: center;
        }
        
        .period-select {
            padding: 12px;
            border: 2px solid #bdc3c7;
            border-radius: 5px;
            font-size: 15px;
            background: white;
        }
        
        .period-select:focus {
            outline: none;
            border-color: #9b59b6;
        }
        
        /* Action Panel */
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
            color: #9b59b6;
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
            display: block;
            padding: 12px 15px;
            border-radius: 6px;
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
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
</head>
<body>
    <div class="frame">
        <!-- Header -->
        <div class="header">
            <div class="header-title">📅 Manage Schedule</div>
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
                            <label>Day of Week:</label>
                            <select name="day" id="daySelect">
                                <option value="">-- Select Day --</option>
                                <?php foreach (['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday'] as $day): ?>
                                    <option value="<?php echo $day; ?>" <?php echo $sel_day == $day ? 'selected' : ''; ?>><?php echo $day; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <button type="submit" name="load" class="btn-load">Load Schedule</button>
                    </div>
                </div>

                <!-- Schedule Panel -->
                <div class="schedule-panel">
                    <h3>⏰ Set Period Schedule</h3>
                    <div class="info-banner">
                        <strong>Note:</strong> Select subject for each period. Leave blank for free periods or lunch breaks.
                    </div>

                    <?php foreach ($periods as $p_num => $time): ?>
                        <?php
                            $existing_subject = $existing[$time['time_from']] ?? '';
                        ?>
                        <div class="period-grid">
                            <div class="period-label">Period <?php echo $p_num; ?><br><small><?php echo $time['time_from']; ?> - <?php echo $time['time_to']; ?></small></div>
                            <select class="period-select" name="period<?php echo $p_num; ?>" id="period<?php echo $p_num; ?>">
                                <option value="">-- Select Subject --</option>
                                <?php foreach ($subjects as $sub): ?>
                                    <option value="<?php echo htmlspecialchars($sub); ?>" <?php echo $existing_subject == $sub ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($sub); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    <?php endforeach; ?>
                </div>

                <!-- Action Panel -->
                <div class="action-panel">
                    <div class="summary">
                        Schedule for: <span>
                            <?php echo ($sel_grade && $sel_division && $sel_day) ? "Grade $sel_grade-$sel_division, $sel_day" : 'Not selected'; ?>
                        </span>
                    </div>
                    <div class="action-buttons">
                        <button type="button" class="btn-clear" onclick="clearAll()">Clear All</button>
                        <button type="submit" name="save" class="btn-save">✓ Save Schedule</button>
                    </div>
                </div>
            </form>

        </div>
    </div>

    <script>
        function clearAll() {
            <?php for ($i = 1; $i <= 10; $i++): ?>
                document.getElementById('period<?php echo $i; ?>').value = '';
            <?php endfor; ?>
        }
    </script>
</body>
</html>