<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';

$customer = require_customer();
$customer = find_customer_by_id((int)$customer['id']) ?? $customer;

$required = max(1, (int)setting('stamps_required', '10'));
$stamps   = min((int)$customer['stamps'], $required);
$missing  = max(0, $required - $stamps);
$reward   = setting('reward_text', 'caldo de salchicha gratis');
$active   = customer_coupons((int)$customer['id'], 'active');
$used     = customer_coupons((int)$customer['id'], 'used');

$cardUrl = url('staff/index.php?code=' . urlencode((string)$customer['code']));
$qrSrc   = 'https://api.qrserver.com/v1/create-qr-code/?size=240x240&margin=8&data=' . rawurlencode($cardUrl);
$shareText = '¡Te invito a ' . setting('business_name', 'Mi Cabaña') . '! 🍲 Regístrate con mi código '
    . $customer['code'] . ' y empieza a llenar tu tarjeta: '
    . url('index.php?tab=registro&ref=' . urlencode((string)$customer['code']));

page_start('Mi tarjeta', 'public');
?>
<section class="card card-hero">
    <div class="row-between">
        <div>
            <span class="pill">Mi tarjeta</span>
            <h1>Hola, <?= e(explode(' ', (string)$customer['name'])[0]) ?> 👋</h1>
            <p class="muted">Llevas <strong><?= $stamps ?> de <?= $required ?></strong> sellos.</p>
        </div>
        <div class="qr-box">
            <img src="<?= e($qrSrc) ?>" alt="Código QR de mi tarjeta" width="120" height="120" loading="lazy">
            <span class="code-tag"><?= e((string)$customer['code']) ?></span>
        </div>
    </div>

    <div class="progress"><span style="width: <?= (int)round($stamps / $required * 100) ?>%"></span></div>

    <div class="stamp-grid stamp-grid-lg" aria-label="<?= $stamps ?> de <?= $required ?> sellos">
        <?php for ($i = 1; $i <= $required; $i++): ?>
            <span class="stamp <?= $i <= $stamps ? 'on' : '' ?>"><?= $i <= $stamps ? '🍲' : $i ?></span>
        <?php endfor; ?>
    </div>

    <p class="center muted">
        <?php if ($missing > 0): ?>
            Te faltan <strong><?= $missing ?></strong> para tu <?= e($reward) ?>.
        <?php else: ?>
            ¡Completa la tarjeta y reclama tu <?= e($reward) ?>!
        <?php endif; ?>
    </p>
</section>

<?php if ($active): ?>
    <section class="card">
        <h2>🎁 Tus premios disponibles</h2>
        <div class="coupon-list">
            <?php foreach ($active as $coupon): ?>
                <div class="coupon">
                    <div>
                        <strong><?= e((string)$coupon['description']) ?></strong>
                        <p class="muted">Vence el <?= e(fmt_date((string)$coupon['expires_at'])) ?></p>
                    </div>
                    <div class="coupon-code"><?= e((string)$coupon['code']) ?></div>
                </div>
            <?php endforeach; ?>
        </div>
        <p class="muted">Muestra este código en el mostrador para canjearlo.</p>
    </section>
<?php endif; ?>

<div class="grid-2">
    <section class="card">
        <h2>Invita a un amigo</h2>
        <p>Comparte tu código <strong><?= e((string)$customer['code']) ?></strong>. Cuando tu amigo se registre, <strong>ganas 1 sello</strong>.</p>
        <div class="btn-row">
            <a class="btn btn-primary" target="_blank" rel="noopener" href="https://wa.me/?text=<?= rawurlencode($shareText) ?>">Compartir por WhatsApp</a>
            <button class="btn btn-ghost" type="button" data-copy="<?= e($shareText) ?>">Copiar invitación</button>
        </div>
    </section>

    <section class="card">
        <h2>Mi historial</h2>
        <p class="stat-inline"><strong><?= (int)$customer['total_stamps'] ?></strong> sellos ganados · <strong><?= (int)$customer['rewards_earned'] ?></strong> premios</p>
        <?php if ($used): ?>
            <ul class="plain-list">
                <?php foreach (array_slice($used, 0, 5) as $coupon): ?>
                    <li>
                        <span><?= e((string)$coupon['description']) ?></span>
                        <small class="muted"><?= e(fmt_date((string)$coupon['used_at'])) ?></small>
                    </li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p class="muted">Todavía no has canjeado premios.</p>
        <?php endif; ?>
    </section>
</div>

<p class="install-hint muted center">💡 Abre el menú del navegador y elige <strong>“Agregar a pantalla de inicio”</strong> para tener tu tarjeta siempre a mano.</p>
<?php page_end(); ?>
