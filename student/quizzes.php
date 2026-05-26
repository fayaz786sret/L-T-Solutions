<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
requireStudent();

$branch_id = $_SESSION['branch_id'];
$student_id = $_SESSION['user_id'];
$subject_id = (int)($_GET['subject_id'] ?? 0);

$subjects = $db->fetchAll("SELECT * FROM subjects WHERE branch_id = ? ORDER BY subject_name", [$branch_id]);
$conditions = "WHERE q.branch_id = ?";
$params = [$branch_id];

if ($subject_id > 0) {
    $subject = $db->fetch("SELECT id FROM subjects WHERE id = ? AND branch_id = ?", [$subject_id, $branch_id]);
    if ($subject) {
        $conditions .= " AND q.subject_id = ?";
        $params[] = $subject_id;
    }
}

$quizQuery = "SELECT q.*, c.chapter_name, s.subject_name,
    (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.id) AS question_count,
    (SELECT id FROM quiz_attempts WHERE quiz_id = q.id AND student_id = ?) AS attempted
    FROM quizzes q
    JOIN chapters c ON q.chapter_id = c.id
    JOIN subjects s ON q.subject_id = s.id
    $conditions
    ORDER BY q.created_at DESC";

$quizzes = $db->fetchAll($quizQuery, array_merge([$student_id], $params));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzes - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= getUrl('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container-fluid py-4">
        <div class="container">
            <h4 class="fw-bold mb-4"><i class="fas fa-question-circle me-2 text-primary"></i>Chapter Quizzes</h4>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label fw-medium small">Subject</label>
                            <select name="subject_id" class="form-select" onchange="this.form.submit()">
                                <option value="">All Subjects</option>
                                <?php foreach ($subjects as $subject): ?>
                                <option value="<?= $subject['id'] ?>" <?= $subject_id === (int)$subject['id'] ? 'selected' : '' ?>>
                                    <?= sanitize($subject['subject_name']) ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <a href="quizzes.php" class="btn btn-outline-secondary w-100 rounded-pill">Reset</a>
                        </div>
                    </form>
                </div>
            </div>
            <div class="row g-3">
                <?php foreach ($quizzes as $q): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100">
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <h6 class="fw-bold mb-0"><?= sanitize($q['title']) ?></h6>
                                <?php if ($q['attempted']): ?>
                                <span class="badge bg-success-soft text-success rounded-pill"><i class="fas fa-check me-1"></i>Done</span>
                                <?php endif; ?>
                            </div>
                            <p class="small text-muted mb-2">
                                <i class="fas fa-book me-1"></i><?= sanitize($q['subject_name']) ?>
                                <i class="fas fa-chevron-right mx-1" style="font-size:10px;"></i>
                                <?= sanitize($q['chapter_name']) ?>
                            </p>
                            <p class="small text-muted mb-3">
                                <i class="fas fa-list-ol me-1"></i><?= $q['question_count'] ?> Questions
                                <?php if ($q['description']): ?> | <?= sanitize($q['description']) ?><?php endif; ?>
                            </p>
                            <?php if ($q['attempted']): ?>
                                <?php $attempt = $db->fetch("SELECT * FROM quiz_attempts WHERE quiz_id = ? AND student_id = ?", [$q['id'], $student_id]); ?>
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="fw-bold text-<?= $attempt['score'] >= $attempt['total_questions']/2 ? 'success' : 'danger' ?>">
                                        Score: <?= $attempt['score'] ?>/<?= $attempt['total_questions'] ?>
                                    </span>
                                    <a href="quiz_result.php?id=<?= $q['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill">View Result</a>
                                </div>
                            <?php else: ?>
                            <a href="take_quiz.php?id=<?= $q['id'] ?>" class="btn btn-primary btn-sm rounded-pill w-100">
                                <i class="fas fa-play me-1"></i>Start Quiz
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($quizzes)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-question-circle fs-1 text-muted mb-3"></i>
                    <p class="text-muted">No quizzes available for your branch yet.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
