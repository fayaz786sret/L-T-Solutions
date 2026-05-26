<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['add'])) {
        $name = sanitize($_POST['branch_name']);
        if (!empty($name)) {
            try {
                $db->insert("INSERT INTO branches (branch_name) VALUES (?)", [$name]);
                flashMessage('success', 'Branch added successfully.');
            } catch (Exception $e) {
                flashMessage('danger', 'Branch already exists.');
            }
        }
    } elseif (isset($_POST['delete'])) {
        $id = (int)$_POST['id'];
        $db->delete("DELETE FROM branches WHERE id = ?", [$id]);
        flashMessage('success', 'Branch deleted successfully.');
    } elseif (isset($_POST['edit'])) {
        $id = (int)$_POST['id'];
        $name = sanitize($_POST['branch_name']);
        if (!empty($name)) {
            try {
                $db->update("UPDATE branches SET branch_name = ? WHERE id = ?", [$name, $id]);
                flashMessage('success', 'Branch updated successfully.');
            } catch (Exception $e) {
                flashMessage('danger', 'Branch name already exists.');
            }
        }
    }
    redirect('admin/branches.php');
}

$branches = getBranches();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Branches - <?= SITE_NAME ?></title>
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
                    <h5 class="fw-bold mb-0"><i class="fas fa-sitemap me-2 text-primary"></i>Manage Branches</h5>
                    <button class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addModal">
                        <i class="fas fa-plus me-1"></i>Add Branch
                    </button>
                </div>
                <div class="card-body">
                    <?= displayFlashMessage() ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr><th>#</th><th>Branch Name</th><th>Created</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($branches as $i => $b): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td class="fw-medium"><?= sanitize($b['branch_name']) ?></td>
                                    <td><small class="text-muted"><?= date('d M Y', strtotime($b['created_at'])) ?></small></td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary rounded-pill" data-bs-toggle="modal" data-bs-target="#editModal<?= $b['id'] ?>">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this branch?')">
                                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                            <button type="submit" name="delete" class="btn btn-sm btn-outline-danger rounded-pill">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                <div class="modal fade" id="editModal<?= $b['id'] ?>">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header"><h5 class="modal-title fw-bold">Edit Branch</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                            <form method="POST">
                                                <div class="modal-body">
                                                    <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                                    <label class="form-label fw-medium">Branch Name</label>
                                                    <input type="text" name="branch_name" class="form-control" value="<?= sanitize($b['branch_name']) ?>" required>
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
                                <?php if (empty($branches)): ?>
                                <tr><td colspan="4" class="text-center text-muted py-4">No branches found</td></tr>
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
                <div class="modal-header"><h5 class="modal-title fw-bold">Add Branch</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <div class="modal-body">
                        <label class="form-label fw-medium">Branch Name</label>
                        <input type="text" name="branch_name" class="form-control" placeholder="Enter branch name" required>
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
