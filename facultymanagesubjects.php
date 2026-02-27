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

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add'])) {
    $subject_name = mysqli_real_escape_string($conn, trim($_POST['subject_name']));
    $grade        = mysqli_real_escape_string($conn, $_POST['grade']);

    if (!$subject_name || !$grade) {
        $error = "Please fill in all fields!";
    } else {
        if ($grade == 'all') {
            for ($g = 1; $g <= 10; $g++) {
                $check = mysqli_query($conn, "SELECT id FROM subjects WHERE subject_name='$subject_name' AND grade='$g' AND institute_id='$institute_id'");
                if (mysqli_num_rows($check) == 0) {
                    mysqli_query($conn, "INSERT INTO subjects (subject_name, grade, institute_id, created_at) VALUES ('$subject_name', '$g', '$institute_id', NOW())");
                }
            }
            $success = "Subject '$subject_name' added for all grades!";
        } else {
            $check = mysqli_query($conn, "SELECT id FROM subjects WHERE subject_name='$subject_name' AND grade='$grade' AND institute_id='$institute_id'");
            if (mysqli_num_rows($check) > 0) {
                $error = "Subject '$subject_name' already exists for Grade $grade!";
            } else {
                mysqli_query($conn, "INSERT INTO subjects (subject_name, grade, institute_id, created_at) VALUES ('$subject_name', '$grade', '$institute_id', NOW())");
                $success = "Subject '$subject_name' added for Grade $grade!";
            }
        }
    }
}

if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    mysqli_query($conn, "DELETE FROM subjects WHERE id=$id AND institute_id='$institute_id'");
    $success = "Subject deleted!";
}

$filter_grade  = isset($_GET['grade'])  ? mysqli_real_escape_string($conn, $_GET['grade'])  : '';
$filter_search = isset($_GET['search']) ? mysqli_real_escape_string($conn, $_GET['search']) : '';

$where = "WHERE institute_id='$institute_id'";
if ($filter_grade)  $where .= " AND grade='$filter_grade'";
if ($filter_search) $where .= " AND subject_name LIKE '%$filter_search%'";

$subjects_q = mysqli_query($conn, "SELECT id, subject_name, grade FROM subjects $where ORDER BY grade ASC, subject_name ASC");
$subjects   = [];
while ($row = mysqli_fetch_assoc($subjects_q)) {
    $subjects[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects</title>
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

        .content { padding: 30px; max-width: 1200px; margin: 0 auto; }

        .top-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        .add-subject-panel, .filter-panel {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .add-subject-panel h3, .filter-panel h3 {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #ecf0f1;
        }

        .form-group { margin-bottom: 15px; }

        label {
            display: block;
            font-weight: 600;
            color: #34495e;
            margin-bottom: 8px;
            font-size: 14px;
        }

        input, select {
            width: 100%;
            padding: 11px 15px;
            border: 2px solid #bdc3c7;
            border-radius: 6px;
            font-size: 15px;
            font-family: 'Segoe UI';
        }

        input:focus, select:focus { outline: none; border-color: #3498db; }

        .btn-add {
            background: #27ae60;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
            width: 100%;
            margin-top: 5px;
        }

        .btn-add:hover { background: #229954; }

        .btn-filter {
            background: #3498db;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 15px;
            width: 100%;
            margin-top: 5px;
        }

        .subjects-table {
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

        table { width: 100%; border-collapse: collapse; }

        th, td {
            padding: 15px 20px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
            font-size: 14px;
        }

        th { background: #34495e; color: white; font-weight: 600; }
        tr:hover { background: #f8f9fa; }

        .btn-delete {
            background: #e74c3c;
            color: white;
            padding: 6px 15px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 13px;
            text-decoration: none;
            display: inline-block;
        }

        .btn-delete:hover { background: #c0392b; }

        .grade-badge {
            background: #3498db;
            color: white;
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
        <div class="header-title">📚 Manage Subjects</div>
        <a href="facultydashboard.php" class="btn-back">← Back To Dashboard</a>
    </div>

    <div class="content">

        <?php if ($error): ?>
            <span class="msg-error"><?php echo htmlspecialchars($error); ?></span>
        <?php endif; ?>
        <?php if ($success): ?>
            <span class="msg-success"><?php echo $success; ?></span>
        <?php endif; ?>

        <div class="top-section">
            <!-- Add Subject Panel -->
            <div class="add-subject-panel">
                <h3>➕ Add New Subject</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Subject Name:</label>
                        <input type="text" name="subject_name" placeholder="e.g. Mathematics, Science...">
                    </div>
                    <div class="form-group">
                        <label>Grade:</label>
                        <select name="grade">
                            <option value="">-- Select Grade --</option>
                            <option value="all">All Grades (1-10)</option>
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?php echo $i; ?>">Grade <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <button type="submit" name="add" class="btn-add">➕ Add Subject</button>
                </form>
            </div>

            <!-- Filter Panel -->
            <div class="filter-panel">
                <h3>🔍 Filter Subjects</h3>
                <form method="GET" action="">
                    <div class="form-group">
                        <label>Filter by Grade:</label>
                        <select name="grade">
                            <option value="">All Grades</option>
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?php echo $i; ?>" <?php echo $filter_grade == $i ? 'selected' : ''; ?>>Grade <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Search Subject:</label>
                        <input type="text" name="search" placeholder="Type subject name..." value="<?php echo htmlspecialchars($filter_search); ?>">
                    </div>
                    <button type="submit" class="btn-filter">🔍 Filter</button>
                </form>
            </div>
        </div>

        <!-- Subjects Table -->
        <div class="subjects-table">
            <div class="table-header">
                <h3>📋 All Subjects (<?php echo count($subjects); ?>)</h3>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Subject Name</th>
                        <th>Grade</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($subjects)): ?>
                        <tr>
                            <td colspan="4" style="text-align:center; color:#7f8c8d; padding:30px;">No subjects found.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($subjects as $i => $sub): ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo htmlspecialchars($sub['subject_name']); ?></td>
                                <td><span class="grade-badge">Grade <?php echo $sub['grade']; ?></span></td>
                                <td>
                                    <a href="facultymanagesubjects.php?delete=<?php echo $sub['id']; ?>"
                                       class="btn-delete"
                                       onclick="return confirm('Delete this subject? This will also affect marks and attendance records.')">
                                        🗑️ Delete
                                    </a>
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