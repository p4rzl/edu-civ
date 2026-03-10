<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireRole(['pescatore', 'admin']);

$user = currentUser();
$fishermanId = (int) ($user['id'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'create_product_request') {
    $requesterName = trim($_POST['requester_name'] ?? '');
    $businessLocation = trim($_POST['business_location'] ?? '');
    $fishType = trim($_POST['fish_type'] ?? '');
    $catchLocation = trim($_POST['catch_location'] ?? '');

    if ($requesterName === '' || $businessLocation === '' || $fishType === '' || $catchLocation === '') {
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
            'fisherman_id' => $fishermanId,
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

    header('Location: fisherman.php');
    exit;
}

$requestsPending = $pdo->prepare(
    'SELECT id, requester_name, business_location, fish_type, catch_location, requested_at
     FROM product_requests
     WHERE fisherman_id = :fisherman_id AND status = "pending"
     ORDER BY requested_at DESC'
);
$requestsPending->bindValue(':fisherman_id', $fishermanId, PDO::PARAM_INT);
$requestsPending->execute();
$pending = $requestsPending->fetchAll();

$requestsApproved = $pdo->prepare(
    'SELECT id, requester_name, business_location, fish_type, catch_location, generated_code, approved_at
     FROM product_requests
     WHERE fisherman_id = :fisherman_id AND status = "approved"
     ORDER BY approved_at DESC'
);
$requestsApproved->bindValue(':fisherman_id', $fishermanId, PDO::PARAM_INT);
$requestsApproved->execute();
$approved = $requestsApproved->fetchAll();

$pageTitle = 'Area Pescatore - Lanz';
$bodyClass = 'fisherman-page';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section container reveal">
    <h1>Area Pescatore</h1>
    <p class="lead">Invia richieste di certificazione prodotto e monitora lo stato delle approvazioni in tempo reale.</p>
</section>

<section class="section container reveal">
    <h2><i class="bi bi-plus-square"></i> Nuova richiesta aggiunta prodotto</h2>
    <form method="post" class="form-card">
        <input type="hidden" name="action" value="create_product_request">
        <label>Nome del richiedente
            <input type="text" name="requester_name" required>
        </label>
        <label>Ubicazione dell'attivita commerciale
            <input type="text" name="business_location" required>
        </label>
        <label>Nome prodotto
            <input type="text" name="fish_type" required>
        </label>
        <label>Luogo in cui e stato pescato il prodotto
            <input type="text" name="catch_location" required>
        </label>
        <button class="btn btn-primary" type="submit">Invia richiesta</button>
    </form>
</section>

<section class="section container reveal">
    <h2><i class="bi bi-hourglass-split"></i> Richieste in attesa</h2>
    <div class="table-wrap card">
        <table>
            <thead>
                <tr>
                    <th>Richiedente</th>
                    <th>Ubicazione attivita</th>
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
    <h2><i class="bi bi-check2-circle"></i> Richieste accettate con QR</h2>
    <div class="table-wrap card">
        <table>
            <thead>
                <tr>
                    <th>Prodotto</th>
                    <th>Richiedente</th>
                    <th>Ubicazione attivita</th>
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
                                <div class="qr-mini" data-qr-text="<?= e((string) $req['generated_code']) ?>"></div>
                                <button type="button" class="btn btn-ghost qr-enlarge" data-qr-text="<?= e((string) $req['generated_code']) ?>">Ingrandisci</button>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" defer></script>
<script src="assets/js/product-qr.js" defer></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
