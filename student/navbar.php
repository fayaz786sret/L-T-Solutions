<nav class="navbar navbar-expand-lg navbar-dark bg-gradient shadow-lg">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= getUrl('student/dashboard.php') ?>">
            <i class="fas fa-graduation-cap me-2"></i>L&T Solutions LearnHub
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#studentNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="studentNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= getUrl('student/dashboard.php') ?>"><i class="fas fa-home me-1"></i>Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= getUrl('student/materials.php') ?>"><i class="fas fa-book-open me-1"></i>Materials</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= getUrl('student/quizzes.php') ?>"><i class="fas fa-question-circle me-1"></i>Quizzes</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= getUrl('student/tests.php') ?>"><i class="fas fa-calendar-check me-1"></i>Monthly Tests</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-user me-1"></i><?= sanitize($_SESSION['user_name']) ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><span class="dropdown-item-text small text-muted">Admission No: <?= sanitize($_SESSION['admission_no'] ?? '') ?></span></li>
                        <li><span class="dropdown-item-text small text-muted"><?= sanitize($_SESSION['branch_name']) ?></span></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= getUrl('student/logout.php') ?>"><i class="fas fa-sign-out-alt me-2 text-danger"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>
