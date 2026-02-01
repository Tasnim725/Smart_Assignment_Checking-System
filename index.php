<?php
require_once 'config.php';

// Redirect if already logged in
if (isset($_SESSION['user_id'])) {
    if ($_SESSION['user_type'] == 'student') {
        header("Location: student_dashboard.php");
    } else {
        header("Location: teacher_dashboard.php");
    }
    exit();
}

// Generate CSRF token
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Smart Assignment Checker - Login</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📚 Smart Assignment Checker</h1>
            <p>AI-Powered Evaluation & Feedback System</p>
        </header>

        <?php if (isset($_GET['error'])): ?>
        <div class="error-message" style="text-align: center;">
            <?php 
            if ($_GET['error'] == 'invalid') echo "❌ Invalid credentials. Please try again.";
            if ($_GET['error'] == 'logout') echo "✅ You have been logged out successfully.";
            if ($_GET['error'] == 'session') echo "⚠️ Session expired. Please login again.";
            ?>
        </div>
        <?php endif; ?>

        <div class="content login-page">
            <div class="login-container">
                <div class="login-tabs">
                    <button class="tab-btn active" onclick="switchTab('student')">
                        👨‍🎓 Student Login
                    </button>
                    <button class="tab-btn" onclick="switchTab('teacher')">
                        👨‍🏫 Teacher Login
                    </button>
                </div>

                <!-- Student Login Form -->
                <div id="student-login" class="login-form active">
                    <h2>Student Login</h2>
                    <form action="login.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="user_type" value="student">
                        
                        <div class="form-group">
                            <label for="student_email">Email Address</label>
                            <input type="email" id="student_email" name="email" required placeholder="your.email@university.edu">
                        </div>

                        <div class="form-group">
                            <label for="student_password">Password</label>
                            <input type="password" id="student_password" name="password" required placeholder="Enter your password">
                        </div>

                        <button type="submit" class="btn-primary">
                            🔐 Login as Student
                        </button>
                    </form>

                    <div class="demo-credentials">
                        <h4>📝 Demo Credentials:</h4>
                        <p><strong>Email:</strong> john.doe@university.edu</p>
                        <p><strong>Password:</strong> student123</p>
                    </div>
                </div>

                <!-- Teacher Login Form -->
                <div id="teacher-login" class="login-form">
                    <h2>Teacher Login</h2>
                    <form action="login.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="user_type" value="teacher">
                        
                        <div class="form-group">
                            <label for="teacher_email">Email Address</label>
                            <input type="email" id="teacher_email" name="email" required placeholder="your.email@university.edu">
                        </div>

                        <div class="form-group">
                            <label for="teacher_password">Password</label>
                            <input type="password" id="teacher_password" name="password" required placeholder="Enter your password">
                        </div>

                        <button type="submit" class="btn-primary">
                            🔐 Login as Teacher
                        </button>
                    </form>

                    <div class="demo-credentials">
                        <h4>📝 Demo Credentials:</h4>
                        <p><strong>Email:</strong> john.smith@university.edu</p>
                        <p><strong>Password:</strong> teacher123</p>
                    </div>
                </div>

                <!-- ✅ SIGNUP LINK -->
                <div style="text-align: center; margin-top: 20px;">
                    <p>Don't have an account? <a href="signup.php">Sign Up</a></p>
                </div>
            </div>

            <div class="info-panel">
                <h3>ℹ️ About the System</h3>
                <div class="feature-box">
                    <h4>👨‍🎓 For Students:</h4>
                    <ul>
                        <li>Submit assignments online</li>
                        <li>Get instant AI evaluation</li>
                        <li>Receive detailed feedback</li>
                        <li>View submission history</li>
                        <li>Track your progress</li>
                    </ul>
                </div>

                <div class="feature-box">
                    <h4>👨‍🏫 For Teachers:</h4>
                    <ul>
                        <li>Create and manage assignments</li>
                        <li>View student submissions</li>
                        <li>Add personal comments</li>
                        <li>Monitor class performance</li>
                        <li>AI-assisted grading</li>
                    </ul>
                </div>

                <div class="alert-info">
                    <strong>🤖 AI-Powered Features:</strong>
                    <ul style="margin-top: 10px; margin-left: 20px;">
                        <li>Automatic content evaluation</li>
                        <li>AI-generated content detection</li>
                        <li>Personalized feedback</li>
                        <li>Quality scoring (0-100)</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <script>
        function switchTab(type) {
            const tabs = document.querySelectorAll('.tab-btn');
            const forms = document.querySelectorAll('.login-form');
            tabs.forEach(tab => tab.classList.remove('active'));
            forms.forEach(form => form.classList.remove('active'));

            if (type === 'student') {
                tabs[0].classList.add('active');
                document.getElementById('student-login').classList.add('active');
            } else {
                tabs[1].classList.add('active');
                document.getElementById('teacher-login').classList.add('active');
            }
        }
    </script>
</body>
</html>
