<?php
declare(strict_types=1);

/**
 * Datos compartidos por las páginas públicas del sitio.
 * Se incluye después de includes/app.php.
 */

/** Convierte "$3.50", "+$1,00" o "4" a número. */
function site_amount(string $text): ?float
{
    if (!preg_match('/\d+(?:[.,]\d{1,2})?/', $text, $match)) {
        return null;
    }
    return (float)str_replace(',', '.', $match[0]);
}

function site_money(float $amount): string
{
    return setting('currency', '$') . number_format($amount, 2, '.', '');
}

function site_wa(string $message): string
{
    $number = trim(setting('whatsapp_orders', ''));
    return $number !== '' ? wa_link($number, $message) : '';
}

$business      = setting('business_name', 'Cabaña Delfina');
$tagline       = setting('tagline', 'Tradición viva — calentito, nutritivo y delicioso');
$heroText      = setting('hero_text', 'Receta tradicional, ingredientes seleccionados y el sabor de nuestra tierra. Hecho con amor para ti.');
$price         = setting('price_caldo', '$3.50');
$address       = setting('address', 'Alpachaca, Guaranda — Bolívar');
$hours         = setting('hours', 'Sábados y domingos');
$delivery      = setting('delivery_info', 'Entregamos dentro de Guaranda. Haz tu pedido con anticipación por WhatsApp.');
$deliveryCost  = setting('delivery_cost', '+$1.00');
$addressNote   = setting('address_note', 'Consulta la ubicación exacta y la disponibilidad por WhatsApp.');
$reservations  = setting('reservations', 'Reserva con anticipación para eventos, reuniones y pedidos grandes.');
$notice        = setting('notice', 'Abrimos fines de semana: sábados y domingos');
$instagram     = trim(setting('instagram', ''));
$waNumber      = trim(setting('whatsapp_orders', ''));
$aboutTitle    = setting('about_title', 'Un rincón tranquilo en Alpachaca');
$aboutText     = setting('about_text', 'Cabaña Delfina es un espacio rodeado de naturaleza para descansar, compartir en familia y disfrutar de un buen caldo. Consulta disponibilidad y tarifas por WhatsApp.');
$aboutFeatures = array_values(array_filter(array_map(
    'trim',
    preg_split('/\r\n|\r|\n/', setting('about_features', "Espacio tranquilo rodeado de naturaleza\nIdeal para desconectarse y descansar\nReservas y disponibilidad por WhatsApp")) ?: []
)));
$customer      = current_customer();
$required      = max(1, (int)setting('stamps_required', '10'));
$reward        = setting('reward_text', 'caldo de salchicha gratis');
$hasLogo       = is_file(__DIR__ . '/../assets/logo.jpeg');
$menu          = menu_grouped();

$heroImage = '';
foreach (['assets/menu/caldo.jpg', 'assets/menu/costilla.jpg'] as $candidate) {
    if (is_file(__DIR__ . '/../' . $candidate)) {
        $heroImage = $candidate;
        break;
    }
}
$aboutImage = is_file(__DIR__ . '/../assets/menu/guaranda.jpg') ? 'assets/menu/guaranda.jpg' : '';

$waLink = site_wa('¡Hola ' . $business . '! Quiero hacer un pedido 🍲');
$mapLink = 'https://www.google.com/maps/search/?api=1&query=' . rawurlencode($address . ', Ecuador');

$priceAmount     = site_amount($price);
$deliverAmount   = site_amount($deliveryCost);
$oneWithDelivery = ($priceAmount !== null && $deliverAmount !== null) ? site_money($priceAmount + $deliverAmount) : '';
$twoBase         = $priceAmount !== null ? site_money($priceAmount * 2) : '';
$twoWithDelivery = ($priceAmount !== null && $deliverAmount !== null) ? site_money($priceAmount * 2 + $deliverAmount) : '';

/**
 * Tarjeta de un plato, usada en la portada y en la página del menú.
 */
function render_menu_card(array $item, bool $isCaldos = false): void
{
    $business     = setting('business_name', 'Cabaña Delfina');
    $deliveryCost = setting('delivery_cost', '+$1.00');
    $waNumber     = trim(setting('whatsapp_orders', ''));
    $deliver      = site_amount($deliveryCost);

    $itemName  = (string)$item['name'];
    $itemPrice = (string)$item['price'];
    $itemImage = trim((string)($item['image'] ?? ''));
    $imageOk   = $itemImage !== '' && is_file(__DIR__ . '/../' . $itemImage);
    $base      = site_amount($itemPrice);
    $withShip  = ($base !== null && $deliver !== null) ? site_money($base + $deliver) : '';
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
    <?php
}
