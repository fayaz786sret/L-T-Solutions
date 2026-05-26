<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
requireAdmin();

$stats = [
    'students' => $db->fetch("SELECT COUNT(*) as count FROM students")['count'],
    'branches' => $db->fetch("SELECT COUNT(*) as count FROM branches")['count'],
    'subjects' => $db->fetch("SELECT COUNT(*) as count FROM subjects")['count'],
    'chapters' => $db->fetch("SELECT COUNT(*) as count FROM chapters")['count'],
    'materials' => $db->fetch("SELECT COUNT(*) as count FROM materials")['count'],
    'quizzes' => $db->fetch("SELECT COUNT(*) as count FROM quizzes")['count'],
    'tests' => $db->fetch("SELECT COUNT(*) as count FROM monthly_tests")['count'],
];
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
                <h4 class="fw-bold mb-0"><i class="fas fa-chart-pie me-2 text-primary"></i>Dashboard</h4>
                <span class="text-muted">Welcome, <strong><?= sanitize($_SESSION['user_name']) ?></strong></span>
            </div>

            <div class="row g-3">
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm stat-card stat-students">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="stat-label mb-1">Students</h6>
                                    <h3 class="fw-bold stat-value mb-0"><?= $stats['students'] ?></h3>
                                </div>
                                <i class="fas fa-user-graduate fs-1 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm stat-card stat-branches">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="stat-label mb-1">Branches</h6>
                                    <h3 class="fw-bold stat-value mb-0"><?= $stats['branches'] ?></h3>
                                </div>
                                <i class="fas fa-sitemap fs-1 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm stat-card stat-subjects">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="stat-label mb-1">Subjects</h6>
                                    <h3 class="fw-bold stat-value mb-0"><?= $stats['subjects'] ?></h3>
                                </div>
                                <i class="fas fa-book fs-1 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm stat-card stat-chapters">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="stat-label mb-1">Chapters</h6>
                                    <h3 class="fw-bold stat-value mb-0"><?= $stats['chapters'] ?></h3>
                                </div>
                                <i class="fas fa-list fs-1 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm stat-card stat-materials">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="stat-label mb-1">Materials</h6>
                                    <h3 class="fw-bold stat-value mb-0"><?= $stats['materials'] ?></h3>
                                </div>
                                <i class="fas fa-file-alt fs-1 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm stat-card stat-quizzes">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="stat-label mb-1">Quizzes</h6>
                                    <h3 class="fw-bold stat-value mb-0"><?= $stats['quizzes'] ?></h3>
                                </div>
                                <i class="fas fa-question-circle fs-1 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm stat-card stat-tests">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="stat-label mb-1">Monthly Tests</h6>
                                    <h3 class="fw-bold stat-value mb-0"><?= $stats['tests'] ?></h3>
                                </div>
                                <i class="fas fa-calendar-check fs-1 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card border-0 shadow-sm stat-card bg-secondary">
                        <div class="card-body p-3">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="stat-label mb-1">Total Items</h6>
                                    <h3 class="fw-bold stat-value mb-0"><?= array_sum($stats) ?></h3>
                                </div>
                                <i class="fas fa-database fs-1 stat-icon"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-3 mt-2">
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-3">
                            <h6 class="fw-bold mb-0"><i class="fas fa-bolt me-2 text-primary"></i>Quick Actions</h6>
                        </div>
                        <div class="card-body">
                            <div class="row g-2">
                                <div class="col-6">
                                    <a href="branches.php" class="btn btn-outline-primary w-100 text-start"><i class="fas fa-sitemap me-2"></i>Branches</a>
                                </div>
                                <div class="col-6">
                                    <a href="subjects.php" class="btn btn-outline-primary w-100 text-start"><i class="fas fa-book me-2"></i>Subjects</a>
                                </div>
                                <div class="col-6">
                                    <a href="chapters.php" class="btn btn-outline-primary w-100 text-start"><i class="fas fa-list me-2"></i>Chapters</a>
                                </div>
                                <div class="col-6">
                                    <a href="materials.php" class="btn btn-outline-primary w-100 text-start"><i class="fas fa-upload me-2"></i>Materials</a>
                                </div>
                                <div class="col-6">
                                    <a href="quizzes.php" class="btn btn-outline-primary w-100 text-start"><i class="fas fa-question-circle me-2"></i>Quizzes</a>
                                </div>
                                <div class="col-6">
                                    <a href="tests.php" class="btn btn-outline-primary w-100 text-start"><i class="fas fa-calendar-check me-2"></i>Tests</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-3">
                            <h6 class="fw-bold mb-0"><i class="fas fa-users me-2 text-primary"></i>Recent Students</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr><th>Admission No</th><th>Name</th><th>Email</th><th>Phone</th><th>Branch</th><th>Joined</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $students = $db->fetchAll("SELECT s.*, b.branch_name FROM students s JOIN branches b ON s.branch_id = b.id ORDER BY s.created_at DESC LIMIT 5");
                                        foreach ($students as $s): ?>
                                        <tr>
                                            <td><span class="badge bg-dark text-white"><?= sanitize($s['admission_no'] ?? '') ?></span></td>
                                            <td><?= sanitize($s['name']) ?></td>
                                            <td><?= sanitize($s['email']) ?></td>
                                            <td><?= sanitize($s['phone'] ?? '') ?></td>
                                            <td><span class="badge bg-primary-soft text-primary"><?= sanitize($s['branch_name']) ?></span></td>
                                            <td><small class="text-muted"><?= date('d M Y', strtotime($s['created_at'])) ?></small></td>
                                        </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($students)): ?>
                                        <tr><td colspan="6" class="text-center text-muted py-3">No students yet</td></tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
