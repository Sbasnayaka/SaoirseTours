<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (isset($_GET['id']) && isset($_GET['page_id'])) {
    $stmt = $pdo->prepare("DELETE FROM sections WHERE id = ?");
    $stmt->execute([$_GET['id']]);

    header('Location: page_sections.php?page_id=' . $_GET['page_id']);
    exit;
}

header('Location: pages.php');
exit;
?>