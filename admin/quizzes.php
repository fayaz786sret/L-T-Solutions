<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['create'])) {
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $chapter_id = (int)$_POST['chapter_id'];
    $subject_id = (int)$_POST['subject_id'];
    $branch_id = (int)$_POST['branch_id'];

    if (!empty($title) && $chapter_id > 0 && $subject_id > 0 && $branch_id > 0) {
        $subject = getSubjectByBranch($subject_id, $branch_id);
        $chapter = $subject ? getChapterBySubject($chapter_id, $subject_id) : null;

        if (!$subject || !$chapter) {
            flashMessage('danger', 'Invalid branch, subject, or chapter combination.');
            redirect('admin/quizzes.php');
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
            $quiz_id = $db->insert("INSERT INTO quizzes (title, description, chapter_id, subject_id, branch_id, created_by) VALUES (?, ?, ?, ?, ?, ?)",
                [$title, $description, $chapter_id, $subject_id, $branch_id, $_SESSION['user_id']]);

            $insertedQuestions = 0;
            foreach ($questions as $i => $q) {
                if (!empty($q) && isset($correct[$i])) {
                    $db->insert("INSERT INTO quiz_questions (quiz_id, question, option_a, option_b, option_c, option_d, correct_answer) VALUES (?, ?, ?, ?, ?, ?, ?)",
                        [$quiz_id, sanitize($q), sanitize($opt_a[$i] ?? ''), sanitize($opt_b[$i] ?? ''), sanitize($opt_c[$i] ?? ''), sanitize($opt_d[$i] ?? ''), strtoupper($correct[$i])]);
                    $insertedQuestions++;
                }
            }

            if ($insertedQuestions === 0) {
                throw new Exception('Please add at least one complete question to create a quiz.');
            }

            $conn->commit();
            flashMessage('success', 'Quiz created successfully with questions.');
        } catch (Exception $e) {
            $conn->rollBack();
            flashMessage('danger', $e->getMessage());
        }
    } else {
        flashMessage('danger', 'Please fill all required fields.');
    }
    redirect('admin/quizzes.php');
}

if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->delete("DELETE FROM quizzes WHERE id = ?", [$id]);
    flashMessage('success', 'Quiz deleted successfully.');
    redirect('admin/quizzes.php');
}

$quizzes = $db->fetchAll("SELECT q.*, c.chapter_name, s.subject_name, b.branch_name, (SELECT COUNT(*) FROM quiz_questions WHERE quiz_id = q.id) as question_count FROM quizzes q JOIN chapters c ON q.chapter_id = c.id JOIN subjects s ON q.subject_id = s.id JOIN branches b ON q.branch_id = b.id ORDER BY q.created_at DESC");
$branches = getBranches();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quizzes - <?= SITE_NAME ?></title>
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
                    <h5 class="fw-bold mb-0"><i class="fas fa-plus-circle me-2 text-primary"></i>Create Quiz</h5>
                </div>
                <div class="card-body">
                    <?= displayFlashMessage() ?>
                    <form method="POST" id="quizForm">
                        <div class="row g-3 mb-3">
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Branch <span class="text-danger">*</span></label>
                                <select name="branch_id" id="qz_branch" class="form-select" required onchange="loadSubjects(this.value, 'qz_subject')">
                                    <option value="">Select Branch</option>
                                    <?php foreach ($branches as $b): ?>
                                    <option value="<?= $b['id'] ?>"><?= sanitize($b['branch_name']) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Subject <span class="text-danger">*</span></label>
                                <select name="subject_id" id="qz_subject" class="form-select" required onchange="loadChapters(this.value, 'qz_chapter')">
                                    <option value="">Select Subject</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label fw-medium">Chapter <span class="text-danger">*</span></label>
                                <select name="chapter_id" id="qz_chapter" class="form-select" required>
                                    <option value="">Select Chapter</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Quiz Title <span class="text-danger">*</span></label>
                                <input type="text" name="title" class="form-control" placeholder="e.g. Chapter 1 Quiz" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-medium">Description</label>
                                <input type="text" name="description" class="form-control" placeholder="Brief description">
                            </div>
                        </div>

                        <h6 class="fw-bold mb-3"><i class="fas fa-list-ol me-2 text-primary"></i>Questions</h6>
                        <div id="questionsContainer">
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
                                        <div class="col-sm-6">
                                            <div class="input-group">
                                                <span class="input-group-text"><input type="radio" name="correct_answer[0]" value="A" required></span>
                                                <input type="text" name="option_a[]" class="form-control" placeholder="Option A" required>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="input-group">
                                                <span class="input-group-text"><input type="radio" name="correct_answer[0]" value="B"></span>
                                                <input type="text" name="option_b[]" class="form-control" placeholder="Option B" required>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="input-group">
                                                <span class="input-group-text"><input type="radio" name="correct_answer[0]" value="C"></span>
                                                <input type="text" name="option_c[]" class="form-control" placeholder="Option C" required>
                                            </div>
                                        </div>
                                        <div class="col-sm-6">
                                            <div class="input-group">
                                                <span class="input-group-text"><input type="radio" name="correct_answer[0]" value="D"></span>
                                                <input type="text" name="option_d[]" class="form-control" placeholder="Option D" required>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="button" id="addQuestion" class="btn btn-outline-primary rounded-pill btn-sm">
                                <i class="fas fa-plus me-1"></i>Add Question
                            </button>
                            <button type="submit" name="create" class="btn btn-primary rounded-pill px-4">
                                <i class="fas fa-save me-2"></i>Create Quiz
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-header bg-transparent border-0 pt-3">
                    <h5 class="fw-bold mb-0"><i class="fas fa-question-circle me-2 text-primary"></i>Quizzes</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr><th>Title</th><th>Questions</th><th>Chapter</th><th>Subject</th><th>Branch</th><th>Created</th><th>Actions</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quizzes as $q): ?>
                                <tr>
                                    <td class="fw-medium"><?= sanitize($q['title']) ?></td>
                                    <td><span class="badge bg-info-soft text-info"><?= $q['question_count'] ?> Q</span></td>
                                    <td><small><?= sanitize($q['chapter_name']) ?></small></td>
                                    <td><?= sanitize($q['subject_name']) ?></td>
                                    <td><span class="badge bg-primary-soft text-primary"><?= sanitize($q['branch_name']) ?></span></td>
                                    <td><small class="text-muted"><?= date('d M Y', strtotime($q['created_at'])) ?></small></td>
                                    <td>
                                        <a href="quiz_questions.php?id=<?= $q['id'] ?>" class="btn btn-sm btn-outline-info rounded-pill">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="?delete=<?= $q['id'] ?>" class="btn btn-sm btn-outline-danger rounded-pill" onclick="return confirm('Delete this quiz?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php if (empty($quizzes)): ?>
                                <tr><td colspan="7" class="text-center text-muted py-4">No quizzes created yet</td></tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    let questionCount = 1;
    document.getElementById('addQuestion').addEventListener('click', function() {
        const container = document.getElementById('questionsContainer');
        const div = document.createElement('div');
        div.className = 'question-item card border shadow-sm mb-3';
        div.innerHTML = `
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span class="fw-medium question-number">Question ${questionCount + 1}</span>
                    <button type="button" class="btn btn-sm btn-outline-danger rounded-pill remove-question"><i class="fas fa-times"></i></button>
                </div>
                <div class="mb-2">
                    <textarea name="question[]" class="form-control" rows="2" placeholder="Enter question" required></textarea>
                </div>
                <div class="row g-2">
                    <div class="col-sm-6">
                        <div class="input-group">
                            <span class="input-group-text"><input type="radio" name="correct_answer[${questionCount}]" value="A" required></span>
                            <input type="text" name="option_a[]" class="form-control" placeholder="Option A" required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="input-group">
                            <span class="input-group-text"><input type="radio" name="correct_answer[${questionCount}]" value="B"></span>
                            <input type="text" name="option_b[]" class="form-control" placeholder="Option B" required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="input-group">
                            <span class="input-group-text"><input type="radio" name="correct_answer[${questionCount}]" value="C"></span>
                            <input type="text" name="option_c[]" class="form-control" placeholder="Option C" required>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="input-group">
                            <span class="input-group-text"><input type="radio" name="correct_answer[${questionCount}]" value="D"></span>
                            <input type="text" name="option_d[]" class="form-control" placeholder="Option D" required>
                        </div>
                    </div>
                </div>
            </div>
        `;
        container.appendChild(div);
        questionCount++;
        updateQuestionNumbers();
    });

    document.getElementById('questionsContainer').addEventListener('click', function(e) {
        if (e.target.closest('.remove-question')) {
            const item = e.target.closest('.question-item');
            if (document.querySelectorAll('.question-item').length > 1) {
                item.remove();
                questionCount--;
                updateQuestionNumbers();
            } else {
                alert('At least one question is required.');
            }
        }
    });

    function updateQuestionNumbers() {
        document.querySelectorAll('.question-item').forEach((el, i) => {
            el.querySelector('.question-number').textContent = `Question ${i + 1}`;
        });
    }

    async function loadSubjects(branchId, targetId) {
        if (!branchId) { document.getElementById(targetId).innerHTML = '<option value="">Select Subject</option>'; return; }
        const res = await fetch('../ajax.php?action=subjects&branch_id=' + branchId);
        const data = await res.json();
        let html = '<option value="">Select Subject</option>';
        data.forEach(s => { html += `<option value="${s.id}">${s.subject_name}</option>`; });
        document.getElementById(targetId).innerHTML = html;
        document.getElementById('qz_chapter').innerHTML = '<option value="">Select Chapter</option>';
    }

    async function loadChapters(subjectId, targetId) {
        if (!subjectId) { document.getElementById(targetId).innerHTML = '<option value="">Select Chapter</option>'; return; }
        const res = await fetch('../ajax.php?action=chapters&subject_id=' + subjectId);
        const data = await res.json();
        let html = '<option value="">Select Chapter</option>';
        data.forEach(c => { html += `<option value="${c.id}">${c.chapter_name}</option>`; });
        document.getElementById(targetId).innerHTML = html;
    }
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
