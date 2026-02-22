<?php
session_start();
require_once 'db_connect.php';
require_once 'session_helper.php';

requireFaculty();

$error   = '';
$success = '';

// Get current and next academic year
$yr_q = mysqli_query($conn, "SELECT year FROM academic_year WHERE is_current=1 LIMIT 1");
$yr   = mysqli_fetch_assoc($yr_q);
$current_year = $yr ? $yr['year'] : date('Y') . '-' . (date('Y') + 1);

// Calculate next year
$parts     = explode('-', $current_year);
$next_year = ($parts[0] + 1) . '-' . ($parts[1] + 1);

// Get student counts per grade
$grade_counts = [];
for ($g = 1; $g <= 10; $g++) {
    $cq = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role='student' AND grade='$g'");
    $grade_counts[$g] = mysqli_fetch_assoc($cq)['cnt'];
}

// Handle Promotion
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['promote'])) {
    $grade = (int) $_POST['grade'];

    if (!$grade) {
        $error = "Please select a grade to promote!";
    } else {
        if ($grade == 10) {
            // Grade 10 → mark as passed out (grade = 11 or delete - here we set grade=11 as "passed out")
            $res = mysqli_query($conn, "UPDATE users SET grade=11, division=NULL, roll_no=NULL WHERE role='student' AND grade=10");
            $count = mysqli_affected_rows($conn);
            $success = "✅ $count Grade 10 students have been marked as Passed Out!";
        } else {
            // Promote grade X to X+1, reset division and roll_no
            $next_grade = $grade + 1;

            // For Grade 7→8: clear division (will be re-assigned based on language)
            // For others: clear division and roll_no too (fresh assignment)
            $res = mysqli_query($conn, "UPDATE users SET grade='$next_grade', division=NULL, roll_no=NULL WHERE role='student' AND grade='$grade'");
            $count = mysqli_affected_rows($conn);
            $success = "✅ $count students promoted from Grade $grade to Grade $next_grade! Divisions and roll numbers have been reset.";
        }

        // Update academic year to next year
        if (isset($_POST['update_year']) && $_POST['update_year'] == '1') {
            mysqli_query($conn, "UPDATE academic_year SET is_current=0 WHERE is_current=1");
            mysqli_query($conn, "INSERT INTO academic_year (year, is_current) VALUES ('$next_year', 1)
                                 ON DUPLICATE KEY UPDATE is_current=1");
            $success .= " Academic year updated to $next_year.";
        }

        // Refresh counts
        for ($g = 1; $g <= 10; $g++) {
            $cq = mysqli_query($conn, "SELECT COUNT(*) as cnt FROM users WHERE role='student' AND grade='$g'");
            $grade_counts[$g] = mysqli_fetch_assoc($cq)['cnt'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Promote Students</title>
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

        .content { padding: 30px; max-width: 1000px; margin: 0 auto; }

        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #f39c12;
            padding: 15px 20px;
            border-radius: 6px;
            margin-bottom: 25px;
            font-size: 14px;
            color: #856404;
        }

        .warning-box strong { display: block; margin-bottom: 5px; font-size: 15px; }

        .academic-year-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 25px;
        }

        .academic-year-section h3 {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 15px;
        }

        .year-display {
            background: #3498db;
            color: white;
            padding: 15px 25px;
            border-radius: 8px;
            font-size: 20px;
            font-weight: bold;
            display: inline-block;
            margin-right: 15px;
        }

        .year-arrow {
            font-size: 24px;
            color: #27ae60;
            margin-right: 15px;
        }

        .grade-cards {
            display: grid;
            grid-template-columns: repeat(5, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }

        .grade-card {
            background: white;
            padding: 20px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            border: 2px solid transparent;
        }

        .grade-card:hover {
            border-color: #3498db;
            transform: translateY(-3px);
        }

        .grade-card.selected {
            border-color: #27ae60;
            background: #d4edda;
        }

        .grade-card .grade-num {
            font-size: 32px;
            font-weight: bold;
            color: #3498db;
            margin-bottom: 5px;
        }

        .grade-card .grade-label {
            font-size: 13px;
            color: #7f8c8d;
        }

        .grade-card .student-count {
            font-size: 12px;
            color: #27ae60;
            font-weight: 600;
            margin-top: 5px;
        }

        .grade-card.grade-10 .grade-num { color: #e74c3c; }

        .promote-section {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }

        .promote-section h3 {
            font-size: 18px;
            color: #2c3e50;
            margin-bottom: 20px;
        }

        .promote-options {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 25px;
        }

        .promote-option {
            background: #ecf0f1;
            padding: 20px;
            border-radius: 10px;
            border: 2px solid transparent;
            transition: all 0.3s;
        }

        .promote-option.selected { border-color: #27ae60; background: #d4edda; }

        .promote-option h4 { font-size: 16px; color: #2c3e50; margin-bottom: 8px; }
        .promote-option p { font-size: 13px; color: #7f8c8d; }

        .btn-promote {
            background: #27ae60;
            color: white;
            padding: 15px 40px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
            width: 100%;
        }

        .btn-promote:hover { background: #229954; }

        .note {
            margin-top: 15px;
            font-size: 13px;
            color: #e74c3c;
            text-align: center;
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

        .year-update-row {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-top: 15px;
            font-size: 14px;
            color: #34495e;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-title">🎓 Promote Students</div>
        <a href="facultydashboard.php" class="btn-back">← Back To Dashboard</a>
    </div>

    <div class="content">

        <?php if ($error): ?>
            <span class="msg-error"><?php echo htmlspecialchars($error); ?></span>
        <?php endif; ?>
        <?php if ($success): ?>
            <span class="msg-success"><?php echo $success; ?></span>
        <?php endif; ?>

        <div class="warning-box">
            <strong>⚠️ Important!</strong>
            Promoting students will move them to the next grade. This action affects all selected students permanently. Grade 10 students will be marked as "Passed Out". Division and roll numbers will be reset and must be reassigned. Please make sure all marks and attendance are finalized before promoting.
        </div>

        <div class="academic-year-section">
            <h3>📅 Academic Year Transition</h3>
            <span class="year-display"><?php echo $current_year; ?></span>
            <span class="year-arrow">→</span>
            <span class="year-display"><?php echo $next_year; ?></span>
        </div>

        <form method="POST" action="" id="promoteForm">
            <input type="hidden" name="grade" id="selectedGradeInput" value="">

            <!-- Grade Cards -->
            <div class="grade-cards">
                <?php for ($g = 1; $g <= 10; $g++): ?>
                    <div class="grade-card <?php echo $g == 10 ? 'grade-10' : ''; ?>"
                         onclick="selectGrade(this, <?php echo $g; ?>)">
                        <div class="grade-num"><?php echo $g; ?></div>
                        <div class="grade-label">
                            <?php echo $g < 10 ? "Grade $g → " . ($g+1) : 'Passed Out'; ?>
                        </div>
                        <div class="student-count"><?php echo $grade_counts[$g]; ?> students</div>
                    </div>
                <?php endfor; ?>
            </div>

            <!-- Promote Section -->
            <div class="promote-section">
                <h3>Promotion Options</h3>
                <div class="promote-options">
                    <div class="promote-option selected">
                        <h4>✅ Promote All Students</h4>
                        <p>All students in selected grade will be moved to the next grade automatically. Divisions and roll numbers will be reset.</p>
                    </div>
                    <div class="promote-option">
                        <h4>📋 What Happens</h4>
                        <p>After promotion, faculty must reassign divisions and roll numbers for the new grade. Grade 7→8 students must also select a language.</p>
                    </div>
                </div>

                <div class="year-update-row">
                    <input type="checkbox" name="update_year" value="1" id="updateYearCheck">
                    <label for="updateYearCheck">Also update academic year to <strong><?php echo $next_year; ?></strong></label>
                </div>

                <br>
                <button type="button" class="btn-promote" onclick="promoteStudents()">
                    🎓 Promote Selected Grade
                </button>
                <p class="note">⚠️ This action cannot be undone. Please confirm before proceeding.</p>
            </div>
        </form>

    </div>

    <script>
        let selectedGrade = null;

        function selectGrade(card, grade) {
            document.querySelectorAll('.grade-card').forEach(c => c.classList.remove('selected'));
            card.classList.add('selected');
            selectedGrade = grade;
            document.getElementById('selectedGradeInput').value = grade;
        }

        function promoteStudents() {
            if (!selectedGrade) {
                alert('Please select a grade first by clicking on a grade card!');
                return;
            }

            const label = selectedGrade == 10
                ? 'Grade 10 students will be marked as Passed Out.'
                : `Grade ${selectedGrade} students will be promoted to Grade ${selectedGrade + 1}.`;

            if (confirm(`Are you sure?\n\n${label}\n\nDivisions and roll numbers will be reset.\n\nThis cannot be undone!`)) {
                const form = document.getElementById('promoteForm');
                const btn  = document.createElement('input');
                btn.type   = 'hidden';
                btn.name   = 'promote';
                btn.value  = '1';
                form.appendChild(btn);
                form.submit();
            }
        }
    </script>
</body>
</html>