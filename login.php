<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $role = $_POST['role'] ?? '';
    $allowedRoles = ['compratore', 'pescatore'];

    if ($username === '' || $password === '' || !in_array($role, $allowedRoles, true)) {
        setFlash('error', 'Credenziali o ruolo non validi.');
        header('Location: login.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, full_name, username, email, password_hash, role FROM users WHERE username = :username LIMIT 1');
    $stmt->bindValue(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, (string) $user['password_hash']) || ($user['role'] !== 'admin' && $user['role'] !== $role)) {
        setFlash('error', 'Login non riuscito: controlla dati e ruolo selezionato.');
        header('Location: login.php');
        exit;
    }

    $_SESSION['user'] = [
        'id' => (int) $user['id'],
        'full_name' => $user['full_name'],
        'username' => $user['username'],
        'email' => $user['email'],
        'role' => $user['role'],
    ];

    setFlash('success', 'Accesso effettuato con successo.');
    if ($user['role'] === 'admin') {
        header('Location: admin.php');
    } else {
        header('Location: dashboard.php');
    }
    exit;
}

$pageTitle = 'Login - Lanz';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section container form-wrap reveal">
    <h1>Accedi a Lanz</h1>
    <form method="post" class="form-card">
        <label>Username
            <input type="text" name="username" required>
        </label>
        <label>Password
            <input type="password" name="password" required>
        </label>
        <div class="role-select">
            <span class="role-label">Sei un</span>
            <div class="role-grid">
                <label class="role-option">
                    <input type="radio" name="role" value="compratore" required>
                    <span class="role-card">
                        <span class="role-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" role="img" aria-hidden="true" focusable="false">
                                <path d="M7.6 6.5h10.3c1 0 1.8.7 2 1.6l1.1 5.4c.2 1-.6 1.9-1.6 1.9H9.1a1.8 1.8 0 0 1-1.7-1.3l-2-7.6H3.5a.75.75 0 0 1 0-1.5h2.6c.4 0 .7.3.9.7l.6 2.3Z" fill="currentColor"/>
                                <circle cx="10.2" cy="18.5" r="1.5" fill="currentColor"/>
                                <circle cx="17.2" cy="18.5" r="1.5" fill="currentColor"/>
                            </svg>
                        </span>
                        <span class="role-text">
                            <strong>Compratore</strong>
                            <small>Acquista dal mercato</small>
                        </span>
                    </span>
                </label>
                <label class="role-option">
                    <input type="radio" name="role" value="pescatore" required>
                    <span class="role-card">
                        <span class="role-icon" aria-hidden="true">
                            <svg viewBox="0 0 24 24" role="img" aria-hidden="true" focusable="false">
                                <path d="M4 12c2.8-3.5 6.1-5.4 9.8-5.4 3.2 0 5.6 1.1 7.7 2.9l-3.1 2.5 3.1 2.5c-2.1 1.8-4.5 2.9-7.7 2.9C10.1 17.4 6.8 15.5 4 12Zm8.7 0a1.6 1.6 0 1 0 3.2 0 1.6 1.6 0 0 0-3.2 0Z" fill="currentColor"/>
                            </svg>
                        </span>
                        <span class="role-text">
                            <strong>Pescatore</strong>
                            <small>Gestisci le vendite</small>
                        </span>
                    </span>
                </label>
            </div>
        </div>
        <button class="btn btn-primary" type="submit">Login</button>
    </form>
    <p class="helper-text">Demo admin: username <strong>admin</strong>, password <strong>Admin123!</strong></p>
    <p class="helper-text">Non hai un account? <a href="register.php">Registrati ora</a>.</p>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
