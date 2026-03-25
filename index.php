<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/config.php';
require_once __DIR__ . '/includes/auth.php';
$pageTitle = 'Lanz - Innovazione marina per Bari';
$bodyClass = 'home-page';
require_once __DIR__ . '/includes/header.php';
?>

<section class="hero container reveal">
    <div>
        <p class="eyebrow">Blue Economy Locale</p>
        <h1>Lanz modernizza il mare di Bari con boe IoT intelligenti.</h1>
        <p class="lead">
            In collaborazione con il Comune di Bari, Lanz rileva dati marini in tempo reale
            per garantire qualita del pescato, tracciabilita e sostenibilita.
        </p>
        <div class="hero-chips" aria-label="Punti chiave del progetto">
            <span class="hero-chip">Rete boe sul litorale barese</span>
            <span class="hero-chip">Dashboard dati in tempo reale</span>
            <span class="hero-chip">Certificazione digitale QR</span>
        </div>
        <div class="hero-actions">
            <a class="btn btn-primary" href="register.php">Partecipa al progetto</a>
        </div>
    </div>
    <div class="hero-card floating-card telemetry-panel">
        <div class="telemetry-head">
            <h3>Telemetria in diretta</h3>
            <span class="status-pill"><i class="bi bi-broadcast-pin"></i> 4 boe online</span>
        </div>
        <div class="telemetry-metrics" aria-label="Indicatori telemetrici principali">
            <article class="metric-item">
                <p>Microplastiche</p>
                <strong>0.8%</strong>
                <span class="metric-bar"><span style="width: 26%"></span></span>
            </article>
            <article class="metric-item">
                <p>Ossigeno disciolto</p>
                <strong>7.4 mg/L</strong>
                <span class="metric-bar"><span style="width: 74%"></span></span>
            </article>
            <article class="metric-item">
                <p>Salinita</p>
                <strong>37.9 PSU</strong>
                <span class="metric-bar"><span style="width: 63%"></span></span>
            </article>
            <article class="metric-item">
                <p>Trasmissione</p>
                <strong>5G + LoRaWAN</strong>
                <span class="metric-bar"><span style="width: 92%"></span></span>
            </article>
        </div>
        <p class="telemetry-note">Aggiornamento continuo dalla rete boe sul litorale barese.</p>
    </div>
</section>

<div class="container reveal skyline-decor" aria-hidden="true">
    <div class="skyline-track">
        <img src="assets/svg/harbor-illustration.svg" alt="" class="skyline-item skyline-left tint-brand-b">
        <img src="assets/svg/sea-line.svg" alt="" class="skyline-item skyline-center tint-brand-a">
        <img src="assets/svg/fish-emblem.svg" alt="" class="skyline-item skyline-right tint-brand-c">
    </div>
</div>

<section class="section container reveal home-overview no-card-overview">
    <div class="section-head">
        <p class="eyebrow">Visione e metodo</p>
        <h2>Una filiera marina leggibile, dal mare al banco vendita</h2>
    </div>
    <div class="editorial-layout">
        <div class="editorial-grid">
            <article class="text-block">
                <h3>Chi siamo</h3>
                <p>
                    Lanz e una start-up barese che unisce tecnologia marina, tradizione locale e sostenibilita.
                    Collaboriamo con il Comune di Bari per rendere la filiera del pescato trasparente, affidabile e verificabile.
                </p>
            </article>
            <article class="text-block">
                <h3>Cosa facciamo</h3>
                <p>
                    Raccogliamo dati su salinita, ossigeno e microplastiche tramite boe IoT e li trasformiamo in certificazioni digitali,
                    consultabili da compratori, pescatori e operatori del territorio.
                </p>
            </article>
        </div>
        <aside class="process-panel" aria-label="Processo operativo Lanz">
            <h3>Percorso del dato</h3>
            <ol class="process-list">
                <li><strong>Rilevazione in mare</strong><span>I sensori raccolgono indicatori ambientali 24/7.</span></li>
                <li><strong>Analisi intelligente</strong><span>Modelli AI rilevano anomalie e trend di qualita.</span></li>
                <li><strong>Validazione filiera</strong><span>Ogni lotto ottiene prove digitali consultabili.</span></li>
            </ol>
        </aside>
    </div>
    <div class="capabilities-row" aria-label="Capacita principali della piattaforma">
        <article class="capability-item">
            <span class="capability-icon"><i class="bi bi-water"></i></span>
            <h3>Boe Intelligenti</h3>
            <p>Sensori in punti strategici del mare barese per monitoraggio continuo e allarmi immediati.</p>
        </article>
        <article class="capability-item">
            <span class="capability-icon"><i class="bi bi-cpu"></i></span>
            <h3>AI e Data Center</h3>
            <p>Analisi automatica dei dati con supporto decisionale su qualita acqua, sicurezza e stabilita della filiera.</p>
        </article>
        <article class="capability-item">
            <span class="capability-icon"><i class="bi bi-qr-code-scan"></i></span>
            <h3>Certificazione QR</h3>
            <p>Ogni lotto ottiene una traccia univoca con indicatori ambientali e origine del pescato.</p>
        </article>
    </div>
</section>

<section class="section container reveal certification-showcase">
    <div class="cert-copy">
        <p class="eyebrow">Certificazione Lanz</p>
        <h2>Rendi ogni banco vendita piu credibile con un QR che racconta dati reali</h2>
        <p>
            Espone la tua etichetta con QR code e mostra ai clienti area di pesca, qualita dell'acqua,
            controlli effettuati e storico ambientale del lotto.
        </p>
        <ul class="cert-list">
            <li><i class="bi bi-check2-circle"></i> Tracciabilita certificata per ogni lotto</li>
            <li><i class="bi bi-check2-circle"></i> Fiducia immediata per compratori e cittadini</li>
            <li><i class="bi bi-check2-circle"></i> Storico verificabile e conforme agli standard locali</li>
        </ul>
        <a class="btn btn-primary" href="register.php">Diventa partner</a>
    </div>
    <div class="cert-visual" aria-label="Anteprima certificazione digitale">
        <div class="qr-preview">
            <div class="qr-grid" aria-hidden="true">
                <span></span><span></span><span></span><span></span>
                <span></span><span></span><span></span><span></span>
                <span></span><span></span><span></span><span></span>
                <span></span><span></span><span></span><span></span>
            </div>
            <h3>Certificato QR #BARI-0241</h3>
            <p>Origine: Molo San Nicola</p>
            <p>Qualita acqua: conforme</p>
            <p>Ultimo controllo: 14 min fa</p>
        </div>
    </div>
</section>

<section class="section container reveal">
    <h2 class="section-title-center">Dati marini in tempo reale</h2>
    <div class="charts-grid">
        <div class="card chart-card">
            <h3>Qualita dell'acqua (ultimi 7 giorni)</h3>
            <canvas id="qualityChart"></canvas>
        </div>
        <div class="card chart-card">
            <h3>Microplastiche rilevate</h3>
            <canvas id="microplasticsChart"></canvas>
        </div>
        <div class="card chart-card">
            <h3>Status boe monitoraggio</h3>
            <canvas id="buoyStatusChart"></canvas>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>


