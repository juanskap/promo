<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';

$business     = setting('business_name', 'Cabaña Delfina');
$tagline      = setting('tagline', 'Tradición viva — calentito, nutritivo y delicioso');
$heroText     = setting('hero_text', 'Receta tradicional, ingredientes seleccionados y el sabor de nuestra tierra. Hecho con amor para ti.');
$price        = setting('price_caldo', '$3.50');
$address      = setting('address', 'Alpachaca, Guaranda — Bolívar');
$hours        = setting('hours', 'Sábados y domingos');
$delivery     = setting('delivery_info', 'Entregamos dentro de Guaranda. Haz tu pedido con anticipación por WhatsApp.');
$deliveryCost = setting('delivery_cost', '+$1.00');
$addressNote  = setting('address_note', 'Consulta la ubicación exacta y la disponibilidad por WhatsApp.');
$reservations = setting('reservations', 'Reserva con anticipación para eventos, reuniones y pedidos grandes.');
$notice       = setting('notice', 'Abrimos fines de semana: sábados y domingos');
$instagram    = trim(setting('instagram', ''));
$waNumber     = trim(setting('whatsapp_orders', ''));
$aboutTitle   = setting('about_title', 'Un rincón tranquilo en Alpachaca');
$aboutText    = setting('about_text', 'Cabaña Delfina es un espacio rodeado de naturaleza para descansar, compartir en familia y disfrutar de un buen caldo. Consulta disponibilidad y tarifas por WhatsApp.');
$aboutFeatures = array_values(array_filter(array_map(
    'trim',
    preg_split('/\r\n|\r|\n/', setting('about_features', "Espacio tranquilo rodeado de naturaleza\nIdeal para desconectarse y descansar\nReservas y disponibilidad por WhatsApp")) ?: []
)));
$customer     = current_customer();
$required     = max(1, (int)setting('stamps_required', '10'));
$reward       = setting('reward_text', 'caldo de salchicha gratis');
$hasLogo      = is_file(__DIR__ . '/assets/logo.jpeg');

$menu = menu_grouped();

$heroImage = '';
foreach (['assets/menu/caldo.jpg', 'assets/menu/costilla.jpg'] as $candidate) {
    if (is_file(__DIR__ . '/' . $candidate)) {
        $heroImage = $candidate;
        break;
    }
}
$aboutImage = is_file(__DIR__ . '/assets/menu/guaranda.jpg') ? 'assets/menu/guaranda.jpg' : '';

$waLink = $waNumber !== ''
    ? wa_link($waNumber, '¡Hola ' . $business . '! Quiero hacer un pedido 🍲')
    : '';

/** Convierte "$3.50", "+$1,00" o "4" a número. */
function landing_amount(string $text): ?float
{
    if (!preg_match('/\d+(?:[.,]\d{1,2})?/', $text, $match)) {
        return null;
    }
    return (float)str_replace(',', '.', $match[0]);
}

function landing_money(float $amount): string
{
    return setting('currency', '$') . number_format($amount, 2, '.', '');
}

$priceAmount  = landing_amount($price);
$deliverAmount = landing_amount($deliveryCost);
$oneWithDelivery = ($priceAmount !== null && $deliverAmount !== null) ? landing_money($priceAmount + $deliverAmount) : '';
$twoBase         = $priceAmount !== null ? landing_money($priceAmount * 2) : '';
$twoWithDelivery = ($priceAmount !== null && $deliverAmount !== null) ? landing_money($priceAmount * 2 + $deliverAmount) : '';

$mapLink = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address . ', Ecuador');

page_start('Inicio', 'home');
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
                <span><strong>Cortes de chancho a la parrilla</strong><small>Costilla, pierna, espaldilla, chuleta y panza</small></span>
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
            <a class="btn btn-ghost" href="#menu">Ver nuestro menú</a>
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

<section class="section" id="menu">
    <div class="section-head">
        <span class="eyebrow">🥘 Nuestro menú</span>
        <h2>Caldo de salchicha y cortes a la parrilla</h2>
        <p class="muted">Preparado al momento. Pide por WhatsApp y te lo enviamos calientito dentro de Guaranda.</p>
    </div>

    <?php if ($menu): ?>
        <?php foreach ($menu as $category => $items): ?>
            <?php $isCaldos = mb_stripos($category, 'caldo') !== false; ?>
            <div class="menu-group">
                <h3 class="menu-group-title">
                    <span><?= $isCaldos ? '🍲' : '🔥' ?></span>
                    <?= e($category) ?>
                </h3>
                <div class="menu-grid">
                    <?php foreach ($items as $item): ?>
                        <?php
                        $itemName  = (string)$item['name'];
                        $itemPrice = (string)$item['price'];
                        $itemImage = trim((string)($item['image'] ?? ''));
                        $imageOk   = $itemImage !== '' && is_file(__DIR__ . '/' . $itemImage);
                        $base      = landing_amount($itemPrice);
                        $withShip  = ($base !== null && $deliverAmount !== null) ? landing_money($base + $deliverAmount) : '';
                        $itemWa    = $waNumber !== ''
                            ? wa_link($waNumber, '¡Hola ' . $business . '! Quiero pedir: ' . $itemName . ' (' . $itemPrice . ') 🍲')
                            : '';
                        ?>
                        <article class="menu-card">
                            <div class="menu-photo">
                                <?php if ($imageOk): ?>
                                    <img src="<?= e(url($itemImage)) ?>" alt="<?= e($itemName) ?>" width="800" height="600" loading="lazy">
                                <?php else: ?>
                                    <span class="menu-photo-empty"><?= $isCaldos ? '🍲' : '🔥' ?></span>
                                <?php endif; ?>
                                <?php if ($isCaldos): ?><span class="menu-flag">El favorito</span><?php endif; ?>
                            </div>
                            <div class="menu-body">
                                <div class="menu-title">
                                    <h4><?= e($itemName) ?></h4>
                                    <span class="menu-price<?= $base === null ? ' menu-price-text' : '' ?>"><?= e($itemPrice) ?></span>
                                </div>
                                <?php if (trim((string)$item['description']) !== ''): ?>
                                    <p class="menu-desc"><?= e((string)$item['description']) ?></p>
                                <?php endif; ?>
                                <div class="menu-delivery">
                                    <span>🛵 Envío <?= e($deliveryCost) ?> por pedido</span>
                                    <?php if ($withShip !== ''): ?>
                                        <strong>Con envío: <?= e($withShip) ?></strong>
                                    <?php endif; ?>
                                </div>
                                <?php if ($itemWa !== ''): ?>
                                    <a class="btn btn-primary btn-block" target="_blank" rel="noopener" href="<?= e($itemWa) ?>">Pedir este plato</a>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    <?php else: ?>
        <div class="card">
            <h3>Estamos actualizando el menú</h3>
            <p class="muted">Escríbenos por WhatsApp y te contamos qué hay disponible hoy.</p>
            <?php if ($waLink !== ''): ?>
                <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= e($waLink) ?>">Consultar por WhatsApp</a>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <p class="menu-note">📌 <strong>Importante:</strong> el envío es <?= e($deliveryCost) ?> <strong>por pedido</strong>, no por cada plato. Si pides varios productos, pagas el envío una sola vez.</p>
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

<section class="section" id="envios">
    <div class="section-head">
        <span class="eyebrow">🚚 Envíos a domicilio</span>
        <h2>Tu pedido llega calientito</h2>
        <p class="muted"><?= e($delivery) ?></p>
    </div>

    <div class="split">
        <div class="delivery-info">
            <ul class="check-list">
                <li><span>✅</span> Disponible dentro de Guaranda</li>
                <li><span>💰</span> Costo de envío: <strong><?= e($deliveryCost) ?></strong> por pedido</li>
                <li><span>📲</span> Haz tu pedido con anticipación por WhatsApp</li>
                <li><span>⏱️</span> Llega calentito y listo para disfrutar</li>
            </ul>
            <div class="btn-row">
                <?php if ($waLink !== ''): ?>
                    <a class="btn btn-primary" target="_blank" rel="noopener"
                       href="<?= e(wa_link($waNumber, '¡Hola ' . $business . '! Quiero un envío a domicilio en Guaranda 🛵')) ?>">
                        Pedir a domicilio
                    </a>
                <?php endif; ?>
                <a class="btn btn-ghost" href="#menu">Ver precios del menú</a>
            </div>
        </div>

        <aside class="delivery-card">
            <h3>Ejemplos rápidos</h3>
            <ul class="example-list">
                <li>
                    <span>1 caldo</span>
                    <span><?= e($price) ?> + <?= e($deliveryCost) ?> = <strong><?= e($oneWithDelivery !== '' ? $oneWithDelivery : '$4.50') ?></strong></span>
                </li>
                <li>
                    <span>2 caldos</span>
                    <span><?= e($twoBase !== '' ? $twoBase : '$7.00') ?> + <?= e($deliveryCost) ?> = <strong><?= e($twoWithDelivery !== '' ? $twoWithDelivery : '$8.00') ?></strong></span>
                </li>
                <li>
                    <span>1 caldo + 1 corte</span>
                    <span>suma de precios + <?= e($deliveryCost) ?> <strong>(un solo envío)</strong></span>
                </li>
            </ul>
            <p class="muted small">Los cortes a la parrilla se consultan según el precio del día. Escríbenos y te confirmamos.</p>
        </aside>
    </div>
</section>

<section class="section section-alt" id="cabana">
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
            <?php if ($aboutFeatures): ?>
                <ul class="check-list">
                    <?php foreach ($aboutFeatures as $feature): ?>
                        <li><span>✅</span> <?= e($feature) ?></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
            <div class="btn-row">
                <?php if ($waLink !== ''): ?>
                    <a class="btn btn-primary" target="_blank" rel="noopener"
                       href="<?= e(wa_link($waNumber, '¡Hola ' . $business . '! Quiero consultar disponibilidad y tarifas de la cabaña 🌿')) ?>">
                        Consultar disponibilidad
                    </a>
                <?php endif; ?>
                <a class="btn btn-ghost" href="#contacto">Ubicación y contacto</a>
            </div>
            <?php if ($addressNote !== ''): ?>
                <p class="muted small"><?= e($addressNote) ?></p>
            <?php endif; ?>
        </div>
    </div>
</section>

<section class="section" id="contacto">
    <div class="section-head">
        <span class="eyebrow">📍 Ubicación y contacto</span>
        <h2>Visítanos en Alpachaca, Guaranda</h2>
        <p class="muted">Estamos a las afueras de Guaranda, en un espacio tranquilo rodeado de naturaleza.</p>
    </div>

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
        <?php if ($waLink !== ''): ?>
            <a class="btn btn-primary" target="_blank" rel="noopener"
               href="<?= e(wa_link($waNumber, '¡Hola ' . $business . '! Quiero reservar para un evento 🎉')) ?>">
                Reservar para un evento
            </a>
        <?php endif; ?>
        <a class="btn btn-dark" target="_blank" rel="noopener" href="<?= e($mapLink) ?>">Cómo llegar 📍</a>
    </div>
</section>

<section class="section faq" id="preguntas">
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
        <h2>¿Se te antojó un caldo calientito?</h2>
        <p>Haz tu pedido por WhatsApp y te lo llevamos hasta la puerta.</p>
    </div>
    <?php if ($waLink !== ''): ?>
        <a class="btn btn-primary btn-big" target="_blank" rel="noopener" href="<?= e($waLink) ?>">📲 Pedir por WhatsApp</a>
    <?php endif; ?>
</div>

<?php page_end(); ?>
