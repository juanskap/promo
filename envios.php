<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/site.php';

$deliveryLink = site_wa('¡Hola ' . $business . '! Quiero un envío a domicilio en Guaranda 🛵');

page_start('Envíos a Domicilio', 'home', 'envios');
?>

<section class="page-head">
    <span class="eyebrow">🚚 Envíos a domicilio</span>
    <h1>Tu pedido llega calientito</h1>
    <p><?= e($delivery) ?></p>
    <div class="btn-row">
        <?php if ($deliveryLink !== ''): ?>
            <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= e($deliveryLink) ?>">📲 Pedir a domicilio</a>
        <?php endif; ?>
        <a class="btn btn-ghost" href="<?= e(url('menu.php')) ?>">Ver precios del menú</a>
    </div>
</section>

<section class="section">
    <div class="split">
        <div class="delivery-info">
            <ul class="check-list">
                <li><span>✅</span> Disponible dentro de Guaranda</li>
                <li><span>💰</span> Costo de envío: <strong><?= e($deliveryCost) ?></strong> por pedido</li>
                <li><span>📲</span> Haz tu pedido con anticipación por WhatsApp</li>
                <li><span>⏱️</span> Llega calentito y listo para disfrutar</li>
            </ul>
            <p class="muted">El envío se cobra <strong>una sola vez por pedido</strong>, aunque lleves varios platos. Así puedes combinar caldo y cortes sin pagar más de envío.</p>
            <div class="btn-row">
                <?php if ($waLink !== ''): ?>
                    <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= e($waLink) ?>">Hacer mi pedido</a>
                <?php endif; ?>
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

<section class="section">
    <div class="section-head">
        <span class="eyebrow">📲 Cómo pedir</span>
        <h2>En tres pasos</h2>
    </div>
    <div class="feature-grid">
        <article class="feature">
            <span>1️⃣</span>
            <h3>Escríbenos</h3>
            <p>Mándanos un WhatsApp con lo que quieres pedir y tu dirección dentro de Guaranda.</p>
        </article>
        <article class="feature">
            <span>2️⃣</span>
            <h3>Confirmamos</h3>
            <p>Te confirmamos disponibilidad, precio del día y el valor del envío.</p>
        </article>
        <article class="feature">
            <span>3️⃣</span>
            <h3>Recibe</h3>
            <p>Preparamos todo al momento y te lo llevamos calientito hasta la puerta.</p>
        </article>
    </div>
</section>

<div class="final-cta">
    <div>
        <h2>¿Pedimos a domicilio?</h2>
        <p>El envío es <?= e($deliveryCost) ?> por pedido. Coordinemos por WhatsApp.</p>
    </div>
    <?php if ($deliveryLink !== ''): ?>
        <a class="btn btn-primary btn-big" target="_blank" rel="noopener" href="<?= e($deliveryLink) ?>">📲 Pedir ahora</a>
    <?php endif; ?>
</div>

<?php page_end(); ?>
