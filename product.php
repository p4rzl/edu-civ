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
}

$pageTitle = 'Scheda Prodotto - Lanz';
$bodyClass = 'product-page';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section container reveal">
    <h1>Tracciabilita prodotto</h1>

    <?php if ($code === ''): ?>
        <div class="alert alert-error">Inserisci un codice prodotto valido.</div>
    <?php elseif (!$product): ?>
        <div class="alert alert-error">Nessun prodotto trovato per il codice <?= e($code) ?>.</div>
    <?php else: ?>
        <div class="card">
            <h2><?= e((string) $product['fish_type']) ?> - Codice <?= e((string) $product['code']) ?></h2>
            <div class="metrics-grid">
                <p><strong>Zona di pesca:</strong> <?= e((string) $product['catch_area']) ?></p>
                <p><strong>Data di pesca:</strong> <?= e((string) $product['catch_date']) ?></p>
                <p><strong>Pescatore:</strong> <?= e((string) ($product['fisherman_name'] ?? 'N/D')) ?></p>
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
            <div class="card review-card">
                <h3>Lascia una recensione al pescatore</h3>
                <form method="post" class="form-card compact-form">
                    <input type="hidden" name="action" value="add_review">
                    <input type="hidden" name="code" value="<?= e((string) $product['code']) ?>">
                    <label>Voto
                        <select name="rating" required>
                            <option value="">Seleziona</option>
                            <option value="5">5 - Eccellente</option>
                            <option value="4">4 - Ottimo</option>
                            <option value="3">3 - Buono</option>
                            <option value="2">2 - Sufficiente</option>
                            <option value="1">1 - Scarso</option>
                        </select>
                    </label>
                    <label>Commento
                        <input type="text" name="comment" maxlength="255" required>
                    </label>
                    <button class="btn btn-primary" type="submit">Invia recensione</button>
                </form>
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

    <a class="btn btn-ghost" href="buyer.php">Torna alla ricerca</a>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
