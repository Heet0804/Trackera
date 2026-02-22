<?php
session_start();
require_once 'db_connect.php';
require_once 'session_helper.php';

requireFaculty();

$user         = getLoggedInUser();
$faculty_email = $user['email'];

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title    = mysqli_real_escape_string($conn, trim($_POST['title']));
    $message  = mysqli_real_escape_string($conn, trim($_POST['message']));
    $grade    = mysqli_real_escape_string($conn, $_POST['grade']);
    $division = mysqli_real_escape_string($conn, $_POST['division']);
    $date     = date('Y-m-d');

    if (empty($title) || empty($message)) {
        $error = "Please fill in both Title and Description!";
    } else {
        $grade_val    = $grade == 'All'    ? "NULL" : "'$grade'";
        $division_val = $division == 'All' ? "NULL" : "'$division'";

        $ins = "INSERT INTO notices (title, message, date, posted_by, created_at)
                VALUES ('$title', '$message', '$date', '$faculty_email', NOW())";

        if (mysqli_query($conn, $ins)) {
            $success = "Notice posted successfully!";
        } else {
            $error = "Failed to post notice: " . mysqli_error($conn);
        }
    }
}

// Fetch recent notices
$notices_q = mysqli_query($conn, "SELECT title, message, date, posted_by FROM notices ORDER BY created_at DESC LIMIT 10");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Faculty Post Notices</title>
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
        
        /* Form Panel */
        .form-panel {
            background: white;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            max-width: 900px;
            margin: 0 auto;
        }
        
        .form-panel h3 {
            font-size: 24px;
            color: #2c3e50;
            margin-bottom: 30px;
            border-bottom: 3px solid #3498db;
            padding-bottom: 15px;
        }
        
        .input-group {
            margin-bottom: 25px;
        }
        
        .input-group label {
            display: block;
            font-size: 16px;
            font-weight: 600;
            color: #34495e;
            margin-bottom: 10px;
        }
        
        .input-group input,
        .input-group textarea,
        .input-group select {
            width: 100%;
            padding: 14px;
            border: 2px solid #bdc3c7;
            border-radius: 6px;
            font-size: 15px;
            font-family: 'Segoe UI', sans-serif;
        }
        
        .input-group input:focus,
        .input-group textarea:focus,
        .input-group select:focus {
            outline: none;
            border-color: #3498db;
        }
        
        .input-group textarea {
            min-height: 180px;
            resize: vertical;
        }
        
        .input-row {
            display: flex;
            gap: 20px;
        }
        
        .input-row .input-group {
            flex: 1;
        }
        
        .info-box {
            background: #e3f2fd;
            border-left: 4px solid #2196f3;
            padding: 15px;
            margin-bottom: 25px;
            border-radius: 4px;
        }
        
        .info-box p {
            font-size: 14px;
            color: #1976d2;
            margin: 0;
        }
        
        .char-count {
            text-align: right;
            font-size: 13px;
            color: #7f8c8d;
            margin-top: 5px;
        }
        
        /* Action Buttons */
        .action-panel {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
            margin-top: 35px;
            padding-top: 25px;
            border-top: 2px solid #ecf0f1;
        }
        
        .btn-clear {
            background: #95a5a6;
            color: white;
            border: none;
            padding: 14px 35px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
        }
        
        .btn-clear:hover {
            background: #7f8c8d;
        }
        
        .btn-post {
            background: #27ae60;
            color: white;
            border: none;
            padding: 14px 40px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: bold;
            font-size: 16px;
        }
        
        .btn-post:hover {
            background: #229954;
        }
        
        /* Recent Notices */
        .recent-notices {
            background: white;
            padding: 30px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            max-width: 900px;
            margin: 30px auto 0;
        }
        
        .recent-notices h3 {
            font-size: 20px;
            color: #2c3e50;
            margin-bottom: 20px;
        }
        
        .notice-item {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 12px;
            border-left: 4px solid #3498db;
        }
        
        .notice-item .notice-title {
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 5px;
        }
        
        .notice-item .notice-meta {
            font-size: 12px;
            color: #7f8c8d;
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
            <div class="header-title">📢 Post Notices</div>
            <a href="facultydashboard.php" class="btn-back">← Back to Dashboard</a>
        </div>

        <!-- Content -->
        <div class="content">

            <!-- Form Panel -->
            <div class="form-panel">
                <h3>📝 Create New Notice</h3>

                <?php if ($error): ?>
                    <span class="msg-error"><?php echo htmlspecialchars($error); ?></span>
                <?php endif; ?>
                <?php if ($success): ?>
                    <span class="msg-success"><?php echo $success; ?></span>
                <?php endif; ?>

                <div class="info-box">
                    <p><strong>Info:</strong> Post notices for specific grades/divisions or for all students. Leave Grade and Division as "All" to broadcast to everyone.</p>
                </div>

                <form method="POST" action="" id="noticeForm">
                    <div class="input-group">
                        <label>Notice Title: *</label>
                        <input type="text" name="title" id="noticeTitle" placeholder="Enter notice title (e.g., Mid-term Exam Schedule)" maxlength="100">
                        <div class="char-count"><span id="titleCount">0</span>/100 characters</div>
                    </div>

                    <div class="input-group">
                        <label>Notice Description: *</label>
                        <textarea name="message" id="noticeDescription" placeholder="Enter detailed description of the notice..."></textarea>
                        <div class="char-count"><span id="descCount">0</span>/500 characters</div>
                    </div>

                    <div class="input-row">
                        <div class="input-group">
                            <label>Target Grade:</label>
                            <select name="grade" id="gradeSelect">
                                <option value="All">All Grades</option>
                                <?php for ($i = 1; $i <= 10; $i++): ?>
                                    <option value="<?php echo $i; ?>">Grade <?php echo $i; ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>

                        <div class="input-group">
                            <label>Target Division:</label>
                            <select name="division" id="divisionSelect">
                                <option value="All">All Divisions</option>
                                <option value="A">Division A</option>
                                <option value="B">Division B</option>
                                <option value="C">Division C</option>
                                <option value="D">Division D</option>
                                <option value="E">Division E</option>
                            </select>
                        </div>
                    </div>

                    <div class="action-panel">
                        <button type="button" class="btn-clear" onclick="clearForm()">Clear Form</button>
                        <button type="submit" class="btn-post">📢 Post Notice</button>
                    </div>
                </form>
            </div>

            <!-- Recent Notices -->
            <div class="recent-notices">
                <h3>📋 Recently Posted Notices</h3>
                <?php if (mysqli_num_rows($notices_q) > 0): ?>
                    <?php while ($notice = mysqli_fetch_assoc($notices_q)): ?>
                        <div class="notice-item">
                            <div class="notice-title"><?php echo htmlspecialchars($notice['title']); ?></div>
                            <div class="notice-meta">
                                <?php echo htmlspecialchars($notice['message']); ?><br>
                                <small>Posted by: <?php echo htmlspecialchars($notice['posted_by']); ?> • <?php echo $notice['date']; ?></small>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p style="color:#7f8c8d;">No notices posted yet.</p>
                <?php endif; ?>
            </div>

        </div>
    </div>

    <script>
        document.getElementById('noticeTitle').addEventListener('input', function() {
            document.getElementById('titleCount').textContent = this.value.length;
        });

        document.getElementById('noticeDescription').addEventListener('input', function() {
            document.getElementById('descCount').textContent = this.value.length;
        });

        function clearForm() {
            document.getElementById('noticeTitle').value = '';
            document.getElementById('noticeDescription').value = '';
            document.getElementById('gradeSelect').value = 'All';
            document.getElementById('divisionSelect').value = 'All';
            document.getElementById('titleCount').textContent = '0';
            document.getElementById('descCount').textContent = '0';
        }
    </script>
</body>
</html>