<?php
class Auth
{
    private $pdo;

    public function __construct($pdo)
    {
        $this->pdo = $pdo;
    }

    public function login($username, $password)
    {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = :username LIMIT 1");
        $stmt->execute([':username' => $username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_logged_in'] = true;
            $_SESSION['admin_id'] = $user['id'];
            $_SESSION['admin_username'] = $user['username'];
            return true;
        }
        return false;
    }

    public function isLoggedIn()
    {
        return isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;
    }

    public function requireLogin()
    {
        if (!$this->isLoggedIn()) {
            header('Location: ' . BASE_URL . 'admin/login.php');
            exit;
        }
    }

    public function logout()
    {
        session_unset();
        session_destroy();
    }
}
?>