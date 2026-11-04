<?php
declare(strict_types=1);
require __DIR__ . '/../includes/app.php';

$staff = current_staff();
$pinError = '';

/* ------------------------------------------------------------------ *
 *  Login del personal
 * ------------------------------------------------------------------ */
if (!$staff) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'login') {
        csrf_verify();
        $staffId = (int)($_POST['staff_id'] ?? 0);
        $pin     = (string)($_POST['pin'] ?? '');
        $row     = q('SELECT * FROM staff WHERE id = ? AND active = 1', [$staffId])->fetch();

        if (!$row || !password_verify($pin, (string)$row['pin_hash'])) {
            usleep(400000);
            $pinError = 'PIN incorrecto.';
        } else {
            login_staff($row);
            redirect('staff/index.php' . (isset($_GET['code']) ? '?code=' . urlencode((string)$_GET['code']) : ''));
        }
    }

    $team = q('SELECT id, name, role FROM staff WHERE active = 1 ORDER BY role DESC, name')->fetchAll();
    page_start('Mostrador', 'staff');
    ?>
    <section class="card card-narrow">
        <h1>Mostrador</h1>
        <p class="muted">Ingresa con tu PIN para sellar tarjetas y canjear premios.</p>
        <?php if ($pinError): ?><div class="alert alert-error"><?= e($pinError) ?></div><?php endif; ?>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="login">
            <label class="field">
                <span>¿Quién eres?</span>
                <select class="input" name="staff_id" required>
                    <?php foreach ($team as $member): ?>
                        <option value="<?= (int)$member['id'] ?>">
                            <?= e((string)$member['name']) ?><?= $member['role'] === 'admin' ? ' (admin)' : '' ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </label>
            <label class="field">
                <span>PIN</span>
                <input class="input" type="password" name="pin" inputmode="numeric" pattern="\d{4,6}" maxlength="6" placeholder="••••" required autofocus>
            </label>
            <button class="btn btn-primary btn-block" type="submit">Entrar</button>
        </form>
    </section>
    <?php
    page_end();
    exit;
}

/* ------------------------------------------------------------------ *
 *  Acciones del panel
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'register') {
        $phoneRaw = trim((string)($_POST['phone'] ?? ''));
        $pin      = trim((string)($_POST['pin'] ?? ''));
        if ($pin === '') {
            $pin = substr(normalize_phone($phoneRaw), -4);
        }
        if (!preg_match('/^\d{4,6}$/', $pin)) {
            $pin = '1234';
        }

        $result = register_customer(
            (string)($_POST['name'] ?? ''),
            $phoneRaw,
            $pin,
            $_POST['birthday'] ?? null,
            '',
            true,
            (string)($_POST['document'] ?? '')
        );

        if ($result['ok']) {
            flash('ok', 'Cliente registrado. PIN de acceso: ' . $pin . '. Ya quedó en la lista para avisarle por WhatsApp.');
            redirect('staff/index.php?q=' . urlencode(normalize_phone($phoneRaw)));
        }

        flash('error', $result['error']);
        redirect('staff/index.php');
    }

    if ($action === 'stamp') {
        $customerId = (int)($_POST['customer_id'] ?? 0);
        $amount     = (float)str_replace([',', ' '], ['', ''], (string)($_POST['amount'] ?? '0'));
        $note       = trim((string)($_POST['note'] ?? ''));
        $target     = find_customer_by_id($customerId);

        if ($target) {
            $result = add_stamp($customerId, (int)$staff['id'], max(0, $amount), $note);
            $message = 'Sello agregado a ' . $target['name'] . '. Lleva ' . $result['stamps'] . '/' . $result['required'] . '.';
            if ($result['rewards']) {
                $codes = array_map(static fn(array $r): string => $r['code'], $result['rewards']);
                $message .= ' 🎁 ¡Ganó ' . count($codes) . ' premio(s)! Código: ' . implode(', ', $codes) . '.';
            }
            flash('ok', $message);
        } else {
            flash('error', 'No se encontró el cliente.');
        }
        redirect('staff/index.php?q=' . urlencode($target ? (string)$target['phone'] : ''));
    }

    if ($action === 'redeem') {
        $result = redeem_coupon((string)($_POST['coupon_code'] ?? ''), (int)$staff['id']);
        if ($result['ok']) {
            $owner = find_customer_by_id((int)$result['coupon']['customer_id']);
            flash('ok', 'Cupón ' . $result['coupon']['code'] . ' canjeado a ' . ($owner['name'] ?? 'cliente') . '.');
        } else {
            flash('error', $result['error']);
        }
        redirect('staff/index.php');
    }

    if ($action === 'notif_sent') {
        $id = (int)($_POST['notification_id'] ?? 0);
        q("UPDATE notifications SET status = 'sent', sent_at = ? WHERE id = ?", [now(), $id]);
        flash('ok', 'Mensaje marcado como enviado.');
        redirect('staff/index.php?q=' . urlencode((string)($_POST['back_q'] ?? '')));
    }
}

/* ------------------------------------------------------------------ *
 *  Búsqueda de cliente
 * ------------------------------------------------------------------ */
$search   = trim((string)($_GET['q'] ?? $_GET['code'] ?? ''));
$customer = $search !== '' ? find_customer($search) : null;

$pendingNotifs = [];
if ($customer) {
    $pendingNotifs = q(
        "SELECT * FROM notifications WHERE customer_id = ? AND status = 'queued' ORDER BY created_at DESC LIMIT 3",
        [(int)$customer['id']]
    )->fetchAll();
}

$required = max(1, (int)setting('stamps_required', '10'));
$reward   = setting('reward_text', 'caldo de salchicha gratis');
$todayStart = date('Y-m-d 00:00:00');
$today = q(
    'SELECT COUNT(*) AS ventas, COALESCE(SUM(amount), 0) AS total
       FROM visits WHERE staff_id = ? AND created_at >= ? AND stamps > 0',
    [(int)$staff['id'], $todayStart]
)->fetch();

page_start('Mostrador', 'staff');
?>
<div class="row-between page-head">
    <div>
        <h1>Mostrador</h1>
        <p class="muted">Hola, <?= e((string)$staff['name']) ?> · Hoy: <?= (int)$today['ventas'] ?> ventas · <?= e(money((float)$today['total'])) ?></p>
    </div>
    <button class="btn btn-ghost" type="button" data-scan>📷 Escanear QR</button>
</div>

<div id="scanner" class="scanner" hidden></div>

<section class="card">
    <h2>Buscar cliente</h2>
    <form method="get" class="search-row">
        <input class="input" type="search" name="q" value="<?= e($search) ?>"
               placeholder="Celular, cédula o código del cliente" autofocus>
        <button class="btn btn-primary" type="submit">Buscar</button>
    </form>
</section>

<details class="card">
    <summary><strong>+ Registrar cliente nuevo</strong></summary>
    <p class="muted">El celular es el dato principal (por ahí se avisa por WhatsApp). La cédula es opcional y sirve para identificarlo.</p>
    <form method="post" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="register">
        <div class="grid-2 tight">
            <label class="field">
                <span>Nombre</span>
                <input class="input" type="text" name="name" required maxlength="100">
            </label>
            <label class="field">
                <span>Celular (WhatsApp)</span>
                <input class="input" type="tel" name="phone" required placeholder="300 123 4567">
            </label>
            <label class="field">
                <span>Cédula <small>(opcional)</small></span>
                <input class="input" type="text" name="document" inputmode="numeric" maxlength="15" placeholder="1234567890">
            </label>
            <label class="field">
                <span>Cumpleaños <small>(opcional)</small></span>
                <input class="input" type="date" name="birthday">
            </label>
            <label class="field">
                <span>PIN de acceso <small>(si se deja vacío: últimos 4 del celular)</small></span>
                <input class="input" type="text" name="pin" inputmode="numeric" pattern="\d{4,6}" maxlength="6" placeholder="Automático">
            </label>
        </div>
        <button class="btn btn-primary" type="submit">Registrar cliente</button>
    </form>
</details>

<?php if ($search !== '' && !$customer): ?>
    <div class="alert alert-error">No encontré a nadie con “<?= e($search) ?>”. Pídele que se registre primero.</div>
<?php endif; ?>

<?php if ($customer): ?>
    <?php
    $stamps = min((int)$customer['stamps'], $required);
    $activeCoupons = customer_coupons((int)$customer['id'], 'active');
    ?>
    <section class="card">
        <div class="row-between">
            <div>
                <span class="pill"><?= e((string)$customer['code']) ?></span>
                <h2><?= e((string)$customer['name']) ?></h2>
                <p class="muted">
                    <?= e((string)$customer['phone']) ?>
                    <?php if (!empty($customer['document'])): ?> · C.C. <?= e((string)$customer['document']) ?><?php endif; ?>
                    · Última visita: <?= e(fmt_datetime((string)$customer['last_visit_at'])) ?>
                </p>
            </div>
            <div class="big-count">
                <strong><?= $stamps ?></strong><span>/<?= $required ?></span>
            </div>
        </div>

        <div class="stamp-grid">
            <?php for ($i = 1; $i <= $required; $i++): ?>
                <span class="stamp <?= $i <= $stamps ? 'on' : '' ?>"><?= $i <= $stamps ? '🍲' : $i ?></span>
            <?php endfor; ?>
        </div>

        <?php if ($activeCoupons): ?>
            <div class="alert alert-info">
                🎁 Premio activo:
                <?php foreach ($activeCoupons as $coupon): ?>
                    <strong><?= e((string)$coupon['code']) ?></strong> (<?= e((string)$coupon['description']) ?>, vence <?= e(fmt_date((string)$coupon['expires_at'])) ?>)
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="grid-2 tight">
            <form method="post" class="form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="stamp">
                <input type="hidden" name="customer_id" value="<?= (int)$customer['id'] ?>">
                <h3>Agregar sello</h3>
                <label class="field">
                    <span>Valor de la compra <small>(opcional)</small></span>
                    <input class="input" type="number" name="amount" min="0" step="100" placeholder="0">
                </label>
                <label class="field">
                    <span>Nota <small>(opcional)</small></span>
                    <input class="input" type="text" name="note" maxlength="120" placeholder="Ej. caldo + bebida">
                </label>
                <button class="btn btn-primary btn-block" type="submit">+1 sello</button>
            </form>

            <form method="post" class="form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="redeem">
                <h3>Canjear premio</h3>
                <label class="field">
                    <span>Código del cupón</span>
                    <input class="input" type="text" name="coupon_code" maxlength="12" placeholder="ABC123" style="text-transform:uppercase">
                </label>
                <button class="btn btn-dark btn-block" type="submit">Canjear</button>
                <p class="muted small">El cupón queda marcado como usado.</p>
            </form>
        </div>
    </section>

    <?php if ($pendingNotifs): ?>
        <section class="card">
            <h2>Mensajes por enviar</h2>
            <p class="muted">Abre WhatsApp y envía el mensaje; luego márcalo como enviado.</p>
            <ul class="notif-list">
                <?php foreach ($pendingNotifs as $notification): ?>
                    <li>
                        <p><?= e((string)$notification['message']) ?></p>
                        <div class="btn-row">
                            <a class="btn btn-wa" target="_blank" rel="noopener"
                               href="<?= e(wa_link((string)$customer['phone'], (string)$notification['message'])) ?>">Enviar por WhatsApp</a>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="notif_sent">
                                <input type="hidden" name="notification_id" value="<?= (int)$notification['id'] ?>">
                                <input type="hidden" name="back_q" value="<?= e((string)$customer['phone']) ?>">
                                <button class="btn btn-ghost" type="submit">Ya lo envié</button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ul>
        </section>
    <?php endif; ?>
<?php endif; ?>

<section class="card card-soft">
    <h2>Reglas del programa</h2>
    <p><?= $required ?> sellos = <strong><?= e($reward) ?></strong>. Un sello por compra. Los premios vencen en <?= (int)setting('coupon_valid_days', '30') ?> días.</p>
</section>
<?php page_end(); ?>
