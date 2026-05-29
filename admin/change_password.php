<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
requireAdmin();

$admin = $db->fetch("SELECT * FROM admin WHERE id = ?", [$_SESSION['user_id']]);
if (!$admin) {
    flashMessage('danger', 'Admin account not found.');
    redirect('admin/dashboard.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';

    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error = 'Please fill in all password fields.';
    } elseif (!verifyPassword($current_password, $admin['password'])) {
        $error = 'Current password is incorrect.';
    } elseif (strlen($new_password) < 8) {
        $error = 'New password must be at least 8 characters.';
    } elseif ($new_password !== $confirm_password) {
        $error = 'New password and confirm password do not match.';
    } else {
        $hashed = hashPassword($new_password);
        $db->update("UPDATE admin SET password = ? WHERE id = ?", [$hashed, $admin['id']]);
        flashMessage('success', 'Password changed successfully. Use the new password the next time you login.');
        redirect('admin/change_password.php');
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= getUrl('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container-fluid py-4">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-6 col-xl-5">
                    <div class="card border-0 shadow-sm">
                        <div class="card-header bg-transparent border-0 pt-3">
                            <h5 class="fw-bold mb-0"><i class="fas fa-key me-2 text-primary"></i>Change Password</h5>
                        </div>
                        <div class="card-body">
                            <?= displayFlashMessage() ?>
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show">
                                    <i class="fas fa-exclamation-circle me-2"></i><?= sanitize($error) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                                </div>
                            <?php endif; ?>
                            <form method="POST">
                                <div class="mb-3">
                                    <label class="form-label fw-medium">Current Password</label>
                                    <input type="password" name="current_password" class="form-control" required autocomplete="current-password">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label fw-medium">New Password</label>
                                    <input type="password" name="new_password" class="form-control" required minlength="8" placeholder="Minimum 8 characters" autocomplete="new-password">
                                </div>
                                <div class="mb-4">
                                    <label class="form-label fw-medium">Confirm New Password</label>
                                    <input type="password" name="confirm_password" class="form-control" required minlength="8" autocomplete="new-password">
                                </div>
                                <button type="submit" class="btn btn-primary rounded-pill px-4">
                                    <i class="fas fa-save me-2"></i>Update Password
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>