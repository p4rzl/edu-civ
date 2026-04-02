<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = currentUser();
$userId = (int) ($user['id'] ?? 0);
$userRole = (string) ($user['role'] ?? '');

$catchLocations = [
    'Bari Vecchia',
    'Molo San Nicola',
    'Pane e Pomodoro',
    'San Girolamo',
    'Torre a Mare',
    'Lama Balice',
];

$fullNameSource = trim((string) ($user['full_name'] ?? ''));
$nameParts = $fullNameSource !== '' ? preg_split('/\s+/', $fullNameSource) : [];
$userFirstName = (string) ($nameParts[0] ?? '');
$userLastName = $nameParts ? trim((string) implode(' ', array_slice($nameParts, 1))) : '';
$displayName = trim($userFirstName . ' ' . $userLastName);
if ($displayName === '') {
    $displayName = (string) ($user['username'] ?? 'Utente');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string) ($_POST['action'] ?? '');

    if ($action === 'update_profile' && $userId > 0) {
        $firstName = trim((string) ($_POST['first_name'] ?? ''));
        $lastName = trim((string) ($_POST['last_name'] ?? ''));
        $fullName = trim($firstName . ' ' . $lastName);
        $email = trim((string) ($_POST['email'] ?? ''));

        if ($firstName === '' || $lastName === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            setFlash('error', 'Nome, cognome o email non validi.');
        } else {
            try {
                $updateProfile = $pdo->prepare(
                    'UPDATE users SET full_name = :full_name, email = :email WHERE id = :id'
                );
                $updateProfile->execute([
                    'full_name' => $fullName,
                    'email' => $email,
                    'id' => $userId,
                ]);

                if (isset($_SESSION['user'])) {
                    $_SESSION['user']['full_name'] = $fullName;
                    $_SESSION['user']['email'] = $email;
                }

                setFlash('success', 'Profilo aggiornato con successo.');
            } catch (Throwable $e) {
                setFlash('error', 'Impossibile aggiornare il profilo (email gia in uso).');
            }
        }

        header('Location: dashboard.php');
        exit;
    }

    if ($action === 'update_password' && $userId > 0) {
        $currentPassword = (string) ($_POST['current_password'] ?? '');
        $newPassword = (string) ($_POST['new_password'] ?? '');
        $confirmPassword = (string) ($_POST['confirm_password'] ?? '');

        if ($currentPassword === '' || $newPassword === '' || $confirmPassword === '') {
            setFlash('error', 'Compila tutti i campi password.');
        } elseif ($newPassword !== $confirmPassword) {
            setFlash('error', 'Le nuove password non coincidono.');
        } elseif (strlen($newPassword) < 8) {
            setFlash('error', 'La nuova password deve avere almeno 8 caratteri.');
        } else {
            $stmt = $pdo->prepare('SELECT password_hash FROM users WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $userId]);
            $row = $stmt->fetch();

            if (!$row || !password_verify($currentPassword, (string) $row['password_hash'])) {
                setFlash('error', 'Password attuale non corretta.');
            } else {
                $updatePwd = $pdo->prepare('UPDATE users SET password_hash = :password_hash WHERE id = :id');
                $updatePwd->execute([
                    'password_hash' => password_hash($newPassword, PASSWORD_DEFAULT),
                    'id' => $userId,
                ]);
                setFlash('success', 'Password aggiornata.');
            }
        }

        header('Location: dashboard.php');
        exit;
    }

    if ($action === 'create_product_request' && $userRole === 'pescatore') {
        $requesterName = trim((string) ($user['full_name'] ?? 'Pescatore'));
        $businessLocation = trim((string) ($_POST['business_location'] ?? ''));
        $fishType = trim((string) ($_POST['fish_type'] ?? ''));
        $catchLocation = trim((string) ($_POST['catch_location'] ?? ''));

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
                'message' => 'Nuova richiesta prodotto da ' . $requesterName . ': ' . $fishType,
            ]);

            setFlash('success', 'Richiesta inviata all\'amministratore.');
        }

        header('Location: dashboard.php');
        exit;
    }

    if ($action === 'update_buyer_review' && $userRole === 'compratore') {
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        $rating = (int) ($_POST['rating'] ?? 0);
        $comment = trim((string) ($_POST['comment'] ?? ''));

        if ($reviewId <= 0 || $rating < 1 || $rating > 5 || $comment === '') {
            setFlash('error', 'Modifica recensione non valida.');
        } else {
            $update = $pdo->prepare(
                'UPDATE reviews
                 SET rating = :rating, comment = :comment
                 WHERE id = :id AND buyer_id = :buyer_id'
            );
            $update->execute([
                'rating' => $rating,
                'comment' => $comment,
                'id' => $reviewId,
                'buyer_id' => $userId,
            ]);
            setFlash('success', 'Recensione aggiornata.');
        }

        header('Location: dashboard.php');
        exit;
    }

    if ($action === 'delete_buyer_review' && $userRole === 'compratore') {
        $reviewId = (int) ($_POST['review_id'] ?? 0);
        if ($reviewId > 0) {
            $delete = $pdo->prepare('DELETE FROM reviews WHERE id = :id AND buyer_id = :buyer_id');
            $delete->execute([
                'id' => $reviewId,
                'buyer_id' => $userId,
            ]);
            setFlash('success', 'Recensione eliminata.');
        }

        header('Location: dashboard.php');
        exit;
    }

    if ($action === 'delete_own_request' && $userRole === 'pescatore') {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        if ($requestId > 0) {
            $deleteReq = $pdo->prepare(
                'DELETE FROM product_requests
                 WHERE id = :id AND fisherman_id = :fisherman_id AND status IN ("pending", "rejected")'
            );
            $deleteReq->execute([
                'id' => $requestId,
                'fisherman_id' => $userId,
            ]);
            setFlash('success', 'Richiesta rimossa dalla cronologia.');
        }

        header('Location: dashboard.php');
        exit;
    }
}

$buyerStats = [
    'scanCount' => 0,
    'favoriteProduct' => 'N/D',
    'favoriteLocation' => 'N/D',
];
$buyerActivities = [];

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

    $buyerReviewsStmt = $pdo->prepare(
        'SELECT r.id, r.rating, r.comment, r.created_at, p.fish_type, p.code
         FROM reviews r
         JOIN products p ON p.id = r.product_id
         WHERE r.buyer_id = :buyer_id
         ORDER BY r.created_at DESC
         LIMIT 30'
    );
    $buyerReviewsStmt->execute(['buyer_id' => $userId]);
    $buyerActivities = $buyerReviewsStmt->fetchAll();
}

$fisherStats = [
    'avgRating' => null,
    'reviewsCount' => 0,
    'productCount' => 0,
    'pendingCount' => 0,
    'approvedCount' => 0,
    'rejectedCount' => 0,
];
$fisherProcessed = [];
$fisherHistory = [];

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

    $countsStmt = $pdo->prepare(
        'SELECT
            SUM(CASE WHEN status = "pending" THEN 1 ELSE 0 END) AS pending_count,
            SUM(CASE WHEN status = "approved" THEN 1 ELSE 0 END) AS approved_count,
            SUM(CASE WHEN status = "rejected" THEN 1 ELSE 0 END) AS rejected_count
         FROM product_requests
         WHERE fisherman_id = :fisherman_id'
    );
    $countsStmt->execute(['fisherman_id' => $userId]);
    $countRow = $countsStmt->fetch();
    $fisherStats['pendingCount'] = (int) ($countRow['pending_count'] ?? 0);
    $fisherStats['approvedCount'] = (int) ($countRow['approved_count'] ?? 0);
    $fisherStats['rejectedCount'] = (int) ($countRow['rejected_count'] ?? 0);

    $processedStmt = $pdo->prepare(
        'SELECT id, requester_name, business_location, fish_type, catch_location, generated_code, approved_at, requested_at, status, admin_note
         FROM product_requests
         WHERE fisherman_id = :fisherman_id AND status IN ("approved", "rejected")
         ORDER BY COALESCE(approved_at, requested_at) DESC'
    );
    $processedStmt->execute(['fisherman_id' => $userId]);
    $fisherProcessed = $processedStmt->fetchAll();

    $historyStmt = $pdo->prepare(
        'SELECT id, fish_type, business_location, requested_at, status, generated_code, admin_note
         FROM product_requests
         WHERE fisherman_id = :fisherman_id
         ORDER BY requested_at DESC
         LIMIT 30'
    );
    $historyStmt->execute(['fisherman_id' => $userId]);
    $fisherHistory = $historyStmt->fetchAll();
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
    <div class="dashboard-header dashboard-hero-card card">
        <div>
            <p class="eyebrow">Pannello personale</p>
            <h1>Ciao, <?= e($displayName) ?></h1>
            <p class="lead">Gestisci attivita, tracciabilita e operazioni quotidiane in un unico spazio.</p>
        </div>
        <span class="role-pill">Ruolo: <?= e((string) $userRole) ?></span>
    </div>
</section>

<?php if ($userRole === 'compratore'): ?>
    <section class="section container reveal">
        <h2>Panoramica acquisti</h2>
        <div class="stats-grid compact-stats-grid">
            <article class="stat-card">
                <span class="stat-label">QR scansionati</span>
                <span class="stat-value"><?= e((string) $buyerStats['scanCount']) ?></span>
                <span class="stat-meta">Totale scansioni registrate</span>
            </article>
            <article class="stat-card">
                <span class="stat-label">Locale frequente</span>
                <span class="stat-value"><?= e((string) $buyerStats['favoriteLocation']) ?></span>
                <span class="stat-meta">Dove acquisti piu spesso</span>
            </article>
            <article class="stat-card">
                <span class="stat-label">Prodotto preferito</span>
                <span class="stat-value"><?= e((string) $buyerStats['favoriteProduct']) ?></span>
                <span class="stat-meta">Piu acquistato</span>
            </article>
        </div>
    </section>

    <section class="section container reveal">
        <h2>Verifica prodotto</h2>
        <form class="form-card action-card scan-card compact-scan-card" action="product.php" method="get">
            <label>Codice prodotto
                <div class="scan-input">
                    <input type="text" id="productCode" name="code" placeholder="Es. LZ-2026-0001" required>
                    <button id="startScan" class="scan-btn" type="button" aria-label="Avvia scansione">
                        <i class="bi bi-camera"></i>
                    </button>
                </div>
            </label>
            <button type="submit" class="btn btn-primary">Cerca prodotto</button>
        </form>

        <div id="scanModal" class="admin-modal" aria-hidden="true">
            <div class="admin-modal-content scan-modal-content">
                <div class="admin-modal-head">
                    <h3><i class="bi bi-camera"></i> Scansione QR</h3>
                    <button type="button" class="btn btn-ghost btn-icon" data-modal-close="scanModal" aria-label="Chiudi">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <div id="qr-reader" class="qr-inline compact-qr-reader"></div>
                <p class="helper-text">Inquadra il QR e attendi il riconoscimento automatico.</p>
            </div>
        </div>
    </section>
<?php endif; ?>

<?php if ($userRole === 'pescatore'): ?>
    <section class="section container reveal">
        <h2>Panoramica attivita</h2>
        <div class="stats-grid compact-stats-grid">
            <article class="stat-card">
                <span class="stat-label">Valutazione media</span>
                <span class="stat-value"><?= e(renderStars($fisherStats['avgRating'])) ?></span>
                <span class="stat-meta"><?= e((string) $fisherStats['reviewsCount']) ?> recensioni</span>
            </article>
            <article class="stat-card">
                <span class="stat-label">Prodotti certificati</span>
                <span class="stat-value"><?= e((string) $fisherStats['productCount']) ?></span>
                <span class="stat-meta">Totale prodotti creati</span>
            </article>
            <article class="stat-card">
                <span class="stat-label">Richieste</span>
                <span class="stat-value"><?= e((string) $fisherStats['pendingCount']) ?></span>
                <span class="stat-meta"><?= e((string) $fisherStats['approvedCount']) ?> approvate / <?= e((string) $fisherStats['rejectedCount']) ?> rifiutate</span>
            </article>
        </div>
    </section>

    <section class="section container reveal">
        <div class="quick-add-row">
            <h2>Nuova richiesta aggiunta prodotto</h2>
            <button type="button" id="openAddProductModal" class="quick-add-btn" aria-label="Aggiungi prodotto">
                <i class="bi bi-plus-lg"></i>
            </button>
        </div>

        <div id="addProductModal" class="admin-modal" aria-hidden="true">
            <div class="admin-modal-content">
                <div class="admin-modal-head">
                    <h3><i class="bi bi-plus-circle"></i> Aggiunta prodotto</h3>
                    <button type="button" class="btn btn-ghost btn-icon" data-modal-close="addProductModal" aria-label="Chiudi">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
                <form method="post" class="form-card action-card compact-form-card">
                    <input type="hidden" name="action" value="create_product_request">
                    <label>Locale o bancarella
                        <input type="text" name="business_location" required>
                    </label>
                    <label>Nome prodotto
                        <input type="text" name="fish_type" required>
                    </label>
                    <label>Zona di pesca
                        <select name="catch_location" required>
                            <option value="">Seleziona zona</option>
                            <?php foreach ($catchLocations as $location): ?>
                                <option value="<?= e($location) ?>"><?= e($location) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <button class="btn btn-primary" type="submit">Invia richiesta</button>
                </form>
            </div>
        </div>
    </section>

    <section class="section container reveal">
        <h2>Esito richieste prodotto</h2>
        <div class="table-wrap card compact-table-card">
            <table>
                <thead>
                    <tr>
                        <th>Stato</th>
                        <th>Prodotto</th>
                        <th>Locale</th>
                        <th>Codice</th>
                        <th>QR</th>
                        <th>Data</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!$fisherProcessed): ?>
                        <tr><td colspan="6" class="helper-text">Nessuna richiesta elaborata.</td></tr>
                    <?php else: ?>
                        <?php foreach ($fisherProcessed as $req): ?>
                            <tr>
                                <td>
                                    <span class="status-led <?= $req['status'] === 'approved' ? 'is-green' : 'is-red' ?>">
                                        <?= $req['status'] === 'approved' ? 'Approvata' : 'Rifiutata' ?>
                                    </span>
                                </td>
                                <td><?= e((string) $req['fish_type']) ?></td>
                                <td><?= e((string) $req['business_location']) ?></td>
                                <td><?= e((string) ($req['generated_code'] ?? '-')) ?></td>
                                <td>
                                    <?php if ($req['status'] === 'approved' && !empty($req['generated_code'])): ?>
                                        <div class="qr-stack qr-stack-premium">
                                            <div class="qr-mini" data-qr-text="<?= e((string) $req['generated_code']) ?>"></div>
                                            <button type="button" class="qr-zoom" data-qr-text="<?= e((string) $req['generated_code']) ?>" aria-label="Ingrandisci QR">
                                                <i class="bi bi-zoom-in"></i>
                                            </button>
                                        </div>
                                    <?php else: ?>
                                        <span class="small">Non disponibile</span>
                                    <?php endif; ?>
                                </td>
                                <td><?= e((string) ($req['approved_at'] ?? $req['requested_at'] ?? 'N/D')) ?></td>
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
            <p class="lead">Accedi al controllo admin per gestire utenti, boe e richieste prodotti.</p>
            <a class="btn btn-primary" href="admin.php">Apri controllo admin</a>
        </div>
    </section>
<?php endif; ?>

<?php if ($userRole !== 'admin'): ?>
    <div id="userSettingsModal" class="admin-modal" aria-hidden="true">
        <div class="admin-modal-content settings-modal-content">
            <div class="admin-modal-head">
                <h3><i class="bi bi-gear"></i> Impostazioni account</h3>
                <button type="button" class="btn btn-ghost btn-icon" data-modal-close="userSettingsModal" aria-label="Chiudi">
                    <i class="bi bi-x-lg"></i>
                </button>
            </div>

            <div class="settings-tabs" role="tablist" aria-label="Sezioni impostazioni">
                <button type="button" class="settings-tab is-active" data-settings-tab="profile">Gestione profilo</button>
                <button type="button" class="settings-tab" data-settings-tab="activity">Gestione attivita</button>
            </div>

            <div class="settings-panes" id="settingsPanes">
                <section class="settings-pane is-active" data-settings-pane="profile">
                    <div class="settings-grid">
                        <form method="post" class="form-card compact-form">
                            <input type="hidden" name="action" value="update_profile">
                            <h4>Profilo account</h4>
                            <div class="name-split">
                                <label>Nome
                                    <input type="text" name="first_name" value="<?= e($userFirstName) ?>" required>
                                </label>
                                <label>Cognome
                                    <input type="text" name="last_name" value="<?= e($userLastName) ?>" required>
                                </label>
                            </div>
                            <label>Email
                                <input type="email" name="email" value="<?= e((string) ($user['email'] ?? '')) ?>" required>
                            </label>
                            <button class="btn btn-primary" type="submit">Salva profilo</button>
                        </form>

                        <form method="post" class="form-card compact-form">
                            <input type="hidden" name="action" value="update_password">
                            <h4>Sicurezza</h4>
                            <label>Password attuale
                                <input type="password" name="current_password" required>
                            </label>
                            <label>Nuova password
                                <input type="password" name="new_password" minlength="8" required>
                            </label>
                            <label>Conferma nuova password
                                <input type="password" name="confirm_password" minlength="8" required>
                            </label>
                            <button class="btn btn-primary" type="submit">Aggiorna password</button>
                        </form>
                    </div>
                </section>

                <section class="settings-pane" data-settings-pane="activity">
                    <?php if ($userRole === 'compratore'): ?>
                        <p class="helper-text">Cronologia commenti e recensioni con modifica o eliminazione.</p>
                        <?php if (!$buyerActivities): ?>
                            <p class="helper-text">Nessuna attivita disponibile.</p>
                        <?php else: ?>
                            <div class="activity-list">
                                <?php foreach ($buyerActivities as $activity): ?>
                                    <div class="activity-card">
                                        <strong><?= e((string) $activity['fish_type']) ?> (<?= e((string) $activity['code']) ?>)</strong>
                                        <p class="small"><?= e((string) $activity['created_at']) ?></p>
                                        <form method="post" class="compact-form">
                                            <input type="hidden" name="action" value="update_buyer_review">
                                            <input type="hidden" name="review_id" value="<?= e((string) $activity['id']) ?>">
                                            <label>Valutazione
                                                <select name="rating" required>
                                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                                        <option value="<?= $i ?>" <?= (int) $activity['rating'] === $i ? 'selected' : '' ?>><?= $i ?></option>
                                                    <?php endfor; ?>
                                                </select>
                                            </label>
                                            <label>Commento
                                                <input type="text" name="comment" maxlength="255" value="<?= e((string) $activity['comment']) ?>" required>
                                            </label>
                                            <div class="activity-actions">
                                                <button class="btn btn-primary" type="submit">Salva</button>
                                            </div>
                                        </form>
                                        <form method="post" onsubmit="return confirm('Eliminare questa recensione?');">
                                            <input type="hidden" name="action" value="delete_buyer_review">
                                            <input type="hidden" name="review_id" value="<?= e((string) $activity['id']) ?>">
                                            <button class="btn btn-danger" type="submit">Elimina</button>
                                        </form>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>

                    <?php if ($userRole === 'pescatore'): ?>
                        <p class="helper-text">Cronologia richieste di aggiunta prodotto.</p>
                        <?php if (!$fisherHistory): ?>
                            <p class="helper-text">Nessuna richiesta disponibile.</p>
                        <?php else: ?>
                            <div class="activity-list">
                                <?php foreach ($fisherHistory as $history): ?>
                                    <div class="activity-card">
                                        <div class="activity-head-row">
                                            <strong><?= e((string) $history['fish_type']) ?> - <?= e((string) $history['business_location']) ?></strong>
                                            <span class="status-led <?= $history['status'] === 'approved' ? 'is-green' : ($history['status'] === 'rejected' ? 'is-red' : 'is-amber') ?>"><?= e((string) ucfirst($history['status'])) ?></span>
                                        </div>
                                        <p class="small">Richiesta: <?= e((string) $history['requested_at']) ?></p>
                                        <?php if (!empty($history['generated_code'])): ?>
                                            <p class="small">Codice: <?= e((string) $history['generated_code']) ?></p>
                                        <?php endif; ?>
                                        <?php if (!empty($history['admin_note'])): ?>
                                            <p class="small">Nota admin: <?= e((string) $history['admin_note']) ?></p>
                                        <?php endif; ?>
                                        <?php if ($history['status'] !== 'approved'): ?>
                                            <form method="post" onsubmit="return confirm('Rimuovere questa richiesta?');">
                                                <input type="hidden" name="action" value="delete_own_request">
                                                <input type="hidden" name="request_id" value="<?= e((string) $history['id']) ?>">
                                                <button class="btn btn-danger" type="submit">Rimuovi</button>
                                            </form>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </section>
            </div>
        </div>
    </div>
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