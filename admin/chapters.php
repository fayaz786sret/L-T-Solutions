<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $name = sanitize($_POST['chapter_name']);
        $subject_id = (int)$_POST['subject_id'];
        $chapter_no = (int)($_POST['chapter_no'] ?? 0);
        if (!empty($name) && $subject_id > 0 && $chapter_no > 0) {
            $db->insert("INSERT INTO chapters (chapter_name, chapter_no, subject_id) VALUES (?, ?, ?)", [$name, $chapter_no, $subject_id]);
            flashMessage('success', 'Chapter added successfully.');
        }
    } elseif (isset($_POST['delete'])) {
        $id = (int)$_POST['id'];
        $db->delete("DELETE FROM chapters WHERE id = ?", [$id]);
        flashMessage('success', 'Chapter deleted successfully.');
    } elseif (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        $name = sanitize($_POST['chapter_name']);
        $subject_id = (int)$_POST['subject_id'];
        $chapter_no = (int)($_POST['chapter_no'] ?? 0);
        if (!empty($name) && $subject_id > 0 && $chapter_no > 0) {
            $db->update("UPDATE chapters SET chapter_name = ?, chapter_no = ?, subject_id = ? WHERE id = ?", [$name, $chapter_no, $subject_id, $id]);
            flashMessage('success', 'Chapter updated successfully.');
        }
    }
    redirect('admin/chapters.php');
}

$chapters = $db->fetchAll("SELECT c.*, s.subject_name, b.branch_name FROM chapters c JOIN subjects s ON c.subject_id = s.id JOIN branches b ON s.branch_id = b.id ORDER BY b.branch_name, s.subject_name, c.chapter_no IS NULL, c.chapter_no, c.chapter_name");
$subjects = $db->fetchAll("SELECT s.*, b.branch_name FROM subjects s JOIN branches b ON s.branch_id = b.id ORDER BY b.branch_name, s.subject_name");
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Chapters - <?= SITE_NAME ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="<?= getUrl('assets/css/style.css') ?>" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>
    <div class="container-fluid py-4">
        <div class="container">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 d-flex justify-content-between align-items-center pt-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-list me-2 text-primary"></i>Manage Chapters</h5>
                    <button class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus me-1"></i>Add Chapter
                    </button>
                </div>
                <div class="card-body">
                    <?= displayFlashMessage() ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr><th>#</th><th>Chapter No.</th><th>Chapter</th><th>Subject</th><th>Branch</th><th>Created</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($chapters as $i => $c): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td class="fw-semibold text-primary"><?= $c['chapter_no'] !== null ? (int)$c['chapter_no'] : '—' ?></td>
                                    <td class="fw-medium"><?= sanitize($c['chapter_name']) ?></td>
                                    <td><?= sanitize($c['subject_name']) ?></td>
                                    <td><span class="badge bg-primary-soft text-primary"><?= sanitize($c['branch_name']) ?></span></td>
                                    <td><small class="text-muted"><?= date('d M Y', strtotime($c['created_at'])) ?></small></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#editModal<?= $c['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this chapter?')">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <button type="submit" name="delete" class="btn btn-sm btn-outline-danger rounded-pill">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <div class="modal fade" id="editModal<?= $c['id'] ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header"><h5 class="modal-title fw-bold">Edit Chapter</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-medium">Chapter Name</label>
                                                        <input type="text" name="chapter_name" class="form-control" value="<?= sanitize($c['chapter_name']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-medium">Chapter No.</label>
                                                        <input type="number" name="chapter_no" class="form-control" min="1" step="1" value="<?= $c['chapter_no'] !== null ? (int)$c['chapter_no'] : '' ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-medium">Subject</label>
                                                        <select name="subject_id" class="form-select" required>
                                                            <?php foreach ($subjects as $sub): ?>
                                                            <option value="<?= $sub['id'] ?>" <?= $sub['id'] == $c['subject_id'] ? 'selected' : '' ?>>
                                                                <?= sanitize($sub['subject_name']) ?> (<?= sanitize($sub['branch_name']) ?>)
                                                            </option>
                                                            <?php endforeach; ?>
                                                        </select>
                                                    </div>
                                                </div>
                                                <div class="modal-footer">
                                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                                                    <button type="submit" name="edit" class="btn btn-primary rounded-pill px-4">Update</button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php if (empty($chapters)): ?>
                                <tr><td colspan="6" class="text-center text-muted py-4">No chapters found</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addModal">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title fw-bold">Add Chapter</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-medium">Chapter Name</label>
                            <input type="text" name="chapter_name" class="form-control" placeholder="Enter chapter name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Chapter No.</label>
                            <input type="number" name="chapter_no" class="form-control" placeholder="1" min="1" step="1" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Subject</label>
                            <select name="subject_id" class="form-select" required>
                                <option value="">Select Subject</option>
                                <?php foreach ($subjects as $sub): ?>
                                <option value="<?= $sub['id'] ?>"><?= sanitize($sub['subject_name']) ?> (<?= sanitize($sub['branch_name']) ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add" class="btn btn-primary rounded-pill px-4">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
