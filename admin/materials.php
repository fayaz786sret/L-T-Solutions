<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['upload'])) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $branch_id = (int)$_POST['branch_id'];
    $subject_name = sanitize($_POST['subject_name'] ?? '');
    $chapter_name = sanitize($_POST['chapter_name'] ?? '');
    $chapter_no = (int)($_POST['chapter_no'] ?? 0);

    if (!empty($title) && !empty($subject_name) && !empty($chapter_name) && $chapter_no > 0 && $branch_id > 0 && isset($_FILES['file'])) {
        $subject = getOrCreateSubjectByBranch($branch_id, $subject_name);
        $chapter = $subject ? getOrCreateChapterBySubject($subject['id'], $chapter_name, $chapter_no) : null;

        if (!$subject || !$chapter) {
            flashMessage('danger', 'Unable to save the subject or chapter. Please try again.');
            redirect('admin/materials.php');
        }

        $upload = uploadFile($_FILES['file'], '../assets/uploads/materials');
        if ($upload['success']) {
            $file_type = strtolower(pathinfo($_FILES['file']['name'], PATHINFO_EXTENSION));
            $preview_path = null;

            if (isOfficeDocument($file_type)) {
                $preview_relative = 'previews/' . pathinfo($upload['filename'], PATHINFO_FILENAME) . '.pdf';
                $preview_absolute = '../assets/uploads/materials/' . $preview_relative;
                $conversion = convertOfficeDocumentToPdf('../assets/uploads/materials/' . $upload['filename'], $preview_absolute, $file_type);

                if (!$conversion['success']) {
                    @unlink('../assets/uploads/materials/' . $upload['filename']);
                    flashMessage('danger', $conversion['message']);
                    redirect('admin/materials.php');
                }

                $preview_path = $preview_relative;
            }

            $db->insert("INSERT INTO materials (title, description, file_path, file_type, chapter_id, subject_id, branch_id, uploaded_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)",
                [$title, $description, $upload['filename'], $file_type, $chapter['id'], $subject['id'], $branch_id, $_SESSION['user_id']]);
            if ($preview_path) {
                $db->update("UPDATE materials SET preview_path = ? WHERE id = LAST_INSERT_ID()", [$preview_path]);
            }
            flashMessage('success', 'Material uploaded successfully.');
        } else {
            flashMessage('danger', $upload['message']);
        }
    } else {
        flashMessage('danger', 'Please fill all required fields.');
    }
    redirect('admin/materials.php');
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $mat = $db->fetch("SELECT * FROM materials WHERE id = ?", [$id]);
    if ($mat) {
        $file_path = '../assets/uploads/materials/' . $mat['file_path'];
        $preview_path = !empty($mat['preview_path']) ? '../assets/uploads/materials/' . $mat['preview_path'] : null;
        if (file_exists($file_path)) unlink($file_path);
        if ($preview_path && file_exists($preview_path)) unlink($preview_path);
        $db->delete("DELETE FROM materials WHERE id = ?", [$id]);
        flashMessage('success', 'Material deleted successfully.');
    }
    redirect('admin/materials.php');
}

$materials = $db->fetchAll("SELECT m.*, c.chapter_name, c.chapter_no, s.subject_name, b.branch_name FROM materials m JOIN chapters c ON m.chapter_id = c.id JOIN subjects s ON m.subject_id = s.id JOIN branches b ON m.branch_id = b.id ORDER BY m.created_at DESC");
$branches = getBranches();
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
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pt-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-upload me-2 text-primary"></i>Upload Material</h5>
                </div>
                <div class="card-body">
                    <?= displayFlashMessage() ?>
                    <form method="POST" enctype="multipart/form-data">
                        <div class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label fw-medium">Branch <span class="text-danger">*</span></label>
                                <select name="branch_id" id="mat_branch" class="form-select" required>
                                    <option value="">Select Branch</option>
                                    <?php foreach ($branches as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= sanitize($b['branch_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label fw-medium">Subject Name <span class="text-danger">*</span></label>
                                <input type="text" name="subject_name" class="form-control" placeholder="Enter subject name" required>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label fw-medium">Chapter No. <span class="text-danger">*</span></label>
                                <input type="number" name="chapter_no" class="form-control" min="1" step="1" placeholder="1" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Chapter Name <span class="text-danger">*</span></label>
                                <input type="text" name="chapter_name" class="form-control" placeholder="Enter chapter name" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" placeholder="Enter material title" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">File (PDF)</label>
                                <input type="file" name="file" class="form-control" required accept=".pdf,.doc,.docx,.ppt,.pptx">
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium">Description</label>
                                <textarea name="description" class="form-control" rows="2" placeholder="Brief description"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" name="upload" class="btn btn-primary rounded-pill px-4">
                                    <i class="fas fa-cloud-upload-alt me-2"></i>Upload
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-file-alt me-2 text-primary"></i>Uploaded Materials</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr><th>Title</th><th>Type</th><th>Chapter</th><th>Subject</th><th>Branch</th><th>Uploaded</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($materials as $m): ?>
                                <tr>
                                    <td class="fw-medium">
                                        <i class="fas <?= getFileIcon($m['file_type']) ?> me-2"></i>
                                        <?= sanitize($m['title']) ?>
                                    </td>
                                    <td><span class="badge bg-light text-dark"><?= getFileTypeLabel($m['file_type']) ?></span></td>
                                    <td><small><?= $m['chapter_no'] !== null ? '#' . (int)$m['chapter_no'] . ' ' : '' ?><?= sanitize($m['chapter_name']) ?></small></td>
                                    <td><?= sanitize($m['subject_name']) ?></td>
                                    <td><span class="badge bg-primary-soft text-primary"><?= sanitize($m['branch_name']) ?></span></td>
                                    <td><small class="text-muted"><?= date('d M Y', strtotime($m['created_at'])) ?></small></td>
                                    <td>
                                        <a href="<?= getUrl('material_view.php?id=' . $m['id']) ?>" target="_blank" class="btn btn-sm btn-outline-primary rounded-pill">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?delete=<?= $m['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Delete this material?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($materials)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">No materials uploaded yet</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
