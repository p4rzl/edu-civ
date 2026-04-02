<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireRole(['compratore', 'admin', 'pescatore']);

$user = currentUser();
$currentUserId = (int) ($user['id'] ?? 0);
$currentUserRole = (string) ($user['role'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_review') {
    if ($currentUserRole !== 'compratore') {
        setFlash('error', 'Solo i compratori possono lasciare recensioni.');
        header('Location: product.php?code=' . urlencode((string) ($_POST['code'] ?? '')));
        exit;
    }

    $codePost = trim($_POST['code'] ?? '');
    $rating = (int) ($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($codePost === '' || $rating < 1 || $rating > 5 || $comment === '') {
        setFlash('error', 'Recensione non valida: compila voto e commento.');
        header('Location: product.php?code=' . urlencode($codePost));
        exit;
    }

    $productStmt = $pdo->prepare('SELECT id, fisherman_id FROM products WHERE code = :code LIMIT 1');
    $productStmt->bindValue(':code', $codePost);
    $productStmt->execute();
    $productRow = $productStmt->fetch();

    if (!$productRow || (int) ($productRow['fisherman_id'] ?? 0) <= 0) {
        setFlash('error', 'Prodotto non valido per recensione.');
        header('Location: product.php?code=' . urlencode($codePost));
        exit;
    }

    try {
        $insertReview = $pdo->prepare(
            'INSERT INTO reviews (product_id, buyer_id, fisherman_id, rating, comment)
             VALUES (:product_id, :buyer_id, :fisherman_id, :rating, :comment)'
        );
        $insertReview->execute([
            'product_id' => (int) $productRow['id'],
            'buyer_id' => $currentUserId,
            'fisherman_id' => (int) $productRow['fisherman_id'],
            'rating' => $rating,
            'comment' => $comment,
        ]);

        $notify = $pdo->prepare(
            'INSERT INTO notifications (target_user_id, message, link)
             VALUES (:target_user_id, :message, :link)'
        );
        $notify->execute([
            'target_user_id' => (int) $productRow['fisherman_id'],
            'message' => 'Nuova recensione ricevuta sul prodotto ' . $codePost,
            'link' => 'fisherman.php',
        ]);

        setFlash('success', 'Recensione inviata con successo.');
    } catch (Throwable $e) {
        setFlash('error', 'Hai gia recensito questo prodotto.');
    }

    header('Location: product.php?code=' . urlencode($codePost));
    exit;
}

$code = trim($_GET['code'] ?? '');

$product = null;
if ($code !== '') {
    $stmt = $pdo->prepare(
        'SELECT p.*, u.full_name AS fisherman_name
         FROM products p
         LEFT JOIN users u ON u.id = p.fisherman_id
         WHERE p.code = :code
         LIMIT 1'
    );
    $stmt->bindValue(':code', $code);
    $stmt->execute();
    $product = $stmt->fetch();
}

$isCertified = false;
if ($product) {
    $labelValue = (string) ($product['quality_label'] ?? '');
    $isCertified = stripos($labelValue, 'lanz') !== false || stripos($labelValue, 'certific') !== false;
}

$reviews = [];
if ($product) {
    $reviewsStmt = $pdo->prepare(
        'SELECT r.rating, r.comment, r.created_at, u.full_name AS buyer_name
         FROM reviews r
         JOIN users u ON u.id = r.buyer_id
         WHERE r.product_id = :product_id
         ORDER BY r.created_at DESC'
    );
    $reviewsStmt->bindValue(':product_id', (int) $product['id'], PDO::PARAM_INT);
    $reviewsStmt->execute();
    $reviews = $reviewsStmt->fetchAll();

    if ($currentUserRole === 'compratore' && $currentUserId > 0) {
        $scanStmt = $pdo->prepare(
            'INSERT INTO product_scans (product_id, buyer_id, scanned_at)
             VALUES (:product_id, :buyer_id, :scanned_at)'
        );
        $scanStmt->execute([
            'product_id' => (int) $product['id'],
            'buyer_id' => $currentUserId,
            'scanned_at' => date('Y-m-d H:i:s'),
        ]);
    }
}

$pageTitle = 'Scheda Prodotto - Lanz';
$bodyClass = 'product-page';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section container reveal">
    <a class="btn btn-ghost btn-back" href="buyer.php"><i class="bi bi-arrow-left"></i> Torna alla ricerca</a>
    <h1>Tracciabilita prodotto</h1>

    <?php if ($code === ''): ?>
        <div class="alert alert-error">Inserisci un codice prodotto valido.</div>
    <?php elseif (!$product): ?>
        <div class="alert alert-error">Nessun prodotto trovato per il codice <?= e($code) ?>.</div>
    <?php else: ?>
        <div class="card">
            <div class="product-header">
                <div>
                    <h2><?= e((string) $product['fish_type']) ?> - Codice <?= e((string) $product['code']) ?></h2>
                    <p class="helper-text">Tracciamento certificato del lotto con dati ambientali e origine del pescato.</p>
                </div>
                <div class="fisherman-badge">
                    <span class="fisherman-name">
                        <?= e((string) ($product['fisherman_name'] ?? 'N/D')) ?>
                        <?php if ($isCertified): ?>
                            <span class="cert-icon-inline" aria-label="Certificato Lanz"><i class="bi bi-patch-check-fill"></i></span>
                        <?php endif; ?>
                    </span>
                    <span class="fisherman-meta"><?= e((string) ($product['business_location'] ?? 'Attivita locale')) ?></span>
                    <span class="fisherman-meta"><?= $isCertified ? 'Pescatore certificato Lanz' : 'Pescatore verificato' ?></span>
                </div>
            </div>
            <div class="metrics-grid">
                <p><strong>Locale di vendita:</strong> <?= e((string) ($product['business_location'] ?? 'N/D')) ?></p>
                <p><strong>Zona di pesca:</strong> <?= e((string) $product['catch_area']) ?></p>
                <p><strong>Data di pesca:</strong> <?= e((string) $product['catch_date']) ?></p>
                <p><strong>Qualita certificata:</strong> <?= e((string) $product['quality_label']) ?></p>
                <p><strong>Microplastiche:</strong> <?= e((string) $product['microplastics_percent']) ?>%</p>
                <p><strong>Ossigeno disciolto:</strong> <?= e((string) $product['dissolved_oxygen']) ?> mg/L</p>
                <p><strong>Salinita:</strong> <?= e((string) $product['salinity']) ?> PSU</p>
            </div>
            <p class="helper-text">
                Dati raccolti da boe IoT Lanz nel mare di Bari e analizzati tramite algoritmi di monitoraggio avanzato.
            </p>
        </div>

        <?php if ($currentUserRole === 'compratore'): ?>
            <div class="review-layout">
                <div class="card review-card">
                    <h3>Lascia una recensione al pescatore</h3>
                    <form method="post" class="form-card compact-form">
                        <input type="hidden" name="action" value="add_review">
                        <input type="hidden" name="code" value="<?= e((string) $product['code']) ?>">
                        <label>Valutazione
                            <div class="star-rating" role="radiogroup" aria-label="Valutazione">
                                <input type="radio" id="rating-5" name="rating" value="5" required>
                                <label for="rating-5" title="5 stelle">★</label>
                                <input type="radio" id="rating-4" name="rating" value="4">
                                <label for="rating-4" title="4 stelle">★</label>
                                <input type="radio" id="rating-3" name="rating" value="3">
                                <label for="rating-3" title="3 stelle">★</label>
                                <input type="radio" id="rating-2" name="rating" value="2">
                                <label for="rating-2" title="2 stelle">★</label>
                                <input type="radio" id="rating-1" name="rating" value="1">
                                <label for="rating-1" title="1 stella">★</label>
                            </div>
                        </label>
                        <label>Commento
                            <input type="text" name="comment" maxlength="255" required>
                        </label>
                        <button class="btn btn-primary" type="submit">Invia recensione</button>
                    </form>
                </div>
                <aside class="card product-mini">
                    <h4>Sintesi qualita</h4>
                    <ul>
                        <li><strong>Certificazione:</strong> <?= $isCertified ? 'Conforme standard Lanz' : 'Verifica base disponibile' ?></li>
                        <li><strong>Stato ecosistema:</strong> <?= ((float) $product['dissolved_oxygen'] >= 6.0 && (float) $product['microplastics_percent'] <= 1.0) ? 'Buono' : 'Da monitorare' ?></li>
                        <li><strong>Indice trasparenza:</strong> Tracciabilita completa del lotto</li>
                    </ul>
                </aside>
            </div>
        <?php endif; ?>

        <div class="card review-card">
            <h3>Recensioni compratori</h3>
            <?php if (!$reviews): ?>
                <p class="helper-text">Nessuna recensione disponibile per questo prodotto.</p>
            <?php else: ?>
                <ul class="notification-list">
                    <?php foreach ($reviews as $review): ?>
                        <li>
                            <div>
                                <strong><?= str_repeat('★', (int) $review['rating']) . str_repeat('☆', 5 - (int) $review['rating']) ?> - <?= e((string) $review['buyer_name']) ?></strong>
                                <p><?= e((string) $review['comment']) ?></p>
                                <p class="small"><?= e((string) $review['created_at']) ?></p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </div>
    <?php endif; ?>

</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
