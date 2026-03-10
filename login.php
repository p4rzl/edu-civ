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

    if ($username === '' || $password === '' || !in_array($role, ['compratore', 'pescatore', 'admin'], true)) {
        setFlash('error', 'Credenziali o ruolo non validi.');
        header('Location: login.php');
        exit;
    }

    $stmt = $pdo->prepare('SELECT id, full_name, username, email, password_hash, role FROM users WHERE username = :username LIMIT 1');
    $stmt->bindValue(':username', $username);
    $stmt->execute();
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, (string) $user['password_hash']) || $user['role'] !== $role) {
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
        <label>Sei un
            <select name="role" required>
                <option value="">Seleziona</option>
                <option value="compratore">Compratore</option>
                <option value="pescatore">Pescatore</option>
                <option value="admin">Amministratore</option>
            </select>
        </label>
        <button class="btn btn-primary" type="submit">Login</button>
    </form>
    <p class="helper-text">Demo admin: username <strong>admin</strong>, password <strong>Admin123!</strong></p>
    <p class="helper-text">Non hai un account? <a href="register.php">Registrati ora</a>.</p>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
