<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
requireStudent();

$branch_id = (int)$_SESSION['branch_id'];
$subject_id = (int)($_GET['subject_id'] ?? 0);
$chapter_id = (int)($_GET['chapter_id'] ?? 0);
$search = sanitize($_GET['search'] ?? '');

$subjects = $db->fetchAll(
    "SELECT s.id, s.subject_name,
            COUNT(DISTINCT c.id) AS chapter_count,
            COUNT(DISTINCT m.id) AS material_count
     FROM subjects s
     LEFT JOIN chapters c ON c.subject_id = s.id
     LEFT JOIN materials m ON m.subject_id = s.id AND m.branch_id = s.branch_id
     WHERE s.branch_id = ?
     GROUP BY s.id, s.subject_name
     ORDER BY s.subject_name",
    [$branch_id]
);

$selected_subject = null;
if ($subject_id > 0) {
    $selected_subject = $db->fetch("SELECT * FROM subjects WHERE id = ? AND branch_id = ?", [$subject_id, $branch_id]);
    if (!$selected_subject) {
        $subject_id = 0;
    }
}

$selected_chapter = null;
if ($chapter_id > 0) {
    $selected_chapter = $db->fetch(
        "SELECT c.*, s.subject_name, s.branch_id
         FROM chapters c
         JOIN subjects s ON c.subject_id = s.id
         WHERE c.id = ? AND s.branch_id = ?",
        [$chapter_id, $branch_id]
    );

    if (!$selected_chapter) {
        $chapter_id = 0;
    } elseif ($selected_subject && (int)$selected_chapter['subject_id'] !== (int)$selected_subject['id']) {
        $selected_chapter = null;
        $chapter_id = 0;
    } else {
        $subject_id = (int)$selected_chapter['subject_id'];
        if (!$selected_subject) {
            $selected_subject = $db->fetch("SELECT * FROM subjects WHERE id = ? AND branch_id = ?", [$subject_id, $branch_id]);
        }
    }
}

$chapters = [];
if ($selected_subject) {
    $chapters = $db->fetchAll(
        "SELECT c.id, c.chapter_name, c.chapter_no,
                COUNT(DISTINCT m.id) AS material_count
         FROM chapters c
         LEFT JOIN materials m ON m.chapter_id = c.id
         WHERE c.subject_id = ?
         GROUP BY c.id, c.chapter_name, c.chapter_no
         ORDER BY c.chapter_no IS NULL, c.chapter_no, c.chapter_name",
        [$selected_subject['id']]
    );
}

$materials = [];
if ($selected_subject && $selected_chapter) {
    $conditions = "WHERE m.branch_id = ? AND m.subject_id = ? AND m.chapter_id = ?";
    $params = [$branch_id, $selected_subject['id'], $selected_chapter['id']];

    if (!empty($search)) {
        $conditions .= " AND (m.title LIKE ? OR m.description LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    $materials = $db->fetchAll(
        "SELECT m.*, c.chapter_name, c.chapter_no, s.subject_name, b.branch_name
         FROM materials m
         JOIN chapters c ON m.chapter_id = c.id
         JOIN subjects s ON m.subject_id = s.id
         JOIN branches b ON m.branch_id = b.id
         $conditions
         ORDER BY m.created_at DESC",
        $params
    );
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Materials - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= getUrl('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container-fluid py-4">
        <div class="container">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="fas fa-book-open me-2 text-primary"></i>Materials by Subject</h5>
                            <p class="text-muted mb-0">Choose a subject first, then open a chapter to see the materials uploaded by admin.</p>
                        </div>
                        <?php if ($selected_subject): ?>
                        <div class="d-flex gap-2">
                            <a href="<?= getUrl('student/materials.php') ?>" class="btn btn-outline-secondary rounded-pill btn-sm">
                                <i class="fas fa-layer-group me-1"></i>All Subjects
                            </a>
                            <?php if ($selected_chapter): ?>
                            <a href="<?= getUrl('student/materials.php?subject_id=' . (int)$selected_subject['id']) ?>" class="btn btn-outline-primary rounded-pill btn-sm">
                                <i class="fas fa-arrow-left me-1"></i>Back to Chapters
                            </a>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!$selected_subject): ?>
            <div class="row g-3">
                <?php foreach ($subjects as $subject): ?>
                <div class="col-md-6 col-lg-4">
                    <a href="<?= getUrl('student/materials.php?subject_id=' . (int)$subject['id']) ?>" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100 material-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <div class="file-icon-box">
                                        <i class="fas fa-book-open fs-2"></i>
                                    </div>
                                    <span class="badge bg-light text-dark"><?= (int)$subject['chapter_count'] ?> chapters</span>
                                </div>
                                <h6 class="fw-bold mb-1 text-dark"><?= sanitize($subject['subject_name']) ?></h6>
                                <p class="small text-muted mb-0"><?= (int)$subject['material_count'] ?> materials available</p>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
                <?php if (empty($subjects)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-book fs-1 text-muted mb-3"></i>
                    <p class="text-muted mb-0">No subjects have been uploaded for your branch yet.</p>
                </div>
                <?php endif; ?>
            </div>
            <?php elseif (!$selected_chapter): ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="fw-bold mb-1"><?= sanitize($selected_subject['subject_name']) ?></h5>
                        <p class="text-muted mb-0">Pick a chapter to open the uploaded materials in ascending order.</p>
                    </div>
                    <a href="<?= getUrl('student/materials.php') ?>" class="btn btn-outline-secondary rounded-pill btn-sm">
                        <i class="fas fa-layer-group me-1"></i>All Subjects
                    </a>
                </div>
            </div>

            <div class="row g-3">
                <?php foreach ($chapters as $chapter): ?>
                <div class="col-md-6 col-lg-4">
                    <a href="<?= getUrl('student/materials.php?subject_id=' . (int)$selected_subject['id'] . '&chapter_id=' . (int)$chapter['id']) ?>" class="text-decoration-none">
                        <div class="card border-0 shadow-sm h-100 material-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge bg-primary-soft text-primary">Chapter <?= $chapter['chapter_no'] !== null ? (int)$chapter['chapter_no'] : '—' ?></span>
                                    <span class="badge bg-light text-dark"><?= (int)$chapter['material_count'] ?> materials</span>
                                </div>
                                <h6 class="fw-bold mb-1 text-dark"><?= sanitize($chapter['chapter_name']) ?></h6>
                                <p class="small text-muted mb-0">Open the materials uploaded by admin</p>
                            </div>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
                <?php if (empty($chapters)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-list fs-1 text-muted mb-3"></i>
                    <p class="text-muted mb-0">No chapters have been added for this subject yet.</p>
                </div>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body d-flex justify-content-between align-items-start flex-wrap gap-3">
                    <div>
                        <h5 class="fw-bold mb-1"><?= sanitize($selected_subject['subject_name']) ?></h5>
                        <p class="text-muted mb-0">
                            Chapter <?= $selected_chapter['chapter_no'] !== null ? (int)$selected_chapter['chapter_no'] : '—' ?>: <?= sanitize($selected_chapter['chapter_name']) ?>
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="<?= getUrl('student/materials.php?subject_id=' . (int)$selected_subject['id']) ?>" class="btn btn-outline-secondary rounded-pill btn-sm">
                            <i class="fas fa-arrow-left me-1"></i>Back to Chapters
                        </a>
                        <a href="<?= getUrl('student/materials.php') ?>" class="btn btn-outline-primary rounded-pill btn-sm">
                            <i class="fas fa-layer-group me-1"></i>All Subjects
                        </a>
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-2 align-items-end">
                        <input type="hidden" name="subject_id" value="<?= (int)$selected_subject['id'] ?>">
                        <input type="hidden" name="chapter_id" value="<?= (int)$selected_chapter['id'] ?>">
                        <div class="col-md-10">
                            <label class="form-label fw-medium small">Search</label>
                            <input type="text" name="search" class="form-control" placeholder="Search materials..." value="<?= sanitize($search) ?>">
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100 rounded-pill"><i class="fas fa-search me-1"></i>Search</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="row g-3">
                <?php foreach ($materials as $m): ?>
                <div class="col-md-6 col-lg-4">
                    <div class="card border-0 shadow-sm h-100 material-card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="file-icon-box me-3">
                                    <i class="fas <?= getFileIcon($m['file_type']) ?> fs-2"></i>
                                </div>
                                <div class="flex-grow-1 min-width-0">
                                    <h6 class="fw-bold mb-1 text-truncate"><?= sanitize($m['title']) ?></h6>
                                    <div class="d-flex gap-2">
                                        <span class="badge bg-light text-dark"><?= getFileTypeLabel($m['file_type']) ?></span>
                                        <small class="text-muted"><?= date('d M Y', strtotime($m['created_at'])) ?></small>
                                    </div>
                                </div>
                            </div>
                            <p class="small text-muted mb-2">
                                <i class="fas fa-book me-1"></i><?= sanitize($m['subject_name']) ?>
                                <i class="fas fa-chevron-right mx-1" style="font-size:10px;"></i>
                                <?= $m['chapter_no'] !== null ? '#' . (int)$m['chapter_no'] . ' ' : '' ?><?= sanitize($m['chapter_name']) ?>
                            </p>
                            <?php if ($m['description']): ?>
                            <p class="small text-muted mb-3"><?= sanitize($m['description']) ?></p>
                            <?php endif; ?>
                            <a href="<?= getUrl('material_view.php?id=' . $m['id']) ?>" target="_blank" class="btn btn-primary btn-sm rounded-pill w-100">
                                <i class="fas fa-eye me-1"></i>Open Material
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php if (empty($materials)): ?>
                <div class="col-12 text-center py-5">
                    <i class="fas fa-file-alt fs-1 text-muted mb-3"></i>
                    <p class="text-muted mb-0">No materials found for this chapter.</p>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
