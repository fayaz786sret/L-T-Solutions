<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

requireLogin();

$material_id = (int)($_GET['id'] ?? 0);
$material = $db->fetch(
    "SELECT m.*, c.chapter_name, c.chapter_no, s.subject_name, b.branch_name
     FROM materials m
     JOIN chapters c ON m.chapter_id = c.id
     JOIN subjects s ON m.subject_id = s.id
     JOIN branches b ON m.branch_id = b.id
     WHERE m.id = ?",
    [$material_id]
);

if (!$material) {
    flashMessage('danger', 'Material not found.');
    redirect(isAdmin() ? 'admin/materials.php' : 'student/materials.php');
}

if (isStudent() && (int)$_SESSION['branch_id'] !== (int)$material['branch_id']) {
    flashMessage('danger', 'You do not have access to this material.');
    redirect('student/materials.php');
}

$previewFile = !empty($material['preview_path']) ? $material['preview_path'] : $material['file_path'];
$previewUrl = getUrl('assets/uploads/materials/' . $previewFile);
$isPdf = strtolower($material['file_type']) === 'pdf' || str_ends_with(strtolower($previewFile), '.pdf');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($material['title']) ?> - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= getUrl('assets/css/style.css') ?>" rel="stylesheet">
    <style>
        .viewer-shell { min-height: calc(100vh - 140px); }
        .viewer-frame { width: 100%; min-height: 76vh; border: 0; border-radius: 16px; background: #fff; }
        .viewer-card { border: 1px solid var(--border); border-radius: 18px; overflow: hidden; background: #fff; }
        .viewer-meta { color: var(--muted); }
    </style>
</head>
<body>
    <?php if (isAdmin()) { include 'admin/navbar.php'; } else { include 'student/navbar.php'; } ?>

    <div class="container-fluid py-4 viewer-shell">
        <div class="container">
            <div class="d-flex justify-content-between align-items-start mb-3 flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold mb-1"><i class="fas fa-eye me-2 text-primary"></i><?= sanitize($material['title']) ?></h4>
                    <div class="viewer-meta small">
                        <span class="me-3"><i class="fas fa-book me-1"></i><?= sanitize($material['subject_name']) ?></span>
                        <span class="me-3"><i class="fas fa-chevron-right me-1" style="font-size:10px;"></i><?= $material['chapter_no'] !== null ? '#' . (int)$material['chapter_no'] . ' ' : '' ?><?= sanitize($material['chapter_name']) ?></span>
                        <span class="me-3"><i class="fas fa-sitemap me-1"></i><?= sanitize($material['branch_name']) ?></span>
                        <span><i class="fas fa-file me-1"></i><?= getFileTypeLabel($material['file_type']) ?></span>
                    </div>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?= isAdmin() ? getUrl('admin/materials.php') : getUrl('student/materials.php') ?>" class="btn btn-outline-secondary rounded-pill">
                        <i class="fas fa-arrow-left me-1"></i>Back
                    </a>
                </div>
            </div>

            <div class="viewer-card shadow-sm">
                <div class="card-body p-0">
                    <?php if ($isPdf): ?>
                        <iframe class="viewer-frame" src="<?= $previewUrl ?>"></iframe>
                    <?php else: ?>
                        <iframe class="viewer-frame" src="<?= $previewUrl ?>"></iframe>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>