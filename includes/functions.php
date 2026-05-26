<?php
require_once 'db.php';

function isLoggedIn() {
    return isset($_SESSION['user_id']) && isset($_SESSION['user_type']);
}

function isAdmin() {
    return isLoggedIn() && $_SESSION['user_type'] === 'admin';
}

function isStudent() {
    return isLoggedIn() && $_SESSION['user_type'] === 'student';
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: ' . getUrl('login.php'));
        exit;
    }
}

function requireAdmin() {
    if (!isAdmin()) {
        header('Location: ' . getUrl('login.php'));
        exit;
    }
}

function requireStudent() {
    if (!isStudent()) {
        header('Location: ' . getUrl('login.php'));
        exit;
    }
}

function getUrl($path = '') {
    return SITE_URL . '/' . ltrim($path, '/');
}

function redirect($path) {
    header('Location: ' . getUrl($path));
    exit;
}

function sanitize($input) {
    return htmlspecialchars(strip_tags(trim($input)), ENT_QUOTES, 'UTF-8');
}

function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

function hashPassword($password) {
    return password_hash($password, PASSWORD_BCRYPT);
}

function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

function generateOTP($length = 6) {
    return str_pad(random_int(0, pow(10, $length) - 1), $length, '0', STR_PAD_LEFT);
}

function generateAdmissionNo($studentId, $prefix = 'LH') {
    return sprintf('%s-%d-%04d', $prefix, date('Y'), (int)$studentId);
}

function isOfficeDocument($fileType) {
    return in_array(strtolower($fileType), ['doc', 'docx', 'ppt', 'pptx'], true);
}

function getMaterialPreviewPath($filePath, $fileType) {
    if (strtolower($fileType) === 'pdf') {
        return $filePath;
    }

    $baseName = pathinfo($filePath, PATHINFO_FILENAME) . '.pdf';
    return 'previews/' . $baseName;
}

function convertOfficeDocumentToPdf($sourcePath, $targetPath, $fileType) {
    $sourcePath = realpath($sourcePath);
    if ($sourcePath === false) {
        return ['success' => false, 'message' => 'Source file was not found.'];
    }

    $targetDirectory = dirname($targetPath);
    if (!is_dir($targetDirectory)) {
        mkdir($targetDirectory, 0755, true);
    }

    $isWord = in_array(strtolower($fileType), ['doc', 'docx'], true);
    $sourcePathPs = str_replace("'", "''", $sourcePath);
    $targetPathPs = str_replace("'", "''", $targetPath);
    $script = $isWord ? <<<'PS'
try {
    $word = New-Object -ComObject Word.Application
    $word.Visible = $false
    $doc = $word.Documents.Open('__SOURCE__', $false, $true)
    $doc.ExportAsFixedFormat('__TARGET__', 17)
    $doc.Close()
    $word.Quit()
    exit 0
} catch {
    exit 1
}
PS
: <<<'PS'
try {
    $ppt = New-Object -ComObject PowerPoint.Application
    $ppt.Visible = 1
    $pres = $ppt.Presentations.Open('__SOURCE__', $true, $false, $false)
    $pres.SaveAs('__TARGET__', 32)
    $pres.Close()
    $ppt.Quit()
    exit 0
} catch {
    exit 1
}
PS;

    $script = str_replace(['__SOURCE__', '__TARGET__'], [$sourcePathPs, $targetPathPs], $script);
    $tempScript = tempnam(sys_get_temp_dir(), 'lh_pdf_') . '.ps1';
    file_put_contents($tempScript, $script);
    $powershell = 'powershell -NoProfile -ExecutionPolicy Bypass -File ' . escapeshellarg($tempScript);
    $output = [];
    $exitCode = 0;
    exec($powershell, $output, $exitCode);
    @unlink($tempScript);

    if ($exitCode !== 0 || !file_exists($targetPath)) {
        return ['success' => false, 'message' => 'Could not convert office document to preview PDF.'];
    }

    return ['success' => true, 'path' => $targetPath];
}

function sendEmail($to, $subject, $message) {
    if (class_exists('PHPMailer\\PHPMailer\\PHPMailer')) {
        try {
            $mailer = new PHPMailer\PHPMailer\PHPMailer(true);
            $mailer->isSMTP();
            $mailer->Host = SMTP_HOST;
            $mailer->Port = SMTP_PORT;
            $mailer->SMTPAuth = true;
            $mailer->Username = SMTP_USER;
            $mailer->Password = SMTP_PASS;
            $mailer->SMTPSecure = PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
            $mailer->setFrom(SMTP_FROM, SMTP_FROM_NAME);
            $mailer->addAddress($to);
            $mailer->isHTML(true);
            $mailer->Subject = $subject;
            $mailer->Body = $message;
                return $mailer->send();
        } catch (Exception $e) {
            // Fallback to native mail() when SMTP transport fails.
        }
    }

        // Try direct SMTP connection as a fallback before using mail()
        $smtpResult = smtpSend($to, $subject, $message);
        if ($smtpResult === true) {
            return true;
        }

        // As a last resort, try native mail()
        $headers = "From: " . SMTP_FROM_NAME . " <" . SMTP_FROM . ">\r\n";
        $headers .= "Reply-To: " . SMTP_FROM . "\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $mailResult = mail($to, $subject, $message, $headers);
        if ($mailResult) {
            return true;
        }

        // Log the SMTP error for debugging
        $logDir = __DIR__ . '/../storage';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0755, true);
        }
        $logFile = $logDir . '/email_errors.log';
        $err = date('Y-m-d H:i:s') . " - sendEmail failed for {$to}. smtp: " . json_encode($smtpResult) . " mail(): " . ($mailResult ? 'ok' : 'failed') . "\n";
        @file_put_contents($logFile, $err, FILE_APPEND);

        return false;
}

    /**
     * Minimal SMTP sender using STARTTLS + AUTH LOGIN for servers like Gmail.
     * Returns true on success, or an array with error info on failure.
     */
    function smtpSend($to, $subject, $message) {
        $host = SMTP_HOST;
        $port = SMTP_PORT ?: 587;
        $user = SMTP_USER;
        $pass = SMTP_PASS;
        $from = SMTP_FROM;
        $fromName = SMTP_FROM_NAME ?: $from;

        $timeout = 30;
        // Relax peer verification for development environments where CA bundle may be missing.
        $ctx = stream_context_create(['ssl' => ['verify_peer' => false, 'verify_peer_name' => false]]);
        $errNo = 0; $errStr = '';
        $fp = @stream_socket_client($host . ':' . $port, $errNo, $errStr, $timeout, STREAM_CLIENT_CONNECT, $ctx);
        if (!$fp) {
            return ['error' => 'connect_failed', 'details' => $errStr, 'errno' => $errNo];
        }
        stream_set_timeout($fp, $timeout);

        $res = fgets($fp, 515);
        if (strpos($res, '220') !== 0) {
            fclose($fp);
            return ['error' => 'no_banner', 'details' => $res];
        }

        $localhost = gethostname() ?: 'localhost';
        fwrite($fp, "EHLO {$localhost}\r\n");
        // consume multi-line EHLO response (lines starting with 250-)
        while (($line = fgets($fp, 515)) !== false) {
            if (strlen($line) >= 4 && $line[3] === '-') {
                continue;
            }
            break;
        }
        // Some servers require STARTTLS
        fwrite($fp, "STARTTLS\r\n");
        $start = fgets($fp, 515);
        if (strpos($start, '220') === 0) {
            if (!stream_socket_enable_crypto($fp, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                fclose($fp);
                return ['error' => 'starttls_failed', 'details' => $start];
            }
            // EHLO again after STARTTLS
            fwrite($fp, "EHLO {$localhost}\r\n");
            // consume response
            usleep(100000);
            while (($line = fgets($fp, 515)) !== false) {
                if (substr($line, 3, 1) !== '-') break;
            }
        }

        // AUTH LOGIN
        fwrite($fp, "AUTH LOGIN\r\n");
        $auth = fgets($fp, 515);
        if (strpos($auth, '334') !== 0) {
            fclose($fp);
            return ['error' => 'auth_not_supported', 'details' => $auth];
        }
        fwrite($fp, base64_encode($user) . "\r\n");
        $userResp = fgets($fp, 515);
        fwrite($fp, base64_encode($pass) . "\r\n");
        $passResp = fgets($fp, 515);
        if (strpos($passResp, '235') !== 0) {
            fclose($fp);
            return ['error' => 'auth_failed', 'details' => $passResp];
        }

        // MAIL FROM
        fwrite($fp, "MAIL FROM:<{$from}>\r\n");
        $mailFromResp = fgets($fp, 515);
        if (strpos($mailFromResp, '250') !== 0) {
            fclose($fp);
            return ['error' => 'mail_from_failed', 'details' => $mailFromResp];
        }

        // RCPT TO
        fwrite($fp, "RCPT TO:<{$to}>\r\n");
        $rcptResp = fgets($fp, 515);
        if (strpos($rcptResp, '250') !== 0 && strpos($rcptResp, '251') !== 0) {
            fclose($fp);
            return ['error' => 'rcpt_failed', 'details' => $rcptResp];
        }

        // DATA
        fwrite($fp, "DATA\r\n");
        $dataResp = fgets($fp, 515);
        if (strpos($dataResp, '354') !== 0) {
            fclose($fp);
            return ['error' => 'data_not_accepted', 'details' => $dataResp];
        }

        $headers = [];
        $headers[] = 'From: ' . $fromName . ' <' . $from . '>';
        $headers[] = 'To: ' . $to;
        $headers[] = 'Subject: ' . $subject;
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/html; charset=UTF-8';
        $headerStr = implode("\r\n", $headers) . "\r\n\r\n";

        $data = $headerStr . $message . "\r\n.\r\n";
        fwrite($fp, $data);
        $dataOk = fgets($fp, 515);
        if (strpos($dataOk, '250') !== 0) {
            fclose($fp);
            return ['error' => 'data_failed', 'details' => $dataOk];
        }

        fwrite($fp, "QUIT\r\n");
        fclose($fp);
        return true;
    }

function sendOtpEmail($to, $otp) {
    $subject = "Password Reset OTP - " . SITE_NAME;
    $message = "
    <html>
    <head><style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f4f7fc; margin: 0; padding: 30px; }
        .container { max-width: 500px; margin: 0 auto; background: #fff; border-radius: 12px; overflow: hidden; box-shadow: 0 4px 20px rgba(0,0,0,0.08); }
        .header { background: linear-gradient(135deg, #4f46e5, #7c3aed); padding: 30px; text-align: center; }
        .header h1 { color: #fff; margin: 0; font-size: 24px; }
        .body { padding: 30px; }
        .otp-box { background: #f0f4ff; border: 2px dashed #4f46e5; border-radius: 10px; padding: 20px; text-align: center; margin: 20px 0; }
        .otp-code { font-size: 36px; font-weight: bold; color: #4f46e5; letter-spacing: 8px; }
        .footer { text-align: center; padding: 20px; color: #94a3b8; font-size: 12px; border-top: 1px solid #e2e8f0; }
    </style></head>
    <body>
        <div class='container'>
            <div class='header'><h1>" . SITE_NAME . "</h1></div>
            <div class='body'>
                <h2 style='color:#1e293b;'>Password Reset Request</h2>
                <p style='color:#64748b;'>You requested to reset your password. Use the OTP below:</p>
                <div class='otp-box'>
                    <div class='otp-code'>$otp</div>
                </div>
                <p style='color:#64748b;font-size:14px;'>This OTP is valid for 10 minutes. If you didn't request this, ignore this email.</p>
            </div>
            <div class='footer'>&copy; " . date('Y') . " " . SITE_NAME . ". All rights reserved.</div>
        </div>
    </body>
    </html>";
    return sendEmail($to, $subject, $message);
}

function validateFileType($file, $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx']) {
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    return in_array($ext, $allowed_types);
}

function uploadFile($file, $destination_dir, $allowed_types = ['pdf', 'doc', 'docx', 'ppt', 'pptx']) {
    if ($file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Upload failed with error code: ' . $file['error']];
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed_types)) {
        return ['success' => false, 'message' => 'Invalid file type. Allowed: ' . implode(', ', $allowed_types)];
    }

    $max_size = 50 * 1024 * 1024;
    if ($file['size'] > $max_size) {
        return ['success' => false, 'message' => 'File too large. Max 50MB.'];
    }

    if (!is_dir($destination_dir)) {
        mkdir($destination_dir, 0755, true);
    }

    $filename = time() . '_' . bin2hex(random_bytes(8)) . '.' . $ext;
    $destination = $destination_dir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $destination)) {
        return ['success' => true, 'filename' => $filename, 'path' => $destination];
    }

    return ['success' => false, 'message' => 'Failed to move uploaded file.'];
}

function getFileIcon($file_type) {
    $icons = [
        'pdf' => 'fa-file-pdf text-danger',
        'doc' => 'fa-file-word text-primary',
        'docx' => 'fa-file-word text-primary',
        'ppt' => 'fa-file-powerpoint text-warning',
        'pptx' => 'fa-file-powerpoint text-warning',
    ];
    return isset($icons[$file_type]) ? $icons[$file_type] : 'fa-file text-secondary';
}

function getFileTypeLabel($file_type) {
    $labels = [
        'pdf' => 'PDF',
        'doc' => 'Word',
        'docx' => 'Word',
        'ppt' => 'PPT',
        'pptx' => 'PPT',
    ];
    return isset($labels[$file_type]) ? $labels[$file_type] : strtoupper($file_type);
}

function flashMessage($type, $message) {
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function displayFlashMessage() {
    if (isset($_SESSION['flash'])) {
        $type = $_SESSION['flash']['type'];
        $message = $_SESSION['flash']['message'];
        unset($_SESSION['flash']);
        $icons = [
            'success' => 'fa-check-circle',
            'danger' => 'fa-exclamation-circle',
            'warning' => 'fa-exclamation-triangle',
            'info' => 'fa-info-circle'
        ];
        $icon = isset($icons[$type]) ? $icons[$type] : 'fa-info-circle';
        return "<div class='alert alert-$type alert-dismissible fade show shadow-sm' role='alert'>
            <i class='fas $icon me-2'></i>$message
            <button type='button' class='btn-close' data-bs-dismiss='alert'></button>
        </div>";
    }
    return '';
}

function getBranches() {
    global $db;
    return $db->fetchAll("SELECT * FROM branches ORDER BY branch_name");
}

function getSubjects($branch_id = null) {
    global $db;
    if ($branch_id) {
        return $db->fetchAll("SELECT * FROM subjects WHERE branch_id = ? ORDER BY subject_name", [$branch_id]);
    }
    return $db->fetchAll("SELECT s.*, b.branch_name FROM subjects s JOIN branches b ON s.branch_id = b.id ORDER BY b.branch_name, s.subject_name");
}

function getChapters($subject_id = null) {
    global $db;
    if ($subject_id) {
        return $db->fetchAll("SELECT * FROM chapters WHERE subject_id = ? ORDER BY chapter_no IS NULL, chapter_no, chapter_name", [$subject_id]);
    }
    return $db->fetchAll("SELECT c.*, s.subject_name FROM chapters c JOIN subjects s ON c.subject_id = s.id ORDER BY s.subject_name, c.chapter_no IS NULL, c.chapter_no, c.chapter_name");
}

function getSubjectByBranch($subject_id, $branch_id) {
    global $db;
    return $db->fetch("SELECT id, subject_name, branch_id FROM subjects WHERE id = ? AND branch_id = ?", [$subject_id, $branch_id]);
}

function ensureLearningPlatformSchema() {
    global $db;
    static $schemaChecked = false;

    if ($schemaChecked) {
        return;
    }

    $schemaChecked = true;

    try {
        $chapterNoColumn = $db->fetch("SHOW COLUMNS FROM chapters LIKE 'chapter_no'");
        if (!$chapterNoColumn) {
            $db->query("ALTER TABLE chapters ADD COLUMN chapter_no INT DEFAULT NULL AFTER chapter_name");
        }
    } catch (Exception $e) {
        // Keep the application running even if the migration cannot be applied here.
    }
}

function getOrCreateSubjectByBranch($branch_id, $subject_name) {
    global $db;

    $branch_id = (int)$branch_id;
    $subject_name = trim($subject_name);

    if ($branch_id <= 0 || $subject_name === '') {
        return false;
    }

    $subject = $db->fetch("SELECT id, subject_name, branch_id FROM subjects WHERE branch_id = ? AND subject_name = ? LIMIT 1", [$branch_id, $subject_name]);
    if ($subject) {
        return $subject;
    }

    try {
        $subjectId = $db->insert("INSERT INTO subjects (subject_name, branch_id) VALUES (?, ?)", [$subject_name, $branch_id]);
        return $db->fetch("SELECT id, subject_name, branch_id FROM subjects WHERE id = ?", [$subjectId]);
    } catch (Exception $e) {
        $subject = $db->fetch("SELECT id, subject_name, branch_id FROM subjects WHERE branch_id = ? AND subject_name = ? LIMIT 1", [$branch_id, $subject_name]);
        return $subject ?: false;
    }
}

function getOrCreateChapterBySubject($subject_id, $chapter_name, $chapter_no = null) {
    global $db;

    $subject_id = (int)$subject_id;
    $chapter_name = trim($chapter_name);
    $chapter_no = $chapter_no !== null && $chapter_no !== '' ? (int)$chapter_no : null;

    if ($subject_id <= 0 || $chapter_name === '') {
        return false;
    }

    if ($chapter_no !== null) {
        $chapter = $db->fetch("SELECT id, chapter_name, chapter_no, subject_id FROM chapters WHERE subject_id = ? AND chapter_no = ? LIMIT 1", [$subject_id, $chapter_no]);
        if ($chapter) {
            if ($chapter['chapter_name'] !== $chapter_name) {
                $db->update("UPDATE chapters SET chapter_name = ? WHERE id = ?", [$chapter_name, $chapter['id']]);
            }
            return $db->fetch("SELECT id, chapter_name, chapter_no, subject_id FROM chapters WHERE id = ?", [$chapter['id']]);
        }
    }

    $chapter = $db->fetch("SELECT id, chapter_name, chapter_no, subject_id FROM chapters WHERE subject_id = ? AND chapter_name = ? LIMIT 1", [$subject_id, $chapter_name]);
    if ($chapter) {
        if ($chapter_no !== null && ($chapter['chapter_no'] === null || (int)$chapter['chapter_no'] !== $chapter_no)) {
            $db->update("UPDATE chapters SET chapter_no = ? WHERE id = ?", [$chapter_no, $chapter['id']]);
        }
        return $db->fetch("SELECT id, chapter_name, chapter_no, subject_id FROM chapters WHERE id = ?", [$chapter['id']]);
    }

    try {
        $chapterId = $db->insert("INSERT INTO chapters (chapter_name, chapter_no, subject_id) VALUES (?, ?, ?)", [$chapter_name, $chapter_no, $subject_id]);
        return $db->fetch("SELECT id, chapter_name, chapter_no, subject_id FROM chapters WHERE id = ?", [$chapterId]);
    } catch (Exception $e) {
        $chapter = $db->fetch("SELECT id, chapter_name, chapter_no, subject_id FROM chapters WHERE subject_id = ? AND chapter_name = ? LIMIT 1", [$subject_id, $chapter_name]);
        return $chapter ?: false;
    }
}

function getChapterBySubject($chapter_id, $subject_id) {
    global $db;
    return $db->fetch("SELECT id, chapter_name, subject_id FROM chapters WHERE id = ? AND subject_id = ?", [$chapter_id, $subject_id]);
}
