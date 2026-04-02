<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
requireRole(['admin']);

function generateUniqueProductCode(PDO $pdo): string
{
    do {
        $code = 'LZ-' . date('Y') . '-' . strtoupper(bin2hex(random_bytes(3)));
        $check = $pdo->prepare('SELECT COUNT(*) FROM products WHERE code = :code');
        $check->bindValue(':code', $code);
        $check->execute();
    } while ((int) $check->fetchColumn() > 0);

    return $code;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $selfId = (int) (currentUser()['id'] ?? 0);

    if ($action === 'update_user') {
        $userId = (int) ($_POST['user_id'] ?? 0);
        $fullName = trim($_POST['full_name'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $role = $_POST['role'] ?? '';

        if ($userId <= 0 || $fullName === '' || $username === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || !in_array($role, ['compratore', 'pescatore', 'admin'], true)) {
            setFlash('error', 'Dati utente non validi.');
        } else {
            try {
                $stmt = $pdo->prepare(
                    'UPDATE users
                     SET full_name = :full_name, username = :username, email = :email, role = :role
                     WHERE id = :id'
                );
                $stmt->bindValue(':id', $userId, PDO::PARAM_INT);
                $stmt->bindValue(':full_name', $fullName);
                $stmt->bindValue(':username', $username);
                $stmt->bindValue(':email', $email);
                $stmt->bindValue(':role', $role);
                $stmt->execute();
                setFlash('success', 'Profilo utente aggiornato.');
            } catch (Throwable $e) {
                setFlash('error', 'Impossibile aggiornare l\'utente: username o email gia in uso.');
            }
        }
    }

    if ($action === 'delete_user') {
        $deleteUserId = (int) ($_POST['delete_user_id'] ?? 0);

        if ($deleteUserId === $selfId) {
            setFlash('error', 'Non puoi eliminare il tuo account amministratore attivo.');
        } else {
            try {
                $pdo->beginTransaction();

                $cleanupStatements = [
                    'DELETE FROM reviews WHERE buyer_id = :id OR fisherman_id = :id',
                    'DELETE FROM product_scans WHERE buyer_id = :id',
                    'DELETE FROM notifications WHERE target_user_id = :id',
                    'DELETE FROM products WHERE fisherman_id = :id',
                    'DELETE FROM product_requests WHERE fisherman_id = :id OR approved_by = :id',
                    'DELETE FROM users WHERE id = :id',
                ];

                foreach ($cleanupStatements as $sql) {
                    $stmt = $pdo->prepare($sql);
                    $stmt->bindValue(':id', $deleteUserId, PDO::PARAM_INT);
                    $stmt->execute();
                }

                $pdo->commit();
                setFlash('success', 'Utente eliminato con successo.');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                setFlash('error', 'Impossibile eliminare l\'utente: esistono ancora vincoli collegati.');
            }
        }
    }

    if ($action === 'update_buoy') {
        $buoyId = (int) ($_POST['buoy_id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $zone = trim($_POST['zone'] ?? '');
        $status = trim($_POST['status'] ?? '');
        $lat = filter_var($_POST['lat'] ?? null, FILTER_VALIDATE_FLOAT);
        $lng = filter_var($_POST['lng'] ?? null, FILTER_VALIDATE_FLOAT);

        if ($buoyId <= 0 || $name === '' || $zone === '' || $status === '' || $lat === false || $lng === false || $lat < -90 || $lat > 90 || $lng < -180 || $lng > 180) {
            setFlash('error', 'Parametri boa non validi.');
        } else {
            $stmt = $pdo->prepare(
                'UPDATE buoys
                 SET name = :name, zone = :zone, lat = :lat, lng = :lng, status = :status
                 WHERE id = :id'
            );
            $stmt->bindValue(':id', $buoyId, PDO::PARAM_INT);
            $stmt->bindValue(':name', $name);
            $stmt->bindValue(':zone', $zone);
            $stmt->bindValue(':lat', (float) $lat);
            $stmt->bindValue(':lng', (float) $lng);
            $stmt->bindValue(':status', $status);
            $stmt->execute();
            setFlash('success', 'Configurazione boa aggiornata.');
        }
    }

    if ($action === 'approve_request') {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $adminId = (int) (currentUser()['id'] ?? 0);

        $requestStmt = $pdo->prepare('SELECT * FROM product_requests WHERE id = :id AND status = "pending" LIMIT 1');
        $requestStmt->bindValue(':id', $requestId, PDO::PARAM_INT);
        $requestStmt->execute();
        $request = $requestStmt->fetch();

        if (!$request) {
            setFlash('error', 'Richiesta non trovata o gia elaborata.');
        } else {
            $code = generateUniqueProductCode($pdo);
            $now = date('Y-m-d H:i');

            try {
                $pdo->beginTransaction();

                $insertProduct = $pdo->prepare(
                    'INSERT INTO products (
                        code, fish_type, catch_area, catch_date,
                        microplastics_percent, dissolved_oxygen, salinity,
                        quality_label, fisherman_id, requester_name, business_location, source_request_id
                    ) VALUES (
                        :code, :fish_type, :catch_area, :catch_date,
                        :microplastics_percent, :dissolved_oxygen, :salinity,
                        :quality_label, :fisherman_id, :requester_name, :business_location, :source_request_id
                    )'
                );
                $insertProduct->execute([
                    'code' => $code,
                    'fish_type' => $request['fish_type'],
                    'catch_area' => $request['catch_location'],
                    'catch_date' => date('Y-m-d'),
                    'microplastics_percent' => mt_rand(5, 16) / 10,
                    'dissolved_oxygen' => mt_rand(65, 82) / 10,
                    'salinity' => mt_rand(365, 390) / 10,
                    'quality_label' => 'Certificato Lanz',
                    'fisherman_id' => (int) $request['fisherman_id'],
                    'requester_name' => $request['requester_name'],
                    'business_location' => $request['business_location'],
                    'source_request_id' => $requestId,
                ]);

                $updateRequest = $pdo->prepare(
                    'UPDATE product_requests
                     SET status = "approved", generated_code = :generated_code, approved_by = :approved_by, approved_at = :approved_at
                     WHERE id = :id'
                );
                $updateRequest->execute([
                    'generated_code' => $code,
                    'approved_by' => $adminId,
                    'approved_at' => $now,
                    'id' => $requestId,
                ]);

                $notifyFisherman = $pdo->prepare(
                    'INSERT INTO notifications (target_user_id, message, link)
                     VALUES (:target_user_id, :message, :link)'
                );
                $notifyFisherman->execute([
                    'target_user_id' => (int) $request['fisherman_id'],
                    'message' => 'Richiesta approvata: prodotto certificato con codice ' . $code,
                    'link' => 'fisherman.php',
                ]);

                $notifyBuyers = $pdo->prepare(
                    'INSERT INTO notifications (target_role, message, link)
                     VALUES ("compratore", :message, :link)'
                );
                $notifyBuyers->execute([
                    'message' => 'Nuovo prodotto certificato disponibile: ' . $request['fish_type'] . ' (' . $code . ')',
                    'link' => 'product.php?code=' . urlencode((string) $code),
                ]);

                $pdo->commit();
                setFlash('success', 'Richiesta approvata e QR univoco generato.');
            } catch (Throwable $e) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                setFlash('error', 'Errore durante l\'approvazione della richiesta.');
            }
        }
    }

    if ($action === 'reject_request') {
        $requestId = (int) ($_POST['request_id'] ?? 0);
        $adminNote = trim($_POST['admin_note'] ?? 'Richiesta rifiutata dall\'amministratore.');

        $requestStmt = $pdo->prepare('SELECT fisherman_id FROM product_requests WHERE id = :id AND status = "pending" LIMIT 1');
        $requestStmt->bindValue(':id', $requestId, PDO::PARAM_INT);
        $requestStmt->execute();
        $request = $requestStmt->fetch();

        if (!$request) {
            setFlash('error', 'Richiesta non trovata o gia elaborata.');
        } else {
            $updateRequest = $pdo->prepare('UPDATE product_requests SET status = "rejected", admin_note = :admin_note WHERE id = :id');
            $updateRequest->execute([
                'admin_note' => $adminNote,
                'id' => $requestId,
            ]);

            $notifyFisherman = $pdo->prepare(
                'INSERT INTO notifications (target_user_id, message, link)
                 VALUES (:target_user_id, :message, :link)'
            );
            $notifyFisherman->execute([
                'target_user_id' => (int) $request['fisherman_id'],
                'message' => 'Una richiesta prodotto e stata rifiutata: ' . $adminNote,
                'link' => 'fisherman.php',
            ]);

            setFlash('success', 'Richiesta rifiutata.');
        }
    }

    header('Location: admin.php');
    exit;
}

$users = $pdo->query('SELECT id, full_name, username, email, role, created_at FROM users ORDER BY id DESC')->fetchAll();
$buoys = $pdo->query('SELECT id, name, zone, lat, lng, status, last_update FROM buoys ORDER BY id ASC')->fetchAll();
$readings = $pdo->query(
    'SELECT buoy_id, salinity, dissolved_oxygen, microplastics_percent, recorded_at
     FROM buoy_readings
     ORDER BY recorded_at ASC'
)->fetchAll();

$buoysWithLatestData = $pdo->query(
    'SELECT
        b.id,
        b.name,
        b.zone,
        b.status,
        b.last_update,
        (
            SELECT salinity FROM buoy_readings br
            WHERE br.buoy_id = b.id
            ORDER BY br.recorded_at DESC
            LIMIT 1
        ) AS salinity,
        (
            SELECT dissolved_oxygen FROM buoy_readings br
            WHERE br.buoy_id = b.id
            ORDER BY br.recorded_at DESC
            LIMIT 1
        ) AS dissolved_oxygen,
        (
            SELECT microplastics_percent FROM buoy_readings br
            WHERE br.buoy_id = b.id
            ORDER BY br.recorded_at DESC
            LIMIT 1
        ) AS microplastics_percent
    FROM buoys b
    ORDER BY b.id ASC'
)->fetchAll();

$pendingRequests = $pdo->query(
    'SELECT pr.*, u.full_name AS fisherman_name
     FROM product_requests pr
     JOIN users u ON u.id = pr.fisherman_id
     WHERE pr.status = "pending"
     ORDER BY pr.requested_at DESC'
)->fetchAll();

$processedRequests = $pdo->query(
    'SELECT pr.*, u.full_name AS fisherman_name
     FROM product_requests pr
     JOIN users u ON u.id = pr.fisherman_id
     WHERE pr.status IN ("approved", "rejected")
     ORDER BY COALESCE(pr.approved_at, pr.requested_at) DESC'
)->fetchAll();

$pageTitle = 'Admin - Lanz';
$bodyClass = 'admin-page';
require_once __DIR__ . '/includes/header.php';
?>

<section class="section container reveal">
    <h1>Pannello Amministratore Lanz</h1>
    <p class="lead">Gestione operativa completa: utenti registrati, boe, posizioni e telemetria.</p>
</section>

<section class="section container reveal">
    <h2><i class="bi bi-clipboard-check"></i> Richieste aggiunta prodotto</h2>
    <div class="table-wrap card">
        <table>
            <thead>
                <tr>
                    <th>Pescatore</th>
                    <th>Richiedente</th>
                    <th>Ubicazione attivita</th>
                    <th>Prodotto</th>
                    <th>Luogo pesca</th>
                    <th>Data richiesta</th>
                    <th>Azioni</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$pendingRequests): ?>
                    <tr>
                        <td colspan="7" class="helper-text">Nessuna richiesta in attesa.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($pendingRequests as $req): ?>
                        <tr>
                            <td><?= e((string) $req['fisherman_name']) ?></td>
                            <td><?= e((string) $req['requester_name']) ?></td>
                            <td><?= e((string) $req['business_location']) ?></td>
                            <td><?= e((string) $req['fish_type']) ?></td>
                            <td><?= e((string) $req['catch_location']) ?></td>
                            <td><?= e((string) $req['requested_at']) ?></td>
                            <td>
                                <div class="request-actions">
                                    <form method="post">
                                        <input type="hidden" name="action" value="approve_request">
                                        <input type="hidden" name="request_id" value="<?= e((string) $req['id']) ?>">
                                        <button class="btn btn-primary" type="submit">Approva</button>
                                    </form>
                                    <form method="post">
                                        <input type="hidden" name="action" value="reject_request">
                                        <input type="hidden" name="request_id" value="<?= e((string) $req['id']) ?>">
                                        <input type="text" name="admin_note" placeholder="Motivo rifiuto" required>
                                        <button class="btn btn-danger" type="submit">Rifiuta</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="section container reveal">
    <h2><i class="bi bi-qr-code"></i> Esito richieste prodotto</h2>
    <div class="table-wrap card">
        <table>
            <thead>
                <tr>
                    <th>Stato</th>
                    <th>Pescatore</th>
                    <th>Richiedente</th>
                    <th>Prodotto</th>
                    <th>Codice univoco</th>
                    <th>QR</th>
                    <th>Data esito</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$processedRequests): ?>
                    <tr>
                        <td colspan="7" class="helper-text">Nessuna richiesta elaborata al momento.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($processedRequests as $req): ?>
                        <tr>
                            <td>
                                <span class="status-led <?= $req['status'] === 'approved' ? 'is-green' : 'is-red' ?>">
                                    <?= $req['status'] === 'approved' ? 'Approvata' : 'Rifiutata' ?>
                                </span>
                            </td>
                            <td><?= e((string) $req['fisherman_name']) ?></td>
                            <td><?= e((string) $req['requester_name']) ?></td>
                            <td><?= e((string) $req['fish_type']) ?></td>
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

<section class="section container reveal">
    <h2><i class="bi bi-table"></i> Elenco utenti</h2>
    <div class="table-wrap card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Username</th>
                    <th>Email</th>
                    <th>Ruolo</th>
                    <th>Creato il</th>
                    <th>Azione</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $item): ?>
                    <tr>
                        <td><?= e((string) $item['id']) ?></td>
                        <td><?= e((string) $item['full_name']) ?></td>
                        <td><?= e((string) $item['username']) ?></td>
                        <td><?= e((string) $item['email']) ?></td>
                        <td><?= e((string) $item['role']) ?></td>
                        <td><?= e((string) $item['created_at']) ?></td>
                        <td>
                            <?php if ((int) $item['id'] !== (int) (currentUser()['id'] ?? 0)): ?>
                                <div class="table-actions">
                                    <button class="btn btn-ghost btn-icon user-edit-btn" type="button" data-user-id="<?= e((string) $item['id']) ?>" aria-label="Modifica utente">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <form method="post" onsubmit="return confirm('Eliminare questo utente?');">
                                        <input type="hidden" name="action" value="delete_user">
                                        <input type="hidden" name="delete_user_id" value="<?= e((string) $item['id']) ?>">
                                        <button class="btn btn-danger btn-icon" type="submit" aria-label="Elimina utente">
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </form>
                                </div>
                            <?php else: ?>
                                <span class="helper-text">Account attivo</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="section container reveal">
    <h2><i class="bi bi-map"></i> Mappa boe allocate</h2>
    <p class="helper-text">Controlla la posizione e lo stato in tempo reale delle boe sul litorale barese.</p>
    <div id="map"></div>

    <h3 class="admin-subtitle">Dati correnti boe</h3>
    <div class="table-wrap card">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Boa</th>
                    <th>Zona</th>
                    <th>Stato</th>
                    <th>Salinita (PSU)</th>
                    <th>Ossigeno (mg/L)</th>
                    <th>Microplastiche (%)</th>
                    <th>Ultimo aggiornamento</th>
                    <th>Azione</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($buoysWithLatestData as $buoy): ?>
                    <tr>
                        <td><?= e((string) $buoy['id']) ?></td>
                        <td><?= e((string) $buoy['name']) ?></td>
                        <td><?= e((string) $buoy['zone']) ?></td>
                        <td><?= e((string) $buoy['status']) ?></td>
                        <td><?= e(number_format((float) ($buoy['salinity'] ?? 0), 1)) ?></td>
                        <td><?= e(number_format((float) ($buoy['dissolved_oxygen'] ?? 0), 1)) ?></td>
                        <td><?= e(number_format((float) ($buoy['microplastics_percent'] ?? 0), 1)) ?></td>
                        <td><?= e((string) $buoy['last_update']) ?></td>
                        <td>
                            <button class="btn btn-ghost btn-icon buoy-edit-btn" type="button" data-buoy-id="<?= e((string) $buoy['id']) ?>" aria-label="Modifica boa">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</section>

<section class="section container reveal">
    <h2><i class="bi bi-graph-up-arrow"></i> Analisi storica sensori</h2>
    <p class="helper-text">Visualizzazione su grafico a linee delle serie temporali registrate dalle boe (es. salinita).</p>
    <div class="card chart-card">
        <label for="chartBuoySelect">Boa per il grafico</label>
        <select id="chartBuoySelect">
            <?php foreach ($buoys as $buoy): ?>
                <option value="<?= e((string) $buoy['id']) ?>"><?= e((string) $buoy['name']) ?> - <?= e((string) $buoy['zone']) ?></option>
            <?php endforeach; ?>
        </select>
        <canvas id="buoyChart" height="130"></canvas>
    </div>
</section>

<div id="userEditModal" class="admin-modal" aria-hidden="true">
    <div class="admin-modal-content">
        <div class="admin-modal-head">
            <h3>Modifica utente</h3>
            <button type="button" class="btn btn-ghost btn-icon" data-modal-close="userEditModal" aria-label="Chiudi">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form method="post" id="userEditModalForm" class="form-card compact-form" onsubmit="return confirm('Salvare le modifiche utente?');">
            <input type="hidden" name="action" value="update_user">
            <input type="hidden" name="user_id" id="modalUserId">
            <label>Nome completo
                <input type="text" name="full_name" id="modalUserFullName" required>
            </label>
            <label>Username
                <input type="text" name="username" id="modalUserUsername" required>
            </label>
            <label>Email
                <input type="email" name="email" id="modalUserEmail" required>
            </label>
            <label>Ruolo
                <select name="role" id="modalUserRole" required>
                    <option value="compratore">Compratore</option>
                    <option value="pescatore">Pescatore</option>
                    <option value="admin">Admin</option>
                </select>
            </label>
            <button class="btn btn-primary" type="submit"><i class="bi bi-pencil-square"></i> Salva modifiche</button>
        </form>
    </div>
</div>

<div id="buoyEditModal" class="admin-modal" aria-hidden="true">
    <div class="admin-modal-content">
        <div class="admin-modal-head">
            <h3>Modifica boa</h3>
            <button type="button" class="btn btn-ghost btn-icon" data-modal-close="buoyEditModal" aria-label="Chiudi">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <form method="post" id="buoyEditModalForm" class="form-card compact-form" onsubmit="return confirm('Salvare le modifiche boa?');">
            <input type="hidden" name="action" value="update_buoy">
            <input type="hidden" name="buoy_id" id="modalBuoyId">
            <label>Nome boa
                <input type="text" name="name" id="modalBuoyName" required>
            </label>
            <label>Zona
                <input type="text" name="zone" id="modalBuoyZone" required>
            </label>
            <div class="split">
                <label>Latitudine
                    <input type="number" name="lat" id="modalBuoyLat" step="0.0001" required>
                </label>
                <label>Longitudine
                    <input type="number" name="lng" id="modalBuoyLng" step="0.0001" required>
                </label>
            </div>
            <label>Stato
                <input type="text" name="status" id="modalBuoyStatus" required>
            </label>
            <button class="btn btn-primary" type="submit"><i class="bi bi-geo-alt"></i> Salva modifiche</button>
        </form>
    </div>
</div>

<link
    rel="stylesheet"
    href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css"
    integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY="
    crossorigin=""
/>
<script
    src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"
    integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo="
    crossorigin=""
    defer
></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js" defer></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/qrcodejs/1.0.0/qrcode.min.js" defer></script>
<script>
    window.USERS_DATA = <?= json_encode($users, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    window.BUOYS_DATA = <?= json_encode($buoys, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    window.BUOY_READINGS = <?= json_encode($readings, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
</script>
<script src="assets/js/admin-map.js" defer></script>
<script src="assets/js/admin-panel.js" defer></script>
<script src="assets/js/product-qr.js" defer></script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
