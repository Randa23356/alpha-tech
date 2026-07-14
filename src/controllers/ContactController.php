<?php
require_once __DIR__ . '/../config/db.php';

class ContactController {
    private $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function submitMessage() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            header('Location: /informatics_a/contact');
            exit;
        }

        // Sanitize input
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $subject = trim($_POST['subject'] ?? '');
        $message = trim($_POST['message'] ?? '');

        // Validation
        if (empty($name) || empty($email) || empty($subject) || empty($message)) {
            $_SESSION['error'] = 'Semua field harus diisi.';
            header('Location: /informatics_a/contact');
            exit;
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'Email tidak valid.';
            header('Location: /informatics_a/contact');
            exit;
        }

        try {
            $stmt = $this->pdo->prepare("INSERT INTO contact_messages (name, email, subject, message) VALUES (?, ?, ?, ?)");
            $stmt->execute([$name, $email, $subject, $message]);

            $_SESSION['success'] = 'Pesan berhasil dikirim. Admin akan segera merespons.';
            header('Location: /informatics_a/contact');
            exit;
        } catch (PDOException $e) {
            error_log('Contact form error: ' . $e->getMessage());
            $_SESSION['error'] = 'Terjadi kesalahan saat mengirim pesan. Silakan coba lagi.';
            header('Location: /informatics_a/contact');
            exit;
        }
    }

    public function getMessages($limit = 50, $offset = 0) {
        $stmt = $this->pdo->prepare("SELECT * FROM contact_messages ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->bindValue(1, $limit, PDO::PARAM_INT);
        $stmt->bindValue(2, $offset, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function markAsRead($id) {
        $stmt = $this->pdo->prepare("UPDATE contact_messages SET status = 'read' WHERE id = ?");
        return $stmt->execute([$id]);
    }

    public function markAllAsRead() {
        $stmt = $this->pdo->query("UPDATE contact_messages SET status = 'read' WHERE status = 'unread'");
        return $stmt->execute();
    }

    public function getUnreadCount() {
        $stmt = $this->pdo->query("SELECT COUNT(*) FROM contact_messages WHERE status = 'unread'");
        return $stmt->fetchColumn();
    }

    public function getMessage($id) {
        $stmt = $this->pdo->prepare("SELECT * FROM contact_messages WHERE id = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
