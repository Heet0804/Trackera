<?php
session_start();
require_once 'db_connect.php';
require_once 'session_helper.php';

requireFaculty();

$error   = '';
$success = '';

// Handle Save All Divisions
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {
    $grade = mysqli_real_escape_string($conn, $_POST['grade']);
    $saved = 0;

    if (isset($_POST['division']) && is_array($_POST['division'])) {
        foreach ($_POST['division'] as $student_email => $division) {
            $student_email = mysqli_real_escape_string($conn, $student_email);
            $division      = mysqli_real_escape_string($conn, $division);
            if ($division !== '') {
                mysqli_query($conn, "UPDATE users SET division='$division' WHERE email='$student_email' AND role='student'");
                $saved++;
            }
        }
    }
    $success = "Divisions saved for $saved students!";
}

// Load students for selected grade
$sel_grade = isset($_POST['grade']) ? mysqli_real_escape_string($conn, $_POST['grade']) : 
             (isset($_GET['grade'])  ? mysqli_real_escape_string($conn, $_GET['grade'])  : '');

$students = [];
if ($sel_grade) {
    $st_q = mysqli_query($conn, "SELECT user_id, name, email, division, gender FROM users WHERE role='student' AND grade='$sel_grade' ORDER BY name ASC");
    while ($row = mysqli_fetch_assoc($st_q)) {
        $students[] = $row;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Divisions</title>
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
            font-size: 15px;
            text-decoration: none;
        }

        .btn-back:hover { background: #c0392b; }

        .content { padding: 30px; max-width: 1200px; margin: 0 auto; }

        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #1565c0;
        }

        .filter-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 25px;
            display: flex;
            gap: 20px;
            align-items: flex-end;
        }

        .filter-group { flex: 1; }

        .filter-group label {
            display: block;
            font-weight: 600;
            color: #34495e;
            margin-bottom: 8px;
            font-size: 14px;
        }

        select {
            width: 100%;
            padding: 12px;
            border: 2px solid #bdc3c7;
            border-radius: 6px;
            font-size: 15px;
            font-family: 'Segoe UI';
        }

        select:focus { outline: none; border-color: #3498db; }

        .btn-load {
            background: #3498db;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
            height: 46px;
        }

        .btn-load:hover { background: #2980b9; }

        .students-table {
            background: white;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }

        .table-header {
            background: #ecf0f1;
            padding: 20px 25px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .table-header h3 { font-size: 20px; color: #2c3e50; }

        .btn-save-all {
            background: #27ae60;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
        }

        .btn-save-all:hover { background: #229954; }

        table { width: 100%; border-collapse: collapse; }

        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
            font-size: 14px;
        }

        th { background: #34495e; color: white; font-weight: 600; }

        tr:hover { background: #f8f9fa; }

        .division-select {
            padding: 8px 12px;
            border: 2px solid #bdc3c7;
            border-radius: 6px;
            font-size: 14px;
            width: 130px;
        }

        .division-select:focus { outline: none; border-color: #3498db; }

        .badge-unassigned {
            background: #f8d7da;
            color: #721c24;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-assigned {
            background: #d4edda;
            color: #155724;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
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
    <div class="header">
        <div class="header-title">🏷️ Assign Divisions</div>
        <a href="facultydashboard.php" class="btn-back">← Back</a>
    </div>

    <div class="content">

        <?php if ($error): ?>
            <span class="msg-error"><?php echo htmlspecialchars($error); ?></span>
        <?php endif; ?>
        <?php if ($success): ?>
            <span class="msg-success"><?php echo $success; ?></span>
        <?php endif; ?>

        <div class="info-box">
            📌 Select a grade to view all registered students. Assign divisions individually to each student. Students in Grade 8-10 selecting a language will be auto-assigned divisions (A = Sanskrit, E = French, B/C/D = Hindi).
        </div>

        <!-- Grade Filter -->
        <form method="POST" action="">
            <div class="filter-section">
                <div class="filter-group">
                    <label>Select Grade:</label>
                    <select name="grade" id="gradeSelect">
                        <option value="">-- Select Grade --</option>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $sel_grade == $i ? 'selected' : ''; ?>>Grade <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <button type="submit" name="load" class="btn-load">Load Students</button>
            </div>

            <!-- Students Table -->
            <?php if ($sel_grade): ?>
            <div class="students-table">
                <div class="table-header">
                    <h3>Grade <?php echo $sel_grade; ?> Students (<?php echo count($students); ?>)</h3>
                    <button type="submit" name="save" class="btn-save-all">💾 Save All Divisions</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Gender</th>
                            <th>Current Division</th>
                            <th>Assign Division</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center; color:#7f8c8d; padding:30px;">No students found for Grade <?php echo $sel_grade; ?>.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $i => $s): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo htmlspecialchars($s['name']); ?></td>
                                    <td><?php echo htmlspecialchars($s['email']); ?></td>
                                    <td><?php echo htmlspecialchars($s['gender']); ?></td>
                                    <td>
                                        <?php if ($s['division']): ?>
                                            <span class="badge-assigned">Division <?php echo $s['division']; ?></span>
                                        <?php else: ?>
                                            <span class="badge-unassigned">Unassigned</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <select class="division-select" name="division[<?php echo htmlspecialchars($s['email']); ?>]">
                                            <option value="">Select</option>
                                            <?php foreach (['A','B','C','D','E'] as $d): ?>
                                                <option value="<?php echo $d; ?>" <?php echo $s['division'] == $d ? 'selected' : ''; ?>>
                                                    Division <?php echo $d; ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>

        </form>

    </div>
</body>
</html>