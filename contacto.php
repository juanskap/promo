<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/site.php';

$eventLink = site_wa('¡Hola ' . $business . '! Quiero reservar para un evento 🎉');

page_start('Ubicación y Contacto', 'home', 'contacto');
?>

<section class="page-head">
    <span class="eyebrow">📍 Ubicación y contacto</span>
    <h1>Visítanos en Alpachaca, Guaranda</h1>
    <p>Estamos a las afueras de Guaranda, en un espacio tranquilo rodeado de naturaleza. Escríbenos para pedidos, reservas o para confirmar cómo llegar.</p>
    <div class="btn-row">
        <?php if ($waLink !== ''): ?>
            <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= e($waLink) ?>">📲 Escribir por WhatsApp</a>
        <?php endif; ?>
        <a class="btn btn-ghost" target="_blank" rel="noopener" href="<?= e($mapLink) ?>">Cómo llegar 📍</a>
    </div>
</section>

<section class="section">
    <div class="info-grid">
        <div class="info-box">
            <span class="info-label">Dirección</span>
            <p><?= e($address) ?></p>
            <?php if ($addressNote !== ''): ?>
                <p class="muted small"><?= e($addressNote) ?></p>
            <?php endif; ?>
        </div>
        <div class="info-box">
            <span class="info-label">Horario</span>
            <p><?= e($hours) ?></p>
            <p class="muted small">Fines de semana. Entre semana, reservas con anticipación.</p>
        </div>
        <div class="info-box">
            <span class="info-label">Domicilios</span>
            <p>Dentro de Guaranda</p>
            <p class="muted small"><?= e($deliveryCost) ?> por pedido · <?= e($delivery) ?></p>
        </div>
        <div class="info-box">
            <span class="info-label">WhatsApp</span>
            <?php if ($waNumber !== ''): ?>
                <p><a href="<?= e($waLink) ?>" target="_blank" rel="noopener"><?= e($waNumber) ?></a></p>
            <?php endif; ?>
            <p class="muted small">Pedidos, reservas y consultas de la cabaña.</p>
            <?php if ($instagram !== ''): ?>
                <p class="small">Instagram:
                    <a target="_blank" rel="noopener" href="https://instagram.com/<?= e(ltrim($instagram, '@')) ?>">@<?= e(ltrim($instagram, '@')) ?></a>
                </p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($reservations !== ''): ?>
        <p class="notice-inline">🎉 <?= e($reservations) ?></p>
    <?php endif; ?>

    <div class="btn-row">
        <?php if ($eventLink !== ''): ?>
            <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= e($eventLink) ?>">Reservar para un evento</a>
        <?php endif; ?>
        <a class="btn btn-dark" target="_blank" rel="noopener" href="<?= e($mapLink) ?>">Abrir en Google Maps 📍</a>
    </div>
</section>

<section class="section faq">
    <div class="section-head">
        <span class="eyebrow">❓ Preguntas frecuentes</span>
        <h2>Todo lo que necesitas saber</h2>
    </div>
    <details>
        <summary>¿Dónde están ubicados?</summary>
        <p>En <strong><?= e($address) ?></strong>. <?= e($addressNote) ?> También puedes abrir la ubicación en Google Maps desde el botón “Cómo llegar”.</p>
    </details>
    <details>
        <summary>¿Qué días atienden?</summary>
        <p>Abrimos <strong><?= e($hours) ?></strong>. Entre semana atendemos reservas para eventos, reuniones y pedidos grandes con anticipación.</p>
    </details>
    <details>
        <summary>¿Hacen envíos a domicilio?</summary>
        <p>Sí, entregamos dentro de Guaranda. El costo es <strong><?= e($deliveryCost) ?> por pedido</strong>, no por plato. Escríbenos por WhatsApp para coordinar.</p>
    </details>
    <details>
        <summary>¿Cómo funciona la tarjeta de sellos?</summary>
        <p>Cada caldo suma un sello. Al completar <strong><?= e((string)$required) ?> sellos</strong>, ganas tu <strong><?= e($reward) ?></strong>. Puedes registrarte con tu celular y revisar tu tarjeta desde el sitio.</p>
    </details>
    <details>
        <summary>¿Puedo reservar la cabaña?</summary>
        <p>Claro. Escríbenos por WhatsApp para consultar disponibilidad y tarifas. La cabaña es un espacio tranquilo rodeado de naturaleza, ideal para descansar.</p>
    </details>
</section>

<div class="final-cta">
    <div>
        <h2>Estamos para atenderte</h2>
        <p>Pedidos, reservas y consultas por WhatsApp.</p>
    </div>
    <?php if ($waLink !== ''): ?>
        <a class="btn btn-primary btn-big" target="_blank" rel="noopener" href="<?= e($waLink) ?>">📲 Escribir ahora</a>
    <?php endif; ?>
</div>

<?php page_end(); ?>
