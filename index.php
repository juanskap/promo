<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';

$business  = setting('business_name', 'Cabaña Delfina');
$tagline   = setting('tagline', 'Calentito, nutritivo y hecho con amor para ti');
$heroText  = setting('hero_text', 'Caldo de salchicha tradicional, casero y con ingredientes seleccionados. Ideal para días fríos, almuerzos y para compartir en familia.');
$price     = setting('price_caldo', '$3.50');
$address   = setting('address', 'Guaranda, Bolívar');
$hours     = setting('hours', 'Sábados y domingos');
$delivery  = setting('delivery_info', 'Llevamos tu caldo calientito a domicilio en Guaranda.');
$deliveryCost = setting('delivery_cost', '+$1,50');
$addressNote  = setting('address_note', 'Estamos arreglando el local físico. Confirma el punto de entrega por WhatsApp.');
$reservations = setting('reservations', 'Entre semana puedes reservar para eventos: cumpleaños, reuniones y pedidos grandes.');
$notice    = setting('notice', 'Abrimos sábados y domingos · Entre semana, reservas para eventos');
$instagram = trim(setting('instagram', ''));
$waNumber  = trim(setting('whatsapp_orders', ''));
$customer  = current_customer();
$required  = max(1, (int)setting('stamps_required', '10'));
$reward    = setting('reward_text', 'caldo de salchicha gratis');
$hasLogo   = is_file(__DIR__ . '/assets/logo.jpeg');

$waLink = $waNumber !== ''
    ? wa_link($waNumber, '¡Hola ' . $business . '! Quiero hacer un pedido 🍲')
    : '';

page_start('Inicio', 'public');
?>
<?php if ($notice !== ''): ?>
    <div class="notice-strip">📍 <?= e($notice) ?></div>
<?php endif; ?>

<section class="hero hero-home">
    <div class="hero-copy">
        <?php if ($hasLogo): ?>
            <img class="hero-logo" src="<?= e(url('assets/logo.jpeg')) ?>" alt="Logo de <?= e($business) ?>" width="220" height="184">
        <?php endif; ?>
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
        <div class="menu-line">
            <span>🛵 A domicilio</span>
            <strong class="menu-extra"><?= e($deliveryCost) ?></strong>
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
            <?php if ($addressNote !== ''): ?>
                <p class="muted small"><?= e($addressNote) ?></p>
            <?php endif; ?>
        </div>
        <div>
            <span class="info-label">Horario</span>
            <p><?= e($hours) ?></p>
        </div>
        <div>
            <span class="info-label">Domicilios</span>
            <p><?= e($delivery) ?> <strong><?= e($deliveryCost) ?></strong></p>
        </div>
        <div>
            <span class="info-label">Pedidos y reservas</span>
            <?php if ($waLink !== ''): ?>
                <p><a target="_blank" rel="noopener" href="<?= e($waLink) ?>">Escríbenos por WhatsApp</a></p>
            <?php else: ?>
                <p class="muted">Próximamente</p>
            <?php endif; ?>
            <?php if ($reservations !== ''): ?>
                <p class="muted small"><?= e($reservations) ?></p>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($instagram !== ''): ?>
        <p class="muted">Síguenos:
            <a target="_blank" rel="noopener" href="https://instagram.com/<?= e(ltrim($instagram, '@')) ?>">@<?= e(ltrim($instagram, '@')) ?></a>
        </p>
    <?php endif; ?>

    <?php if ($waLink !== ''): ?>
        <div class="btn-row">
            <a class="btn btn-primary" target="_blank" rel="noopener"
               href="<?= e(wa_link($waNumber, '¡Hola ' . $business . '! Quiero reservar para un evento 🎉')) ?>">
                Reservar para un evento
            </a>
            <a class="btn btn-ghost" target="_blank" rel="noopener" href="<?= e($waLink) ?>">
                Pedir a domicilio
            </a>
        </div>
    <?php endif; ?>
</section>

<?php page_end(); ?>
