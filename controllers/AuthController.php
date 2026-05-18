<?php
// ============================================================
// controllers/AuthController.php
// Gère login / logout / session
// ============================================================

class AuthController {

    public function login(): void {
        // Déjà connecté → dashboard
        if (!empty($_SESSION['user_id'])) {
            $this->redirect('dashboard');
        }

        $error = null;

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            csrf_verify();

            $email    = sanitize($_POST['email']    ?? '');
            $password = $_POST['password'] ?? '';

            if (empty($email) || empty($password)) {
                $error = 'Veuillez remplir tous les champs.';
            } else {
                $db   = getDB();
                $stmt = $db->prepare(
                    "SELECT * FROM users WHERE email = :email AND statut = 1 LIMIT 1"
                );
                $stmt->execute([':email' => $email]);
                $user = $stmt->fetch();

                if ($user && password_verify($password, $user['password'])) {
                    // Régénérer l'ID de session pour éviter fixation
                    session_regenerate_id(true);

                    $_SESSION['user_id']    = $user['id'];
                    $_SESSION['user_nom']   = $user['prenom'] . ' ' . $user['nom'];
                    $_SESSION['user_role']  = $user['role'];
                    $_SESSION['user_email'] = $user['email'];

                    $this->redirect('dashboard');
                } else {
                    $error = 'Email ou mot de passe incorrect.';
                }
            }
        }

        // Passer l'erreur à la vue
        $login_error = $error;
        include __DIR__ . '/../views/auth/login.php';
    }

    public function logout(): void {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $p['path'], $p['domain'], $p['secure'], $p['httponly']
            );
        }
        session_destroy();
        header('Location: ' . APP_URL . '/index.php?page=login');
        exit;
    }

    private function redirect(string $page): void {
        header('Location: ' . APP_URL . '/index.php?page=' . $page);
        exit;
    }
}
