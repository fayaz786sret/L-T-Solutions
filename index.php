<?php require_once 'includes/config.php'; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= SITE_NAME ?> - Learning Platform</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= getUrl('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="bg-light">
    <nav class="navbar navbar-expand-lg navbar-dark bg-gradient shadow-lg">
        <div class="container">
            <a class="navbar-brand fw-bold fs-4" href="<?= getUrl() ?>">
                <i class="fas fa-graduation-cap me-2"></i><?= SITE_NAME ?>
            </a>
            <div class="ms-auto">
                <a href="<?= getUrl('login.php') ?>" class="btn btn-light btn-sm me-2 rounded-pill px-3">
                    <i class="fas fa-user me-1"></i>Student
                </a>
                <a href="<?= getUrl('login.php') ?>" class="btn btn-outline-light btn-sm rounded-pill px-3">
                    <i class="fas fa-shield-alt me-1"></i>Admin
                </a>
            </div>
        </div>
    </nav>

    <section class="hero-section d-flex align-items-center">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="badge bg-white text-primary mb-3 px-3 py-2 fs-6 rounded-pill">
                        <i class="fas fa-rocket me-1"></i> Empowering Education
                    </span>
                    <h1 class="display-4 fw-bold text-white mb-3">Learn Smarter,<br><span class="text-warning">Achieve Greater</span></h1>
                    <p class="lead text-white-50 mb-4">A comprehensive learning platform with branch-wise materials, chapter-wise quizzes, and monthly assessments to help you excel.</p>
                    <div class="d-flex gap-3 flex-wrap">
                        <a href="<?= getUrl('student/register.php') ?>" class="btn btn-warning btn-lg rounded-pill px-4 fw-bold shadow">
                            <i class="fas fa-user-plus me-2"></i>Get Started
                        </a>
                        <a href="<?= getUrl('login.php') ?>" class="btn btn-outline-light btn-lg rounded-pill px-4 fw-bold">
                            <i class="fas fa-sign-in-alt me-2"></i>Student Login
                        </a>
                    </div>
                </div>
                <div class="col-lg-6 text-center">
                    <img src="https://cdn-icons-png.flaticon.com/512/2436/2436872.png" alt="Learning" class="img-fluid hero-img" style="max-width: 400px; filter: drop-shadow(0 10px 30px rgba(0,0,0,0.3));">
                </div>
            </div>
        </div>
        <div class="hero-shape"></div>
    </section>

    <section class="py-5">
        <div class="container">
            <div class="row g-4">
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 feature-card">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-primary-soft mx-auto mb-3">
                                <i class="fas fa-book-open text-primary fs-3"></i>
                            </div>
                            <h5 class="fw-bold">Branch-wise Materials</h5>
                            <p class="text-muted mb-0">Access study materials tailored to your branch with subject and chapter-wise organization.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 feature-card">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-success-soft mx-auto mb-3">
                                <i class="fas fa-question-circle text-success fs-3"></i>
                            </div>
                            <h5 class="fw-bold">Chapter Quizzes</h5>
                            <p class="text-muted mb-0">Test your knowledge after each chapter with quizzes designed to reinforce learning.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card border-0 shadow-sm h-100 feature-card">
                        <div class="card-body text-center p-4">
                            <div class="feature-icon bg-warning-soft mx-auto mb-3">
                                <i class="fas fa-calendar-alt text-warning fs-3"></i>
                            </div>
                            <h5 class="fw-bold">Monthly Tests</h5>
                            <p class="text-muted mb-0">Regular monthly assessments to track your progress and prepare for exams.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <section class="py-5 bg-white">
        <div class="container text-center">
            <h2 class="fw-bold mb-4">How It Works</h2>
            <div class="row g-4 mt-3">
                <div class="col-md-3">
                    <div class="step-circle mx-auto mb-3">1</div>
                    <h6 class="fw-bold">Register</h6>
                    <small class="text-muted">Sign up with your branch</small>
                </div>
                <div class="col-md-3">
                    <div class="step-circle mx-auto mb-3">2</div>
                    <h6 class="fw-bold">Learn</h6>
                    <small class="text-muted">Access study materials</small>
                </div>
                <div class="col-md-3">
                    <div class="step-circle mx-auto mb-3">3</div>
                    <h6 class="fw-bold">Practice</h6>
                    <small class="text-muted">Take chapter quizzes</small>
                </div>
                <div class="col-md-3">
                    <div class="step-circle mx-auto mb-3">4</div>
                    <h6 class="fw-bold">Assess</h6>
                    <small class="text-muted">Monthly tests</small>
                </div>
            </div>
        </div>
    </section>

    <footer class="bg-dark text-white-50 py-4">
        <div class="container text-center">
            <small>&copy; <?= date('Y') ?> <?= SITE_NAME ?>. All rights reserved.</small>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
