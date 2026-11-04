<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';

if (current_customer()) {
    redirect('card.php');
}

$tab    = ($_GET['tab'] ?? 'login') === 'registro' ? 'registro' : 'login';
$errors = [];
$loginPhone = '';
$regName = '';
$regPhone = '';
$referral  = trim((string)($_GET['ref'] ?? ''));
$business  = setting('business_name', 'Mi Cabaña');
$required  = max(1, (int)setting('stamps_required', '10'));
$reward    = setting('reward_text', 'caldo de salchicha gratis');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'login') {
        $tab = 'login';
        $loginPhone = trim((string)($_POST['phone'] ?? ''));
        $pin        = (string)($_POST['pin'] ?? '');
        $customer   = find_customer($loginPhone);

        if (!$customer || !verify_customer_pin($customer, $pin)) {
            usleep(400000);
            $errors[] = 'Celular o PIN incorrecto.';
        } else {
            login_customer((int)$customer['id']);
            flash('ok', '¡Hola de nuevo, ' . $customer['name'] . '!');
            redirect('card.php');
        }
    } elseif ($action === 'register') {
        $tab      = 'registro';
        $regName  = trim((string)($_POST['name'] ?? ''));
        $regPhone = trim((string)($_POST['phone'] ?? ''));
        $referral = trim((string)($_POST['referral'] ?? ''));
        $result   = register_customer(
            $regName,
            $regPhone,
            (string)($_POST['pin'] ?? ''),
            $_POST['birthday'] ?? null,
            $referral,
            isset($_POST['opt_in']),
            (string)($_POST['document'] ?? '')
        );

        if ($result['ok']) {
            login_customer((int)$result['id']);
            flash('ok', '¡Bienvenido! Ya tienes tu tarjeta digital.');
            redirect('card.php');
        }
        $errors[] = $result['error'];
    }
}

page_start('Tarjeta de fidelidad', 'public');
?>
<section class="hero">
    <span class="pill">Programa de fidelidad</span>
    <h1>Compra <?= e((string)$required) ?> y el siguiente<br><em>va gratis</em>.</h1>
    <p class="lead">
        Cada visita suma un sello en tu tarjeta digital. Al completar <?= e((string)$required) ?>
        te llevas <strong><?= e($reward) ?></strong>. Sin tarjetas de cartón y sin instalar nada.
    </p>

    <div class="stamp-preview" aria-hidden="true">
        <?php for ($i = 1; $i <= $required; $i++): ?>
            <span class="stamp <?= $i <= 3 ? 'on' : '' ?>"><?= $i <= 3 ? '🍲' : $i ?></span>
        <?php endfor; ?>
    </div>
</section>

<div class="grid-2">
    <section class="card">
        <h2><?= $tab === 'login' ? 'Ya soy cliente' : 'Crear mi tarjeta' ?></h2>

        <?php if ($tab === 'login'): ?>
            <form method="post" class="form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="login">
                <label class="field">
                    <span>Celular</span>
                    <input class="input" type="tel" name="phone" value="<?= e($loginPhone) ?>" placeholder="300 123 4567" required autocomplete="tel">
                </label>
                <label class="field">
                    <span>PIN</span>
                    <input class="input" type="password" name="pin" inputmode="numeric" pattern="\d{4,6}" maxlength="6" placeholder="••••" required autocomplete="current-password">
                </label>
                <button class="btn btn-primary btn-block" type="submit">Entrar a mi tarjeta</button>
            </form>
            <p class="muted center">¿Primera vez? <a href="<?= e(url('tarjeta.php?tab=registro' . ($referral !== '' ? '&ref=' . urlencode($referral) : ''))) ?>">Crea tu tarjeta gratis</a></p>
        <?php else: ?>
            <form method="post" class="form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="register">
                <label class="field">
                    <span>Nombre</span>
                    <input class="input" type="text" name="name" value="<?= e($regName) ?>" placeholder="Tu nombre" required maxlength="100">
                </label>
                <label class="field">
                    <span>Celular (WhatsApp)</span>
                    <input class="input" type="tel" name="phone" value="<?= e($regPhone) ?>" placeholder="300 123 4567" required autocomplete="tel">
                </label>
                <label class="field">
                    <span>Cédula <small>(opcional)</small></span>
                    <input class="input" type="text" name="document" inputmode="numeric" placeholder="1234567890" maxlength="15">
                </label>
                <label class="field">
                    <span>PIN para entrar (4 a 6 números)</span>
                    <input class="input" type="password" name="pin" inputmode="numeric" pattern="\d{4,6}" maxlength="6" placeholder="••••" required autocomplete="new-password">
                </label>
                <label class="field">
                    <span>Cumpleaños <small>(opcional)</small></span>
                    <input class="input" type="date" name="birthday">
                </label>
                <label class="field">
                    <span>Código de un amigo <small>(opcional)</small></span>
                    <input class="input" type="text" name="referral" value="<?= e($referral) ?>" placeholder="Ej. ABCD1234" maxlength="12" style="text-transform:uppercase">
                </label>
                <label class="check">
                    <input type="checkbox" name="opt_in" checked>
                    <span>Acepto recibir mi tarjeta y promociones por WhatsApp.</span>
                </label>
                <button class="btn btn-primary btn-block" type="submit">Crear mi tarjeta</button>
            </form>
            <p class="muted center">¿Ya tienes tarjeta? <a href="<?= e(url('tarjeta.php?tab=login')) ?>">Ingresa con tu PIN</a></p>
        <?php endif; ?>

        <?php foreach ($errors as $error): ?>
            <div class="alert alert-error"><?= e($error) ?></div>
        <?php endforeach; ?>
    </section>

    <section class="card card-soft">
        <h2>Cómo funciona</h2>
        <ol class="steps">
            <li><strong>Regístrate</strong> con tu celular y crea un PIN.</li>
            <li><strong>Muestra tu QR</strong> cada vez que compres; el mostrador suma tu sello.</li>
            <li><strong>Completa <?= e((string)$required) ?></strong> y recibe tu código para el <?= e($reward) ?>.</li>
            <li><strong>Entérate por WhatsApp</strong> de tus sellos, premios y promociones.</li>
        </ol>
        <p class="muted">Tus datos solo se usan para tu tarjeta y las promociones de <?= e($business) ?>.</p>
    </section>
</div>
<?php page_end(); ?>
