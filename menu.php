<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';
require __DIR__ . '/includes/site.php';

page_start('Nuestro Menú', 'home', 'menu');
?>

<section class="page-head">
    <span class="eyebrow">🥘 Nuestro menú</span>
    <h1>Caldo de salchicha y cortes a la parrilla</h1>
    <p>Preparado al momento, con sazón casera. Cada corte se sirve con <strong>papas, ensalada y chimichurri</strong>. Pide por WhatsApp y te lo enviamos calientito dentro de Guaranda.</p>
    <div class="btn-row">
        <?php if ($waLink !== ''): ?>
            <a class="btn btn-primary" target="_blank" rel="noopener" href="<?= e($waLink) ?>">📲 Pedir por WhatsApp</a>
        <?php endif; ?>
        <a class="btn btn-ghost" href="<?= e(url('envios.php')) ?>">Ver envíos a domicilio</a>
    </div>
</section>

<section class="section">
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
                        <?php render_menu_card($item, $isCaldos); ?>
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

<div class="final-cta">
    <div>
        <h2>¿Qué se te antoja hoy?</h2>
        <p>Escríbenos por WhatsApp y te confirmamos disponibilidad y precios del día.</p>
    </div>
    <?php if ($waLink !== ''): ?>
        <a class="btn btn-primary btn-big" target="_blank" rel="noopener" href="<?= e($waLink) ?>">📲 Hacer mi pedido</a>
    <?php endif; ?>
</div>

<?php page_end(); ?>
