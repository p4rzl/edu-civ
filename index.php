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
    <div class="collaboration-badge">
        <div class="collab-logo lanz-logo">
            <img src="assets/svg/icon.svg" alt="Logo Lanz">
            <span>Lanz</span>
        </div>
        <div class="collab-divider"></div>
        <div class="collab-logo bari-logo">
            <img src="assets/svg/Bari-Stemma.svg" alt="Stemma Comune di Bari">
            <span>Bari</span>
        </div>
    </div>
</section>

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
        <h2>Qualità certificata dal mare al banco vendita</h2>
        <p>
            Ogni lotto di pescato arriva con garanzia digitale tracciabile. 
            I tuoi clienti vedono l'origine, la qualità dell'acqua e tutta la filiera verificata.
        </p>
        <ul class="cert-list">
            <li><i class="bi bi-check2-circle"></i> Tracciabilità certificata</li>
            <li><i class="bi bi-check2-circle"></i> Fiducia immediata agli acquirenti</li>
            <li><i class="bi bi-check2-circle"></i> Qualità verificabile online</li>
        </ul>
        <a class="btn btn-primary" href="register.php">Diventa partner</a>
    </div>
    <div class="cert-visual" aria-label="Anteprima certificazione digitale">
        <div class="cert-badge">
            <div class="cert-badge-stars">
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
                <i class="bi bi-star-fill"></i>
            </div>
            <div class="cert-icon">
                <i class="bi bi-shield-check"></i>
            </div>
            <h3>Verificato</h3>
            <div class="badge-details">
                <p><strong>Molo San Nicola</strong></p>
                <p>Qualità garantita</p>
            </div>
        </div>
    </div>
</section>



<?php require_once __DIR__ . '/includes/footer.php'; ?>


