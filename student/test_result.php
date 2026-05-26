<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
requireStudent();

$test_id = (int)($_GET['id'] ?? 0);
$student_id = $_SESSION['user_id'];
$branch_id = $_SESSION['branch_id'];

$test = $db->fetch("SELECT t.*, s.subject_name FROM monthly_tests t JOIN subjects s ON t.subject_id = s.id WHERE t.id = ? AND t.branch_id = ?", [$test_id, $branch_id]);
if (!$test) { flashMessage('danger', 'Test not found.'); redirect('student/tests.php'); }

$attempt = $db->fetch("SELECT * FROM test_attempts WHERE test_id = ? AND student_id = ?", [$test_id, $student_id]);
if (!$attempt) { flashMessage('danger', 'You have not attempted this test.'); redirect('student/tests.php'); }

$questions = $db->fetchAll("SELECT * FROM test_questions WHERE test_id = ?", [$test_id]);
$percentage = $attempt['total_questions'] > 0 ? round(($attempt['score'] / $attempt['total_questions']) * 100) : 0;
$passed = $percentage >= 40;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Result - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= getUrl('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container-fluid py-4">
        <div class="container">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body text-center py-5">
                    <div class="mb-3">
                        <div class="result-circle mx-auto <?= $passed ? 'bg-success-soft' : 'bg-danger-soft' ?>">
                            <i class="fas <?= $passed ? 'fa-check-circle text-success' : 'fa-times-circle text-danger' ?> fs-1"></i>
                        </div>
                    </div>
                    <h3 class="fw-bold"><?= $passed ? 'Congratulations!' : 'Better Luck Next Time!' ?></h3>
                    <p class="text-muted"><?= sanitize($test['title']) ?></p>
                    <div class="d-flex justify-content-center gap-4 my-4">
                        <div><div class="fs-1 fw-bold text-success"><?= $attempt['score'] ?></div><small class="text-muted">Correct</small></div>
                        <div class="border-start ps-4"><div class="fs-1 fw-bold text-danger"><?= $attempt['total_questions'] - $attempt['score'] ?></div><small class="text-muted">Wrong</small></div>
                        <div class="border-start ps-4"><div class="fs-1 fw-bold text-primary"><?= $attempt['total_questions'] ?></div><small class="text-muted">Total</small></div>
                        <div class="border-start ps-4"><div class="fs-1 fw-bold <?= $passed ? 'text-success' : 'text-danger' ?>"><?= $percentage ?>%</div><small class="text-muted">Percentage</small></div>
                    </div>
                    <a href="tests.php" class="btn btn-primary rounded-pill px-4"><i class="fas fa-arrow-left me-2"></i>Back to Tests</a>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h6 class="fw-bold"><i class="fas fa-list-ol me-2 text-primary"></i>Answer Review</h6>
                </div>
                <div class="card-body">
                    <?php foreach ($questions as $i => $q): ?>
                    <div class="border-bottom pb-3 mb-3">
                        <h6 class="fw-bold">Q<?= $i + 1 ?>. <?= sanitize($q['question']) ?></h6>
                        <div class="row g-1">
                            <?php foreach (['A', 'B', 'C', 'D'] as $opt): ?>
                            <div class="col-sm-6">
                                <span class="badge bg-<?= strtoupper($q['correct_answer']) === $opt ? 'success' : 'light' ?> text-<?= strtoupper($q['correct_answer']) === $opt ? 'white' : 'dark' ?> p-2 w-100 text-start">
                                    <?= $opt ?>. <?= sanitize($q['option_' . strtolower($opt)]) ?>
                                    <?php if (strtoupper($q['correct_answer']) === $opt): ?><i class="fas fa-check-circle ms-1"></i><?php endif; ?>
                                </span>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
    <style>
    .result-circle { width: 80px; height: 80px; border-radius: 50%; display: flex; align-items: center; justify-content: center; }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
