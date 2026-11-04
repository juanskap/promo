<?php
/**
 * Tareas automáticas. Ejecutar una vez al día.
 *   Navegador: /promo/cron.php?key=CLAVE
 *   CLI:       php cron.php
 */
declare(strict_types=1);
require __DIR__ . '/includes/app.php';

$isCli = PHP_SAPI === 'cli';

if (!$isCli) {
    $provided = (string)($_GET['key'] ?? '');
    $expected = setting('cron_key', '');
    if ($expected === '' || !hash_equals($expected, $provided)) {
        http_response_code(403);
        exit('Clave inválida.');
    }
}

header('Content-Type: text/plain; charset=utf-8');
$log = [];

$now = time();

/* 1. Cupones vencidos y por vencer ---------------------------------- */
q("UPDATE coupons SET status = 'expired' WHERE status = 'active' AND expires_at < ?", [now()]);

$expiring = q(
    "SELECT * FROM coupons
      WHERE status = 'active'
        AND expiry_notified_at IS NULL
        AND expires_at BETWEEN ? AND ?",
    [now(), date('Y-m-d H:i:s', strtotime('+2 days'))]
)->fetchAll();

foreach ($expiring as $coupon) {
    $days = max(0, (int)ceil((strtotime((string)$coupon['expires_at']) - $now) / 86400));
    queue_notification((int)$coupon['customer_id'], 'expiring', [
        'code'  => $coupon['code'],
        'days'  => $days,
    ]);
    q('UPDATE coupons SET expiry_notified_at = ? WHERE id = ?', [now(), (int)$coupon['id']]);
}
$log[] = 'Cupones por vencer avisados: ' . count($expiring);

/* 2. Clientes inactivos --------------------------------------------- */
$inactiveDays = max(1, (int)setting('inactive_days', '21'));
$limit        = date('Y-m-d H:i:s', strtotime('-' . $inactiveDays . ' days'));
$noticeLimit  = date('Y-m-d H:i:s', strtotime('-15 days'));

$inactive = q(
    'SELECT * FROM customers
      WHERE last_visit_at IS NOT NULL
        AND last_visit_at < ?
        AND created_at < ?
        AND (last_seen_notice_at IS NULL OR last_seen_notice_at < ?)',
    [$limit, $limit, $noticeLimit]
)->fetchAll();

foreach ($inactive as $customer) {
    queue_notification((int)$customer['id'], 'inactive');
    q('UPDATE customers SET last_seen_notice_at = ? WHERE id = ?', [now(), (int)$customer['id']]);
}
$log[] = 'Clientes inactivos recordados: ' . count($inactive);

/* 3. Cumpleaños ------------------------------------------------------ */
$todayMd = date('m-d');
$year    = (int)date('Y');
$birthdays = q(
    'SELECT * FROM customers WHERE birthday IS NOT NULL AND birthday <> ?',
    ['']
)->fetchAll();

$birthdayCount = 0;
foreach ($birthdays as $customer) {
    $md = date('m-d', strtotime((string)$customer['birthday']));
    if ($md !== $todayMd) {
        continue;
    }
    if ((int)($customer['birthday_notified_year'] ?? 0) === $year) {
        continue;
    }
    queue_notification((int)$customer['id'], 'birthday');
    q('UPDATE customers SET birthday_notified_year = ? WHERE id = ?', [$year, (int)$customer['id']]);
    $birthdayCount++;
}
$log[] = 'Felicitaciones de cumpleaños: ' . $birthdayCount;

/* 4. Enviar la cola si la API está configurada ---------------------- */
$sent = 0;
$failed = 0;

if (setting('whatsapp_mode', 'link') === 'cloud'
    && setting('wa_cloud_token', '') !== ''
    && setting('wa_cloud_phone_id', '') !== '') {
    $queue = q(
        "SELECT n.*, c.phone
           FROM notifications n
           JOIN customers c ON c.id = n.customer_id
          WHERE n.status = 'queued'
       ORDER BY n.created_at ASC
          LIMIT 30"
    )->fetchAll();

    foreach ($queue as $notification) {
        [$ok, $error] = send_whatsapp_cloud((string)$notification['phone'], (string)$notification['message']);
        if ($ok) {
            q("UPDATE notifications SET status = 'sent', sent_at = ?, error = NULL WHERE id = ?", [now(), (int)$notification['id']]);
            $sent++;
        } else {
            q("UPDATE notifications SET status = 'failed', error = ? WHERE id = ?", [mb_substr($error, 0, 250), (int)$notification['id']]);
            $failed++;
        }
    }
}
$log[] = 'Mensajes enviados por API: ' . $sent . ' (fallidos: ' . $failed . ')';

/* 5. Resumen --------------------------------------------------------- */
$queue = (int)q("SELECT COUNT(*) FROM notifications WHERE status = 'queued'")->fetchColumn();
$log[] = 'Mensajes en cola para envío manual: ' . $queue;

echo implode(PHP_EOL, $log) . PHP_EOL;
