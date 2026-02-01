<?php
require_once 'config.php';

// Generate CSRF token
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Smart Assignment Checker</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>📚 Smart Assignment Checker</h1>
            <p>Create Your Account</p>
        </header>

        <?php if (isset($_GET['error'])): ?>
        <div class="error-message" style="text-align: center; margin-bottom: 20px;">
            <?php 
            if ($_GET['error'] == 'empty') echo "❌ All fields are required.";
            if ($_GET['error'] == 'exists') echo "❌ Email already exists.";
            if ($_GET['error'] == 'fail') echo "❌ Registration failed. Please try again.";
            ?>
        </div>
        <?php endif; ?>

        <?php if (isset($_GET['success'])): ?>
        <div class="alert-success" style="text-align: center; margin-bottom: 20px;">
            ✅ Registration successful! Please login.
        </div>
        <?php endif; ?>

        <div class="content login-page">
            <div class="login-container">
                <div class="login-tabs">
                    <button class="tab-btn active" onclick="switchTab('student')">
                        👨‍🎓 Student Sign Up
                    </button>
                    <button class="tab-btn" onclick="switchTab('teacher')">
                        👨‍🏫 Teacher Sign Up
                    </button>
                </div>

                <!-- Student Signup -->
                <div id="student" class="login-form active">
                    <h2>Student Registration</h2>
                    <form action="register.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="user_type" value="student">

                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="name" required placeholder="e.g., John Doe">
                        </div>

                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" required placeholder="your.email@university.edu">
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label>Student ID *</label>
                                <input type="text" name="user_code" required placeholder="e.g., CS2024001">
                            </div>
                            <div class="form-group">
                                <label>Batch Number *</label>
                                <input type="text" name="batch_number" required placeholder="e.g., Batch-2024">
                                <small>Enter your batch year (e.g., Batch-2024, Batch-2023)</small>
                            </div>
                        </div>

                        <div class="form-group">
                            <label>Department *</label>
                            <input type="text" name="department" required placeholder="e.g., Computer Science">
                        </div>

                        <div class="form-group">
                            <label>Password *</label>
                            <input type="password" name="password" required placeholder="Enter a strong password" minlength="6">
                            <small>Minimum 6 characters</small>
                        </div>

                        <button type="submit" class="btn-primary">📝 Sign Up as Student</button>
                    </form>
                </div>

                <!-- Teacher Signup -->
                <div id="teacher" class="login-form">
                    <h2>Teacher Registration</h2>
                    <form action="register.php" method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="user_type" value="teacher">

                        <div class="form-group">
                            <label>Full Name *</label>
                            <input type="text" name="name" required placeholder="e.g., Dr. John Smith">
                        </div>

                        <div class="form-group">
                            <label>Email Address *</label>
                            <input type="email" name="email" required placeholder="your.email@university.edu">
                        </div>

                        <div class="form-group">
                            <label>Teacher ID *</label>
                            <input type="text" name="user_code" required placeholder="e.g., T001">
                        </div>

                        <div class="form-group">
                            <label>Department *</label>
                            <input type="text" name="department" required placeholder="e.g., Computer Science">
                        </div>

                        <div class="form-group">
                            <label>Password *</label>
                            <input type="password" name="password" required placeholder="Enter a strong password" minlength="6">
                            <small>Minimum 6 characters</small>
                        </div>

                        <button type="submit" class="btn-primary">📝 Sign Up as Teacher</button>
                    </form>
                </div>

                <div style="text-align: center; margin-top: 20px;">
                    <p>Already have an account? <a href="index.php" style="color: #667eea; text-decoration: none; font-weight: 600;">Login Here</a></p>
                </div>
            </div>

            <div class="info-panel">
                <h3>ℹ️ Registration Information</h3>
                <div class="feature-box">
                    <h4>📋 Required Information:</h4>
                    <ul>
                        <li>Valid university email address</li>
                        <li>Student/Teacher ID from institution</li>
                        <li>Department information</li>
                        <li><strong>Batch Number (for students)</strong></li>
                        <li>Secure password (min. 6 characters)</li>
                    </ul>
                </div>

                <div class="alert-info">
                    <strong>🔒 Your Data is Safe</strong>
                    <p style="margin-top: 10px; font-size: 0.9em;">
                        All passwords are securely hashed using industry-standard encryption. 
                        Your personal information is protected and never shared.
                    </p>
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
                document.getElementById('student').classList.add('active');
            } else {
                tabs[1].classList.add('active');
                document.getElementById('teacher').classList.add('active');
            }
        }
    </script>
</body>
</html>
