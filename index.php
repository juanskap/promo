<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';

$business  = setting('business_name', 'Cabaña Delfina');
$tagline   = setting('tagline', 'Calentito, nutritivo y hecho con amor para ti');
$heroText  = setting('hero_text', 'Caldo de salchicha tradicional, casero y con ingredientes seleccionados. Ideal para días fríos, almuerzos y para compartir en familia.');
$price     = setting('price_caldo', '$3.500');
$address   = setting('address', 'Guaranda, Bolívar');
$hours     = setting('hours', 'Sábados');
$delivery  = setting('delivery_info', 'Domicilios en Guaranda. Consulta zona y costo por WhatsApp.');
$instagram = trim(setting('instagram', ''));
$waNumber  = trim(setting('whatsapp_orders', ''));
$customer  = current_customer();
$required  = max(1, (int)setting('stamps_required', '10'));
$reward    = setting('reward_text', 'caldo de salchicha gratis');

$waLink = $waNumber !== ''
    ? wa_link($waNumber, '¡Hola ' . $business . '! Quiero hacer un pedido 🍲')
    : '';

page_start('Inicio', 'public');
?>
<section class="hero hero-home">
    <div class="hero-copy">
        <span class="pill"><?= e($address) ?></span>
        <h1>El caldo de salchicha<br><em>hecho con amor</em></h1>
        <p class="lead"><?= e($heroText) ?></p>

        <div class="btn-row">
            <?php if ($waLink !== ''): ?>
                <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= e($waLink) ?>">Pedir por WhatsApp 🍲</a>
            <?php endif; ?>
            <a class="btn btn-dark" href="<?= e(url($customer ? 'card.php' : 'tarjeta.php')) ?>">
                <?= $customer ? 'Ver mi tarjeta' : 'Tener mi tarjeta' ?>
            </a>
        </div>

        <p class="promo-note">🎁 Compra <?= e((string)$required) ?> caldos y el siguiente <strong>va gratis</strong>.</p>
    </div>

    <figure class="hero-flyer">
        <img src="<?= e(url('assets/afiche-caldo.jpeg')) ?>" alt="Afiche de <?= e($business) ?>: caldo de salchicha" width="452" height="640" loading="eager">
        <figcaption><?= e($tagline) ?></figcaption>
    </figure>
</section>

<section class="feature-grid">
    <article class="feature">
        <span>🔥</span>
        <h3>Calentito y nutritivo</h3>
        <p>Perfecto para días fríos, almuerzos y para compartir en familia.</p>
    </article>
    <article class="feature">
        <span>🌿</span>
        <h3>Ingredientes seleccionados</h3>
        <p>Salchicha tradicional de calidad y sazón casera.</p>
    </article>
    <article class="feature">
        <span>💛</span>
        <h3>Hecho en casa</h3>
        <p>Simple, casero y preparado con cariño para ti.</p>
    </article>
</section>

<div class="grid-2">
    <section class="card">
        <span class="pill">Nuestro menú</span>
        <h2 style="margin-top:10px">Caldo de salchicha</h2>
        <div class="menu-line">
            <span>Porción para llevar</span>
            <strong><?= e($price) ?></strong>
        </div>
        <p class="muted"><?= e($tagline) ?></p>
        <?php if ($waLink !== ''): ?>
            <a class="btn btn-primary btn-block" target="_blank" rel="noopener" href="<?= e($waLink) ?>">Pedir ahora</a>
        <?php else: ?>
            <a class="btn btn-primary btn-block" href="<?= e(url('tarjeta.php')) ?>">Quiero mi tarjeta de sellos</a>
        <?php endif; ?>
    </section>

    <section class="card card-soft">
        <span class="pill">Fidelidad</span>
        <h2 style="margin-top:10px">Tu tarjeta de sellos</h2>
        <p>Cada caldo suma un sello. Al completar <?= e((string)$required) ?>, tu <?= e($reward) ?> es gratis.</p>
        <div class="stamp-preview">
            <?php for ($i = 1; $i <= $required; $i++): ?>
                <span class="stamp <?= $i <= 4 ? 'on' : '' ?>"><?= $i <= 4 ? '🍲' : $i ?></span>
            <?php endfor; ?>
        </div>
        <a class="btn btn-dark btn-block" href="<?= e(url($customer ? 'card.php' : 'tarjeta.php?tab=registro')) ?>">
            <?= $customer ? 'Ver mi tarjeta' : 'Empezar a sumar sellos' ?>
        </a>
    </section>
</div>

<section class="card" id="contacto">
    <h2>Visítanos</h2>
    <div class="info-grid">
        <div>
            <span class="info-label">Dirección</span>
            <p><?= e($address) ?></p>
        </div>
        <div>
            <span class="info-label">Horario</span>
            <p><?= e($hours) ?></p>
        </div>
        <div>
            <span class="info-label">Domicilios</span>
            <p><?= e($delivery) ?></p>
        </div>
        <div>
            <span class="info-label">Pedidos</span>
            <?php if ($waLink !== ''): ?>
                <p><a target="_blank" rel="noopener" href="<?= e($waLink) ?>">Escríbenos por WhatsApp</a></p>
            <?php else: ?>
                <p class="muted">Próximamente</p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($instagram !== ''): ?>
        <p class="muted">Síguenos:
            <a target="_blank" rel="noopener" href="https://instagram.com/<?= e(ltrim($instagram, '@')) ?>">@<?= e(ltrim($instagram, '@')) ?></a>
        </p>
    <?php endif; ?>
</section>

<?php page_end(); ?>
