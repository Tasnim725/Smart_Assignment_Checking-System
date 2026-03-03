<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'student') {
    header("Location: ../index.php?error=session"); exit();
}

$conn       = getDBConnection();
$student_id = $_SESSION['user_code'];

$stmt = $conn->prepare("SELECT s.*, a.title as assignment_title FROM submissions s JOIN assignments a ON s.assignment_id = a.id WHERE s.student_id = ? ORDER BY s.submitted_at DESC");
$stmt->bind_param("s", $student_id); $stmt->execute(); $submissions = $stmt->get_result(); $stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Submissions - Smart Assignment Checker</title>
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
                <li class="nav-item"><a class="nav-link" href="student_dashboard.php"><i class="bi bi-house-fill me-1"></i>Dashboard</a></li>
                <li class="nav-item"><a class="nav-link active" href="student_submissions.php"><i class="bi bi-file-text me-1"></i>My Submissions</a></li>
                <li class="nav-item"><span class="nav-link"><i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['user_name'], ENT_QUOTES, 'UTF-8'); ?></span></li>
                <li class="nav-item"><a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a></li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid py-4">
    <div class="row mb-4"><div class="col"><h2><i class="bi bi-file-earmark-text me-2"></i>My Submissions</h2></div></div>

    <?php if (isset($_GET['success']) && $_GET['success'] == 'submitted'): ?>
    <div class="alert alert-success alert-dismissible fade show">
        <i class="bi bi-check-circle-fill me-2"></i>Assignment submitted successfully!
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <div class="card">
        <div class="card-header"><i class="bi bi-list-check me-2"></i>All Submissions</div>
        <div class="card-body p-0">
            <?php if ($submissions->num_rows > 0): ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead><tr><th>Assignment</th><th>Submitted</th><th>Status</th><th>Score</th><th>Grade</th><th>AI %</th><th>Action</th></tr></thead>
                    <tbody>
                    <?php while ($sub = $submissions->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($sub['assignment_title'], ENT_QUOTES, 'UTF-8'); ?></td>
                        <td><?php echo date('M d, Y H:i', strtotime($sub['submitted_at'])); ?></td>
                        <td><span class="status-badge status-<?php echo $sub['evaluation_status']; ?>"><?php echo ucfirst($sub['evaluation_status']); ?></span></td>
                        <td><strong><?php echo $sub['overall_score'] !== null ? $sub['overall_score'] : '-'; ?>/100</strong></td>
                        <td><strong class="text-primary"><?php echo $sub['grade'] !== null ? htmlspecialchars($sub['grade'], ENT_QUOTES, 'UTF-8') : '-'; ?></strong></td>
                        <td>
                            <?php if ($sub['ai_probability'] !== null): ?>
                            <span class="ai-badge <?php echo $sub['ai_probability'] > 70 ? 'high' : ($sub['ai_probability'] > 40 ? 'medium' : 'low'); ?>">
                                <?php echo $sub['ai_probability']; ?>%
                            </span>
                            <?php else: ?>-<?php endif; ?>
                        </td>
                        <td>
                            <?php if ($sub['evaluation_status'] == 'evaluated'): ?>
                            <a href="../shared/view_details.php?id=<?php echo $sub['id']; ?>" class="btn btn-sm btn-primary">
                                <i class="bi bi-eye me-1"></i>View
                            </a>
                            <?php else: ?><span class="text-muted"><i class="bi bi-clock me-1"></i>Pending</span><?php endif; ?>
                        </td>
                    </tr>
                    <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="text-center py-5">
                <i class="bi bi-inbox" style="font-size:3rem;color:#d1d5db;"></i>
                <h5 class="mt-3">No Submissions Yet</h5>
                <a href="student_dashboard.php" class="btn btn-primary mt-3"><i class="bi bi-arrow-left me-2"></i>Go to Dashboard</a>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php $conn->close(); ?>
