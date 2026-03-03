<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: ../index.php?error=session"); exit();
}

$conn         = getDBConnection();
$student_id   = $_SESSION['user_code'];
$batch_number = isset($_SESSION['batch_number']) ? $_SESSION['batch_number'] : '';

// Available assignments for this batch
$stmt = $conn->prepare("SELECT * FROM assignments WHERE deadline >= NOW() AND batch_number = ? ORDER BY deadline ASC");
$stmt->bind_param("s", $batch_number); $stmt->execute(); $assignments = $stmt->get_result(); $stmt->close();

// Recent submissions
$stmt = $conn->prepare("SELECT s.*, a.title as assignment_title FROM submissions s JOIN assignments a ON s.assignment_id = a.id WHERE s.student_id = ? ORDER BY s.submitted_at DESC");
$stmt->bind_param("s", $student_id); $stmt->execute(); $submissions = $stmt->get_result(); $stmt->close();

// Stats
$stmt = $conn->prepare("SELECT COUNT(*) as total_submissions, COUNT(CASE WHEN evaluation_status='evaluated' THEN 1 END) as evaluated, AVG(CASE WHEN overall_score IS NOT NULL THEN overall_score END) as avg_score FROM submissions WHERE student_id = ?");
$stmt->bind_param("s", $student_id); $stmt->execute(); $stats = $stmt->get_result()->fetch_assoc(); $stmt->close();

$csrf_token = generate_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Dashboard - Smart Assignment Checker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-light sticky-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="student_dashboard.php"><i class="bi bi-mortarboard-fill me-2"></i>Smart Assignment Checker</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav"><span class="navbar-toggler-icon"></span></button>
        <div class="collapse navbar-collapse" id="nav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item"><a class="nav-link active" href="student_dashboard.php"><i class="bi bi-house-fill me-1"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="student_submissions.php"><i class="bi bi-file-text me-1"></i>My Submissions</a></li>
                <li class="nav-item"><span class="nav-link"><i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?></span></li>
                <li class="nav-item"><a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col">
            <h2 class="mb-1">Welcome, <?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?>! 👋</h2>
            <p class="text-muted mb-0">
                <i class="bi bi-person-badge me-2"></i><?php echo htmlspecialchars($_SESSION['user_code'], ENT_QUOTES, 'UTF-8'); ?>
                <i class="bi bi-calendar-event ms-3 me-2"></i>Batch: <?php echo htmlspecialchars($batch_number, ENT_QUOTES, 'UTF-8'); ?>
            </p>
        </div>
    </div>

    <?php if (isset($_GET['success']) && $_GET['success'] == 'submitted'): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i>Assignment submitted successfully! Your teacher will evaluate it soon.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3"><div class="stat-card"><div class="stat-number"><?php echo $stats['total_submissions']; ?></div><div class="text-muted">Total Submissions</div></div></div>
        <div class="col-md-4 mb-3"><div class="stat-card"><div class="stat-number"><?php echo $stats['evaluated']; ?></div><div class="text-muted">Evaluated</div></div></div>
        <div class="col-md-4 mb-3"><div class="stat-card"><div class="stat-number"><?php echo $stats['avg_score'] ? round($stats['avg_score']) : '-'; ?></div><div class="text-muted">Average Score</div></div></div>
    </div>

    <div class="row">
        <!-- Submit Form -->
        <div class="col-lg-5 mb-4">
            <div class="card">
                <div class="card-header"><i class="bi bi-cloud-upload me-2"></i>Submit New Assignment</div>
                <div class="card-body">
                    <form action="submit_assignment.php" method="POST" enctype="multipart/form-data">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                        <input type="hidden" name="student_id" value="<?php echo $_SESSION['user_code']; ?>">
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Select Assignment *</label>
                            <select name="assignment_id" class="form-select" required>
                                <option value="">-- Choose Assignment --</option>
                                <?php $assignments->data_seek(0); while ($a = $assignments->fetch_assoc()): ?>
                                <option value="<?php echo $a['id']; ?>">
                                    <?php echo htmlspecialchars($a['title'], ENT_QUOTES, 'UTF-8') . ' (Due: ' . date('M d, Y', strtotime($a['deadline'])) . ')'; ?>
                                </option>
                                <?php endwhile; ?>
                            </select>
                            <?php if ($assignments->num_rows == 0): ?>
                            <small class="text-danger">No assignments available for your batch (<?php echo htmlspecialchars($batch_number, ENT_QUOTES, 'UTF-8'); ?>).</small>
                            <?php endif; ?>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-semibold">Upload File (PDF or Image) *</label>
                            <input type="file" name="assignment_file" class="form-control" accept=".pdf,image/*" required>
                            <small class="text-muted">Max: 10MB. Formats: PDF, JPG, PNG</small>
                        </div>
                        <button type="submit" class="btn btn-primary w-100" <?php echo $assignments->num_rows == 0 ? 'disabled' : ''; ?>>
                            <i class="bi bi-send me-2"></i>Submit Assignment
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Submissions -->
        <div class="col-lg-7 mb-4">
            <div class="card">
                <div class="card-header"><i class="bi bi-clock-history me-2"></i>Recent Submissions</div>
                <div class="card-body p-0">
                    <?php if ($submissions->num_rows > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead><tr><th>Assignment</th><th>Submitted</th><th>Status</th><th>Score</th><th>Grade</th><th>Action</th></tr></thead>
                            <tbody>
                            <?php $submissions->data_seek(0); $c = 0; while ($sub = $submissions->fetch_assoc()): if ($c >= 5) break; $c++; ?>
                            <tr>
                                <td><?php echo htmlspecialchars($sub['assignment_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?php echo date('M d, Y', strtotime($sub['submitted_at'])); ?></td>
                                <td><span class="status-badge status-<?php echo $sub['evaluation_status']; ?>"><?php echo ucfirst($sub['evaluation_status']); ?></span></td>
                                <td><strong><?php echo $sub['overall_score'] !== null ? $sub['overall_score'] : '-'; ?></strong></td>
                                <td><strong><?php echo $sub['grade'] !== null ? htmlspecialchars($sub['grade'], ENT_QUOTES, 'UTF-8') : '-'; ?></strong></td>
                                <td>
                                    <?php if ($sub['evaluation_status'] == 'evaluated'): ?>
                                    <a href="../shared/view_details.php?id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-primary"><i class="bi bi-eye"></i></a>
                                    <?php else: ?><span class="text-muted small">Pending...</span><?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                    <div class="card-footer bg-white text-center">
                        <a href="student_submissions.php" class="btn btn-outline-primary btn-sm">View All Submissions <i class="bi bi-arrow-right ms-1"></i></a>
                    </div>
                    <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bi bi-inbox" style="font-size:3rem;color:#d1d5db;"></i>
                        <h5 class="mt-3">No Submissions Yet</h5>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
