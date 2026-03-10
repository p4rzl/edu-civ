<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireRole(['compratore', 'admin']);

$user = currentUser();
$buyerId = (int) ($user['id'] ?? 0);

$pageTitle = 'Area Compratore - Lanz';
$bodyClass = 'buyer-page';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section container reveal">
    <h1>Verifica prodotto certificato</h1>
    <p class="lead">Inserisci il codice etichetta oppure usa la fotocamera del telefono per scansionare il QR code.</p>

    <div class="split">
        <form class="form-card" action="product.php" method="get">
            <label>Codice prodotto
                <input type="text" id="productCode" name="code" placeholder="Es. LZ-2026-0001" required>
            </label>
            <button type="submit" class="btn btn-primary">Cerca prodotto</button>
        </form>

        <div class="card">
            <h3>Scansione QR (mobile)</h3>
            <div id="qr-reader"></div>
            <button id="startScan" class="btn btn-ghost" type="button">Avvia scansione</button>
            <p class="helper-text">Su dispositivi mobili, il browser chiedera il permesso per la fotocamera.</p>
        </div>
    </div>
</section>

<section class="section container reveal promo-banner">
    <div>
        <h2>Zona promozionale partnership</h2>
        <p>I venditori aderenti ricevono badge "Pescato Sostenibile Lanz" con etichetta smart per valorizzare qualita e trasparenza.</p>
    </div>
    <a class="btn btn-primary" href="fisherman.php">Scopri i vantaggi</a>
</section>

<script src="https://unpkg.com/html5-qrcode" defer></script>
<script src="assets/js/qr.js" defer></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
