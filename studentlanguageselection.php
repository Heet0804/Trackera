<?php
session_start();
require_once 'db_connect.php';
require_once 'session_helper.php';

requireStudent();

$user          = getLoggedInUser();
$student_email = $user['email'];
$student_name  = $user['name'];
$grade         = $user['grade'];

// Only grade 7 students can select language
if ($grade != 7) {
    header("Location: studentdashboard.php");
    exit();
}

// Check if already selected
$already_q = mysqli_query($conn, "SELECT language FROM language_selection WHERE student_email='$student_email'");
if (mysqli_num_rows($already_q) > 0) {
    $already = mysqli_fetch_assoc($already_q);
    $already_selected = $already['language'];
} else {
    $already_selected = null;
}

// Get deadline from a faculty-set record (optional - fetch from language_selection table meta or hardcode)
// For now we check if any setup exists
$deadline = "31st March 2026"; // can be made dynamic later

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && !$already_selected) {
    $language = mysqli_real_escape_string($conn, $_POST['language']);

    if (!in_array($language, ['Sanskrit', 'Hindi', 'French'])) {
        $error = "Invalid language selection.";
    } else {
        // Auto-assign division based on language
        if ($language == 'Sanskrit') {
            $division = 'A';
        } elseif ($language == 'French') {
            $division = 'E';
        } else {
            // Hindi → division stays NULL, faculty will assign B/C/D
            $division = null;
        }

        // Save to language_selection table
        $ins = "INSERT INTO language_selection (student_email, language, grade, created_at)
                VALUES ('$student_email', '$language', '$grade', NOW())";

        if (mysqli_query($conn, $ins)) {
            // Update users table: set language and division
            $div_val = $division ? "'$division'" : "NULL";
            mysqli_query($conn, "UPDATE users SET language='$language', division=$div_val WHERE email='$student_email'");

            // Update session division
            $_SESSION['division'] = $division;

            $success = "Language selected successfully! Redirecting to dashboard...";
            header("refresh:2;url=studentdashboard.php");
        } else {
            $error = "Something went wrong. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Select Your Language</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .header {
            background: rgba(0,0,0,0.2);
            height: 70px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 0 30px;
        }

        .header-title { color: white; font-size: 24px; font-weight: bold; }

        .btn-back {
            background: rgba(255,255,255,0.2);
            color: white;
            border: 2px solid white;
            padding: 8px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            text-decoration: none;
        }

        .content {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            padding: 40px 20px;
        }

        .selection-card {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 750px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        }

        .selection-card h2 {
            text-align: center;
            font-size: 28px;
            color: #2c3e50;
            margin-bottom: 10px;
        }

        .selection-card p.subtitle {
            text-align: center;
            color: #7f8c8d;
            margin-bottom: 15px;
            font-size: 15px;
        }

        .deadline-box {
            background: #fff3cd;
            border: 2px solid #f39c12;
            border-radius: 8px;
            padding: 12px 20px;
            text-align: center;
            margin-bottom: 30px;
            font-size: 14px;
            color: #856404;
        }

        .deadline-box strong { font-size: 16px; }

        .language-options {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }

        .language-option {
            border: 3px solid #e0e0e0;
            border-radius: 15px;
            padding: 30px 20px;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            position: relative;
        }

        .language-option:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 20px rgba(0,0,0,0.15);
        }

        .language-option.selected-sanskrit {
            border-color: #f39c12;
            background: #fef9e7;
        }

        .language-option.selected-french {
            border-color: #3498db;
            background: #ebf5fb;
        }

        .language-option.selected-hindi {
            border-color: #27ae60;
            background: #eafaf1;
        }

        .check {
            position: absolute;
            top: 10px;
            right: 10px;
            font-size: 22px;
            display: none;
        }

        .language-option.selected-sanskrit .check,
        .language-option.selected-french .check,
        .language-option.selected-hindi .check {
            display: block;
        }

        .lang-icon { font-size: 50px; margin-bottom: 15px; }

        .lang-name {
            font-size: 22px;
            font-weight: bold;
            margin-bottom: 8px;
        }

        .lang-name.sanskrit { color: #f39c12; }
        .lang-name.french { color: #3498db; }
        .lang-name.hindi { color: #27ae60; }

        .lang-desc {
            font-size: 13px;
            color: #7f8c8d;
            margin-top: 8px;
            line-height: 1.5;
        }

        .btn-confirm {
            width: 100%;
            padding: 16px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 18px;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.3s;
        }

        .btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.4);
        }

        .note {
            text-align: center;
            font-size: 12px;
            color: #e74c3c;
            margin-top: 15px;
        }

        @media (max-width: 600px) {
            .language-options { grid-template-columns: 1fr; }
        }

        .msg-success {
            display: block;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
            text-align: center;
        }

        .msg-error {
            display: block;
            padding: 12px 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-size: 14px;
            font-weight: 500;
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
            text-align: center;
        }

        .already-selected {
            background: #e8f5e9;
            border: 2px solid #27ae60;
            border-radius: 10px;
            padding: 20px;
            text-align: center;
            font-size: 18px;
            color: #155724;
            margin-bottom: 20px;
        }
    </style>
</head>
<body>
    <div class="header">
        <div class="header-title">🌐 Language Selection</div>
        <a href="studentdashboard.php" class="btn-back">← Dashboard</a>
    </div>

    <div class="content">
        <div class="selection-card">
            <h2>Choose Your Language Subject</h2>
            <p class="subtitle">You are about to enter Grade 8. Please select your language subject carefully.</p>

            <?php if ($error): ?>
                <span class="msg-error"><?php echo htmlspecialchars($error); ?></span>
            <?php endif; ?>
            <?php if ($success): ?>
                <span class="msg-success"><?php echo $success; ?></span>
            <?php endif; ?>

            <?php if ($already_selected): ?>
                <!-- Already selected - show locked view -->
                <div class="already-selected">
                    ✅ You have already selected <strong><?php echo htmlspecialchars($already_selected); ?></strong> as your language subject.<br>
                    <small style="color:#7f8c8d;">Contact faculty if you wish to change it.</small>
                </div>

            <?php else: ?>
                <div class="deadline-box">
                    ⏰ <strong>Deadline: <?php echo $deadline; ?></strong> — Please make your selection before this date.
                </div>

                <form method="POST" action="" id="langForm">
                    <input type="hidden" name="language" id="selectedLangInput" value="">

                    <div class="language-options">
                        <!-- Sanskrit -->
                        <div class="language-option" id="option-sanskrit" onclick="selectLanguage('Sanskrit')">
                            <span class="check">✅</span>
                            <div class="lang-icon">🕉️</div>
                            <div class="lang-name sanskrit">Sanskrit</div>
                            <div class="lang-desc">
                                Classical language with rich literary heritage. Ideal for students interested in Indian culture and tradition.
                            </div>
                        </div>

                        <!-- Hindi -->
                        <div class="language-option" id="option-hindi" onclick="selectLanguage('Hindi')">
                            <span class="check">✅</span>
                            <div class="lang-icon">🇮🇳</div>
                            <div class="lang-name hindi">Hindi</div>
                            <div class="lang-desc">
                                National language of India. A great choice for students looking to strengthen their Hindi communication skills.
                            </div>
                        </div>

                        <!-- French -->
                        <div class="language-option" id="option-french" onclick="selectLanguage('French')">
                            <span class="check">✅</span>
                            <div class="lang-icon">🇫🇷</div>
                            <div class="lang-name french">French</div>
                            <div class="lang-desc">
                                International language spoken across the world. Perfect for students with global career aspirations.
                            </div>
                        </div>
                    </div>

                    <button type="button" class="btn-confirm" onclick="confirmSelection()">✅ Confirm My Selection</button>
                    <p class="note">⚠️ Once confirmed, this selection cannot be changed without faculty approval.</p>
                </form>
            <?php endif; ?>

        </div>
    </div>

    <script>
        let selectedLanguage = null;

        function selectLanguage(language) {
            document.getElementById('option-sanskrit').classList.remove('selected-sanskrit');
            document.getElementById('option-hindi').classList.remove('selected-hindi');
            document.getElementById('option-french').classList.remove('selected-french');

            const key = language.toLowerCase();
            document.getElementById(`option-${key}`).classList.add(`selected-${key}`);
            selectedLanguage = language;
            document.getElementById('selectedLangInput').value = language;
        }

        function confirmSelection() {
            if (!selectedLanguage) {
                alert('Please select a language first!');
                return;
            }

            if (confirm(`You have selected ${selectedLanguage} as your language subject.\n\nAre you sure? This cannot be changed without faculty approval.`)) {
                document.getElementById('langForm').submit();
            }
        }
    </script>
</body>
</html>