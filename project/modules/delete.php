<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/../scripts/db.php';

if (empty($_SESSION['login']) || empty($_SESSION['admin']) || $_SESSION['admin'] !== true) {
    header('Location: ../admin?error=' . urlencode('Доступ запрещен. Требуются права администратора'));
    exit;
}

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ../admin?error=' . urlencode('Неверный ID заявки'));
    exit;
}

$id = (int)$_GET['id'];
$db = db_connect();

try {
    $stmt = $db->prepare("DELETE FROM application_languages WHERE application_id = ?");
    $stmt->execute([$id]);

    $stmt = $db->prepare("DELETE FROM applications WHERE id = ?");
    $stmt->execute([$id]);

    header('Location: ../admin?success=' . urlencode('Заявка успешно удалена'));
    exit;

} catch (PDOException $e) {
    error_log("Delete error: " . $e->getMessage());
    header('Location: ../admin?error=' . urlencode('Ошибка при удалении заявки'));
    exit;
}