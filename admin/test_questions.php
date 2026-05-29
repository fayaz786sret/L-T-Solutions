<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
requireAdmin();

$test_id = (int)($_GET['id'] ?? 0);
$test = $db->fetch("SELECT t.*, s.subject_name, b.branch_name FROM monthly_tests t JOIN subjects s ON t.subject_id = s.id JOIN branches b ON t.branch_id = b.id WHERE t.id = ?", [$test_id]);
if (!$test) { flashMessage('danger', 'Test not found.'); redirect('admin/tests.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_question'])) {
    $db->insert("INSERT INTO test_questions (test_id, question, option_a, option_b, option_c, option_d, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?)",
        [$test_id, sanitize($_POST['question']), sanitize($_POST['option_a']), sanitize($_POST['option_b']), sanitize($_POST['option_c']), sanitize($_POST['option_d']), strtoupper($_POST['correct_answer'])]);
    flashMessage('success', 'Question added.');
    redirect('admin/test_questions.php?id=' . $test_id);
}

if (isset($_GET['delete_q'])) {
    $db->delete("DELETE FROM test_questions WHERE id = ? AND test_id = ?", [(int)$_GET['delete_q'], $test_id]);
    flashMessage('success', 'Question deleted.');
    redirect('admin/test_questions.php?id=' . $test_id);
}

$questions = $db->fetchAll("SELECT * FROM test_questions WHERE test_id = ?", [$test_id]);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Questions - <?= SITE_NAME ?></title>
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
                <div class="card-header bg-transparent border-0 pt-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h5 class="fw-bold mb-1"><i class="fas fa-list-ol me-2 text-primary"></i><?= sanitize($test['title']) ?></h5>
                            <small class="text-muted"><?= sanitize($test['branch_name']) ?> | <?= sanitize($test['subject_name']) ?> | <?= (int)($test['duration_minutes'] ?? 30) ?> min</small>
                        </div>
                        <div>
                            <button class="btn btn-primary btn-sm rounded-pill px-3" data-bs-toggle="modal" data-bs-target="#addQModal"><i class="fas fa-plus me-1"></i>Add Question</button>
                            <a href="tests.php" class="btn btn-light btn-sm rounded-pill px-3"><i class="fas fa-arrow-left me-1"></i>Back</a>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <?= displayFlashMessage() ?>
                    <?php foreach ($questions as $i => $q): ?>
                    <div class="card border shadow-sm mb-3">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <h6 class="fw-bold">Q<?= $i + 1 ?>. <?= sanitize($q['question']) ?></h6>
                                <a href="?id=<?= $test_id ?>&delete_q=<?= $q['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Delete?')"><i class="fas fa-trash"></i></a>
                            </div>
                            <div class="row g-2 mt-2">
                                <div class="col-sm-3"><span class="badge bg-light text-dark p-2 w-100 text-start">A. <?= sanitize($q['option_a']) ?></span></div>
                                <div class="col-sm-3"><span class="badge bg-light text-dark p-2 w-100 text-start">B. <?= sanitize($q['option_b']) ?></span></div>
                                <div class="col-sm-3"><span class="badge bg-light text-dark p-2 w-100 text-start">C. <?= sanitize($q['option_c']) ?></span></div>
                                <div class="col-sm-3"><span class="badge bg-light text-dark p-2 w-100 text-start">D. <?= sanitize($q['option_d']) ?></span></div>
                            </div>
                            <div class="mt-2"><span class="badge bg-success text-white">Correct: <?= strtoupper($q['correct_answer']) ?></span></div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    <?php if (empty($questions)): ?>
                    <p class="text-muted text-center py-4">No questions yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="addQModal">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title fw-bold">Add Question</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3"><textarea name="question" class="form-control" rows="2" required></textarea></div>
                        <div class="row g-2">
                            <div class="col-sm-6"><label>Option A <input type="radio" name="correct_answer" value="A" required class="ms-2"></label><input type="text" name="option_a" class="form-control" required></div>
                            <div class="col-sm-6"><label>Option B <input type="radio" name="correct_answer" value="B" class="ms-2"></label><input type="text" name="option_b" class="form-control" required></div>
                            <div class="col-sm-6"><label>Option C <input type="radio" name="correct_answer" value="C" class="ms-2"></label><input type="text" name="option_c" class="form-control" required></div>
                            <div class="col-sm-6"><label>Option D <input type="radio" name="correct_answer" value="D" class="ms-2"></label><input type="text" name="option_d" class="form-control" required></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="add_question" class="btn btn-primary rounded-pill px-4">Add</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
