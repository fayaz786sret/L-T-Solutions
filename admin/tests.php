<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $duration_minutes = max(1, (int)($_POST['duration_minutes'] ?? 30));
    $branch_id = (int)$_POST['branch_id'];
    $subject_id = (int)$_POST['subject_id'];

    if (!empty($title) && $branch_id > 0 && $subject_id > 0) {
        $subject = getSubjectByBranch($subject_id, $branch_id);
        if (!$subject) {
            flashMessage('danger', 'Invalid branch and subject combination.');
            redirect('admin/tests.php');
        }

        $questions = $_POST['question'] ?? [];
        $opt_a = $_POST['option_a'] ?? [];
        $opt_b = $_POST['option_b'] ?? [];
        $opt_c = $_POST['option_c'] ?? [];
        $opt_d = $_POST['option_d'] ?? [];
        $correct = $_POST['correct_answer'] ?? [];

        $conn = $db->getConnection();
        $conn->beginTransaction();

        try {
            $test_id = $db->insert("INSERT INTO monthly_tests (title, description, duration_minutes, branch_id, subject_id, created_by) VALUES (?, ?, ?, ?, ?, ?)",
                [$title, $description, $duration_minutes, $branch_id, $subject_id, $_SESSION['user_id']]);

            $insertedQuestions = 0;
            foreach ($questions as $i => $q) {
                if (!empty($q) && isset($correct[$i])) {
                    $db->insert("INSERT INTO test_questions (test_id, question, option_a, option_b, option_c, option_d, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?)",
                        [$test_id, sanitize($q), sanitize($opt_a[$i] ?? ''), sanitize($opt_b[$i] ?? ''), sanitize($opt_c[$i] ?? ''), sanitize($opt_d[$i] ?? ''), strtoupper($correct[$i])]);
                    $insertedQuestions++;
                }
            }

            if ($insertedQuestions === 0) {
                throw new Exception('Please add at least one complete question to create a monthly test.');
            }

            $conn->commit();
            flashMessage('success', 'Monthly test created successfully.');
        } catch (Exception $e) {
            $conn->rollBack();
            flashMessage('danger', $e->getMessage());
        }
    } else {
        flashMessage('danger', 'Please fill all required fields.');
    }
    redirect('admin/tests.php');
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->delete("DELETE FROM monthly_tests WHERE id = ?", [$id]);
    flashMessage('success', 'Test deleted successfully.');
    redirect('admin/tests.php');
}

$tests = $db->fetchAll("SELECT t.*, s.subject_name, b.branch_name, (SELECT COUNT(*) FROM test_questions WHERE test_id = t.id) as question_count FROM monthly_tests t JOIN subjects s ON t.subject_id = s.id JOIN branches b ON t.branch_id = b.id ORDER BY t.created_at DESC");
$branches = getBranches();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Monthly Tests - <?= SITE_NAME ?></title>
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
                    <h5 class="fw-bold mb-0"><i class="fas fa-plus-circle me-2 text-primary"></i>Create Monthly Test</h5>
                </div>
                <div class="card-body">
                    <?= displayFlashMessage() ?>
                    <form method="POST" id="testForm">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Branch <span class="text-danger">*</span></label>
                                <select name="branch_id" id="ts_branch" class="form-select" required onchange="loadSubjects(this.value, 'ts_subject')">
                                    <option value="">Select Branch</option>
                                    <?php foreach ($branches as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= sanitize($b['branch_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Subject <span class="text-danger">*</span></label>
                                <select name="subject_id" id="ts_subject" class="form-select" required>
                                    <option value="">Select Subject</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Test Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. Monthly Test - May 2026" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label fw-medium">Description</label>
                                <input type="text" name="description" class="form-control" placeholder="Brief description">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Duration (Minutes) <span class="text-danger">*</span></label>
                                <input type="number" name="duration_minutes" class="form-control" min="1" max="300" value="30" required>
                                <small class="text-muted">This time will be shown to students and enforced during the test.</small>
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3"><i class="fas fa-list-ol me-2 text-primary"></i>Questions</h6>
                        <div id="testQuestionsContainer">
                            <div class="question-item card border shadow-sm mb-3">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <span class="fw-medium question-number">Question 1</span>
                                        <button type="button" class="btn btn-sm btn-outline-danger rounded-pill remove-question"><i class="fas fa-times"></i></button>
                                    </div>
                                    <div class="mb-2">
                                        <textarea name="question[]" class="form-control" rows="2" placeholder="Enter question" required></textarea>
                                    </div>
                                    <div class="row g-2">
                                        <div class="col-sm-6"><div class="input-group"><span class="input-group-text"><input type="radio" name="correct_answer[0]" value="A" required></span><input type="text" name="option_a[]" class="form-control" placeholder="Option A" required></div></div>
                                        <div class="col-sm-6"><div class="input-group"><span class="input-group-text"><input type="radio" name="correct_answer[0]" value="B"></span><input type="text" name="option_b[]" class="form-control" placeholder="Option B" required></div></div>
                                        <div class="col-sm-6"><div class="input-group"><span class="input-group-text"><input type="radio" name="correct_answer[0]" value="C"></span><input type="text" name="option_c[]" class="form-control" placeholder="Option C" required></div></div>
                                        <div class="col-sm-6"><div class="input-group"><span class="input-group-text"><input type="radio" name="correct_answer[0]" value="D"></span><input type="text" name="option_d[]" class="form-control" placeholder="Option D" required></div></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" id="addQuestion" class="btn btn-outline-primary rounded-pill btn-sm">
                                <i class="fas fa-plus me-1"></i>Add Question
                            </button>
                            <button type="submit" name="create" class="btn btn-primary rounded-pill px-4">
                                <i class="fas fa-save me-2"></i>Create Test
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-calendar-check me-2 text-primary"></i>Monthly Tests</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr><th>Title</th><th>Duration</th><th>Questions</th><th>Subject</th><th>Branch</th><th>Created</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tests as $t): ?>
                                <tr>
                                    <td class="fw-medium"><?= sanitize($t['title']) ?></td>
                                    <td><span class="badge bg-warning-soft text-warning"><?= (int)($t['duration_minutes'] ?? 30) ?> min</span></td>
                                    <td><span class="badge bg-info-soft text-info"><?= $t['question_count'] ?> Q</span></td>
                                    <td><?= sanitize($t['subject_name']) ?></td>
                                    <td><span class="badge bg-primary-soft text-primary"><?= sanitize($t['branch_name']) ?></span></td>
                                    <td><small class="text-muted"><?= date('d M Y', strtotime($t['created_at'])) ?></small></td>
                                    <td>
                                        <a href="test_questions.php?id=<?= $t['id'] ?>" class="btn btn-sm btn-outline-info rounded-pill"><i class="fas fa-eye"></i></a>
                                        <a href="?delete=<?= $t['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Delete this test?')"><i class="fas fa-trash"></i></a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($tests)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">No tests created yet</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    let qCount = 1;
    document.getElementById('addQuestion').addEventListener('click', function() {
        const c = document.getElementById('testQuestionsContainer');
        const div = document.createElement('div');
        div.className = 'question-item card border shadow-sm mb-3';
        div.innerHTML = `
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-medium question-number">Question ${qCount + 1}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill remove-question"><i class="fas fa-times"></i></button>
                </div>
                <div class="mb-2"><textarea name="question[]" class="form-control" rows="2" placeholder="Enter question" required></textarea></div>
                <div class="row g-2">
                    <div class="col-sm-6"><div class="input-group"><span class="input-group-text"><input type="radio" name="correct_answer[${qCount}]" value="A" required></span><input type="text" name="option_a[]" class="form-control" placeholder="Option A" required></div></div>
                    <div class="col-sm-6"><div class="input-group"><span class="input-group-text"><input type="radio" name="correct_answer[${qCount}]" value="B"></span><input type="text" name="option_b[]" class="form-control" placeholder="Option B" required></div></div>
                    <div class="col-sm-6"><div class="input-group"><span class="input-group-text"><input type="radio" name="correct_answer[${qCount}]" value="C"></span><input type="text" name="option_c[]" class="form-control" placeholder="Option C" required></div></div>
                    <div class="col-sm-6"><div class="input-group"><span class="input-group-text"><input type="radio" name="correct_answer[${qCount}]" value="D"></span><input type="text" name="option_d[]" class="form-control" placeholder="Option D" required></div></div>
                </div>
            </div>
        `;
        c.appendChild(div); qCount++; updateQNums();
    });
    document.getElementById('testQuestionsContainer').addEventListener('click', function(e) {
        if (e.target.closest('.remove-question')) {
            const item = e.target.closest('.question-item');
            if (document.querySelectorAll('#testQuestionsContainer .question-item').length > 1) { item.remove(); qCount--; updateQNums(); }
            else alert('At least one question required.');
        }
    });
    function updateQNums() {
        document.querySelectorAll('#testQuestionsContainer .question-item').forEach((el, i) => el.querySelector('.question-number').textContent = `Question ${i + 1}`);
    }
    async function loadSubjects(branchId, targetId) {
        if (!branchId) { document.getElementById(targetId).innerHTML = '<option value="">Select Subject</option>'; return; }
        const res = await fetch('../ajax.php?action=subjects&branch_id=' + branchId);
        const data = await res.json();
        let html = '<option value="">Select Subject</option>';
        data.forEach(s => { html += `<option value="${s.id}">${s.subject_name}</option>`; });
        document.getElementById(targetId).innerHTML = html;
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
