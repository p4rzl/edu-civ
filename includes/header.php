<?php
declare(strict_types=1);

require_once __DIR__ . '/auth.php';
$user = currentUser();
$flash = getFlash();
$pageTitle = $pageTitle ?? 'Lanz - Blue Economy Bari';
$bodyClass = $bodyClass ?? '';

$quickNotifications = [];
$quickUnreadCount = 0;
if ($user && isset($pdo)) {
    try {
        $quickStmt = $pdo->prepare(
            'SELECT id, message, link, created_at
             FROM notifications
             WHERE (target_user_id = :uid OR target_role = :role)
             ORDER BY created_at DESC
             LIMIT 8'
        );
        $quickStmt->bindValue(':uid', (int) ($user['id'] ?? 0), PDO::PARAM_INT);
        $quickStmt->bindValue(':role', (string) ($user['role'] ?? ''));
        $quickStmt->execute();
        $quickNotifications = $quickStmt->fetchAll();

        $countStmt = $pdo->prepare(
            'SELECT COUNT(*)
             FROM notifications
             WHERE is_read = 0 AND (target_user_id = :uid OR target_role = :role)'
        );
        $countStmt->bindValue(':uid', (int) ($user['id'] ?? 0), PDO::PARAM_INT);
        $countStmt->bindValue(':role', (string) ($user['role'] ?? ''));
        $countStmt->execute();
        $quickUnreadCount = (int) $countStmt->fetchColumn();
    } catch (Throwable $e) {
        $quickNotifications = [];
        $quickUnreadCount = 0;
    }
}
?>
<!DOCTYPE html>
<html lang="it">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= e($pageTitle) ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Space+Grotesk:wght@400;500;700&family=Outfit:wght@300;400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
</head>
<body class="<?= e($bodyClass) ?>">
    <div class="bg-gradient"></div>
    <header class="site-header">
        <nav class="navbar container">
            <a href="index.php" class="logo">
                <img src="assets/svg/icon.svg" alt="Logo Lanz" class="logo-mark">
                <span>Lanz</span>
            </a>
            <button class="menu-toggle" aria-label="Apri menu" id="menuToggle">Menu</button>
            <ul class="nav-links" id="navLinks">
                <li><a href="index.php"><i class="bi bi-house-door"></i> Home</a></li>
                <?php if ($user): ?>
                    <?php if ($user['role'] !== 'admin'): ?>
                        <li><a href="dashboard.php"><i class="bi bi-grid"></i> Dashboard</a></li>
                    <?php endif; ?>
                    <?php if ($user['role'] === 'compratore'): ?>
                        <li><a href="buyer.php"><i class="bi bi-upc-scan"></i> Tracciabilita</a></li>
                    <?php endif; ?>
                    <?php if ($user['role'] === 'pescatore'): ?>
                        <li><a href="fisherman.php"><i class="bi bi-basket2"></i> Area Pescatore</a></li>
                    <?php endif; ?>
                    <?php if ($user['role'] === 'admin'): ?>
                        <li><a href="admin.php" class="btn-nav"><i class="bi bi-shield-lock"></i> Controllo Admin</a></li>
                    <?php endif; ?>
                    <li><a href="logout.php"><i class="bi bi-box-arrow-right"></i> Logout</a></li>
                <?php else: ?>
                    <li><a class="btn-nav" href="login.php"><i class="bi bi-person-circle"></i> Accedi / Registrati</a></li>
                <?php endif; ?>
            </ul>
            <?php if ($user): ?>
                <div class="notify-wrap">
                    <button id="notifyToggle" class="notify-btn" type="button" aria-label="Apri notifiche" aria-expanded="false">
                        <i class="bi bi-bell"></i>
                        <?php if ($quickUnreadCount > 0): ?>
                            <span class="notify-dot"><?= e((string) $quickUnreadCount) ?></span>
                        <?php endif; ?>
                    </button>
                    <div id="notifyMenu" class="notify-menu" aria-hidden="true">
                        <h4>Notifiche</h4>
                        <?php if (!$quickNotifications): ?>
                            <p class="small">Nessuna notifica disponibile.</p>
                        <?php else: ?>
                            <ul>
                                <?php foreach ($quickNotifications as $note): ?>
                                    <li>
                                        <p><?= e((string) $note['message']) ?></p>
                                        <?php if (!empty($note['link'])): ?>
                                            <a href="<?= e((string) $note['link']) ?>">Apri</a>
                                        <?php endif; ?>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </nav>
    </header>

    <main>
        <?php if ($flash): ?>
            <div class="container">
                <div class="alert alert-<?= e($flash['type']) ?>"><?= e($flash['message']) ?></div>
            </div>
        <?php endif; ?>
