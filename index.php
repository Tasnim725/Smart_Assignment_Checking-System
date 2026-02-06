<?php
require_once 'config.php';

// If user is already logged in, redirect to dashboard
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
    <title>Login - Smart Assignment Checker</title>
    
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <style>
        :root {
            --primary-color: #6366f1;
            --secondary-color: #8b5cf6;
        }
        
        * {
            font-family: 'Poppins', sans-serif;
        }
        
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 40px 0;
        }
        
        .login-container {
            background: white;
            border-radius: 20px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            overflow: hidden;
        }
        
        .login-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            color: white;
            padding: 40px;
            text-align: center;
        }
        
        .login-header h1 {
            font-size: 2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        
        .nav-pills .nav-link {
            border-radius: 10px;
            font-weight: 500;
            padding: 12px 30px;
        }
        
        .nav-pills .nav-link:not(.active) {
            color: #6b7280;
            background: #f3f4f6;
        }
        
        .nav-pills .nav-link.active {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
        }
        
        .form-control {
            border-radius: 10px;
            padding: 12px 16px;
            border: 2px solid #e5e7eb;
        }
        
        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(99, 102, 241, 0.15);
        }
        
        .btn-login {
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--secondary-color) 100%);
            border: none;
            border-radius: 10px;
            padding: 14px;
            font-weight: 600;
            transition: transform 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(99, 102, 241, 0.3);
        }
        
        .demo-box {
            background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
            border-radius: 15px;
            padding: 20px;
            border-left: 4px solid var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="container">
        <?php if (isset($_GET['error'])): ?>
        <div class="row mb-3">
            <div class="col-lg-10 mx-auto">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php 
                    if ($_GET['error'] == 'invalid') echo "Invalid email or password.";
                    if ($_GET['error'] == 'session') echo "Please login to continue.";
                    if ($_GET['error'] == 'logout') echo "You have been logged out successfully.";
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['success']) && $_GET['success'] == 'registered'): ?>
        <div class="row mb-3">
            <div class="col-lg-10 mx-auto">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    Registration successful! Please login with your credentials.
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-10 mx-auto">
                <div class="login-container">
                    <div class="login-header">
                        <h1><i class="bi bi-mortarboard-fill me-2"></i>Smart Assignment Checker</h1>
                        <p>AI-Powered Assignment Evaluation System</p>
                    </div>
                    
                    <div class="p-4 p-md-5">
                        <ul class="nav nav-pills mb-4 d-flex justify-content-center" role="tablist">
                            <li class="nav-item me-2" role="presentation">
                                <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#student" type="button">
                                    <i class="bi bi-person-fill me-2"></i>Student Login
                                </button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" data-bs-toggle="pill" data-bs-target="#teacher" type="button">
                                    <i class="bi bi-person-badge-fill me-2"></i>Teacher Login
                                </button>
                            </li>
                        </ul>
                        
                        <div class="tab-content">
                            <!-- Student Login -->
                            <div class="tab-pane fade show active" id="student">
                                <form action="login.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="user_type" value="student">
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">
                                            <i class="bi bi-envelope me-2"></i>Email Address
                                        </label>
                                        <input type="email" class="form-control" name="email" required placeholder="your.email@university.edu">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">
                                            <i class="bi bi-lock me-2"></i>Password
                                        </label>
                                        <input type="password" class="form-control" name="password" required placeholder="Enter your password">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary btn-login w-100 mt-3">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>Login as Student
                                    </button>
                                </form>
                                
                                <div class="demo-box mt-4">
                                    <h6 class="text-primary mb-2"><i class="bi bi-info-circle me-2"></i>Demo Credentials</h6>
                                    <p class="mb-1 small"><strong>Email:</strong> john.doe@university.edu</p>
                                    <p class="mb-0 small"><strong>Password:</strong> student123</p>
                                </div>
                            </div>
                            
                            <!-- Teacher Login -->
                            <div class="tab-pane fade" id="teacher">
                                <form action="login.php" method="POST">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                                    <input type="hidden" name="user_type" value="teacher">
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">
                                            <i class="bi bi-envelope me-2"></i>Email Address
                                        </label>
                                        <input type="email" class="form-control" name="email" required placeholder="your.email@university.edu">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label class="form-label fw-semibold">
                                            <i class="bi bi-lock me-2"></i>Password
                                        </label>
                                        <input type="password" class="form-control" name="password" required placeholder="Enter your password">
                                    </div>
                                    
                                    <button type="submit" class="btn btn-primary btn-login w-100 mt-3">
                                        <i class="bi bi-box-arrow-in-right me-2"></i>Login as Teacher
                                    </button>
                                </form>
                                
                                <div class="demo-box mt-4">
                                    <h6 class="text-primary mb-2"><i class="bi bi-info-circle me-2"></i>Demo Credentials</h6>
                                    <p class="mb-1 small"><strong>Email:</strong> john.smith@university.edu</p>
                                    <p class="mb-0 small"><strong>Password:</strong> teacher123</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mt-4">
                            <p class="mb-0">Don't have an account? <a href="signup.php" class="text-decoration-none fw-semibold">Sign Up Here</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>