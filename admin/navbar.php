<nav class="navbar navbar-expand-lg navbar-dark bg-gradient shadow-lg">
    <div class="container">
        <a class="navbar-brand fw-bold" href="<?= getUrl('admin/dashboard.php') ?>">
            <i class="fas fa-graduation-cap me-2"></i>L&T Solutions LearnHub <span class="badge bg-warning text-dark ms-1">Admin</span>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#adminNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="adminNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?= getUrl('admin/change_password.php') ?>"><i class="fas fa-key me-1"></i>Change Password</a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="#" data-bs-toggle="dropdown">
                        <i class="fas fa-cog me-1"></i>Manage
                    </a>
                    <ul class="dropdown-menu">
                        <li><a class="dropdown-item" href="<?= getUrl('admin/branches.php') ?>"><i class="fas fa-sitemap me-2"></i>Branches</a></li>
                        <li><a class="dropdown-item" href="<?= getUrl('admin/subjects.php') ?>"><i class="fas fa-book me-2"></i>Subjects</a></li>
                        <li><a class="dropdown-item" href="<?= getUrl('admin/chapters.php') ?>"><i class="fas fa-list me-2"></i>Chapters</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= getUrl('admin/materials.php') ?>"><i class="fas fa-file-alt me-2"></i>Materials</a></li>
                        <li><a class="dropdown-item" href="<?= getUrl('admin/quizzes.php') ?>"><i class="fas fa-question-circle me-2"></i>Quizzes</a></li>
                        <li><a class="dropdown-item" href="<?= getUrl('admin/tests.php') ?>"><i class="fas fa-calendar-check me-2"></i>Monthly Tests</a></li>
                    </ul>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?= getUrl('admin/dashboard.php') ?>"><i class="fas fa-chart-pie me-1"></i>Dashboard</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link text-warning" href="<?= getUrl('admin/logout.php') ?>"><i class="fas fa-sign-out-alt me-1"></i>Logout</a>
                </li>
            </ul>
        </div>
    </div>
</nav>
