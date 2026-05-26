<?php
require_once '../includes/config.php';
require_once '../includes/db.php';
requireStudent();

$quiz_id = (int)($_GET['id'] ?? 0);
$branch_id = $_SESSION['branch_id'];
$student_id = $_SESSION['user_id'];

$quiz = $db->fetch("SELECT q.*, c.chapter_name, s.subject_name FROM quizzes q JOIN chapters c ON q.chapter_id = c.id JOIN subjects s ON q.subject_id = s.id WHERE q.id = ? AND q.branch_id = ?", [$quiz_id, $branch_id]);
if (!$quiz) { flashMessage('danger', 'Quiz not found.'); redirect('student/quizzes.php'); }

$attempted = $db->fetch("SELECT id FROM quiz_attempts WHERE quiz_id = ? AND student_id = ?", [$quiz_id, $student_id]);
if ($attempted) { flashMessage('warning', 'You have already attempted this quiz.'); redirect('student/quizzes.php'); }

$questions = $db->fetchAll("SELECT * FROM quiz_questions WHERE quiz_id = ?", [$quiz_id]);
if (empty($questions)) { flashMessage('danger', 'No questions in this quiz.'); redirect('student/quizzes.php'); }

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    $answers = $_POST['answer'] ?? [];
    $score = 0;
    $total = count($questions);

    foreach ($questions as $q) {
        $ans = strtoupper($answers[$q['id']] ?? '');
        if ($ans === strtoupper($q['correct_answer'])) {
            $score++;
        }
    }

    try {
        $db->insert("INSERT INTO quiz_attempts (student_id, quiz_id, score, total_questions) VALUES (?, ?, ?, ?)",
            [$student_id, $quiz_id, $score, $total]);
        flashMessage('success', "Quiz completed! You scored $score out of $total.");
    } catch (Exception $e) {
        flashMessage('warning', 'Your quiz was already submitted.');
    }
    redirect('student/quizzes.php');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= sanitize($quiz['title']) ?> - <?= SITE_NAME ?></title>
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
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h4 class="fw-bold mb-1"><?= sanitize($quiz['title']) ?></h4>
                            <small class="text-muted"><?= sanitize($quiz['subject_name']) ?> | <?= sanitize($quiz['chapter_name']) ?> | <?= count($questions) ?> Questions</small>
                        </div>
                        <div id="timer" class="fs-4 fw-bold text-primary"></div>
                    </div>
                </div>
            </div>

            <form method="POST" id="quizForm">
                <?php foreach ($questions as $i => $q): ?>
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body">
                        <h6 class="fw-bold mb-3">Q<?= $i + 1 ?>. <?= sanitize($q['question']) ?></h6>
                        <div class="row g-2">
                            <?php foreach (['A', 'B', 'C', 'D'] as $opt): ?>
                            <div class="col-sm-6">
                                <div class="form-check quiz-option">
                                    <input class="form-check-input" type="radio" name="answer[<?= $q['id'] ?>]" value="<?= $opt ?>" id="q<?= $q['id'] . $opt ?>" required>
                                    <label class="form-check-label w-100 p-2 rounded" for="q<?= $q['id'] . $opt ?>">
                                        <strong><?= $opt ?>.</strong> <?= sanitize($q['option_' . strtolower($opt)]) ?>
                                    </label>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <div class="text-center">
                    <button type="submit" name="submit_quiz" class="btn btn-primary btn-lg rounded-pill px-5" onclick="return confirm('Submit quiz? You cannot change answers after submission.')">
                        <i class="fas fa-check-circle me-2"></i>Submit Quiz
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
    let timeLeft = 600;
    function updateTimer() {
        const m = Math.floor(timeLeft / 60);
        const s = timeLeft % 60;
        document.getElementById('timer').textContent = `${m.toString().padStart(2,'0')}:${s.toString().padStart(2,'0')}`;
        if (timeLeft <= 0) { document.getElementById('quizForm').submit(); }
        else { timeLeft--; setTimeout(updateTimer, 1000); }
    }
    updateTimer();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
