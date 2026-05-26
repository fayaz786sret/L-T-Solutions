<?php
require_once 'includes/config.php';
require_once 'includes/db.php';

header('Content-Type: application/json');

$action = $_GET['action'] ?? '';

if ($action === 'subjects' && isset($_GET['branch_id'])) {
    $branch_id = (int)$_GET['branch_id'];

    if (isStudent()) {
        $branch_id = (int)$_SESSION['branch_id'];
    }

    $subjects = $db->fetchAll("SELECT id, subject_name FROM subjects WHERE branch_id = ? ORDER BY subject_name", [$branch_id]);
    echo json_encode($subjects);
    exit;
}

if ($action === 'chapters' && isset($_GET['subject_id'])) {
    $subject_id = (int)$_GET['subject_id'];

    if (isStudent()) {
        $allowedSubject = $db->fetch("SELECT id FROM subjects WHERE id = ? AND branch_id = ?", [$subject_id, (int)$_SESSION['branch_id']]);
        if (!$allowedSubject) {
            echo json_encode([]);
            exit;
        }
    }

    $chapters = $db->fetchAll("SELECT id, chapter_name, chapter_no FROM chapters WHERE subject_id = ? ORDER BY chapter_no IS NULL, chapter_no, chapter_name", [$subject_id]);
    echo json_encode($chapters);
    exit;
}

echo json_encode([]);
