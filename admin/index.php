<?php
declare(strict_types=1);
require __DIR__ . '/../includes/app.php';

$staff = require_staff('admin');
$tab   = (string)($_GET['tab'] ?? 'dashboard');
$tabs  = [
    'dashboard' => 'Resumen',
    'clientes'  => 'Clientes',
    'cupones'   => 'Cupones',
    'mensajes'  => 'Mensajes',
    'menu'      => 'Menú',
    'ajustes'   => 'Ajustes',
    'equipo'    => 'Equipo',
];
if (!isset($tabs[$tab])) {
    $tab = 'dashboard';
}

/* ------------------------------------------------------------------ *
 *  Acciones
 * ------------------------------------------------------------------ */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = (string)($_POST['action'] ?? '');

    if ($action === 'settings_save') {
        $textKeys = ['business_name', 'reward_text', 'country_code', 'currency', 'whatsapp_mode',
                     'wa_cloud_token', 'wa_cloud_phone_id', 'milestones', 'tagline', 'hero_text',
                     'price_caldo', 'address', 'hours', 'delivery_info', 'whatsapp_orders', 'instagram',
                     'delivery_cost', 'notice', 'address_note', 'reservations',
                     'about_title', 'about_text', 'about_features'];
        foreach ($textKeys as $key) {
            if (isset($_POST[$key])) {
                set_setting($key, trim((string)$_POST[$key]));
            }
        }
        foreach (['stamps_required', 'coupon_valid_days', 'inactive_days'] as $key) {
            set_setting($key, (string)max(1, (int)($_POST[$key] ?? 1)));
        }
        set_setting('notify_every_stamp', isset($_POST['notify_every_stamp']) ? '1' : '0');
        flash('ok', 'Ajustes guardados.');
        redirect('admin/index.php?tab=ajustes');
    }

    if ($action === 'menu_save') {
        $id          = (int)($_POST['item_id'] ?? 0);
        $name        = trim((string)($_POST['name'] ?? ''));
        $category    = trim((string)($_POST['category'] ?? ''));
        $description = trim((string)($_POST['description'] ?? ''));
        $price       = trim((string)($_POST['price'] ?? ''));
        $position    = (int)($_POST['position'] ?? 0);
        $active      = isset($_POST['active']) ? 1 : 0;
        $image       = trim((string)($_POST['image_current'] ?? ''));

        if ($name === '' || $price === '') {
            flash('error', 'El producto necesita nombre y precio.');
        } else {
            if (!empty($_FILES['image_file']['tmp_name']) && is_uploaded_file($_FILES['image_file']['tmp_name'])) {
                $upload = $_FILES['image_file'];
                if ((int)$upload['size'] > 4 * 1024 * 1024) {
                    flash('error', 'La imagen no puede superar 4 MB.');
                    redirect('admin/index.php?tab=menu');
                }
                $info = @getimagesize($upload['tmp_name']);
                $extMap = [
                    'image/jpeg' => 'jpg',
                    'image/png'  => 'png',
                    'image/webp' => 'webp',
                ];
                $mime = $info['mime'] ?? '';
                if (!$info || !isset($extMap[$mime])) {
                    flash('error', 'La imagen debe ser JPG, PNG o WEBP.');
                    redirect('admin/index.php?tab=menu');
                }
                $imageDir = dirname(__DIR__) . '/assets/menu';
                if (!is_dir($imageDir)) {
                    mkdir($imageDir, 0775, true);
                }
                $fileName = 'menu-' . date('Ymd-His') . '-' . bin2hex(random_bytes(3)) . '.' . $extMap[$mime];
                if (!move_uploaded_file($upload['tmp_name'], $imageDir . '/' . $fileName)) {
                    flash('error', 'No se pudo guardar la imagen.');
                    redirect('admin/index.php?tab=menu');
                }
                $image = 'assets/menu/' . $fileName;
            }
            if (isset($_POST['image_remove'])) {
                $image = '';
            }

            $category = $category !== '' ? $category : 'Caldos';
            if ($id > 0) {
                q(
                    'UPDATE menu_items SET category = ?, name = ?, description = ?, price = ?, image = ?, position = ?, active = ? WHERE id = ?',
                    [$category, $name, $description, $price, $image !== '' ? $image : null, $position, $active, $id]
                );
                flash('ok', 'Producto actualizado.');
            } else {
                q(
                    'INSERT INTO menu_items (category, name, description, price, image, position, active, created_at)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
                    [$category, $name, $description, $price, $image !== '' ? $image : null, $position, $active, now()]
                );
                flash('ok', 'Producto agregado al menú.');
            }
        }
        redirect('admin/index.php?tab=menu');
    }

    if ($action === 'menu_delete') {
        q('DELETE FROM menu_items WHERE id = ?', [(int)($_POST['item_id'] ?? 0)]);
        flash('ok', 'Producto eliminado del menú.');
        redirect('admin/index.php?tab=menu');
    }

    if ($action === 'menu_toggle') {
        q('UPDATE menu_items SET active = CASE WHEN active = 1 THEN 0 ELSE 1 END WHERE id = ?', [(int)($_POST['item_id'] ?? 0)]);
        flash('ok', 'Disponibilidad del producto actualizada.');
        redirect('admin/index.php?tab=menu');
    }

    if ($action === 'staff_add') {
        $name = trim((string)($_POST['name'] ?? ''));
        $pin  = (string)($_POST['pin'] ?? '');
        $role = ($_POST['role'] ?? 'staff') === 'admin' ? 'admin' : 'staff';

        if (mb_strlen($name) < 2) {
            flash('error', 'Escribe el nombre del empleado.');
        } elseif (!preg_match('/^\d{4,6}$/', $pin)) {
            flash('error', 'El PIN debe tener entre 4 y 6 números.');
        } else {
            q(
                'INSERT INTO staff (name, pin_hash, role, active) VALUES (?, ?, ?, 1)',
                [$name, password_hash($pin, PASSWORD_DEFAULT), $role]
            );
            flash('ok', 'Empleado agregado.');
        }
        redirect('admin/index.php?tab=equipo');
    }

    if ($action === 'staff_toggle') {
        $id = (int)($_POST['staff_id'] ?? 0);
        if ($id === (int)$staff['id']) {
            flash('error', 'No puedes desactivar tu propio usuario.');
        } else {
            q('UPDATE staff SET active = CASE WHEN active = 1 THEN 0 ELSE 1 END WHERE id = ?', [$id]);
            flash('ok', 'Estado del empleado actualizado.');
        }
        redirect('admin/index.php?tab=equipo');
    }

    if ($action === 'staff_pin') {
        $id  = (int)($_POST['staff_id'] ?? 0);
        $pin = (string)($_POST['pin'] ?? '');
        if (!preg_match('/^\d{4,6}$/', $pin)) {
            flash('error', 'El PIN debe tener entre 4 y 6 números.');
        } else {
            q('UPDATE staff SET pin_hash = ? WHERE id = ?', [password_hash($pin, PASSWORD_DEFAULT), $id]);
            flash('ok', 'PIN actualizado.');
        }
        redirect('admin/index.php?tab=equipo');
    }

    if ($action === 'coupon_expire') {
        q("UPDATE coupons SET status = 'expired' WHERE id = ? AND status = 'active'", [(int)($_POST['coupon_id'] ?? 0)]);
        flash('ok', 'Cupón anulado.');
        redirect('admin/index.php?tab=cupones');
    }

    if ($action === 'notif_sent') {
        q("UPDATE notifications SET status = 'sent', sent_at = ? WHERE id = ?", [now(), (int)($_POST['notification_id'] ?? 0)]);
        flash('ok', 'Mensaje marcado como enviado.');
        redirect('admin/index.php?tab=mensajes');
    }

    if ($action === 'notif_api') {
        $notification = q(
            'SELECT n.*, c.phone FROM notifications n JOIN customers c ON c.id = n.customer_id WHERE n.id = ?',
            [(int)($_POST['notification_id'] ?? 0)]
        )->fetch();

        if ($notification) {
            [$ok, $error] = send_whatsapp_cloud((string)$notification['phone'], (string)$notification['message']);
            if ($ok) {
                q("UPDATE notifications SET status = 'sent', sent_at = ?, error = NULL WHERE id = ?", [now(), (int)$notification['id']]);
                flash('ok', 'Mensaje enviado por la API de WhatsApp.');
            } else {
                q("UPDATE notifications SET status = 'failed', error = ? WHERE id = ?", [mb_substr($error, 0, 250), (int)$notification['id']]);
                flash('error', 'No se pudo enviar: ' . $error);
            }
        }
        redirect('admin/index.php?tab=mensajes');
    }

    if ($action === 'optin_toggle') {
        q('UPDATE customers SET opt_in = CASE WHEN opt_in = 1 THEN 0 ELSE 1 END WHERE id = ?', [(int)($_POST['customer_id'] ?? 0)]);
        flash('ok', 'Preferencia de WhatsApp actualizada.');
        redirect('admin/index.php?tab=clientes');
    }
}

/* ------------------------------------------------------------------ *
 *  Exportar clientes a CSV
 * ------------------------------------------------------------------ */
if ($tab === 'clientes' && isset($_GET['export'])) {
    $rows = q(
        'SELECT code, name, phone, document, birthday, stamps, total_stamps, rewards_earned, opt_in,
                last_visit_at, created_at FROM customers ORDER BY created_at DESC'
    )->fetchAll();

    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename="clientes-' . date('Y-m-d') . '.csv"');
    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Codigo', 'Nombre', 'Celular', 'Cedula', 'Cumpleanos', 'Sellos', 'Sellos totales', 'Premios', 'WhatsApp', 'Ultima visita', 'Registro']);
    foreach ($rows as $row) {
        fputcsv($out, [
            $row['code'], $row['name'], $row['phone'], $row['document'], $row['birthday'], $row['stamps'],
            $row['total_stamps'], $row['rewards_earned'], $row['opt_in'] ? 'Si' : 'No',
            $row['last_visit_at'], $row['created_at'],
        ]);
    }
    fclose($out);
    exit;
}

/* ------------------------------------------------------------------ *
 *  Datos para las vistas
 * ------------------------------------------------------------------ */
$todayStart   = date('Y-m-d 00:00:00');
$monthStart   = date('Y-m-01 00:00:00');
$businessName = setting('business_name', 'Mi Cabaña');

if ($tab === 'dashboard') {
    $stats = [
        'clientes'   => (int)q('SELECT COUNT(*) FROM customers')->fetchColumn(),
        'nuevos_mes' => (int)q('SELECT COUNT(*) FROM customers WHERE created_at >= ?', [$monthStart])->fetchColumn(),
        'cupones'    => (int)q("SELECT COUNT(*) FROM coupons WHERE status = 'active'")->fetchColumn(),
        'pendientes' => (int)q("SELECT COUNT(*) FROM notifications WHERE status = 'queued'")->fetchColumn(),
    ];
    $todayRow = q(
        'SELECT COUNT(*) AS ventas, COALESCE(SUM(amount), 0) AS total
           FROM visits WHERE created_at >= ? AND stamps > 0',
        [$todayStart]
    )->fetch();
    $monthRow = q(
        'SELECT COUNT(*) AS ventas, COALESCE(SUM(amount), 0) AS total
           FROM visits WHERE created_at >= ? AND stamps > 0',
        [$monthStart]
    )->fetch();
    $recent = q(
        'SELECT v.*, c.name AS customer_name, s.name AS staff_name
           FROM visits v
           LEFT JOIN customers c ON c.id = v.customer_id
           LEFT JOIN staff s ON s.id = v.staff_id
       ORDER BY v.created_at DESC LIMIT 12'
    )->fetchAll();
    $top = q(
        'SELECT name, phone, total_stamps, rewards_earned FROM customers
      ORDER BY total_stamps DESC, rewards_earned DESC LIMIT 5'
    )->fetchAll();
}

if ($tab === 'clientes') {
    $term = trim((string)($_GET['q'] ?? ''));
    $sql  = 'SELECT * FROM customers';
    $params = [];
    if ($term !== '') {
        $sql .= ' WHERE name LIKE ? OR phone LIKE ? OR code LIKE ? OR document LIKE ?';
        $like = '%' . $term . '%';
        $params = [$like, $like, $like, $like];
    }
    $sql .= ' ORDER BY created_at DESC LIMIT 200';
    $customers = q($sql, $params)->fetchAll();
}

if ($tab === 'menu') {
    $menuAll  = menu_items(false);
    $editId   = (int)($_GET['edit'] ?? 0);
    $editItem = null;
    foreach ($menuAll as $menuRow) {
        if ((int)$menuRow['id'] === $editId) {
            $editItem = $menuRow;
            break;
        }
    }
    $categories = [];
    foreach ($menuAll as $menuRow) {
        $categories[trim((string)$menuRow['category']) ?: 'Caldos'] = true;
    }
}

if ($tab === 'cupones') {
    $filter = (string)($_GET['estado'] ?? 'active');
    $sql = 'SELECT cp.*, c.name AS customer_name, c.phone
              FROM coupons cp JOIN customers c ON c.id = cp.customer_id';
    $params = [];
    if (in_array($filter, ['active', 'used', 'expired'], true)) {
        $sql .= ' WHERE cp.status = ?';
        $params[] = $filter;
    }
    $sql .= ' ORDER BY cp.created_at DESC LIMIT 200';
    $coupons = q($sql, $params)->fetchAll();
}

if ($tab === 'mensajes') {
    $pending = pending_notifications(100);
    $recent  = q(
        "SELECT n.*, c.name AS customer_name, c.phone
           FROM notifications n JOIN customers c ON c.id = n.customer_id
          WHERE n.status <> 'queued'
       ORDER BY n.created_at DESC LIMIT 30"
    )->fetchAll();
}
if ($tab === 'equipo') {
    $team = q('SELECT * FROM staff ORDER BY active DESC, role DESC, name')->fetchAll();
}

$cronUrl = url('cron.php?key=' . urlencode(setting('cron_key', '')));
$waConfigured = setting('wa_cloud_token', '') !== '' && setting('wa_cloud_phone_id', '') !== '';

page_start('Administración', 'admin');
?>
<div class="page-head">
    <h1>Administración</h1>
    <p class="muted"><?= e($businessName) ?> · <?= e(date('d/m/Y')) ?></p>
</div>

<nav class="tabs">
    <?php foreach ($tabs as $key => $label): ?>
        <a class="<?= $tab === $key ? 'active' : '' ?>" href="<?= e(url('admin/index.php?tab=' . $key)) ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
</nav>

<?php /* ---------------------------------------------------------------- */ ?>
<?php if ($tab === 'dashboard'): ?>
    <div class="stats">
        <div class="stat"><span>Clientes</span><strong><?= $stats['clientes'] ?></strong><small>+<?= $stats['nuevos_mes'] ?> este mes</small></div>
        <div class="stat"><span>Ventas hoy</span><strong><?= (int)$todayRow['ventas'] ?></strong><small><?= e(money((float)$todayRow['total'])) ?></small></div>
        <div class="stat"><span>Ventas del mes</span><strong><?= (int)$monthRow['ventas'] ?></strong><small><?= e(money((float)$monthRow['total'])) ?></small></div>
        <div class="stat"><span>Premios activos</span><strong><?= $stats['cupones'] ?></strong><small><?= $stats['pendientes'] ?> mensajes en cola</small></div>
    </div>

    <div class="grid-2">
        <section class="card">
            <h2>Últimos movimientos</h2>
            <table class="table">
                <thead><tr><th>Cliente</th><th>Acción</th><th>Valor</th><th>Cuándo</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $row): ?>
                    <tr>
                        <td><?= e((string)($row['customer_name'] ?? '—')) ?></td>
                        <td><?= (int)$row['stamps'] > 0 ? 'Sello' : 'Canje' ?></td>
                        <td><?= e(money((float)$row['amount'])) ?></td>
                        <td class="muted"><?= e(fmt_datetime((string)$row['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recent): ?>
                    <tr><td colspan="4" class="muted">Todavía no hay movimientos.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </section>

        <section class="card">
            <h2>Mejores clientes</h2>
            <ul class="plain-list">
                <?php foreach ($top as $row): ?>
                    <li>
                        <span><strong><?= e((string)$row['name']) ?></strong><br><small class="muted"><?= e((string)$row['phone']) ?></small></span>
                        <span class="badge"><?= (int)$row['total_stamps'] ?> sellos</span>
                    </li>
                <?php endforeach; ?>
                <?php if (!$top): ?>
                    <li class="muted">Sin datos todavía.</li>
                <?php endif; ?>
            </ul>
        </section>
    </div>

<?php /* ---------------------------------------------------------------- */ ?>
<?php elseif ($tab === 'clientes'): ?>
    <section class="card">
        <div class="row-between">
            <h2>Clientes (<?= count($customers) ?>)</h2>
            <a class="btn btn-ghost" href="<?= e(url('admin/index.php?tab=clientes&export=1')) ?>">Exportar CSV</a>
        </div>
        <form method="get" class="search-row">
            <input type="hidden" name="tab" value="clientes">
            <input class="input" type="search" name="q" value="<?= e($term ?? '') ?>" placeholder="Buscar por nombre, celular o código">
            <button class="btn btn-primary" type="submit">Buscar</button>
        </form>
        <div class="table-scroll">
            <table class="table">
                <thead>
                <tr><th>Cliente</th><th>Celular</th><th>Cédula</th><th>Sellos</th><th>Premios</th><th>WhatsApp</th><th>Última visita</th><th></th></tr>
                </thead>
                <tbody>
                <?php foreach ($customers as $row): ?>
                    <tr>
                        <td>
                            <strong><?= e((string)$row['name']) ?></strong><br>
                            <small class="muted"><?= e((string)$row['code']) ?></small>
                        </td>
                        <td><?= e((string)$row['phone']) ?></td>
                        <td><?= e((string)($row['document'] ?? '')) ?: '<span class="muted">—</span>' ?></td>
                        <td><?= (int)$row['stamps'] ?>/<?= (int)setting('stamps_required', '10') ?><br><small class="muted"><?= (int)$row['total_stamps'] ?> total</small></td>
                        <td><?= (int)$row['rewards_earned'] ?></td>
                        <td>
                            <a class="btn btn-wa btn-sm" target="_blank" rel="noopener"
                               href="<?= e(wa_link((string)$row['phone'], 'Hola ' . $row['name'] . ' 👋 Te saludamos de ' . $businessName . ' 🍲')) ?>">WA</a>
                            <form method="post" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="optin_toggle">
                                <input type="hidden" name="customer_id" value="<?= (int)$row['id'] ?>">
                                <button class="btn btn-sm <?= $row['opt_in'] ? 'btn-ghost' : 'btn-dark' ?>" type="submit" title="Activar o desactivar mensajes">
                                    <?= $row['opt_in'] ? 'Sí' : 'No' ?>
                                </button>
                            </form>
                        </td>
                        <td class="muted"><?= e(fmt_date((string)$row['last_visit_at'])) ?></td>
                        <td>
                            <a class="link" href="<?= e(url('staff/index.php?q=' . urlencode((string)$row['phone']))) ?>">Sellar</a>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$customers): ?>
                    <tr><td colspan="8" class="muted">No hay clientes que coincidan.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

<?php /* ---------------------------------------------------------------- */ ?>
<?php elseif ($tab === 'cupones'): ?>
    <section class="card">
        <div class="row-between">
            <h2>Cupones</h2>
            <div class="tabs tabs-inline">
                <?php foreach (['active' => 'Activos', 'used' => 'Usados', 'expired' => 'Vencidos'] as $key => $label): ?>
                    <a class="<?= $filter === $key ? 'active' : '' ?>" href="<?= e(url('admin/index.php?tab=cupones&estado=' . $key)) ?>"><?= e($label) ?></a>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="table-scroll">
            <table class="table">
                <thead><tr><th>Código</th><th>Cliente</th><th>Premio</th><th>Estado</th><th>Vence</th><th>Canjeado</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($coupons as $row): ?>
                    <tr>
                        <td><code><?= e((string)$row['code']) ?></code></td>
                        <td><?= e((string)$row['customer_name']) ?><br><small class="muted"><?= e((string)$row['phone']) ?></small></td>
                        <td><?= e((string)$row['description']) ?></td>
                        <td><span class="badge badge-<?= e((string)$row['status']) ?>"><?= e((string)$row['status']) ?></span></td>
                        <td class="muted"><?= e(fmt_date((string)$row['expires_at'])) ?></td>
                        <td class="muted"><?= e(fmt_datetime((string)$row['used_at'])) ?></td>
                        <td>
                            <?php if ($row['status'] === 'active'): ?>
                                <form method="post" onsubmit="return confirm('¿Anular este cupón?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="coupon_expire">
                                    <input type="hidden" name="coupon_id" value="<?= (int)$row['id'] ?>">
                                    <button class="btn btn-sm btn-ghost" type="submit">Anular</button>
                                </form>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$coupons): ?>
                    <tr><td colspan="7" class="muted">No hay cupones en este estado.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

<?php /* ---------------------------------------------------------------- */ ?>
<?php elseif ($tab === 'mensajes'): ?>
    <section class="card">
        <div class="row-between">
            <h2>Por enviar (<?= count($pending) ?>)</h2>
            <span class="badge <?= $waConfigured ? 'badge-active' : '' ?>">
                <?= $waConfigured ? 'API WhatsApp conectada' : 'Modo enlace (manual)' ?>
            </span>
        </div>
        <p class="muted">Con el modo enlace, abres WhatsApp con el mensaje listo y luego marcas “Ya lo envié”. Si configuras la API en Ajustes, puedes enviarlos con un clic.</p>

        <ul class="notif-list">
            <?php foreach ($pending as $notification): ?>
                <li>
                    <div class="row-between">
                        <strong><?= e((string)$notification['name']) ?> · <?= e((string)$notification['phone']) ?></strong>
                        <small class="muted"><?= e(fmt_datetime((string)$notification['created_at'])) ?></small>
                    </div>
                    <p><?= e((string)$notification['message']) ?></p>
                    <div class="btn-row">
                        <a class="btn btn-wa btn-sm" target="_blank" rel="noopener"
                           href="<?= e(wa_link((string)$notification['phone'], (string)$notification['message'])) ?>">WhatsApp</a>
                        <?php if ($waConfigured): ?>
                            <form method="post">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="notif_api">
                                <input type="hidden" name="notification_id" value="<?= (int)$notification['id'] ?>">
                                <button class="btn btn-sm btn-primary" type="submit">Enviar por API</button>
                            </form>
                        <?php endif; ?>
                        <form method="post">
                            <?= csrf_field() ?>
                            <input type="hidden" name="action" value="notif_sent">
                            <input type="hidden" name="notification_id" value="<?= (int)$notification['id'] ?>">
                            <button class="btn btn-sm btn-ghost" type="submit">Ya lo envié</button>
                        </form>
                    </div>
                </li>
            <?php endforeach; ?>
            <?php if (!$pending): ?>
                <li class="muted">No hay mensajes pendientes. 🎉</li>
            <?php endif; ?>
        </ul>
    </section>

    <section class="card">
        <h2>Historial reciente</h2>
        <div class="table-scroll">
            <table class="table">
                <thead><tr><th>Cliente</th><th>Mensaje</th><th>Estado</th><th>Fecha</th></tr></thead>
                <tbody>
                <?php foreach ($recent as $row): ?>
                    <tr>
                        <td><?= e((string)$row['customer_name']) ?></td>
                        <td class="muted small"><?= e(mb_substr((string)$row['message'], 0, 90)) ?>…</td>
                        <td><span class="badge badge-<?= e((string)$row['status']) ?>"><?= e((string)$row['status']) ?></span></td>
                        <td class="muted"><?= e(fmt_datetime((string)$row['sent_at'] ?: $row['created_at'])) ?></td>
                    </tr>
                <?php endforeach; ?>
                <?php if (!$recent): ?>
                    <tr><td colspan="4" class="muted">Sin mensajes enviados todavía.</td></tr>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </section>

<?php /* ---------------------------------------------------------------- */ ?>
<?php elseif ($tab === 'menu'): ?>
    <div class="admin-menu-grid">
        <section class="card">
            <div class="row-between">
                <h2>Productos (<?= count($menuAll) ?>)</h2>
                <a class="btn btn-ghost btn-sm" target="_blank" rel="noopener" href="<?= e(url('menu.php')) ?>">Ver en el sitio</a>
            </div>
            <?php if (!$menuAll): ?>
                <p class="muted">Todavía no hay productos. Agrega el primero con el formulario.</p>
            <?php else: ?>
                <div class="admin-menu-list">
                    <?php foreach ($menuAll as $row): ?>
                        <?php
                        $rowImage   = trim((string)($row['image'] ?? ''));
                        $rowImageOk = $rowImage !== '' && is_file(dirname(__DIR__) . '/' . $rowImage);
                        ?>
                        <div class="admin-menu-row<?= (int)$row['active'] ? '' : ' is-off' ?>">
                            <div class="admin-menu-thumb">
                                <?php if ($rowImageOk): ?>
                                    <img src="<?= e(url($rowImage)) ?>" alt="">
                                <?php else: ?>
                                    <span>🍽️</span>
                                <?php endif; ?>
                            </div>
                            <div class="admin-menu-info">
                                <strong><?= e((string)$row['name']) ?></strong>
                                <small class="muted"><?= e((string)$row['category']) ?> · <?= e((string)$row['price']) ?> · orden <?= (int)$row['position'] ?></small>
                                <span class="badge <?= (int)$row['active'] ? 'badge-active' : '' ?>"><?= (int)$row['active'] ? 'Visible' : 'Oculto' ?></span>
                            </div>
                            <div class="admin-menu-actions">
                                <a class="btn btn-sm btn-ghost" href="<?= e(url('admin/index.php?tab=menu&edit=' . (int)$row['id'])) ?>">Editar</a>
                                <form method="post" class="inline">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="menu_toggle">
                                    <input type="hidden" name="item_id" value="<?= (int)$row['id'] ?>">
                                    <button class="btn btn-sm btn-ghost" type="submit"><?= (int)$row['active'] ? 'Ocultar' : 'Mostrar' ?></button>
                                </form>
                                <form method="post" class="inline" onsubmit="return confirm('¿Eliminar este producto del menú?');">
                                    <?= csrf_field() ?>
                                    <input type="hidden" name="action" value="menu_delete">
                                    <input type="hidden" name="item_id" value="<?= (int)$row['id'] ?>">
                                    <button class="btn btn-sm btn-ghost" type="submit">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </section>

        <section class="card">
            <h2><?= $editItem ? 'Editar producto' : 'Agregar producto' ?></h2>
            <form method="post" enctype="multipart/form-data" class="form">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="menu_save">
                <input type="hidden" name="item_id" value="<?= $editItem ? (int)$editItem['id'] : 0 ?>">
                <input type="hidden" name="image_current" value="<?= e((string)($editItem['image'] ?? '')) ?>">

                <label class="field">
                    <span>Nombre del plato</span>
                    <input class="input" type="text" name="name" required maxlength="120" value="<?= e((string)($editItem['name'] ?? '')) ?>" placeholder="Caldo de salchicha">
                </label>
                <div class="grid-2 tight">
                    <label class="field">
                        <span>Categoría</span>
                        <input class="input" type="text" name="category" list="menu-categories" maxlength="60" value="<?= e((string)($editItem['category'] ?? 'Caldos')) ?>" placeholder="Caldos">
                        <datalist id="menu-categories">
                            <?php foreach (array_keys($categories) as $cat): ?>
                                <option value="<?= e((string)$cat) ?>"></option>
                            <?php endforeach; ?>
                        </datalist>
                    </label>
                    <label class="field">
                        <span>Precio (texto libre)</span>
                        <input class="input" type="text" name="price" required maxlength="30" value="<?= e((string)($editItem['price'] ?? '')) ?>" placeholder="$3.50 o Tu precio">
                    </label>
                </div>
                <label class="field">
                    <span>Descripción corta</span>
                    <textarea class="input" name="description" rows="2" maxlength="255" placeholder="Receta tradicional, calientito y para llevar."><?= e((string)($editItem['description'] ?? '')) ?></textarea>
                </label>
                <div class="grid-2 tight">
                    <label class="field">
                        <span>Orden</span>
                        <input class="input" type="number" name="position" min="0" max="999" value="<?= (int)($editItem['position'] ?? 0) ?>">
                    </label>
                    <label class="check" style="align-self:end">
                        <input type="checkbox" name="active" <?= (!$editItem || (int)$editItem['active']) ? 'checked' : '' ?>>
                        <span>Visible en la página</span>
                    </label>
                </div>

                <div class="field">
                    <span>Foto del plato</span>
                    <?php if ($editItem && trim((string)($editItem['image'] ?? '')) !== '' && is_file(dirname(__DIR__) . '/' . $editItem['image'])): ?>
                        <img class="admin-image-preview" src="<?= e(url((string)$editItem['image'])) ?>" alt="Foto actual de <?= e((string)$editItem['name']) ?>">
                        <label class="check">
                            <input type="checkbox" name="image_remove">
                            <span>Quitar la foto actual</span>
                        </label>
                    <?php endif; ?>
                    <input class="input" type="file" name="image_file" accept="image/jpeg,image/png,image/webp">
                    <small class="muted">JPG, PNG o WEBP · máximo 4 MB. Recomendado: horizontal 4:3.</small>
                </div>

                <div class="btn-row">
                    <button class="btn btn-primary" type="submit"><?= $editItem ? 'Guardar cambios' : 'Agregar al menú' ?></button>
                    <?php if ($editItem): ?>
                        <a class="btn btn-ghost" href="<?= e(url('admin/index.php?tab=menu')) ?>">Cancelar</a>
                    <?php endif; ?>
                </div>
            </form>
        </section>
    </div>

<?php /* ---------------------------------------------------------------- */ ?>
<?php elseif ($tab === 'ajustes'): ?>
    <section class="card">
        <h2>Reglas del programa</h2>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="settings_save">
            <div class="grid-2 tight">
                <label class="field">
                    <span>Nombre del negocio</span>
                    <input class="input" type="text" name="business_name" value="<?= e(setting('business_name')) ?>" required>
                </label>
                <label class="field">
                    <span>Premio al completar la tarjeta</span>
                    <input class="input" type="text" name="reward_text" value="<?= e(setting('reward_text')) ?>" required>
                </label>
                <label class="field">
                    <span>Sellos necesarios</span>
                    <input class="input" type="number" name="stamps_required" min="2" max="30" value="<?= e(setting('stamps_required', '10')) ?>">
                </label>
                <label class="field">
                    <span>Sellos con aviso por WhatsApp</span>
                    <input class="input" type="text" name="milestones" value="<?= e(setting('milestones', '3,6,9')) ?>" placeholder="3,6,9">
                </label>
                <label class="field">
                    <span>Vigencia del premio (días)</span>
                    <input class="input" type="number" name="coupon_valid_days" min="1" value="<?= e(setting('coupon_valid_days', '30')) ?>">
                </label>
                <label class="field">
                    <span>Días de inactividad para recordar</span>
                    <input class="input" type="number" name="inactive_days" min="1" value="<?= e(setting('inactive_days', '21')) ?>">
                </label>
                <label class="field">
                    <span>Código de país (WhatsApp)</span>
                    <input class="input" type="text" name="country_code" value="<?= e(setting('country_code', '593')) ?>" maxlength="4">
                </label>
                <label class="field">
                    <span>Símbolo de moneda</span>
                    <input class="input" type="text" name="currency" value="<?= e(setting('currency', '$')) ?>" maxlength="4">
                </label>
            </div>
            <label class="check">
                <input type="checkbox" name="notify_every_stamp" <?= setting('notify_every_stamp') === '1' ? 'checked' : '' ?>>
                <span>Avisar por WhatsApp en cada sello (puede ser molesto; por defecto solo en los hitos).</span>
            </label>

            <h3>Página principal</h3>
            <div class="grid-2 tight">
                <label class="field">
                    <span>Frase corta (portada)</span>
                    <input class="input" type="text" name="tagline" value="<?= e(setting('tagline')) ?>" maxlength="140">
                </label>
                <label class="field">
                    <span>Precio del caldo</span>
                    <input class="input" type="text" name="price_caldo" value="<?= e(setting('price_caldo')) ?>" maxlength="30">
                </label>
                <label class="field">
                    <span>Dirección</span>
                    <input class="input" type="text" name="address" value="<?= e(setting('address')) ?>" maxlength="140">
                </label>
                <label class="field">
                    <span>Horario</span>
                    <input class="input" type="text" name="hours" value="<?= e(setting('hours')) ?>" maxlength="140">
                </label>
                <label class="field">
                    <span>WhatsApp para pedidos</span>
                    <input class="input" type="tel" name="whatsapp_orders" value="<?= e(setting('whatsapp_orders')) ?>" placeholder="300 123 4567">
                </label>
                <label class="field">
                    <span>Instagram (usuario)</span>
                    <input class="input" type="text" name="instagram" value="<?= e(setting('instagram')) ?>" placeholder="cabanadelfina" maxlength="60">
                </label>
                <label class="field">
                    <span>Costo de domicilio</span>
                    <input class="input" type="text" name="delivery_cost" value="<?= e(setting('delivery_cost')) ?>" placeholder="+$1.00" maxlength="30">
                </label>
                <label class="field">
                    <span>Aviso destacado (franja superior)</span>
                    <input class="input" type="text" name="notice" value="<?= e(setting('notice')) ?>" placeholder="Abrimos sábados y domingos" maxlength="180">
                </label>
            </div>
            <label class="field">
                <span>Texto de presentación</span>
                <textarea class="input" name="hero_text" rows="3" maxlength="400"><?= e(setting('hero_text')) ?></textarea>
            </label>
            <label class="field">
                <span>Información de domicilios</span>
                <input class="input" type="text" name="delivery_info" value="<?= e(setting('delivery_info')) ?>" maxlength="180">
            </label>
            <label class="field">
                <span>Nota de dirección</span>
                <input class="input" type="text" name="address_note" value="<?= e(setting('address_note')) ?>" placeholder="Local en arreglos; confirma el punto por WhatsApp" maxlength="180">
            </label>
            <label class="field">
                <span>Reservas entre semana</span>
                <input class="input" type="text" name="reservations" value="<?= e(setting('reservations')) ?>" maxlength="180">
            </label>

            <h3>Cabaña Delfina</h3>
            <label class="field">
                <span>Título de la sección</span>
                <input class="input" type="text" name="about_title" value="<?= e(setting('about_title')) ?>" placeholder="Un rincón tranquilo en Alpachaca" maxlength="120">
            </label>
            <label class="field">
                <span>Texto de la cabaña</span>
                <textarea class="input" name="about_text" rows="3" maxlength="500"><?= e(setting('about_text')) ?></textarea>
            </label>
            <label class="field">
                <span>Características (una por línea)</span>
                <textarea class="input" name="about_features" rows="4" maxlength="500" placeholder="Espacio tranquilo rodeado de naturaleza&#10;Ideal para desconectarse y descansar"><?= e(setting('about_features')) ?></textarea>
            </label>

            <h3>WhatsApp</h3>
            <label class="field">
                <span>Modo de envío</span>
                <select class="input" name="whatsapp_mode">
                    <option value="link" <?= setting('whatsapp_mode') === 'link' ? 'selected' : '' ?>>Enlace manual (wa.me)</option>
                    <option value="cloud" <?= setting('whatsapp_mode') === 'cloud' ? 'selected' : '' ?>>API oficial de WhatsApp Cloud</option>
                </select>
            </label>
            <div class="grid-2 tight">
                <label class="field">
                    <span>Token de WhatsApp Cloud</span>
                    <input class="input" type="password" name="wa_cloud_token" value="<?= e(setting('wa_cloud_token')) ?>" placeholder="EAA...">
                </label>
                <label class="field">
                    <span>Phone ID de WhatsApp Cloud</span>
                    <input class="input" type="text" name="wa_cloud_phone_id" value="<?= e(setting('wa_cloud_phone_id')) ?>" placeholder="1234567890">
                </label>
            </div>
            <p class="muted small">Los mensajes automáticos masivos requieren plantillas aprobadas por Meta. El envío manual por enlace no tiene costo ni restricciones.</p>

            <button class="btn btn-primary" type="submit">Guardar ajustes</button>
        </form>
    </section>

    <section class="card">
        <h2>Tareas automáticas</h2>
        <p>Este enlace revisa cupones por vencer, clientes inactivos y cumpleaños, y encola los mensajes. Prográmalo una vez al día en el Programador de tareas de Windows o en el cron del hosting.</p>
        <div class="copy-row">
            <input class="input" type="text" readonly value="<?= e($cronUrl) ?>" id="cron-url">
            <button class="btn btn-ghost" type="button" data-copy-target="cron-url">Copiar</button>
            <a class="btn btn-dark" target="_blank" rel="noopener" href="<?= e($cronUrl) ?>">Ejecutar ahora</a>
        </div>
    </section>

<?php /* ---------------------------------------------------------------- */ ?>
<?php elseif ($tab === 'equipo'): ?>
    <section class="card">
        <h2>Equipo</h2>
        <p class="muted">Los empleados solo pueden sellar y canjear. Los administradores ven todo.</p>
        <div class="table-scroll">
            <table class="table">
                <thead><tr><th>Nombre</th><th>Rol</th><th>Estado</th><th>Cambiar PIN</th><th></th></tr></thead>
                <tbody>
                <?php foreach ($team as $member): ?>
                    <tr>
                        <td><?= e((string)$member['name']) ?></td>
                        <td><?= $member['role'] === 'admin' ? 'Administrador' : 'Mostrador' ?></td>
                        <td><span class="badge <?= $member['active'] ? 'badge-active' : 'badge-expired' ?>"><?= $member['active'] ? 'Activo' : 'Inactivo' ?></span></td>
                        <td>
                            <form method="post" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="staff_pin">
                                <input type="hidden" name="staff_id" value="<?= (int)$member['id'] ?>">
                                <input class="input input-sm" type="password" name="pin" inputmode="numeric" pattern="\d{4,6}" maxlength="6" placeholder="Nuevo PIN" required>
                                <button class="btn btn-sm btn-ghost" type="submit">Guardar</button>
                            </form>
                        </td>
                        <td>
                            <form method="post" class="inline">
                                <?= csrf_field() ?>
                                <input type="hidden" name="action" value="staff_toggle">
                                <input type="hidden" name="staff_id" value="<?= (int)$member['id'] ?>">
                                <button class="btn btn-sm <?= $member['active'] ? 'btn-ghost' : 'btn-dark' ?>" type="submit">
                                    <?= $member['active'] ? 'Desactivar' : 'Activar' ?>
                                </button>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section class="card">
        <h2>Agregar empleado</h2>
        <form method="post" class="form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="staff_add">
            <div class="grid-2 tight">
                <label class="field">
                    <span>Nombre</span>
                    <input class="input" type="text" name="name" required maxlength="100">
                </label>
                <label class="field">
                    <span>PIN (4 a 6 números)</span>
                    <input class="input" type="password" name="pin" inputmode="numeric" pattern="\d{4,6}" maxlength="6" required>
                </label>
                <label class="field">
                    <span>Rol</span>
                    <select class="input" name="role">
                        <option value="staff">Mostrador</option>
                        <option value="admin">Administrador</option>
                    </select>
                </label>
            </div>
            <button class="btn btn-primary" type="submit">Agregar</button>
        </form>
    </section>
<?php endif; ?>
<?php page_end(); ?>
