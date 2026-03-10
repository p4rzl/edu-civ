<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';

if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';
    $role = $_POST['role'] ?? '';

    if ($fullName === '' || $username === '' || $email === '' || $password === '' || $confirm === '') {
        setFlash('error', 'Compila tutti i campi obbligatori.');
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        setFlash('error', 'Email non valida.');
    } elseif ($password !== $confirm) {
        setFlash('error', 'Le password non coincidono.');
    } elseif (!in_array($role, ['compratore', 'pescatore'], true)) {
        setFlash('error', 'Ruolo non valido.');
    } else {
        try {
            $stmt = $pdo->prepare(
                'INSERT INTO users (full_name, username, email, password_hash, role)
                 VALUES (:full_name, :username, :email, :password_hash, :role)'
            );
            $stmt->bindValue(':full_name', $fullName);
            $stmt->bindValue(':username', $username);
            $stmt->bindValue(':email', $email);
            $stmt->bindValue(':password_hash', password_hash($password, PASSWORD_DEFAULT));
            $stmt->bindValue(':role', $role);
            $stmt->execute();

            setFlash('success', 'Registrazione completata. Ora puoi accedere.');
            header('Location: login.php');
            exit;
        } catch (Throwable $e) {
            setFlash('error', 'Username o email gia presenti.');
        }
    }

    header('Location: register.php');
    exit;
}

$pageTitle = 'Registrazione - Lanz';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section container form-wrap reveal">
    <h1>Crea il tuo account Lanz</h1>
    <form method="post" class="form-card">
        <label>Nome e cognome
            <input type="text" name="full_name" required>
        </label>
        <label>Username
            <input type="text" name="username" required>
        </label>
        <label>Email
            <input type="email" name="email" required>
        </label>
        <label>Ruolo
            <select name="role" required>
                <option value="">Seleziona ruolo</option>
                <option value="compratore">Compratore</option>
                <option value="pescatore">Pescatore</option>
            </select>
        </label>
        <label>Password
            <input type="password" name="password" minlength="8" required>
        </label>
        <label>Conferma password
            <input type="password" name="confirm_password" minlength="8" required>
        </label>
        <button class="btn btn-primary" type="submit">Registrati</button>
    </form>
    <p class="helper-text">Hai gia un account? <a href="login.php">Accedi</a>.</p>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
