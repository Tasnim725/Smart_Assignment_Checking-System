<?php
require_once '../config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'teacher') {
    header("Location: ../index.php?error=session"); exit();
}

$submission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($submission_id == 0) die("Error: Invalid submission ID");

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT s.*, a.title, a.description FROM submissions s JOIN assignments a ON s.assignment_id = a.id WHERE s.id = ?");
$stmt->bind_param("i", $submission_id); $stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();
if (!$submission) die("Error: Submission not found");
$stmt->close(); $conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Evaluation - Smart Assignment Checker</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../style.css">
    <style>
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
        .eval-card { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 40px; }
    </style>
</head>
<body>
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="eval-card">
                <div id="loading" class="text-center">
                    <div class="spinner"></div>
                    <h2 class="mt-4">🤖 AI Evaluation in Progress</h2>
                    <p class="text-muted">Please wait while we analyze the submission...</p>
                    <div class="mt-4">
                        <div class="step active">📄 Reading document</div>
                        <div class="step">🔍 Analyzing content</div>
                        <div class="step">🤖 Detecting AI usage</div>
                        <div class="step">✅ Generating feedback</div>
                    </div>
                </div>
                <div id="evaluation-result" style="display:none;"></div>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script>
const submissionData = <?php echo json_encode([
    'submission_id'          => $submission_id,
    'assignment_title'       => $submission['title'],
    'assignment_description' => $submission['description'],
    'student_name'           => $submission['student_name'],
    'student_id'             => $submission['student_id'],
    'file_path'              => $submission['file_path'],
    'file_type'              => $submission['file_type']
], JSON_HEX_TAG | JSON_HEX_AMP); ?>;

let step = 0;
const steps = document.querySelectorAll('.step');
const stepTimer = setInterval(() => { if (step < steps.length) steps[step++].classList.add('active'); else clearInterval(stepTimer); }, 2000);

fetch('../ai_evaluate.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify(submissionData)
})
.then(r => r.json())
.then(data => {
    clearInterval(stepTimer);
    if (data.success) {
        const e = data.evaluation;
        document.getElementById('loading').style.display = 'none';
        document.getElementById('evaluation-result').style.display = 'block';
        document.getElementById('evaluation-result').innerHTML = `
            <div class="text-center mb-4">
                <h3>✅ Evaluation Complete</h3>
                <div class="d-flex justify-content-center align-items-center gap-4 my-4">
                    <div class="grade-circle">${e.grade}</div>
                    <div style="font-size:2.5rem;font-weight:bold;">${e.overall_score}/100</div>
                </div>
            </div>
            <div class="alert alert-${e.ai_probability > 70 ? 'danger' : e.ai_probability > 40 ? 'warning' : 'success'}">
                <h5>🤖 AI Detection: ${e.ai_probability}%</h5>
                <p class="mb-0">${e.ai_analysis}</p>
            </div>
            <div class="mb-3">
                <h5>💪 Strengths</h5>
                <ul class="feedback-list strengths">${e.strengths.map(s => '<li>' + s + '</li>').join('')}</ul>
            </div>
            <div class="mb-3">
                <h5>⚠️ Areas for Improvement</h5>
                <ul class="feedback-list weaknesses">${e.weaknesses.map(w => '<li>' + w + '</li>').join('')}</ul>
            </div>
            <div class="text-center mt-4">
                <a href="../shared/view_details.php?id=${submissionData.submission_id}" class="btn btn-primary me-2">View Full Details</a>
                <a href="teacher_dashboard.php" class="btn btn-outline-secondary">Back to Dashboard</a>
            </div>
        `;
    } else {
        document.getElementById('loading').innerHTML = '<div class="alert alert-danger">❌ ' + data.error + '</div><div class="text-center mt-3"><a href="teacher_dashboard.php" class="btn btn-primary">Back to Dashboard</a></div>';
    }
})
.catch(err => {
    document.getElementById('loading').innerHTML = '<div class="alert alert-danger">❌ Request failed: ' + err.message + '</div><div class="text-center mt-3"><a href="teacher_dashboard.php" class="btn btn-primary">Back to Dashboard</a></div>';
});
</script>
</body>
</html>
