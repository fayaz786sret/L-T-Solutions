<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
requireStudent();

$branch_id = $_SESSION['branch_id'];

$stats = [
    'materials' => $db->fetch("SELECT COUNT(*) as count FROM materials WHERE branch_id = ?", [$branch_id])['count'],
    'quizzes' => $db->fetch("SELECT COUNT(*) as count FROM quizzes WHERE branch_id = ?", [$branch_id])['count'],
    'tests' => $db->fetch("SELECT COUNT(*) as count FROM monthly_tests WHERE branch_id = ?", [$branch_id])['count'],
    'subjects' => $db->fetch("SELECT COUNT(*) as count FROM subjects WHERE branch_id = ?", [$branch_id])['count'],
];

$quiz_attempts = $db->fetch("SELECT COUNT(*) as count FROM quiz_attempts WHERE student_id = ?", [$_SESSION['user_id']])['count'];
$test_attempts = $db->fetch("SELECT COUNT(*) as count FROM test_attempts WHERE student_id = ?", [$_SESSION['user_id']])['count'];

$recent_quiz = $db->fetchAll("SELECT qa.*, q.title, q.chapter_id, c.chapter_name FROM quiz_attempts qa JOIN quizzes q ON qa.quiz_id = q.id JOIN chapters c ON q.chapter_id = c.id WHERE qa.student_id = ? AND q.branch_id = ? ORDER BY qa.attempted_at DESC LIMIT 5", [$_SESSION['user_id'], $branch_id]);

$recent_subjects = $db->fetchAll("SELECT s.*, (SELECT COUNT(*) FROM materials WHERE subject_id = s.id AND branch_id = ?) as mat_count, (SELECT COUNT(*) FROM quizzes WHERE subject_id = s.id AND branch_id = ?) as quiz_count FROM subjects s WHERE s.branch_id = ? ORDER BY s.subject_name", [$branch_id, $branch_id, $branch_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= getUrl('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container-fluid py-4">
        <div class="container">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="fw-bold mb-1"><i class="fas fa-home me-2 text-primary"></i>Welcome, <?= sanitize($_SESSION['user_name']) ?>!</h4>
                    <small class="text-muted d-block"><i class="fas fa-id-badge me-1"></i>Admission No: <?= sanitize($_SESSION['admission_no'] ?? '') ?></small>
                    <small class="text-muted"><i class="fas fa-sitemap me-1"></i><?= sanitize($_SESSION['branch_name']) ?> Branch</small>
                </div>
            </div>

            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm stat-card bg-primary">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h6 class="stat-label mb-1">Materials</h6><h3 class="fw-bold stat-value mb-0"><?= $stats['materials'] ?></h3></div>
                                <i class="fas fa-book-open fs-1 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm stat-card bg-success">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h6 class="stat-label mb-1">Quizzes</h6><h3 class="fw-bold stat-value mb-0"><?= $stats['quizzes'] ?></h3></div>
                                <i class="fas fa-question-circle fs-1 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm stat-card bg-warning">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h6 class="stat-label mb-1">Monthly Tests</h6><h3 class="fw-bold stat-value mb-0"><?= $stats['tests'] ?></h3></div>
                                <i class="fas fa-calendar-check fs-1 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm stat-card bg-info">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div><h6 class="stat-label mb-1">Subjects</h6><h3 class="fw-bold stat-value mb-0"><?= $stats['subjects'] ?></h3></div>
                                <i class="fas fa-book fs-1 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-md-8">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-3">
                            <h6 class="fw-bold mb-0"><i class="fas fa-book me-2 text-primary"></i>Your Subjects</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <?php foreach ($recent_subjects as $subj): ?>
                                <div class="col-md-6">
                                    <div class="card border shadow-sm h-100">
                                        <div class="card-body">
                                            <h6 class="fw-bold"><?= sanitize($subj['subject_name']) ?></h6>
                                            <div class="d-flex gap-2 small text-muted mb-2">
                                                <span><i class="fas fa-file-alt me-1"></i><?= $subj['mat_count'] ?> Materials</span>
                                                <span><i class="fas fa-question-circle me-1"></i><?= $subj['quiz_count'] ?> Quizzes</span>
                                            </div>
                                            <a href="materials.php?subject_id=<?= $subj['id'] ?>" class="btn btn-sm btn-outline-primary rounded-pill">View Materials</a>
                                            <a href="quizzes.php?subject_id=<?= $subj['id'] ?>" class="btn btn-sm btn-outline-success rounded-pill">Take Quiz</a>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php if (empty($recent_subjects)): ?>
                                <div class="col-12 text-center text-muted py-4">No subjects available for your branch yet.</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm mb-3">
                        <div class="card-header bg-transparent border-0 pt-3">
                            <h6 class="fw-bold mb-0"><i class="fas fa-chart-bar me-2 text-primary"></i>Your Progress</h6>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>Quizzes Attempted</span>
                                    <span class="fw-bold"><?= $quiz_attempts ?> / <?= $stats['quizzes'] ?></span>
                                </div>
                                <div class="progress" style="height:8px;">
                                    <div class="progress-bar bg-success" style="width: <?= $stats['quizzes'] > 0 ? ($quiz_attempts/$stats['quizzes'])*100 : 0 ?>%"></div>
                                </div>
                            </div>
                            <div>
                                <div class="d-flex justify-content-between small mb-1">
                                    <span>Tests Attempted</span>
                                    <span class="fw-bold"><?= $test_attempts ?> / <?= $stats['tests'] ?></span>
                                </div>
                                <div class="progress" style="height:8px;">
                                    <div class="progress-bar bg-warning" style="width: <?= $stats['tests'] > 0 ? ($test_attempts/$stats['tests'])*100 : 0 ?>%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <?php if ($recent_quiz): ?>
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-3">
                            <h6 class="fw-bold mb-0"><i class="fas fa-history me-2 text-primary"></i>Recent Activity</h6>
                        </div>
                        <div class="card-body p-0">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($recent_quiz as $rq): ?>
                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                    <div>
                                        <small class="fw-medium"><?= sanitize($rq['title']) ?></small><br>
                                        <small class="text-muted"><?= sanitize($rq['chapter_name']) ?></small>
                                    </div>
                                    <span class="badge bg-<?= $rq['score'] >= $rq['total_questions']/2 ? 'success' : 'danger' ?> rounded-pill">
                                        <?= $rq['score'] ?>/<?= $rq['total_questions'] ?>
                                    </span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
