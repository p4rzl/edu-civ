<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = currentUser();
$userId = (int) ($user['id'] ?? 0);
$userRole = (string) ($user['role'] ?? '');
$userName = (string) ($user['full_name'] ?? '');

$catchLocations = [
    'Bari Vecchia',
    'Molo San Nicola',
    'Pane e Pomodoro',
    'San Girolamo',
    'Torre a Mare',
    'Lama Balice',
];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($userRole === 'pescatore') && ($_POST['action'] ?? '') === 'create_product_request') {
    $requesterName = $userName !== '' ? $userName : 'Pescatore';
    $businessLocation = trim($_POST['business_location'] ?? '');
    $fishType = trim($_POST['fish_type'] ?? '');
    $catchLocation = trim($_POST['catch_location'] ?? '');

    if ($businessLocation === '' || $fishType === '' || $catchLocation === '') {
        setFlash('error', 'Compila tutti i campi della richiesta prodotto.');
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO product_requests (
                fisherman_id, requester_name, business_location, fish_type, catch_location
            ) VALUES (
                :fisherman_id, :requester_name, :business_location, :fish_type, :catch_location
            )'
        );
        $stmt->execute([
            'fisherman_id' => $userId,
            'requester_name' => $requesterName,
            'business_location' => $businessLocation,
            'fish_type' => $fishType,
            'catch_location' => $catchLocation,
        ]);

        $notifyAdmin = $pdo->prepare(
            'INSERT INTO notifications (target_role, message, link)
             VALUES ("admin", :message, "admin.php")'
        );
        $notifyAdmin->execute([
            'message' => 'Nuova richiesta prodotto da ' . ($user['full_name'] ?? 'Pescatore') . ': ' . $fishType,
        ]);

        setFlash('success', 'Richiesta inviata all\'amministratore per approvazione.');
    }

    header('Location: dashboard.php');
    exit;
}

$buyerStats = [
    'scanCount' => 0,
    'favoriteProduct' => 'N/D',
    'favoriteLocation' => 'N/D',
];

if ($userRole === 'compratore' && $userId > 0) {
    $countStmt = $pdo->prepare('SELECT COUNT(*) FROM product_scans WHERE buyer_id = :buyer_id');
    $countStmt->execute(['buyer_id' => $userId]);
    $buyerStats['scanCount'] = (int) $countStmt->fetchColumn();

    $favProductStmt = $pdo->prepare(
        'SELECT p.fish_type, COUNT(*) AS total
         FROM product_scans s
         JOIN products p ON p.id = s.product_id
         WHERE s.buyer_id = :buyer_id
         GROUP BY p.fish_type
         ORDER BY total DESC
         LIMIT 1'
    );
    $favProductStmt->execute(['buyer_id' => $userId]);
    $favProduct = $favProductStmt->fetch();
    if ($favProduct && !empty($favProduct['fish_type'])) {
        $buyerStats['favoriteProduct'] = (string) $favProduct['fish_type'];
    }

    $favLocationStmt = $pdo->prepare(
        'SELECT p.business_location, COUNT(*) AS total
         FROM product_scans s
         JOIN products p ON p.id = s.product_id
         WHERE s.buyer_id = :buyer_id AND p.business_location <> ""
         GROUP BY p.business_location
         ORDER BY total DESC
         LIMIT 1'
    );
    $favLocationStmt->execute(['buyer_id' => $userId]);
    $favLocation = $favLocationStmt->fetch();
    if ($favLocation && !empty($favLocation['business_location'])) {
        $buyerStats['favoriteLocation'] = (string) $favLocation['business_location'];
    }
}

$fisherStats = [
    'avgRating' => null,
    'reviewsCount' => 0,
    'productCount' => 0,
    'pendingCount' => 0,
    'approvedCount' => 0,
];

$pending = [];
$approved = [];
if ($userRole === 'pescatore' && $userId > 0) {
    $ratingStmt = $pdo->prepare(
        'SELECT AVG(rating) AS avg_rating, COUNT(*) AS total
         FROM reviews
         WHERE fisherman_id = :fisherman_id'
    );
    $ratingStmt->execute(['fisherman_id' => $userId]);
    $ratingRow = $ratingStmt->fetch();
    if ($ratingRow) {
        $fisherStats['avgRating'] = $ratingRow['avg_rating'] !== null ? (float) $ratingRow['avg_rating'] : null;
        $fisherStats['reviewsCount'] = (int) ($ratingRow['total'] ?? 0);
    }

    $productCountStmt = $pdo->prepare('SELECT COUNT(*) FROM products WHERE fisherman_id = :fisherman_id');
    $productCountStmt->execute(['fisherman_id' => $userId]);
    $fisherStats['productCount'] = (int) $productCountStmt->fetchColumn();

    $pendingCountStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM product_requests WHERE fisherman_id = :fisherman_id AND status = "pending"'
    );
    $pendingCountStmt->execute(['fisherman_id' => $userId]);
    $fisherStats['pendingCount'] = (int) $pendingCountStmt->fetchColumn();

    $approvedCountStmt = $pdo->prepare(
        'SELECT COUNT(*) FROM product_requests WHERE fisherman_id = :fisherman_id AND status = "approved"'
    );
    $approvedCountStmt->execute(['fisherman_id' => $userId]);
    $fisherStats['approvedCount'] = (int) $approvedCountStmt->fetchColumn();

    $requestsPending = $pdo->prepare(
        'SELECT id, requester_name, business_location, fish_type, catch_location, requested_at
         FROM product_requests
         WHERE fisherman_id = :fisherman_id AND status = "pending"
         ORDER BY requested_at DESC'
    );
    $requestsPending->bindValue(':fisherman_id', $userId, PDO::PARAM_INT);
    $requestsPending->execute();
    $pending = $requestsPending->fetchAll();

    $requestsApproved = $pdo->prepare(
        'SELECT id, requester_name, business_location, fish_type, catch_location, generated_code, approved_at
         FROM product_requests
         WHERE fisherman_id = :fisherman_id AND status = "approved"
         ORDER BY approved_at DESC'
    );
    $requestsApproved->bindValue(':fisherman_id', $userId, PDO::PARAM_INT);
    $requestsApproved->execute();
    $approved = $requestsApproved->fetchAll();
}


function renderStars(?float $rating): string
{
    if ($rating === null) {
        return 'N/D';
    }

    $rounded = (int) round($rating);
    return str_repeat('★', $rounded) . str_repeat('☆', max(0, 5 - $rounded));
}

$pageTitle = 'Pannello personale - Lanz';
$bodyClass = 'dashboard-page';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section container reveal">
    <div class="dashboard-header">
        <div>
            <p class="eyebrow">Pannello personale</p>
            <h1>Ciao, <?= e((string) $user['full_name']) ?></h1>
            <p class="lead">Qui trovi dati, richieste e strumenti dedicati al tuo profilo.</p>
        </div>
        <span class="role-pill">Ruolo: <?= e((string) $userRole) ?></span>
    </div>
</section>

<?php if ($userRole === 'compratore'): ?>
    <section class="section container reveal">
        <h2>Panoramica acquisti</h2>
        <div class="stats-grid">
            <article class="stat-card">
                <span class="stat-label">QR scansionati</span>
                <span class="stat-value"><?= e((string) $buyerStats['scanCount']) ?></span>
                <span class="stat-meta">Totale scansioni registrate</span>
            </article>
            <article class="stat-card">
                <span class="stat-label">Locale di fiducia</span>
                <span class="stat-value"><?= e((string) $buyerStats['favoriteLocation']) ?></span>
                <span class="stat-meta">Dove acquisti piu spesso</span>
            </article>
            <article class="stat-card">
                <span class="stat-label">Prodotto preferito</span>
                <span class="stat-value"><?= e((string) $buyerStats['favoriteProduct']) ?></span>
                <span class="stat-meta">Più acquistato</span>
            </article>
        </div>
        <div class="chart-panel card">
            <div class="chart-head">
                <div>
                    <h3>Prodotti piu acquistati</h3>
                    <p class="helper-text">Distribuzione delle ultime scansioni.</p>
                </div>
                <span class="chart-tag">Top 5</span>
            </div>
            <canvas id="buyerStatsChart"
                data-labels="<?= e(json_encode($buyerChartLabels)) ?>"
                data-values="<?= e(json_encode($buyerChartValues)) ?>"></canvas>
        </div>
    </section>

    <section class="section container reveal">
        <h2>Tracciabilita e QR</h2>
        <form class="form-card action-card scan-card" action="product.php" method="get">
            <div class="action-header">
                <span class="action-icon"><i class="bi bi-upc-scan"></i></span>
                <div>
                    <h3>Verifica prodotto</h3>
                    <p class="helper-text">Inserisci il codice o avvia la scansione del QR.</p>
                </div>
            </div>
            <label>Codice prodotto
                <div class="scan-input">
                    <input type="text" id="productCode" name="code" placeholder="Es. LZ-2026-0001" required>
                    <button id="startScan" class="scan-btn" type="button" aria-label="Avvia scansione">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
            </label>
            <div id="qr-reader" class="qr-inline"></div>
            <button type="submit" class="btn btn-primary">Cerca prodotto</button>
        </form>
    </section>
<?php endif; ?>

<?php if ($userRole === 'pescatore'): ?>
    <section class="section container reveal">
        <h2>Panoramica attività</h2>
        <div class="stats-grid">
            <article class="stat-card">
                <span class="stat-label">Valutazione generale</span>
                <span class="stat-value"><?= e(renderStars($fisherStats['avgRating'])) ?></span>
                <span class="stat-meta"><?= e((string) $fisherStats['reviewsCount']) ?> recensioni</span>
            </article>
            <article class="stat-card">
                <span class="stat-label">Prodotti pescati</span>
                <span class="stat-value"><?= e((string) $fisherStats['productCount']) ?></span>
                <span class="stat-meta">Registrati e certificati</span>
            </article>
            <article class="stat-card">
                <span class="stat-label">Richieste attive</span>
                <span class="stat-value"><?= e((string) $fisherStats['pendingCount']) ?></span>
                <span class="stat-meta"><?= e((string) $fisherStats['approvedCount']) ?> approvate</span>
            </article>
        </div>
        <div class="chart-panel card">
            <div class="chart-head">
                <div>
                    <h3>Andamento richieste</h3>
                    <p class="helper-text">Sintesi operativa delle certificazioni.</p>
                </div>
                <span class="chart-tag">Aggiornato</span>
            </div>
            <canvas id="fisherStatsChart"
                data-labels="<?= e(json_encode($fisherChartLabels)) ?>"
                data-values="<?= e(json_encode($fisherChartValues)) ?>"></canvas>
        </div>
    </section>

    <section class="section container reveal">
        <h2>Nuova richiesta aggiunta prodotto</h2>
        <form method="post" class="form-card action-card">
            <input type="hidden" name="action" value="create_product_request">
            <label>Nome del locale o bancarella
                <input type="text" name="business_location" required>
            </label>
            <label>Nome prodotto
                <input type="text" name="fish_type" required>
            </label>
            <label>Luogo in cui e stato pescato il prodotto
                <select name="catch_location" required>
                    <option value="">Seleziona zona</option>
                    <?php foreach ($catchLocations as $location): ?>
                        <option value="<?= e($location) ?>"><?= e($location) ?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <button class="btn btn-primary" type="submit">Invia richiesta</button>
        </form>
    </section>

    <section class="section container reveal">
        <h2>Richieste in attesa</h2>
        <div class="table-wrap card">
            <table>
                <thead>
                    <tr>
                        <th>Richiedente</th>
                        <th>Locale</th>
                        <th>Prodotto</th>
                        <th>Luogo pesca</th>
                        <th>Data richiesta</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$pending): ?>
                        <tr><td colspan="5" class="helper-text">Nessuna richiesta in attesa.</td></tr>
                    <?php else: ?>
                        <?php foreach ($pending as $req): ?>
                            <tr>
                                <td><?= e((string) $req['requester_name']) ?></td>
                                <td><?= e((string) $req['business_location']) ?></td>
                                <td><?= e((string) $req['fish_type']) ?></td>
                                <td><?= e((string) $req['catch_location']) ?></td>
                                <td><?= e((string) $req['requested_at']) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="section container reveal">
        <h2>Richieste accettate con QR</h2>
        <div class="table-wrap card">
            <table>
                <thead>
                    <tr>
                        <th>Prodotto</th>
                        <th>Richiedente</th>
                        <th>Locale</th>
                        <th>Codice</th>
                        <th>QR</th>
                        <th>Approvata il</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$approved): ?>
                        <tr><td colspan="6" class="helper-text">Nessuna richiesta approvata.</td></tr>
                    <?php else: ?>
                        <?php foreach ($approved as $req): ?>
                            <tr>
                                <td><?= e((string) $req['fish_type']) ?></td>
                                <td><?= e((string) $req['requester_name']) ?></td>
                                <td><?= e((string) $req['business_location']) ?></td>
                                <td><?= e((string) $req['generated_code']) ?></td>
                                <td>
                                    <div class="qr-stack">
                                        <div class="qr-mini" data-qr-text="<?= e((string) $req['generated_code']) ?>"></div>
                                        <button type="button" class="qr-zoom" data-qr-text="<?= e((string) $req['generated_code']) ?>" aria-label="Ingrandisci QR">
                                            <i class="bi bi-zoom-in"></i>
                                        </button>
                                    </div>
                                </td>
                                <td><?= e((string) ($req['approved_at'] ?? 'N/D')) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

    <div id="qrModal" class="qr-modal" aria-hidden="true">
        <div class="qr-modal-content">
            <button type="button" id="closeQrModal" class="btn btn-danger">Chiudi</button>
            <div id="qrModalCanvas"></div>
        </div>
    </div>
<?php endif; ?>

<?php if ($userRole === 'admin'): ?>
    <section class="section container reveal">
        <div class="card">
            <h2>Gestione operativa</h2>
            <p class="lead">Accedi al controllo admin per gestire utenti, boe, richieste e analytics.</p>
            <a class="btn btn-primary" href="admin.php">Apri controllo admin</a>
        </div>
    </section>
<?php endif; ?>

<?php if ($userRole === 'compratore'): ?>
    <script src="https://unpkg.com/html5-qrcode" defer></script>
    <script src="assets/js/qr.js" defer></script>
<?php endif; ?>

<?php if ($userRole === 'pescatore'): ?>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" defer></script>
    <script src="assets/js/product-qr.js" defer></script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
