<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/site.php';

$reserveLink = site_wa('¡Hola ' . $business . '! Quiero consultar disponibilidad y tarifas de la cabaña 🌿');

page_start('La Cabaña', 'home', 'cabana');
?>

<section class="page-head">
    <span class="eyebrow">🌿 Cabaña Delfina</span>
    <h1><?= e($aboutTitle) ?></h1>
    <p><?= e($aboutText) ?></p>
    <div class="btn-row">
        <?php if ($reserveLink !== ''): ?>
            <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= e($reserveLink) ?>">Consultar disponibilidad</a>
        <?php endif; ?>
        <a class="btn btn-ghost" href="<?= e(url('contacto.php')) ?>">Ubicación y contacto</a>
    </div>
</section>

<section class="section">
    <div class="split split-about">
        <?php if ($aboutImage !== ''): ?>
            <figure class="about-photo">
                <img src="<?= e(url($aboutImage)) ?>" alt="Paisaje de Guaranda, Ecuador" width="900" height="675" loading="lazy">
                <figcaption>Alpachaca · Guaranda · Bolívar</figcaption>
            </figure>
        <?php endif; ?>
        <div class="about-copy">
            <span class="eyebrow">Un espacio para desconectarse</span>
            <h2>Naturaleza, calma y buena comida</h2>
            <p>La cabaña es un rincón tranquilo en Alpachaca, rodeado de naturaleza. Es el lugar ideal para descansar, compartir en familia o celebrar una reunión con anticipación.</p>
            <?php if ($aboutFeatures): ?>
                <ul class="check-list">
                    <?php foreach ($aboutFeatures as $feature): ?>
                        <li><span>✅</span> <?= e($feature) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <?php if ($addressNote !== ''): ?>
                <p class="muted small"><?= e($addressNote) ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section">
    <div class="section-head">
        <span class="eyebrow">📅 Reservas</span>
        <h2>Visítanos con anticipación</h2>
        <p class="muted">Las reservas, la disponibilidad y las tarifas se coordinan directamente por WhatsApp.</p>
    </div>
    <div class="info-grid">
        <div class="info-box">
            <span class="info-label">Ambiente</span>
            <p>Espacio tranquilo y natural</p>
            <p class="muted small">Ideal para descansar y compartir en familia.</p>
        </div>
        <div class="info-box">
            <span class="info-label">Reservas</span>
            <p>Con anticipación</p>
            <p class="muted small"><?= e($reservations) ?></p>
        </div>
        <div class="info-box">
            <span class="info-label">Ubicación</span>
            <p><?= e($address) ?></p>
            <p class="muted small"><?= e($addressNote) ?></p>
        </div>
        <div class="info-box">
            <span class="info-label">Horario</span>
            <p><?= e($hours) ?></p>
            <p class="muted small">Entre semana, reservas con anticipación.</p>
        </div>
    </div>
</section>

<div class="final-cta">
    <div>
        <h2>¿Quieres reservar la cabaña?</h2>
        <p>Escríbenos y te contamos disponibilidad, tarifas y cómo llegar.</p>
    </div>
    <?php if ($reserveLink !== ''): ?>
        <a class="btn btn-primary btn-big" target="_blank" rel="noopener" href="<?= e($reserveLink) ?>">📲 Reservar por WhatsApp</a>
    <?php endif; ?>
</div>

<?php page_end(); ?>
