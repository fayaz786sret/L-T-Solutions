<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $name = sanitize($_POST['subject_name']);
        $branch_id = (int)$_POST['branch_id'];
        if (!empty($name) && $branch_id > 0) {
            try {
                $db->insert("INSERT INTO subjects (subject_name, branch_id) VALUES (?, ?)", [$name, $branch_id]);
                flashMessage('success', 'Subject added successfully.');
            } catch (Exception $e) {
                flashMessage('danger', 'Subject already exists in this branch.');
            }
        }
    } elseif (isset($_POST['delete'])) {
        $id = (int)$_POST['id'];
        $db->delete("DELETE FROM subjects WHERE id = ?", [$id]);
        flashMessage('success', 'Subject deleted successfully.');
    } elseif (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        $name = sanitize($_POST['subject_name']);
        $branch_id = (int)$_POST['branch_id'];
        if (!empty($name) && $branch_id > 0) {
            try {
                $db->update("UPDATE subjects SET subject_name = ?, branch_id = ? WHERE id = ?", [$name, $branch_id, $id]);
                flashMessage('success', 'Subject updated successfully.');
            } catch (Exception $e) {
                flashMessage('danger', 'Subject already exists in this branch.');
            }
        }
    }
    redirect('admin/subjects.php');
}

$subjects = $db->fetchAll("SELECT s.*, b.branch_name FROM subjects s JOIN branches b ON s.branch_id = b.id ORDER BY b.branch_name, s.subject_name");
$branches = getBranches();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Subjects - <?= SITE_NAME ?></title>
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
                    <h5 class="fw-bold mb-0"><i class="fas fa-book me-2 text-primary"></i>Manage Subjects</h5>
                    <button class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus me-1"></i>Add Subject
                    </button>
                </div>
                <div class="card-body">
                    <?= displayFlashMessage() ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr><th>#</th><th>Subject</th><th>Branch</th><th>Created</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($subjects as $i => $s): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td class="fw-medium"><?= sanitize($s['subject_name']) ?></td>
                                    <td><span class="badge bg-primary-soft text-primary"><?= sanitize($s['branch_name']) ?></span></td>
                                    <td><small class="text-muted"><?= date('d M Y', strtotime($s['created_at'])) ?></small></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#editModal<?= $s['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this subject? All related chapters and materials will be deleted.')">
                                            <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                            <button type="submit" name="delete" class="btn btn-sm btn-outline-danger rounded-pill">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <div class="modal fade" id="editModal<?= $s['id'] ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header"><h5 class="modal-title fw-bold">Edit Subject</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id" value="<?= $s['id'] ?>">
                                                    <div class="mb-3">
                                                        <label class="form-label fw-medium">Subject Name</label>
                                                        <input type="text" name="subject_name" class="form-control" value="<?= sanitize($s['subject_name']) ?>" required>
                                                    </div>
                                                    <div class="mb-3">
                                                        <label class="form-label fw-medium">Branch</label>
                                                        <select name="branch_id" class="form-select" required>
                                                            <?php foreach ($branches as $b): ?>
                                                            <option value="<?= $b['id'] ?>" <?= $b['id'] == $s['branch_id'] ? 'selected' : '' ?>><?= sanitize($b['branch_name']) ?></option>
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
                                <?php if (empty($subjects)): ?>
                                <tr><td colspan="5" class="text-center text-muted py-4">No subjects found</td></tr>
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
                <div class="modal-header"><h5 class="modal-title fw-bold">Add Subject</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label fw-medium">Subject Name</label>
                            <input type="text" name="subject_name" class="form-control" placeholder="Enter subject name" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-medium">Branch</label>
                            <select name="branch_id" class="form-select" required>
                                <option value="">Select Branch</option>
                                <?php foreach ($branches as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= sanitize($b['branch_name']) ?></option>
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
