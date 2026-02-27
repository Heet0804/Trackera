<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();
require_once 'db_connect.php';
require_once 'session_helper.php';

requireFaculty();

$user         = getLoggedInUser();
$institute_id = $user['institute_id'];

$grade    = isset($_GET['grade'])    ? mysqli_real_escape_string($conn, $_GET['grade'])    : '';
$division = isset($_GET['division']) ? mysqli_real_escape_string($conn, $_GET['division']) : '';
$name     = isset($_GET['name'])     ? mysqli_real_escape_string($conn, $_GET['name'])     : '';
$roll     = isset($_GET['roll'])     ? mysqli_real_escape_string($conn, $_GET['roll'])     : '';

$where = "WHERE role='student' AND institute_id='$institute_id'";
if ($grade)    $where .= " AND grade='$grade'";
if ($division) $where .= " AND division='$division'";
if ($name)     $where .= " AND name LIKE '%$name%'";
if ($roll)     $where .= " AND roll_no='$roll'";

$students_q  = mysqli_query($conn, "SELECT user_id, name, email, grade, division, roll_no, gender FROM users $where ORDER BY grade ASC, division ASC, roll_no ASC");
$students    = [];
while ($row = mysqli_fetch_assoc($students_q)) {
    $students[] = $row;
}
$total_count = count($students);

$total_q  = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role='student' AND institute_id='$institute_id'");
$total    = mysqli_fetch_assoc($total_q)['cnt'];

$male_q   = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role='student' AND gender='Male' AND institute_id='$institute_id'");
$male     = mysqli_fetch_assoc($male_q)['cnt'];

$female_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role='student' AND gender='Female' AND institute_id='$institute_id'");
$female   = mysqli_fetch_assoc($female_q)['cnt'];

$div_q    = mysqli_query($conn, "SELECT COUNT(DISTINCT division) as cnt FROM users WHERE role='student' AND division IS NOT NULL AND institute_id='$institute_id'");
$divs     = mysqli_fetch_assoc($div_q)['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty View Students</title>
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
        
        /* Filter Panel */
        .filter-panel {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 20px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .filter-panel h3 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .filter-row {
            display: flex;
            gap: 20px;
            align-items: flex-end;
        }
        
        .filter-group {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .filter-group label {
            font-size: 14px;
            font-weight: 600;
            color: #34495e;
            margin-bottom: 8px;
        }
        
        .filter-group select,
        .filter-group input {
            padding: 12px;
            border: 2px solid #bdc3c7;
            border-radius: 5px;
            font-size: 15px;
            background: white;
        }
        
        .filter-group select:focus,
        .filter-group input:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .btn-search {
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
        
        .btn-search:hover {
            background: #2980b9;
        }
        
        .btn-reset {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 12px 25px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            margin-top: 24px;
            text-decoration: none;
        }
        
        .btn-reset:hover {
            background: #7f8c8d;
        }
        
        /* Students Table */
        .table-panel {
            background: white;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 20px;
        }
        
        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
        }
        
        .table-header h3 {
            font-size: 20px;
            color: #2c3e50;
        }
        
        .student-count {
            font-size: 16px;
            color: #7f8c8d;
        }
        
        .student-count span {
            font-weight: bold;
            color: #3498db;
            font-size: 20px;
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
        
        .email-cell {
            color: #3498db;
        }
        
        .btn-view {
            background: #3498db;
            color: white;
            border: none;
            padding: 6px 15px;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
            font-weight: bold;
        }
        
        .btn-view:hover {
            background: #2980b9;
        }
        
        /* Stats Cards */
        .stats-panel {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .stat-card {
            flex: 1;
            background: white;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
        }
        
        .stat-card.blue {
            border-left: 5px solid #3498db;
        }
        
        .stat-card.green {
            border-left: 5px solid #27ae60;
        }
        
        .stat-card.orange {
            border-left: 5px solid #f39c12;
        }
        
        .stat-card.purple {
            border-left: 5px solid #9b59b6;
        }
        
        .stat-card .number {
            font-size: 36px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .stat-card .label {
            font-size: 14px;
            color: #7f8c8d;
        }
    </style>
</head>
<body>
    <div class="frame">
        <!-- Header -->
        <div class="header">
            <div class="header-title">👥 View Students</div>
            <a href="facultydashboard.php" class="btn-back">← Back to Dashboard</a>
        </div>

        <!-- Content -->
        <div class="content">

            <!-- Stats Panel -->
            <div class="stats-panel">
                <div class="stat-card blue">
                    <div class="number"><?php echo $total; ?></div>
                    <div class="label">Total Students</div>
                </div>
                <div class="stat-card green">
                    <div class="number">10</div>
                    <div class="label">Grades</div>
                </div>
                <div class="stat-card orange">
                    <div class="number"><?php echo $divs; ?></div>
                    <div class="label">Divisions</div>
                </div>
                <div class="stat-card purple">
                    <div class="number"><?php echo $male; ?></div>
                    <div class="label">Male Students</div>
                </div>
            </div>

            <!-- Filter Panel -->
            <div class="filter-panel">
                <h3>🔍 Filter Students</h3>
                <form method="GET" action="">
                    <div class="filter-row">
                        <div class="filter-group">
                            <label>Grade:</label>
                            <select name="grade" id="gradeFilter">
                                <option value="">All Grades</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?php echo $i; ?>" <?php echo $grade == $i ? 'selected' : ''; ?>>Grade <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Division:</label>
                            <select name="division" id="divisionFilter">
                                <option value="">All Divisions</option>
                                <?php foreach (['A','B','C','D','E'] as $d): ?>
                                    <option value="<?php echo $d; ?>" <?php echo $division == $d ? 'selected' : ''; ?>>Division <?php echo $d; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="filter-group">
                            <label>Search by Name:</label>
                            <input type="text" name="name" id="nameSearch" placeholder="Enter student name..." value="<?php echo htmlspecialchars($name); ?>">
                        </div>

                        <div class="filter-group">
                            <label>Search by Roll Number:</label>
                            <input type="text" name="roll" id="rollSearch" placeholder="Enter roll number..." value="<?php echo htmlspecialchars($roll); ?>">
                        </div>

                        <button type="submit" class="btn-search">Search</button>
                        <a href="facultyviewstudents.php" class="btn-reset">Reset</a>
                    </div>
                </form>
            </div>

            <!-- Students Table -->
            <div class="table-panel">
                <div class="table-header">
                    <h3>📋 Student List</h3>
                    <div class="student-count">Showing: <span><?php echo $total_count; ?></span> students</div>
                </div>

                <table class="students-table">
                    <thead>
                        <tr>
                            <th style="width: 250px;">Student Name</th>
                            <th style="width: 100px;">Grade</th>
                            <th style="width: 100px;">Division</th>
                            <th style="width: 120px;">Roll Number</th>
                            <th style="width: 250px;">Email</th>
                            <th style="width: 100px;">Gender</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($students)): ?>
                            <tr>
                                <td colspan="6" style="text-align:center; color:#7f8c8d; padding:30px;">No students found.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($students as $s): ?>
                                <tr>
                                    <td class="name-cell"><?php echo htmlspecialchars($s['name']); ?></td>
                                    <td><?php echo $s['grade']; ?></td>
                                    <td><?php echo $s['division'] ? $s['division'] : '<span style="color:#e74c3c;">Not Assigned</span>'; ?></td>
                                    <td><?php echo $s['roll_no'] ? $s['roll_no'] : '<span style="color:#e74c3c;">Not Assigned</span>'; ?></td>
                                    <td class="email-cell"><?php echo htmlspecialchars($s['email']); ?></td>
                                    <td><?php echo htmlspecialchars($s['gender']); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</body>
</html>