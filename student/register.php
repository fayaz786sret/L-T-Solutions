<?php
require_once '../includes/config.php';
require_once '../includes/db.php';

if (isStudent()) {
    redirect('student/dashboard.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name'] ?? '');
    $email = sanitize($_POST['email'] ?? '');
    $phone = preg_replace('/\D+/', '', (string)($_POST['phone'] ?? ''));
    $age = (int)($_POST['age'] ?? 0);
    $gender = sanitize($_POST['gender'] ?? '');
    $date_of_birth = sanitize($_POST['date_of_birth'] ?? '');
    $address = sanitize($_POST['address'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $branch_id = (int)($_POST['branch_id'] ?? 0);

    if (empty($name) || empty($email) || empty($phone) || $age <= 0 || empty($gender) || empty($password) || $branch_id === 0) {
        $error = 'Please fill all required fields.';
    } elseif (!validateEmail($email)) {
        $error = 'Invalid email address.';
    } elseif (!preg_match('/^[0-9]{10,15}$/', $phone)) {
        $error = 'Please enter a valid phone number.';
    } elseif ($age < 10 || $age > 120) {
        $error = 'Please enter a valid age.';
    } elseif (!in_array($gender, ['Male', 'Female', 'Other'], true)) {
        $error = 'Please select a valid gender.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } elseif ($password !== $confirm_password) {
        $error = 'Passwords do not match.';
    } else {
        $existing = $db->fetch("SELECT id FROM students WHERE email = ?", [$email]);
        if ($existing) {
            $error = 'An account with this email already exists.';
        } else {
            $existingPhone = $db->fetch("SELECT id FROM students WHERE phone = ?", [$phone]);
            if ($existingPhone) {
                $error = 'An account with this phone number already exists.';
            } else {
            $hashed = hashPassword($password);
                $dobValue = !empty($date_of_birth) ? $date_of_birth : null;
                $studentId = $db->insert("INSERT INTO students (admission_no, name, email, password, phone, age, gender, date_of_birth, address, branch_id) VALUES (NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?)",
                    [$name, $email, $hashed, $phone, $age, $gender, $dobValue, $address ?: null, $branch_id]);
                $admissionNo = generateAdmissionNo($studentId);
                $db->update("UPDATE students SET admission_no = ? WHERE id = ?", [$admissionNo, $studentId]);
                flashMessage('success', 'Registration successful! Your Admission No: ' . $admissionNo);
                redirect('login.php');
            }
        }
    }
}

$branches = getBranches();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Registration - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= getUrl('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body class="bg-light d-flex align-items-center" style="min-height: 100vh;">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="text-center mb-4">
                    <a href="<?= getUrl() ?>" class="text-decoration-none">
                        <h3 class="fw-bold text-primary"><i class="fas fa-graduation-cap me-2"></i><?= SITE_NAME ?></h3>
                    </a>
                    <p class="text-muted">Create your student account</p>
                </div>
                <div class="card border-0 shadow-lg rounded-4">
                    <div class="card-body p-4">
                        <div class="text-center mb-4">
                            <div class="avatar-circle bg-success-soft mx-auto mb-3">
                                <i class="fas fa-user-plus text-success fs-3"></i>
                            </div>
                            <h5 class="fw-bold">Student Registration</h5>
                        </div>
                        <?php if ($error): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <i class="fas fa-exclamation-circle me-2"></i><?= $error ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                        <form method="POST">
                            <div class="row g-3">
                                <div class="col-12">
                                    <label class="form-label fw-medium">Full Name</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                                        <input type="text" name="name" class="form-control" required placeholder="Enter your full name">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">Email</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                                        <input type="email" name="email" class="form-control" required placeholder="Enter your email">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Phone No</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-phone text-muted"></i></span>
                                        <input type="tel" name="phone" class="form-control" required placeholder="Enter phone number" maxlength="15">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-medium">Age</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-cake-candles text-muted"></i></span>
                                        <input type="number" name="age" class="form-control" required placeholder="Age" min="10" max="120">
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <label class="form-label fw-medium">Gender</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-venus-mars text-muted"></i></span>
                                        <select name="gender" class="form-select" required>
                                            <option value="">Gender</option>
                                            <option value="Male">Male</option>
                                            <option value="Female">Female</option>
                                            <option value="Other">Other</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Date of Birth</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-calendar text-muted"></i></span>
                                        <input type="date" name="date_of_birth" class="form-control">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Address</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-location-dot text-muted"></i></span>
                                        <input type="text" name="address" class="form-control" placeholder="Enter address">
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-medium">Branch</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-sitemap text-muted"></i></span>
                                        <select name="branch_id" class="form-select" required>
                                            <option value="">Select your branch</option>
                                            <?php foreach ($branches as $b): ?>
                                            <option value="<?= $b['id'] ?>"><?= sanitize($b['branch_name']) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                                        <input type="password" name="password" class="form-control" required placeholder="Min 6 characters" minlength="6">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-medium">Confirm Password</label>
                                    <div class="input-group">
                                        <span class="input-group-text"><i class="fas fa-check-circle text-muted"></i></span>
                                        <input type="password" name="confirm_password" class="form-control" required placeholder="Confirm password">
                                    </div>
                                </div>
                                <div class="col-12 mt-4">
                                    <button type="submit" class="btn btn-success w-100 py-2 fw-bold rounded-pill">
                                        <i class="fas fa-user-plus me-2"></i>Register
                                    </button>
                                </div>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <small class="text-muted">Already have an account? <a href="<?= getUrl('login.php') ?>" class="text-primary fw-medium">Login</a></small>
                        </div>
                    </div>
                </div>
                <div class="text-center mt-3">
                    <a href="<?= getUrl() ?>" class="text-muted text-decoration-none small"><i class="fas fa-arrow-left me-1"></i>Back to Home</a>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
