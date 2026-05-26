<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

if (isStudent()) {
    redirect('student/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Start a clean password-reset flow when user lands on this page.
    unset($_SESSION['reset_email'], $_SESSION['otp_verified'], $_SESSION['reset_record_id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitize($_POST['email'] ?? '');
    if (empty($email) || !validateEmail($email)) {
        $error = 'Please enter a valid email address.';
    } else {
        $student = $db->fetch("SELECT id FROM students WHERE email = ?", [$email]);
        if ($student) {
            $latestReset = $db->fetch("SELECT created_at FROM password_resets WHERE email = ? ORDER BY id DESC LIMIT 1", [$email]);
            if ($latestReset && strtotime($latestReset['created_at']) > (time() - 60)) {
                $error = 'Please wait at least 1 minute before requesting another OTP.';
            } else {
                $otp = generateOTP();
                $expires = date('Y-m-d H:i:s', strtotime('+10 minutes'));
                $db->query("DELETE FROM password_resets WHERE email = ?", [$email]);
                $db->insert("INSERT INTO password_resets (email, otp, expires_at) VALUES (?, ?, ?)", [$email, $otp, $expires]);

                $sent = sendOtpEmail($email, $otp);
                if ($sent) {
                    $_SESSION['reset_email'] = $email;
                    flashMessage('success', 'OTP sent to your email. Please check your inbox.');
                    redirect('student/verify_otp.php');
                } else {
                    $error = 'Failed to send email. Please try again later.';
                }
            }
        } else {
            flashMessage('info', 'If an account exists for this email, an OTP has been sent.');
            redirect('student/forgot_password.php');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= getUrl('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-5">
                <div class="text-center mb-4">
                    <a href="<?= getUrl() ?>" class="text-decoration-none">
                        <h3 class="fw-bold text-primary"><i class="fas fa-graduation-cap me-2"></i><?= SITE_NAME ?></h3>
                    </a>
                </div>
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="avatar-circle bg-warning-soft mx-auto mb-3">
                                <i class="fas fa-key text-warning fs-3"></i>
                            </div>
                            <h5 class="fw-bold">Forgot Password</h5>
                            <p class="text-muted small">Enter your registered email to receive an OTP</p>
                        </div>
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                        <?php endif; ?>
                        <?= displayFlashMessage() ?>
                        <form method="POST">
                            <div class="mb-4">
                                <label class="form-label fw-medium">Email Address</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                                    <input type="email" name="email" class="form-control" required placeholder="Enter your registered email">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-warning w-100 py-2 fw-bold rounded-pill">
                                <i class="fas fa-paper-plane me-2"></i>Send OTP
                            </button>
                        </form>
                        <div class="text-center mt-3">
                            <small class="text-muted">Remember your password? <a href="login.php" class="text-primary fw-medium">Login</a></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
