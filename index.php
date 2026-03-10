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
        <div class="hero-actions">
            <a class="btn btn-primary" href="register.php">Partecipa al progetto</a>
        </div>
    </div>
    <div class="hero-card floating-card">
        <img src="assets/svg/sea-line.svg" alt="Elemento grafico marino" class="hero-svg tint-brand-a">
        <h3>Telemetria in diretta</h3>
        <ul>
            <li>Microplastiche: <strong>0.8%</strong></li>
            <li>Ossigeno disciolto: <strong>7.4 mg/L</strong></li>
            <li>Salinita: <strong>37.9 PSU</strong></li>
            <li>Trasmissione: <strong>5G + LoRaWAN</strong></li>
        </ul>
    </div>
</section>

<section class="section container reveal dynamic-grid">
    <article class="card feature-tile">
        <h2>Chi siamo</h2>
        <p>
            Lanz e una start-up barese che unisce tecnologia marina, tradizione locale e sostenibilita.
            Collaboriamo con il Comune di Bari per rendere la filiera del pescato trasparente e affidabile.
        </p>
    </article>
    <article class="card feature-tile graphic-tile">
        <img src="assets/svg/harbor-illustration.svg" alt="Illustrazione porto e monitoraggio" class="tile-svg tint-brand-b">
    </article>
    <article class="card feature-tile">
        <h2>Cosa facciamo</h2>
        <p>
            Raccogliamo dati su salinita, ossigeno e microplastiche tramite boe IoT e li trasformiamo in certificazioni digitali,
            consultabili da compratori e operatori del territorio.
        </p>
    </article>
    <article class="card feature-tile narrow">
        <h3>Boe Intelligenti</h3>
        <p>Sensori in punti strategici del mare barese per monitoraggio continuo.</p>
    </article>
    <article class="card feature-tile narrow">
        <h3>AI e Data Center</h3>
        <p>Analisi automatica dei dati con supporto decisionale per qualita e sicurezza.</p>
    </article>
    <article class="card feature-tile narrow emblem">
        <img src="assets/svg/fish-emblem.svg" alt="Emblema pescato sostenibile" class="tile-svg tint-brand-c">
        <p>Certificazione pescato sostenibile con QR univoco.</p>
    </article>
</section>

<section class="section container reveal split about-split">
    <div class="card">
        <h2>Impatto sul territorio</h2>
        <p class="lead">Supportiamo pescatori, mercati locali e cittadini con una filiera digitale verificabile e dati ambientali affidabili.</p>
    </div>
    <div class="card">
        <h2>Missione Lanz</h2>
        <p class="lead">Portare la blue economy nel quotidiano della citta di Bari senza perdere il legame con il mare e la tradizione marinara.</p>
    </div>
</section>

<section class="section container reveal promo-banner">
    <div>
        <h2>Certificazione pescato sostenibile Lanz</h2>
        <p>
            Espone la tua etichetta con QR code in bancarella e mostra ai clienti la storia del prodotto:
            area di pesca, qualita dell'acqua e indicatori ambientali.
        </p>
    </div>
    <a class="btn btn-primary" href="register.php">Diventa partner</a>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
