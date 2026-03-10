<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = currentUser();
$pageTitle = 'Dashboard - Lanz';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section container reveal">
    <h1>Benvenuto, <?= e((string) $user['full_name']) ?></h1>
    <p class="lead">Ruolo attivo: <strong><?= e((string) $user['role']) ?></strong></p>

    <div class="grid-3">
        <?php if ($user['role'] === 'compratore'): ?>
            <article class="card">
                <h3>Verifica Tracciabilita</h3>
                <p>Inserisci il codice prodotto o scansiona il QR per visualizzare i dati certificati di qualita.</p>
                <a class="btn btn-primary" href="buyer.php">Apri area compratore</a>
            </article>
        <?php endif; ?>

        <?php if ($user['role'] === 'pescatore'): ?>
            <article class="card">
                <h3>Partner Lanz</h3>
                <p>Promuovi la tua bancarella con il certificato di pescato sostenibile e QR code tracciabile.</p>
                <a class="btn btn-primary" href="fisherman.php">Apri area pescatore</a>
            </article>
        <?php endif; ?>

        <?php if ($user['role'] === 'admin'): ?>
            <article class="card">
                <h3>Gestione Operativa</h3>
                <p>Area amministrativa attiva dalla voce Controllo Admin in navbar, con gestione utenti, boe e analytics marini.</p>
            </article>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
