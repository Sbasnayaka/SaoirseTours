<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM pages WHERE id = ?");
    $stmt->execute([$_GET['id']]);
}

header('Location: pages.php');
exit;
?>