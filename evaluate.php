<?php
require_once 'config.php';

$submission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($submission_id == 0) die("Error: Invalid submission ID");

$conn = getDBConnection();
$stmt = $conn->prepare("SELECT s.*, a.title, a.description FROM submissions s JOIN assignments a ON s.assignment_id = a.id WHERE s.id = ?");
$stmt->bind_param("i", $submission_id);
$stmt->execute();
$submission = $stmt->get_result()->fetch_assoc();
if (!$submission) die("Error: Submission not found");
$stmt->close();
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>AI Evaluation</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Poppins', sans-serif; }
        body { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); min-height: 100vh; display: flex; align-items: center; }
        .eval-card { background: white; border-radius: 20px; box-shadow: 0 20px 60px rgba(0,0,0,0.3); padding: 40px; }
        .spinner { width: 60px; height: 60px; border: 6px solid #f3f3f3; border-top: 6px solid #6366f1; border-radius: 50%; animation: spin 1s linear infinite; margin: 0 auto; }
        @keyframes spin { 0% { transform: rotate(0deg); } 100% { transform: rotate(360deg); } }
        .step { padding: 15px; background: #f5f5f5; border-radius: 10px; margin: 10px 0; transition: all 0.5s; }
        .step.active { background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; transform: scale(1.05); }
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
                    <div id="evaluation-result" style="display: none;"></div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const submissionData = <?php echo json_encode([
            'submission_id' => $submission_id,
            'assignment_title' => $submission['title'],
            'assignment_description' => $submission['description'],
            'student_name' => $submission['student_name'],
            'student_id' => $submission['student_id'],
            'file_path' => $submission['file_path'],
            'file_type' => $submission['file_type']
        ], JSON_HEX_TAG | JSON_HEX_AMP); ?>;
        
        let step = 0;
        const steps = document.querySelectorAll('.step');
        setInterval(() => { if (step < steps.length) steps[step++].classList.add('active'); }, 2000);
        
        fetch('ai_evaluate.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify(submissionData)
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                const e = data.evaluation;
                document.getElementById('loading').style.display = 'none';
                document.getElementById('evaluation-result').style.display = 'block';
                document.getElementById('evaluation-result').innerHTML = `
                    <div class="text-center mb-4">
                        <h3>✅ Evaluation Complete</h3>
                        <div class="d-flex justify-content-center align-items-center gap-4 my-4">
                            <div style="width: 100px; height: 100px; border-radius: 50%; background: linear-gradient(135deg, #6366f1, #8b5cf6); color: white; display: flex; align-items: center; justify-content: center; font-size: 3rem; font-weight: bold;">${e.grade}</div>
                            <div style="font-size: 2.5rem; font-weight: bold;">${e.overall_score}/100</div>
                        </div>
                    </div>
                    <div class="alert alert-${e.ai_probability > 70 ? 'danger' : e.ai_probability > 40 ? 'warning' : 'success'}">
                        <h5>🤖 AI Detection: ${e.ai_probability}%</h5>
                        <p class="mb-0">${e.ai_analysis}</p>
                    </div>
                    <div class="mb-3">
                        <h5>💪 Strengths</h5>
                        <ul class="list-group">${e.strengths.map(s => '<li class="list-group-item">'+s+'</li>').join('')}</ul>
                    </div>
                    <div class="mb-3">
                        <h5>⚠️ Improvements Needed</h5>
                        <ul class="list-group">${e.weaknesses.map(w => '<li class="list-group-item">'+w+'</li>').join('')}</ul>
                    </div>
                    <div class="text-center mt-4">
                        <a href="teacher_dashboard.php" class="btn btn-primary">Back to Dashboard</a>
                    </div>
                `;
            } else {
                document.getElementById('loading').innerHTML = '<div class="alert alert-danger">❌ ' + data.error + '</div>';
            }
        })
        .catch(err => {
            document.getElementById('loading').innerHTML = '<div class="alert alert-danger">❌ Evaluation failed: ' + err.message + '</div>';
        });
    </script>
</body>
</html>