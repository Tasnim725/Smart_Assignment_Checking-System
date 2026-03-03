<?php
require_once 'config.php';
$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sign Up - Smart Assignment Checker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root { --primary: #6366f1; --secondary: #8b5cf6; }
        * { font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; padding: 40px 0; }
        .signup-container { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); overflow: hidden; }
        .signup-header { background: linear-gradient(135deg, var(--primary), var(--secondary)); color: white; padding: 40px; text-align: center; }
        .signup-header h1 { font-size: 2rem; font-weight: 700; }
        .nav-pills .nav-link { border-radius: 10px; font-weight: 500; padding: 12px 30px; }
        .nav-pills .nav-link:not(.active) { color: #6b7280; background: #f3f4f6; }
        .nav-pills .nav-link.active { background: linear-gradient(135deg, var(--primary), var(--secondary)); }
        .form-control { border-radius: 10px; padding: 12px 16px; border: 2px solid #e5e7eb; }
        .form-control:focus { border-color: var(--primary); box-shadow: 0 0 0 0.2rem rgba(99,102,241,0.15); }
        .btn-signup { background: linear-gradient(135deg, var(--primary), var(--secondary)); border: none; border-radius: 10px; padding: 14px; font-weight: 600; }
        .btn-signup:hover { transform: translateY(-2px); box-shadow: 0 10px 25px rgba(99,102,241,0.3); }
    </style>
</head>
<body>
<div class="container">
    <?php if (isset($_GET['error'])): ?>
    <div class="row mb-3"><div class="col-lg-10 mx-auto">
        <div class="alert alert-danger alert-dismissible fade show">
            <?php
            if ($_GET['error'] == 'empty')  echo "All fields are required.";
            if ($_GET['error'] == 'exists') echo "Email already exists.";
            if ($_GET['error'] == 'fail')   echo "Registration failed. Please try again.";
            ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    </div></div>
    <?php endif; ?>

    <div class="row"><div class="col-lg-10 mx-auto">
        <div class="signup-container">
            <div class="signup-header">
                <h1><i class="bi bi-person-plus-fill me-2"></i>Create Account</h1>
                <p class="mb-0">Join Smart Assignment Checker</p>
            </div>
            <div class="p-4 p-md-5">
                <ul class="nav nav-pills mb-4 d-flex justify-content-center">
                    <li class="nav-item me-2">
                        <button class="nav-link active" data-bs-toggle="pill" data-bs-target="#student" type="button">
                            <i class="bi bi-person-fill me-2"></i>Student
                        </button>
                    </li>
                    <li class="nav-item">
                        <button class="nav-link" data-bs-toggle="pill" data-bs-target="#teacher" type="button">
                            <i class="bi bi-person-badge-fill me-2"></i>Teacher
                        </button>
                    </li>
                </ul>
                <div class="tab-content">
                    <!-- Student Signup -->
                    <div class="tab-pane fade show active" id="student">
                        <form action="register.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="user_type" value="student">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Full Name *</label>
                                    <input type="text" class="form-control" name="name" required placeholder="John Doe">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Email Address *</label>
                                    <input type="email" class="form-control" name="email" required placeholder="john@university.edu">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Student ID *</label>
                                    <input type="text" class="form-control" name="user_code" required placeholder="CS2024001">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Batch Number *</label>
                                    <input type="text" class="form-control" name="batch_number" required placeholder="Batch-2024">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Department *</label>
                                    <input type="text" class="form-control" name="department" required placeholder="Computer Science">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Password *</label>
                                    <input type="password" class="form-control" name="password" required minlength="6" placeholder="Min. 6 characters">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-primary btn-signup w-100 mt-2">
                                <i class="bi bi-check-circle me-2"></i>Create Student Account
                            </button>
                        </form>
                    </div>
                    <!-- Teacher Signup -->
                    <div class="tab-pane fade" id="teacher">
                        <form action="register.php" method="POST">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            <input type="hidden" name="user_type" value="teacher">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Full Name *</label>
                                    <input type="text" class="form-control" name="name" required placeholder="Dr. John Smith">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Email Address *</label>
                                    <input type="email" class="form-control" name="email" required placeholder="john@university.edu">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Teacher ID *</label>
                                    <input type="text" class="form-control" name="user_code" required placeholder="T001">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label class="form-label fw-semibold">Department *</label>
                                    <input type="text" class="form-control" name="department" required placeholder="Computer Science">
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Password *</label>
                                <input type="password" class="form-control" name="password" required minlength="6" placeholder="Minimum 6 characters">
                            </div>
                            <button type="submit" class="btn btn-primary btn-signup w-100 mt-2">
                                <i class="bi bi-check-circle me-2"></i>Create Teacher Account
                            </button>
                        </form>
                    </div>
                </div>
                <div class="text-center mt-4">
                    <p class="mb-0">Already have an account? <a href="index.php" class="text-decoration-none fw-semibold">Login Here</a></p>
                </div>
            </div>
        </div>
    </div></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
