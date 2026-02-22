<?php
session_start();
require_once 'db_connect.php';
require_once 'session_helper.php';

requireFaculty();

$error   = '';
$success = '';

// Handle Save Roll Numbers
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {
    $saved = 0;
    if (isset($_POST['roll_no']) && is_array($_POST['roll_no'])) {
        foreach ($_POST['roll_no'] as $student_email => $roll_no) {
            $student_email = mysqli_real_escape_string($conn, $student_email);
            $roll_no       = (int) $roll_no;
            if ($roll_no > 0) {
                mysqli_query($conn, "UPDATE users SET roll_no='$roll_no' WHERE email='$student_email' AND role='student'");
                $saved++;
            }
        }
    }
    $success = "Roll numbers saved for $saved students!";
}

// Handle Auto Generate
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['auto'])) {
    $grade    = mysqli_real_escape_string($conn, $_POST['grade']);
    $division = mysqli_real_escape_string($conn, $_POST['division']);
    if ($grade && $division) {
        $st_q = mysqli_query($conn, "SELECT email FROM users WHERE role='student' AND grade='$grade' AND division='$division' ORDER BY name ASC");
        $roll = 1;
        while ($row = mysqli_fetch_assoc($st_q)) {
            $em = mysqli_real_escape_string($conn, $row['email']);
            mysqli_query($conn, "UPDATE users SET roll_no='$roll' WHERE email='$em'");
            $roll++;
        }
        $success = "Roll numbers auto-generated for Grade $grade-$division!";
    }
}

// Filters
$sel_grade    = isset($_POST['grade'])    ? mysqli_real_escape_string($conn, $_POST['grade'])    : '';
$sel_division = isset($_POST['division']) ? mysqli_real_escape_string($conn, $_POST['division']) : '';

// Load students
$students = [];
if ($sel_grade && $sel_division) {
    $st_q = mysqli_query($conn, "SELECT user_id, name, email, division, roll_no FROM users WHERE role='student' AND grade='$sel_grade' AND division='$sel_division' ORDER BY roll_no ASC, name ASC");
    while ($row = mysqli_fetch_assoc($st_q)) {
        $students[] = $row;
    }
}

$div_badge = ['A' => 'badge-a', 'B' => 'badge-b', 'C' => 'badge-c', 'D' => 'badge-d', 'E' => 'badge-e'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Assign Roll Numbers</title>
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

        .btn-back:hover { background: #c0392b; }

        .content { padding: 30px; max-width: 1200px; margin: 0 auto; }

        .info-box {
            background: #e8f5e9;
            border-left: 4px solid #27ae60;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #1b5e20;
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

        .btn-auto {
            background: #8e44ad;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
            height: 46px;
        }

        .btn-auto:hover { background: #7d3c98; }

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

        .btn-save {
            background: #27ae60;
            color: white;
            padding: 10px 25px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
        }

        table { width: 100%; border-collapse: collapse; }

        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
            font-size: 14px;
        }

        th { background: #34495e; color: white; font-weight: 600; }
        tr:hover { background: #f8f9fa; }

        .roll-input {
            padding: 8px 12px;
            border: 2px solid #bdc3c7;
            border-radius: 6px;
            font-size: 14px;
            width: 100px;
            text-align: center;
        }

        .roll-input:focus { outline: none; border-color: #3498db; }

        .badge {
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 12px;
            font-weight: 600;
        }

        .badge-a { background: #d4edda; color: #155724; }
        .badge-b { background: #cce5ff; color: #004085; }
        .badge-c { background: #fff3cd; color: #856404; }
        .badge-d { background: #f8d7da; color: #721c24; }
        .badge-e { background: #e2d9f3; color: #5a2d82; }

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
        <div class="header-title">🔢 Assign Roll Numbers</div>
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
            📌 Select a grade and division to view students. Click "Auto Generate" to automatically assign roll numbers in order, or assign them manually. Roll numbers are assigned per division.
        </div>

        <form method="POST" action="">
            <div class="filter-section">
                <div class="filter-group">
                    <label>Grade:</label>
                    <select name="grade">
                        <option value="">-- Select Grade --</option>
                        <?php for ($i = 1; $i <= 10; $i++): ?>
                            <option value="<?php echo $i; ?>" <?php echo $sel_grade == $i ? 'selected' : ''; ?>>Grade <?php echo $i; ?></option>
                        <?php endfor; ?>
                    </select>
                </div>
                <div class="filter-group">
                    <label>Division:</label>
                    <select name="division">
                        <option value="">-- Select Division --</option>
                        <?php foreach (['A','B','C','D','E'] as $d): ?>
                            <option value="<?php echo $d; ?>" <?php echo $sel_division == $d ? 'selected' : ''; ?>>Division <?php echo $d; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" name="load" class="btn-load">Load Students</button>
                <button type="submit" name="auto" class="btn-auto"
                        onclick="return confirm('Auto-generate roll numbers 1,2,3... alphabetically for this division?')">
                    ⚡ Auto Generate
                </button>
            </div>

            <?php if ($sel_grade && $sel_division): ?>
            <div class="students-table">
                <div class="table-header">
                    <h3>Grade <?php echo $sel_grade; ?> - Division <?php echo $sel_division; ?> Students (<?php echo count($students); ?>)</h3>
                    <button type="submit" name="save" class="btn-save">💾 Save Roll Numbers</button>
                </div>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Division</th>
                            <th>Roll Number</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="5" style="text-align:center; color:#7f8c8d; padding:30px;">
                                    No students found for Grade <?php echo $sel_grade; ?>-<?php echo $sel_division; ?>.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $i => $s): ?>
                                <tr>
                                    <td><?php echo $i + 1; ?></td>
                                    <td><?php echo htmlspecialchars($s['name']); ?></td>
                                    <td><?php echo htmlspecialchars($s['email']); ?></td>
                                    <td>
                                        <span class="badge <?php echo $div_badge[$s['division']] ?? 'badge-a'; ?>">
                                            <?php echo $s['division']; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <input type="number"
                                               class="roll-input"
                                               name="roll_no[<?php echo htmlspecialchars($s['email']); ?>]"
                                               value="<?php echo $s['roll_no'] ? $s['roll_no'] : ''; ?>"
                                               min="1"
                                               placeholder="--">
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