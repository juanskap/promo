<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/site.php';

$preview = [];
foreach ($menu as $category => $items) {
    $isCaldos = mb_stripos($category, 'caldo') !== false;
    foreach (array_slice($items, 0, $isCaldos ? 1 : 2) as $item) {
        $preview[] = ['item' => $item, 'caldos' => $isCaldos];
    }
    if (count($preview) >= 3) {
        break;
    }
}
$preview = array_slice($preview, 0, 3);

page_start('Inicio', 'home', 'inicio');
?>
<?php if ($notice !== ''): ?>
    <div class="notice-strip">📍 <?= e($notice) ?></div>
<?php endif; ?>

<section class="hero hero-home" id="inicio">
    <div class="hero-copy">
        <span class="pill">📍 <?= e($address) ?></span>
        <h1><?= e($business) ?></h1>
        <p class="hero-tagline"><?= e($tagline) ?></p>
        <p class="lead"><?= e($heroText) ?></p>

        <ul class="hero-offers">
            <li>
                <span class="hero-offer-icon">🍲</span>
                <span><strong>Caldo de salchicha</strong><small>Calentito, nutritivo y delicioso · <?= e($price) ?></small></span>
            </li>
            <li>
                <span class="hero-offer-icon">🔥</span>
                <span><strong>Cortes de chancho a la parrilla</strong><small>Con papas, ensalada y chimichurri</small></span>
            </li>
            <li>
                <span class="hero-offer-icon">🛵</span>
                <span><strong>Envío a domicilio en Guaranda</strong><small><?= e($deliveryCost) ?> por pedido</small></span>
            </li>
            <li>
                <span class="hero-offer-icon">🌿</span>
                <span><strong>Espacio tranquilo en Alpachaca</strong><small>Para descansar y compartir en familia</small></span>
            </li>
        </ul>

        <div class="btn-row">
            <?php if ($waLink !== ''): ?>
                <a class="btn btn-primary btn-big" target="_blank" rel="noopener" href="<?= e($waLink) ?>">📲 Pedir por WhatsApp</a>
            <?php endif; ?>
            <a class="btn btn-ghost" href="<?= e(url('menu.php')) ?>">Ver nuestro menú</a>
        </div>

        <p class="hero-contact">
            <span>🕐 Atención: <strong><?= e($hours) ?></strong></span>
            <?php if ($waNumber !== ''): ?>
                <span>📱 WhatsApp: <a href="<?= e($waLink) ?>" target="_blank" rel="noopener"><?= e($waNumber) ?></a></span>
            <?php endif; ?>
        </p>

        <p class="promo-note">🎁 Compra <?= e((string)$required) ?> caldos y el siguiente <strong>va gratis</strong>.</p>
    </div>

    <figure class="hero-visual">
        <?php if ($heroImage !== ''): ?>
            <img class="hero-photo" src="<?= e(url($heroImage)) ?>" alt="Caldo de salchicha de <?= e($business) ?>" width="800" height="600" loading="eager">
        <?php else: ?>
            <span class="hero-photo hero-photo-empty">🍲</span>
        <?php endif; ?>
        <?php if ($hasLogo): ?>
            <img class="hero-logo-badge" src="<?= e(url('assets/logo.jpeg')) ?>" alt="Logo de <?= e($business) ?>" width="120" height="120">
        <?php endif; ?>
        <figcaption class="hero-price-card">
            <span>Plato para llevar</span>
            <strong><?= e($price) ?></strong>
            <small>+ envío <?= e($deliveryCost) ?></small>
        </figcaption>
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
        <p>Salchicha tradicional de calidad y sazón casera, como en casa.</p>
    </article>
    <article class="feature">
        <span>💛</span>
        <h3>Hecho con amor</h3>
        <p>Receta tradicional y el sabor de nuestra tierra, en cada plato.</p>
    </article>
</section>

<section class="section">
    <div class="section-head">
        <span class="eyebrow">🥘 Nuestro menú</span>
        <h2>Caldo de salchicha y cortes a la parrilla</h2>
        <p class="muted">Cada corte se sirve con papas, ensalada y chimichurri. Mira el menú completo con fotos y precios.</p>
    </div>

    <?php if ($preview): ?>
        <div class="menu-grid">
            <?php foreach ($preview as $entry): ?>
                <?php render_menu_card($entry['item'], $entry['caldos']); ?>
            <?php endforeach; ?>
        </div>
        <div class="btn-row" style="margin-top:20px">
            <a class="btn btn-dark btn-big" href="<?= e(url('menu.php')) ?>">Ver el menú completo →</a>
        </div>
    <?php else: ?>
        <div class="card">
            <h3>Estamos actualizando el menú</h3>
            <p class="muted">Escríbenos por WhatsApp y te contamos qué hay disponible hoy.</p>
            <?php if ($waLink !== ''): ?>
                <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= e($waLink) ?>">Consultar por WhatsApp</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>
</section>

<section class="loyalty-band">
    <div class="loyalty-copy">
        <span class="eyebrow">🎁 Mi tarjeta</span>
        <h2>Tu tarjeta de sellos digital</h2>
        <p>Cada caldo suma un sello. Al completar <strong><?= e((string)$required) ?></strong>, tu <strong><?= e($reward) ?></strong> es gratis. Sin papeles y siempre en tu celular.</p>
        <div class="btn-row">
            <a class="btn btn-dark" href="<?= e(url($customer ? 'card.php' : 'tarjeta.php?tab=registro')) ?>">
                <?= $customer ? 'Ver mi tarjeta' : 'Empezar a sumar sellos' ?>
            </a>
            <?php if (!$customer): ?>
                <a class="btn btn-ghost" href="<?= e(url('tarjeta.php?tab=login')) ?>">Ya tengo tarjeta</a>
            <?php endif; ?>
        </div>
    </div>
    <div class="loyalty-stamps" aria-hidden="true">
        <?php for ($i = 1; $i <= min($required, 10); $i++): ?>
            <span class="stamp <?= $i <= 4 ? 'on' : '' ?>"><?= $i <= 4 ? '🍲' : $i ?></span>
        <?php endfor; ?>
    </div>
</section>

<section class="section section-alt">
    <div class="split split-about">
        <?php if ($aboutImage !== ''): ?>
            <figure class="about-photo">
                <img src="<?= e(url($aboutImage)) ?>" alt="Paisaje de Guaranda, Ecuador" width="900" height="675" loading="lazy">
                <figcaption>Alpachaca · Guaranda · Bolívar</figcaption>
            </figure>
        <?php endif; ?>
        <div class="about-copy">
            <span class="eyebrow">🌿 Cabaña Delfina</span>
            <h2><?= e($aboutTitle) ?></h2>
            <p><?= e($aboutText) ?></p>
            <div class="btn-row">
                <a class="btn btn-primary" href="<?= e(url('cabana.php')) ?>">Conocer la cabaña</a>
                <a class="btn btn-ghost" href="<?= e(url('contacto.php')) ?>">Ubicación y contacto</a>
            </div>
        </div>
    </div>
</section>

<div class="final-cta">
    <div>
        <h2>¿Se te antojó un caldo calientito?</h2>
        <p>Haz tu pedido por WhatsApp y te lo llevamos hasta la puerta.</p>
    </div>
    <?php if ($waLink !== ''): ?>
        <a class="btn btn-primary btn-big" target="_blank" rel="noopener" href="<?= e($waLink) ?>">📲 Pedir por WhatsApp</a>
    <?php endif; ?>
</div>

<?php page_end(); ?>
