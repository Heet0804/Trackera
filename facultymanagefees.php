<?php
header('Content-Type: text/html; charset=UTF-8');
session_start();
require_once 'db_connect.php';
require_once 'session_helper.php';

requireFaculty();

$user          = getLoggedInUser();
$faculty_email = $user['email'];

$error   = '';
$success = '';

// Handle Add/Update Fees
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['save'])) {
    $student_email  = mysqli_real_escape_string($conn, $_POST['student_email']);
    $total_fees     = (float) $_POST['total_fees'];
    $paid_amount    = (float) $_POST['paid_amount'];
    $pending_amount = $total_fees - $paid_amount;
    $payment_date   = mysqli_real_escape_string($conn, $_POST['payment_date']);
    $payment_mode   = mysqli_real_escape_string($conn, $_POST['payment_mode']);
    $receipt_no     = mysqli_real_escape_string($conn, $_POST['receipt_no']);

    if (!$student_email || $total_fees <= 0) {
        $error = "Please select a student and enter valid fee amount!";
    } elseif ($paid_amount > $total_fees) {
        $error = "Paid amount cannot be greater than total fees!";
    } else {
        $yr_q = mysqli_query($conn, "SELECT year FROM academic_year WHERE is_current=1 LIMIT 1");
        $yr   = mysqli_fetch_assoc($yr_q);
        $academic_year = $yr ? $yr['year'] : date('Y') . '-' . (date('Y') + 1);

        $check = mysqli_query($conn, "SELECT id FROM fees WHERE student_email='$student_email' AND academic_year='$academic_year'");
        if (mysqli_num_rows($check) > 0) {
            mysqli_query($conn, "UPDATE fees SET
                total_fees='$total_fees',
                paid_amount='$paid_amount',
                pending_amount='$pending_amount',
                payment_date='$payment_date',
                payment_mode='$payment_mode',
                receipt_no='$receipt_no',
                updated_by='$faculty_email',
                created_at=NOW()
                WHERE student_email='$student_email' AND academic_year='$academic_year'");
            $success = "Fee record updated successfully!";
        } else {
            mysqli_query($conn, "INSERT INTO fees
                (student_email, total_fees, paid_amount, pending_amount, payment_date, payment_mode, receipt_no, updated_by, academic_year, created_at)
                VALUES
                ('$student_email', '$total_fees', '$paid_amount', '$pending_amount', '$payment_date', '$payment_mode', '$receipt_no', '$faculty_email', '$academic_year', NOW())");
            $success = "Fee record added successfully!";
        }
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = (int) $_GET['delete'];
    mysqli_query($conn, "DELETE FROM fees WHERE id=$id");
    $success = "Fee record deleted!";
}

// Filters
$filter_grade    = isset($_GET['grade'])    ? mysqli_real_escape_string($conn, $_GET['grade'])    : '';
$filter_division = isset($_GET['division']) ? mysqli_real_escape_string($conn, $_GET['division']) : '';

// Get current academic year
$yr_q = mysqli_query($conn, "SELECT year FROM academic_year WHERE is_current=1 LIMIT 1");
$yr   = mysqli_fetch_assoc($yr_q);
$academic_year = $yr ? $yr['year'] : date('Y') . '-' . (date('Y') + 1);

// Load all students for dropdown
$all_students_q = mysqli_query($conn, "SELECT name, email, grade, division FROM users WHERE role='student' ORDER BY grade ASC, name ASC");
$all_students = [];
while ($row = mysqli_fetch_assoc($all_students_q)) {
    $all_students[] = $row;
}

// Load fees records with filter
$where = "WHERE f.academic_year='$academic_year'";
if ($filter_grade)    $where .= " AND u.grade='$filter_grade'";
if ($filter_division) $where .= " AND u.division='$filter_division'";

$fees_q = mysqli_query($conn, "
    SELECT f.id, f.student_email, f.total_fees, f.paid_amount, f.pending_amount,
           f.payment_date, f.payment_mode, f.receipt_no, f.updated_by,
           u.name, u.grade, u.division, u.roll_no
    FROM fees f
    JOIN users u ON f.student_email = u.email
    $where
    ORDER BY u.grade ASC, u.division ASC, u.roll_no ASC
");
$fees_records = [];
while ($row = mysqli_fetch_assoc($fees_q)) {
    $fees_records[] = $row;
}

// Stats
$total_students = count($all_students);

$fees_entered_q = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM fees WHERE academic_year='$academic_year'");
$fees_entered   = mysqli_fetch_assoc($fees_entered_q)['cnt'];

$pending_q     = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM fees WHERE academic_year='$academic_year' AND pending_amount > 0");
$pending_count = mysqli_fetch_assoc($pending_q)['cnt'];

$paid_q     = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM fees WHERE academic_year='$academic_year' AND pending_amount = 0");
$paid_count = mysqli_fetch_assoc($paid_q)['cnt'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Fees</title>
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
            font-size: 15px;
        }

        .btn-back:hover { background: #c0392b; }

        .content { padding: 30px; max-width: 1200px; margin: 0 auto; }

        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 25px;
        }

        .stat-box {
            background: white;
            border-radius: 12px;
            padding: 20px;
            text-align: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .stat-box .num  { font-size: 32px; font-weight: bold; margin-bottom: 5px; }
        .stat-box .lbl  { font-size: 13px; color: #7f8c8d; }
        .stat-box.blue .num   { color: #3498db; }
        .stat-box.green .num  { color: #27ae60; }
        .stat-box.red .num    { color: #e74c3c; }
        .stat-box.orange .num { color: #f39c12; }

        .top-section {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 25px;
        }

        .add-panel, .filter-panel {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .add-panel h3, .filter-panel h3 {
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

        .input-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
        }

        .btn-save {
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

        .btn-save:hover { background: #229954; }

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

        .fees-table {
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
            padding: 13px 15px;
            text-align: left;
            border-bottom: 1px solid #ecf0f1;
            font-size: 13px;
        }

        th { background: #34495e; color: white; font-weight: 600; }
        tr:hover { background: #f8f9fa; }

        .badge-paid {
            background: #d4edda; color: #155724;
            padding: 4px 12px; border-radius: 12px;
            font-size: 12px; font-weight: 600;
        }

        .badge-pending {
            background: #f8d7da; color: #721c24;
            padding: 4px 12px; border-radius: 12px;
            font-size: 12px; font-weight: 600;
        }

        .badge-partial {
            background: #fff3cd; color: #856404;
            padding: 4px 12px; border-radius: 12px;
            font-size: 12px; font-weight: 600;
        }

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

        .year-badge {
            background: #3498db; color: white;
            padding: 4px 12px; border-radius: 12px;
            font-size: 13px; font-weight: 600;
        }

        input[readonly] { background: #ecf0f1; color: #7f8c8d; }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-title">💰 Manage Fees</div>
        <a href="facultydashboard.php" class="btn-back">← Back To Dashboard</a>
    </div>

    <div class="content">

        <?php if ($error): ?>
            <span class="msg-error"><?php echo htmlspecialchars($error); ?></span>
        <?php endif; ?>
        <?php if ($success): ?>
            <span class="msg-success"><?php echo $success; ?></span>
        <?php endif; ?>

        <!-- Stats -->
        <div class="stats-row">
            <div class="stat-box blue">
                <div class="num"><?php echo $total_students; ?></div>
                <div class="lbl">Total Students</div>
            </div>
            <div class="stat-box orange">
                <div class="num"><?php echo $fees_entered; ?></div>
                <div class="lbl">Fees Entered</div>
            </div>
            <div class="stat-box green">
                <div class="num"><?php echo $paid_count; ?></div>
                <div class="lbl">Fully Paid</div>
            </div>
            <div class="stat-box red">
                <div class="num"><?php echo $pending_count; ?></div>
                <div class="lbl">Pending</div>
            </div>
        </div>

        <div class="top-section">
            <!-- Add/Update Fees Panel -->
            <div class="add-panel">
                <h3>➕ Add / Update Fee Record</h3>
                <form method="POST" action="">
                    <div class="form-group">
                        <label>Select Grade:</label>
                        <select id="gradeFilter" onchange="loadStudents()">
                            <option value="">-- Select Grade --</option>
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?php echo $i; ?>">Grade <?php echo $i; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Select Student:</label>
                        <select name="student_email" id="studentDropdown">
                            <option value="">-- Select Grade First --</option>
                        </select>
                    </div>
                    <div class="input-row">
                        <div class="form-group">
                            <label>Total Fees (₹):</label>
                            <input type="number" name="total_fees" id="totalFees"
                                   placeholder="e.g. 15000" min="0" step="0.01"
                                   oninput="calcPending()">
                        </div>
                        <div class="form-group">
                            <label>Paid Amount (₹):</label>
                            <input type="number" name="paid_amount" id="paidAmount"
                                   placeholder="e.g. 10000" min="0" step="0.01"
                                   oninput="calcPending()">
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Pending Amount (₹):</label>
                        <input type="number" id="pendingDisplay"
                               placeholder="Auto calculated" readonly>
                    </div>
                    <div class="input-row">
                        <div class="form-group">
                            <label>Payment Date:</label>
                            <input type="date" name="payment_date"
                                   value="<?php echo date('Y-m-d'); ?>">
                        </div>
                        <div class="form-group">
                            <label>Payment Mode:</label>
                            <select name="payment_mode">
                                <option value="Cash">Cash</option>
                                <option value="Online">Online</option>
                                <option value="Cheque">Cheque</option>
                                <option value="DD">Demand Draft</option>
                            </select>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Receipt No:</label>
                        <input type="text" name="receipt_no" placeholder="e.g. REC2026001">
                    </div>
                    <button type="submit" name="save" class="btn-save">💾 Save Fee Record</button>
                </form>
            </div>

            <!-- Filter Panel -->
            <div class="filter-panel">
                <h3>🔍 Filter Records</h3>
                <form method="GET" action="">
                    <div class="form-group">
                        <label>Filter by Grade:</label>
                        <select name="grade">
                            <option value="">All Grades</option>
                            <?php for ($i = 1; $i <= 10; $i++): ?>
                                <option value="<?php echo $i; ?>"
                                    <?php echo $filter_grade == $i ? 'selected' : ''; ?>>
                                    Grade <?php echo $i; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Filter by Division:</label>
                        <select name="division">
                            <option value="">All Divisions</option>
                            <?php foreach (['A','B','C','D','E'] as $d): ?>
                                <option value="<?php echo $d; ?>"
                                    <?php echo $filter_division == $d ? 'selected' : ''; ?>>
                                    Division <?php echo $d; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn-filter">🔍 Filter</button>
                </form>
            </div>
        </div>

        <!-- Fees Table -->
        <div class="fees-table">
            <div class="table-header">
                <h3>📋 Fee Records — <span class="year-badge"><?php echo $academic_year; ?></span></h3>
                <span style="color:#7f8c8d; font-size:14px;"><?php echo count($fees_records); ?> records</span>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student</th>
                        <th>Grade</th>
                        <th>Div</th>
                        <th>Total</th>
                        <th>Paid</th>
                        <th>Pending</th>
                        <th>Date</th>
                        <th>Mode</th>
                        <th>Receipt</th>
                        <th>Status</th>
                        <th>Action</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($fees_records)): ?>
                        <tr>
                            <td colspan="12" style="text-align:center; color:#7f8c8d; padding:30px;">
                                No fee records found. Add fees using the form above.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($fees_records as $i => $f): ?>
                            <?php
                                if ($f['pending_amount'] == 0)  $status = 'paid';
                                elseif ($f['paid_amount'] == 0) $status = 'pending';
                                else                            $status = 'partial';
                            ?>
                            <tr>
                                <td><?php echo $i + 1; ?></td>
                                <td><?php echo htmlspecialchars($f['name']); ?></td>
                                <td>Grade <?php echo $f['grade']; ?></td>
                                <td><?php echo $f['division'] ? $f['division'] : '--'; ?></td>
                                <td>₹<?php echo number_format($f['total_fees'], 2); ?></td>
                                <td style="color:#27ae60; font-weight:600;">
                                    ₹<?php echo number_format($f['paid_amount'], 2); ?>
                                </td>
                                <td style="color:#e74c3c; font-weight:600;">
                                    ₹<?php echo number_format($f['pending_amount'], 2); ?>
                                </td>
                                <td><?php echo $f['payment_date'] ? date('d M Y', strtotime($f['payment_date'])) : '--'; ?></td>
                                <td><?php echo htmlspecialchars($f['payment_mode'] ?? '--'); ?></td>
                                <td><?php echo htmlspecialchars($f['receipt_no'] ?? '--'); ?></td>
                                <td>
                                    <?php if ($status == 'paid'): ?>
                                        <span class="badge-paid">✅ Paid</span>
                                    <?php elseif ($status == 'partial'): ?>
                                        <span class="badge-partial">⚠️ Partial</span>
                                    <?php else: ?>
                                        <span class="badge-pending">❌ Pending</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="facultymanagefees.php?delete=<?php echo $f['id']; ?>"
                                       class="btn-delete"
                                       onclick="return confirm('Delete this fee record?')">
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

    <script>
        // Store all students from PHP
        const allStudents = [
            <?php foreach ($all_students as $s): ?>,
            {
                email    : '<?php echo addslashes($s['email']); ?>',
                name     : '<?php echo addslashes($s['name']); ?>',
                grade    : '<?php echo $s['grade']; ?>',
                division : '<?php echo $s['division'] ?? ''; ?>'
            },
            <?php endforeach; ?>
        ];

        function loadStudents() {
            const grade    = document.getElementById('gradeFilter').value;
            const dropdown = document.getElementById('studentDropdown');

            dropdown.innerHTML = '<option value="">-- Select Student --</option>';

            if (!grade) {
                dropdown.innerHTML = '<option value="">-- Select Grade First --</option>';
                return;
            }

            const filtered = allStudents.filter(s => s.grade === grade);

            if (filtered.length === 0) {
                dropdown.innerHTML = '<option value="">No students found for this grade</option>';
                return;
            }

            filtered.forEach(s => {
                const opt   = document.createElement('option');
                opt.value   = s.email;
                opt.text    = s.name + (s.division ? ' (Div ' + s.division + ')' : '');
                dropdown.appendChild(opt);
            });
        }

        function calcPending() {
            const total   = parseFloat(document.getElementById('totalFees').value) || 0;
            const paid    = parseFloat(document.getElementById('paidAmount').value) || 0;
            const pending = total - paid;
            document.getElementById('pendingDisplay').value = pending >= 0 ? pending.toFixed(2) : '0.00';
        }
    </script>
</body>
</html>