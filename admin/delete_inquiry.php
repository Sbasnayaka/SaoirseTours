<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (isset($_GET['id'])) {
    $stmt = $pdo->prepare("DELETE FROM inquiries WHERE id = ?");
    $stmt->execute([$_GET['id']]);
}

header('Location: inquiries.php');
exit;
?>