<?php
require_once '../includes/config.php';
require_once '../classes/Auth.php';

$auth = new Auth($pdo);
$auth->requireLogin();

if (isset($_GET['id'])) {
    // Optionally unlink file from uploads/
    $stmt = $pdo->prepare("SELECT image FROM gallery WHERE id = ?");
    $stmt->execute([$_GET['id']]);
    $img = $stmt->fetch();
    if ($img && file_exists("../uploads/" . $img['image'])) {
        unlink("../uploads/" . $img['image']);
    }

    $stmt = $pdo->prepare("DELETE FROM gallery WHERE id = ?");
    $stmt->execute([$_GET['id']]);
}

header('Location: gallery.php');
exit;
?>