<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

if (isStudent()) {
    redirect('student/dashboard.php');
}

if (!isset($_SESSION['reset_email'])) {
    redirect('student/forgot_password.php');
}

$error = '';
$email = $_SESSION['reset_email'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = sanitize($_POST['otp'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (!preg_match('/^[0-9]{6}$/', $otp)) {
        $error = 'Please enter a valid 6-digit OTP.';
    } elseif (strlen($password) < 8) {
        $error = 'Password must be at least 8 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'New password and confirm password do not match.';
    } else {
        $record = $db->fetch("SELECT * FROM password_resets WHERE email = ? AND otp = ? AND is_used = 0 AND expires_at > NOW()", [$email, $otp]);
        if ($record) {
            $hashed = hashPassword($password);
            $db->update("UPDATE students SET password = ? WHERE email = ?", [$hashed, $email]);
            $db->update("UPDATE password_resets SET is_used = 1 WHERE id = ?", [$record['id']]);
            $db->delete("DELETE FROM password_resets WHERE email = ?", [$email]);

            unset($_SESSION['reset_email'], $_SESSION['otp_verified'], $_SESSION['reset_record_id']);
            flashMessage('success', 'Password reset successful! Please login with your new password.');
            redirect('student/login.php');
        } else {
            $error = 'Invalid or expired OTP.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP & Reset Password - <?= SITE_NAME ?></title>
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
                        <h3 class="fw-bold text-primary"><i class="fas fa-graduation-cap me-2"></i>L&T Solutions LearnHub</h3>
                    </a>
                </div>
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="avatar-circle bg-info-soft mx-auto mb-3">
                                <i class="fas fa-shield-alt text-info fs-3"></i>
                            </div>
                            <h5 class="fw-bold">Verify OTP & Reset Password</h5>
                            <p class="text-muted small">Enter the OTP sent to <strong><?= sanitize($email) ?></strong> and set a new password.</p>
                        </div>
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show"><i class="fas fa-exclamation-circle me-2"></i><?= $error ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
                        <?php endif; ?>
                        <?= displayFlashMessage() ?>
                        <form method="POST">
                            <div class="mb-3">
                                <label class="form-label fw-medium">OTP Code</label>
                                <input type="text" name="otp" class="form-control form-control-lg text-center fw-bold" placeholder="000000" maxlength="6" required style="font-size: 28px; letter-spacing: 8px;" inputmode="numeric" pattern="[0-9]{6}" autocomplete="one-time-code">
                            </div>
                            <div class="mb-3">
                                <label class="form-label fw-medium">New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                                    <input type="password" name="password" class="form-control" required placeholder="Minimum 8 characters" minlength="8" autocomplete="new-password">
                                </div>
                            </div>
                            <div class="mb-4">
                                <label class="form-label fw-medium">Confirm New Password</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-check-circle text-muted"></i></span>
                                    <input type="password" name="confirm_password" class="form-control" required placeholder="Re-enter new password" minlength="8" autocomplete="new-password">
                                </div>
                            </div>
                            <button type="submit" class="btn btn-info text-white w-100 py-2 fw-bold rounded-pill">
                                <i class="fas fa-check-circle me-2"></i>Verify OTP & Reset Password
                            </button>
                        </form>
                        <div class="text-center mt-3">
                            <small class="text-muted">Didn't receive code? <a href="forgot_password.php" class="text-primary fw-medium">Resend</a></small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
