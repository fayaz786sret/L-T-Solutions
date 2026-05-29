<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

if (isAdmin()) {
    redirect('admin/dashboard.php');
}

if (isStudent()) {
    redirect('student/dashboard.php');
}

$error = '';
$selectedRole = 'student';
$identityValue = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selectedRole = ($_POST['role'] ?? 'student') === 'admin' ? 'admin' : 'student';
    $identity = sanitize($_POST['identity'] ?? '');
    $identityValue = $identity;
    $password = $_POST['password'] ?? '';

    if (empty($identity) || empty($password)) {
        $error = 'Please enter login ID and password.';
    } elseif ($selectedRole === 'student') {
        $student = $db->fetch("SELECT s.*, b.branch_name FROM students s JOIN branches b ON s.branch_id = b.id WHERE s.email = ? OR s.admission_no = ?", [$identity, $identity]);
        if ($student && verifyPassword($password, $student['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $student['id'];
            $_SESSION['user_type'] = 'student';
            $_SESSION['user_name'] = $student['name'];
            $_SESSION['admission_no'] = $student['admission_no'];
            $_SESSION['branch_id'] = $student['branch_id'];
            $_SESSION['branch_name'] = $student['branch_name'];
            redirect('student/dashboard.php');
        }
        $error = 'Invalid student credentials.';
    } else {
        $admin = $db->fetch("SELECT * FROM admin WHERE username = ?", [$identity]);
        if ($admin && verifyPassword($password, $admin['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = $admin['id'];
            $_SESSION['user_type'] = 'admin';
            $_SESSION['user_name'] = $admin['username'];
            redirect('admin/dashboard.php');
        }
        $error = 'Invalid admin credentials.';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= getUrl('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height:100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-5">
                <div class="text-center mb-4">
                    <a href="<?= getUrl() ?>" class="text-decoration-none">
                        <h3 class="fw-bold text-primary"><i class="fas fa-graduation-cap me-2"></i>L&T Solutions LearnHub</h3>
                    </a>
                    <p class="text-muted mb-0">Unified Login Portal</p>
                </div>

                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body p-4 p-lg-5">
                        <h5 class="fw-bold text-center mb-3">Sign In</h5>

                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>

                        <form method="POST" id="loginForm">
                            <div class="mb-3">
                                <label class="form-label fw-medium d-block">Login As</label>
                                <div class="btn-group w-100" role="group">
                                    <input type="radio" class="btn-check" name="role" id="roleStudent" value="student" <?= $selectedRole === 'student' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-primary" for="roleStudent"><i class="fas fa-user-graduate me-1"></i>Student</label>

                                    <input type="radio" class="btn-check" name="role" id="roleAdmin" value="admin" <?= $selectedRole === 'admin' ? 'checked' : '' ?>>
                                    <label class="btn btn-outline-primary" for="roleAdmin"><i class="fas fa-user-shield me-1"></i>Admin</label>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-medium" id="identityLabel">Email / Admission No</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-id-card text-muted"></i></span>
                                    <input type="text" name="identity" id="identityInput" class="form-control" placeholder="Enter email or admission no" value="<?= sanitize($identityValue) ?>" required>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label class="form-label fw-medium">Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                                    <input type="password" name="password" class="form-control" placeholder="Enter password" required>
                                </div>
                            </div>

                            <button type="submit" class="btn btn-primary w-100 py-2 fw-bold rounded-pill">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </form>

                        <div class="text-center mt-3" id="studentLinks">
                            <small class="text-muted">Need a student account? <a href="<?= getUrl('student/register.php') ?>" class="text-primary fw-medium">Register</a></small><br>
                            <a href="<?= getUrl('student/forgot_password.php') ?>" class="small text-primary text-decoration-none">Forgot Student Password?</a>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-3">
                    <a href="<?= getUrl() ?>" class="text-muted text-decoration-none small"><i class="fas fa-arrow-left me-1"></i>Back to Home</a>
                </div>
            </div>
        </div>
    </div>

    <script>
    const roleStudent = document.getElementById('roleStudent');
    const roleAdmin = document.getElementById('roleAdmin');
    const identityLabel = document.getElementById('identityLabel');
    const identityInput = document.getElementById('identityInput');
    const studentLinks = document.getElementById('studentLinks');

    function updateLoginFields() {
        const isStudent = roleStudent.checked;
        identityLabel.textContent = isStudent ? 'Email / Admission No' : 'Admin Username';
        identityInput.placeholder = isStudent ? 'Enter email or admission no' : 'Enter admin username';
        studentLinks.style.display = isStudent ? 'block' : 'none';
    }

    roleStudent.addEventListener('change', updateLoginFields);
    roleAdmin.addEventListener('change', updateLoginFields);
    updateLoginFields();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
