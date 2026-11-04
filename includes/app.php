<?php
/**
 * Núcleo de la aplicación: sesión, base de datos, autenticación,
 * fidelidad, cupones, notificaciones y layout.
 */
declare(strict_types=1);

if (basename((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === 'app.php') {
    http_response_code(403);
    exit('Acceso directo no permitido.');
}

$CONFIG = require dirname(__DIR__) . '/config.php';
date_default_timezone_set($CONFIG['timezone'] ?? 'America/Bogota');

if (session_status() !== PHP_SESSION_ACTIVE) {
    session_name('promocabana');
    session_set_cookie_params([
        'lifetime' => 0,
        'path'     => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

/* ------------------------------------------------------------------ *
 *  Base de datos
 * ------------------------------------------------------------------ */

function db(): PDO
{
    static $pdo = null;
    if ($pdo instanceof PDO) {
        return $pdo;
    }

    global $CONFIG;
    $db = $CONFIG['db'];

    if ($db['driver'] === 'sqlite') {
        $dir = dirname($db['sqlite_path']);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $pdo = new PDO('sqlite:' . $db['sqlite_path']);
        $pdo->exec('PRAGMA foreign_keys = ON');
        $pdo->exec('PRAGMA busy_timeout = 5000');
    } else {
        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $db['host'],
            (int)$db['port'],
            $db['name']
        );
        $pdo = new PDO($dsn, $db['user'], $db['pass']);
    }

    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

    return $pdo;
}

function q(string $sql, array $params = []): PDOStatement
{
    $st = db()->prepare($sql);
    $st->execute($params);
    return $st;
}

/* ------------------------------------------------------------------ *
 *  Ajustes
 * ------------------------------------------------------------------ */

function settings_all(): array
{
    if (!isset($GLOBALS['__settings'])) {
        $GLOBALS['__settings'] = [];
        try {
            foreach (db()->query('SELECT k, v FROM settings') as $row) {
                $GLOBALS['__settings'][$row['k']] = $row['v'];
            }
        } catch (Throwable $e) {
            // Instalación pendiente: se usan los valores por defecto.
        }
    }
    return $GLOBALS['__settings'];
}

function setting(string $key, string $default = ''): string
{
    $all = settings_all();
    return array_key_exists($key, $all) ? (string)$all[$key] : $default;
}

function set_setting(string $key, string $value): void
{
    $st = q('UPDATE settings SET v = ? WHERE k = ?', [$value, $key]);
    if ($st->rowCount() === 0) {
        q('INSERT INTO settings (k, v) VALUES (?, ?)', [$key, $value]);
    }
    $GLOBALS['__settings'][$key] = $value;
}

/* ------------------------------------------------------------------ *
 *  Utilidades
 * ------------------------------------------------------------------ */

function e(?string $text): string
{
    return htmlspecialchars((string)$text, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function now(): string
{
    return date('Y-m-d H:i:s');
}

function fmt_date(?string $date): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date('d/m/Y', $ts) : '—';
}

function fmt_datetime(?string $date): string
{
    if (!$date) {
        return '—';
    }
    $ts = strtotime($date);
    return $ts ? date('d/m/Y H:i', $ts) : '—';
}

function money(float $amount): string
{
    return setting('currency', '$') . ' ' . number_format($amount, 0, ',', '.');
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function base_url(): string
{
    global $CONFIG;

    if (!empty($CONFIG['public_url'])) {
        return rtrim($CONFIG['public_url'], '/');
    }

    $https  = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
    $scheme = $https ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
    $base   = rtrim((string)($CONFIG['app_base'] ?? ''), '/');

    return $scheme . '://' . $host . $base;
}

function url(string $path = ''): string
{
    return base_url() . '/' . ltrim($path, '/');
}

function normalize_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone) ?? '';
    $digits = ltrim($digits, '0');

    return $digits;
}

function wa_number(string $phone): string
{
    $digits = normalize_phone($phone);
    if (strlen($digits) <= 10) {
        $digits = preg_replace('/\D+/', '', setting('country_code', '57')) . $digits;
    }
    return $digits;
}

function wa_link(string $phone, string $message): string
{
    return 'https://wa.me/' . wa_number($phone) . '?text=' . rawurlencode($message);
}

function unique_code(string $table, int $length = 6): string
{
    $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    do {
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $alphabet[random_int(0, strlen($alphabet) - 1)];
        }
        $exists = q("SELECT 1 FROM {$table} WHERE code = ?", [$code])->fetchColumn();
    } while ($exists);

    return $code;
}

/* ------------------------------------------------------------------ *
 *  CSRF y mensajes flash
 * ------------------------------------------------------------------ */

function csrf_token(): string
{
    if (empty($_SESSION['csrf'])) {
        $_SESSION['csrf'] = bin2hex(random_bytes(20));
    }
    return $_SESSION['csrf'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf" value="' . e(csrf_token()) . '">';
}

function csrf_verify(): void
{
    $sent = (string)($_POST['csrf'] ?? '');
    if (!hash_equals((string)($_SESSION['csrf'] ?? ''), $sent)) {
        http_response_code(419);
        exit('La sesión expiró. Recarga la página e intenta de nuevo.');
    }
}

function flash(string $type, string $message): void
{
    $_SESSION['flash'][] = ['type' => $type, 'msg' => $message];
}

function take_flashes(): array
{
    $items = $_SESSION['flash'] ?? [];
    unset($_SESSION['flash']);
    return $items;
}

/* ------------------------------------------------------------------ *
 *  Autenticación
 * ------------------------------------------------------------------ */

function current_customer(): ?array
{
    static $cache = false;

    if ($cache !== false) {
        return $cache;
    }

    $id = (int)($_SESSION['customer_id'] ?? 0);
    $cache = $id > 0 ? find_customer_by_id($id) : null;

    return $cache;
}

function require_customer(): array
{
    $c = current_customer();
    if (!$c) {
        redirect('index.php?tab=login');
    }
    return $c;
}

function login_customer(int $id): void
{
    session_regenerate_id(true);
    $_SESSION['customer_id'] = $id;
    unset($_SESSION['staff_id'], $_SESSION['staff_role'], $_SESSION['staff_name']);
}

function current_staff(): ?array
{
    static $cache = false;

    if ($cache !== false) {
        return $cache;
    }

    $id = (int)($_SESSION['staff_id'] ?? 0);
    $cache = null;
    if ($id > 0) {
        $row = q('SELECT * FROM staff WHERE id = ? AND active = 1', [$id])->fetch();
        $cache = $row ?: null;
    }

    return $cache;
}

function require_staff(?string $role = null): array
{
    $s = current_staff();
    if (!$s) {
        redirect('staff/index.php');
    }
    if ($role === 'admin' && $s['role'] !== 'admin') {
        http_response_code(403);
        exit('No tienes permiso para entrar aquí.');
    }
    return $s;
}

function login_staff(array $staff): void
{
    session_regenerate_id(true);
    $_SESSION['staff_id']   = (int)$staff['id'];
    $_SESSION['staff_role'] = $staff['role'];
    $_SESSION['staff_name'] = $staff['name'];
}

/* ------------------------------------------------------------------ *
 *  Clientes
 * ------------------------------------------------------------------ */

function find_customer_by_id(int $id): ?array
{
    if ($id <= 0) {
        return null;
    }
    $row = q('SELECT * FROM customers WHERE id = ?', [$id])->fetch();
    return $row ?: null;
}

function find_customer(string $term): ?array
{
    $term = trim($term);
    if ($term === '') {
        return null;
    }

    $phone = normalize_phone($term);
    $code  = strtoupper($term);

    $row = q(
        'SELECT * FROM customers WHERE phone = ? OR code = ? OR document = ? LIMIT 1',
        [$phone, $code, $phone]
    )->fetch();

    return $row ?: null;
}

function register_customer(
    string $name,
    string $phone,
    string $pin,
    ?string $birthday,
    string $referral,
    bool $optIn,
    string $document = ''
): array {
    $name     = trim($name);
    $phone    = normalize_phone($phone);
    $document = normalize_phone($document);

    if (mb_strlen($name) < 2) {
        return ['ok' => false, 'error' => 'Escribe tu nombre.'];
    }
    if (strlen($phone) < 7 || strlen($phone) > 15) {
        return ['ok' => false, 'error' => 'Escribe un número de celular válido.'];
    }
    if (!preg_match('/^\d{4,6}$/', $pin)) {
        return ['ok' => false, 'error' => 'El PIN debe tener entre 4 y 6 números.'];
    }
    if ($document !== '' && (strlen($document) < 5 || strlen($document) > 15)) {
        return ['ok' => false, 'error' => 'La cédula debe tener entre 5 y 15 números.'];
    }
    if ($birthday !== null && $birthday !== '') {
        $d = DateTime::createFromFormat('Y-m-d', $birthday);
        if (!$d || $d->format('Y-m-d') !== $birthday) {
            return ['ok' => false, 'error' => 'La fecha de cumpleaños no es válida.'];
        }
    } else {
        $birthday = null;
    }
    if (q('SELECT 1 FROM customers WHERE phone = ?', [$phone])->fetchColumn()) {
        return ['ok' => false, 'error' => 'Ese número ya está registrado. Ingresa con tu PIN.'];
    }
    if ($document !== '' && q('SELECT 1 FROM customers WHERE document = ?', [$document])->fetchColumn()) {
        return ['ok' => false, 'error' => 'Ya existe un cliente registrado con esa cédula.'];
    }

    $referrer = null;
    if (trim($referral) !== '') {
        $referrer = find_customer($referral);
        if (!$referrer) {
            return ['ok' => false, 'error' => 'El código de referido no existe.'];
        }
    }

    q(
        'INSERT INTO customers (code, name, phone, pin_hash, document, birthday, opt_in, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)',
        [
            unique_code('customers', 8),
            $name,
            $phone,
            password_hash($pin, PASSWORD_DEFAULT),
            $document !== '' ? $document : null,
            $birthday,
            $optIn ? 1 : 0,
            now(),
        ]
    );

    $id = (int)db()->lastInsertId();

    if ($referrer) {
        q('UPDATE customers SET referred_by = ? WHERE id = ?', [(int)$referrer['id'], $id]);
        add_stamp((int)$referrer['id'], null, 0, 'Referido: ' . $name);
    }

    queue_notification($id, 'welcome');

    return ['ok' => true, 'id' => $id];
}

function verify_customer_pin(array $customer, string $pin): bool
{
    return password_verify($pin, (string)$customer['pin_hash']);
}

/* ------------------------------------------------------------------ *
 *  Fidelidad y cupones
 * ------------------------------------------------------------------ */

function create_coupon(int $customerId, string $type, string $description): array
{
    $days    = max(1, (int)setting('coupon_valid_days', '30'));
    $code    = unique_code('coupons', 6);
    $expires = date('Y-m-d H:i:s', strtotime('+' . $days . ' days'));

    q(
        'INSERT INTO coupons (customer_id, code, type, description, status, expires_at, created_at)
         VALUES (?, ?, ?, ?, ?, ?, ?)',
        [$customerId, $code, $type, $description, 'active', $expires, now()]
    );

    return [
        'id'         => (int)db()->lastInsertId(),
        'code'       => $code,
        'expires_at' => $expires,
        'description'=> $description,
    ];
}

/**
 * Suma un sello al cliente, registra la venta y genera los cupones ganados.
 */
function add_stamp(int $customerId, ?int $staffId, float $amount = 0, string $note = ''): array
{
    $required = max(1, (int)setting('stamps_required', '10'));
    $pdo      = db();

    $pdo->beginTransaction();
    try {
        $customer = q('SELECT * FROM customers WHERE id = ?', [$customerId])->fetch();
        if (!$customer) {
            throw new RuntimeException('Cliente no encontrado.');
        }

        q(
            'INSERT INTO visits (customer_id, staff_id, amount, stamps, note, created_at)
             VALUES (?, ?, ?, 1, ?, ?)',
            [$customerId, $staffId, $amount, $note !== '' ? $note : null, now()]
        );

        $stamps  = (int)$customer['stamps'] + 1;
        $rewards = [];

        while ($stamps >= $required) {
            $stamps -= $required;
            $rewards[] = create_coupon(
                $customerId,
                'free_caldo',
                setting('reward_text', 'caldo de salchicha gratis')
            );
        }

        q(
            'UPDATE customers
                SET stamps = ?, total_stamps = total_stamps + 1,
                    rewards_earned = rewards_earned + ?, last_visit_at = ?
              WHERE id = ?',
            [$stamps, count($rewards), now(), $customerId]
        );

        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }

    foreach ($rewards as $reward) {
        queue_notification($customerId, 'reward', [
            'code'    => $reward['code'],
            'expires' => fmt_date($reward['expires_at']),
        ]);
    }

    $milestones = array_map('intval', explode(',', setting('milestones', '3,6,9')));
    $notifyEvery = setting('notify_every_stamp', '0') === '1';

    if ($rewards === [] && ($notifyEvery || in_array($stamps, $milestones, true))) {
        queue_notification($customerId, 'stamp', [
            'stamps'  => $stamps,
            'missing' => max(0, $required - $stamps),
        ]);
    }

    return [
        'customer' => find_customer_by_id($customerId),
        'stamps'   => $stamps,
        'required' => $required,
        'rewards'  => $rewards,
    ];
}

function redeem_coupon(string $code, ?int $staffId): array
{
    $code = strtoupper(trim($code));
    if ($code === '') {
        return ['ok' => false, 'error' => 'Escribe el código del cupón.'];
    }

    $coupon = q('SELECT * FROM coupons WHERE code = ?', [$code])->fetch();
    if (!$coupon) {
        return ['ok' => false, 'error' => 'Ese código no existe.'];
    }
    if ($coupon['status'] === 'used') {
        return ['ok' => false, 'error' => 'Ese cupón ya fue canjeado el ' . fmt_datetime($coupon['used_at']) . '.'];
    }
    if ($coupon['status'] === 'expired' || strtotime((string)$coupon['expires_at']) < time()) {
        q("UPDATE coupons SET status = 'expired' WHERE id = ?", [(int)$coupon['id']]);
        return ['ok' => false, 'error' => 'Ese cupón está vencido.'];
    }

    q(
        "UPDATE coupons SET status = 'used', used_at = ?, used_by_staff = ? WHERE id = ?",
        [now(), $staffId, (int)$coupon['id']]
    );
    q(
        'INSERT INTO visits (customer_id, staff_id, amount, stamps, note, created_at)
         VALUES (?, ?, 0, 0, ?, ?)',
        [(int)$coupon['customer_id'], $staffId, 'Canje cupón ' . $code, now()]
    );

    return ['ok' => true, 'coupon' => $coupon];
}

function customer_coupons(int $customerId, ?string $status = null): array
{
    if ($status !== null) {
        return q(
            'SELECT * FROM coupons WHERE customer_id = ? AND status = ? ORDER BY created_at DESC',
            [$customerId, $status]
        )->fetchAll();
    }
    return q(
        'SELECT * FROM coupons WHERE customer_id = ? ORDER BY created_at DESC',
        [$customerId]
    )->fetchAll();
}

/* ------------------------------------------------------------------ *
 *  WhatsApp
 * ------------------------------------------------------------------ */

function notification_templates(): array
{
    return [
        'welcome'  => '¡Hola {name}! 🍲 Bienvenido a {business}. Ya tienes tu tarjeta digital: {link}',
        'stamp'    => '¡Llevas {stamps}/{required} sellos en {business}! Te faltan {missing} para tu {reward}. {link}',
        'reward'   => '🎉 ¡Ganaste tu {reward} en {business}! Código: {code}. Válido hasta {expires}. Muéstralo en el local. {link}',
        'expiring' => '⏳ Tu {reward} vence en {days} días. Código {code}. ¡No lo dejes perder! {link}',
        'inactive' => '¡Te extrañamos en {business}! Vuelve esta semana y tu próximo sello cuenta doble 🍲 {link}',
        'birthday' => '🎂 ¡Feliz cumpleaños, {name}! Este mes tu caldo va con regalo. Te esperamos en {business}. {link}',
    ];
}

function notification_message(string $templateKey, array $vars): string
{
    $templates = notification_templates();
    if (!isset($templates[$templateKey])) {
        return '';
    }
    return strtr($templates[$templateKey], $vars);
}

function notification_vars(array $customer, array $extra = []): array
{
    $vars = [
        '{name}'     => (string)$customer['name'],
        '{business}' => setting('business_name', 'Mi Cabaña'),
        '{link}'     => url('card.php'),
        '{required}' => setting('stamps_required', '10'),
        '{reward}'   => setting('reward_text', 'caldo de salchicha gratis'),
    ];

    foreach ($extra as $key => $value) {
        $vars['{' . $key . '}'] = (string)$value;
    }

    return $vars;
}

function queue_notification(int $customerId, string $templateKey, array $extra = []): void
{
    $customer = find_customer_by_id($customerId);
    if (!$customer || (int)$customer['opt_in'] !== 1) {
        return;
    }

    $message = notification_message($templateKey, notification_vars($customer, $extra));
    if ($message === '') {
        return;
    }

    q(
        "INSERT INTO notifications (customer_id, template_key, message, status, created_at)
         VALUES (?, ?, ?, 'queued', ?)",
        [$customerId, $templateKey, $message, now()]
    );
}

/**
 * Envía por la API oficial de WhatsApp Cloud (opcional).
 * Devuelve [bool exito, string error].
 */
function send_whatsapp_cloud(string $phone, string $message): array
{
    $token   = setting('wa_cloud_token', '');
    $phoneId = setting('wa_cloud_phone_id', '');

    if ($token === '' || $phoneId === '') {
        return [false, 'Falta configurar el token y el phone_id de WhatsApp Cloud.'];
    }

    $payload = json_encode([
        'messaging_product' => 'whatsapp',
        'to'                => wa_number($phone),
        'type'              => 'text',
        'text'              => ['preview_url' => true, 'body' => $message],
    ], JSON_UNESCAPED_UNICODE);

    $endpoint = 'https://graph.facebook.com/v20.0/' . rawurlencode($phoneId) . '/messages';

    if (function_exists('curl_init')) {
        $ch = curl_init($endpoint);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 20,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . $token,
                'Content-Type: application/json',
            ],
        ]);
        $response = curl_exec($ch);
        $status   = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            return [false, $error ?: 'No se pudo conectar con la API.'];
        }
        if ($status >= 200 && $status < 300) {
            return [true, ''];
        }
        return [false, 'HTTP ' . $status . ': ' . mb_substr((string)$response, 0, 200)];
    }

    $context = stream_context_create([
        'http' => [
            'method'  => 'POST',
            'header'  => "Authorization: Bearer {$token}\r\nContent-Type: application/json\r\n",
            'content' => $payload,
            'timeout' => 20,
        ],
    ]);
    $response = @file_get_contents($endpoint, false, $context);
    if ($response === false) {
        return [false, 'No se pudo conectar con la API.'];
    }
    return [true, ''];
}

function pending_notifications(int $limit = 100): array
{
    return q(
        "SELECT n.*, c.name, c.phone
           FROM notifications n
           JOIN customers c ON c.id = n.customer_id
          WHERE n.status = 'queued'
       ORDER BY n.created_at ASC
          LIMIT " . (int)$limit
    )->fetchAll();
}

/* ------------------------------------------------------------------ *
 *  Layout
 * ------------------------------------------------------------------ */

function page_start(string $title, string $area = 'public'): void
{
    $business = setting('business_name', 'Mi Cabaña');
    $customer = current_customer();
    $staff    = current_staff();
    ?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($title) ?> · <?= e($business) ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/app.css')) ?>">
    <link rel="manifest" href="<?= e(url('assets/manifest.webmanifest')) ?>">
    <meta name="theme-color" content="#c2410c">
    <link rel="icon" href="<?= e(url('assets/icon.svg')) ?>" type="image/svg+xml">
</head>
<body class="area-<?= e($area) ?>">
<header class="topbar">
    <a class="brand" href="<?= e(url('index.php')) ?>">
        <span class="brand-mark">🍲</span>
        <span class="brand-name"><?= e($business) ?></span>
    </a>
    <nav class="nav">
        <?php if ($area === 'staff' && $staff): ?>
            <a href="<?= e(url('staff/index.php')) ?>">Mostrador</a>
            <?php if ($staff['role'] === 'admin'): ?>
                <a href="<?= e(url('admin/index.php')) ?>">Admin</a>
            <?php endif; ?>
            <a class="nav-out" href="<?= e(url('staff/logout.php')) ?>">Salir</a>
        <?php elseif ($area === 'admin' && $staff): ?>
            <a href="<?= e(url('staff/index.php')) ?>">Mostrador</a>
            <a href="<?= e(url('admin/index.php')) ?>">Admin</a>
            <a class="nav-out" href="<?= e(url('staff/logout.php')) ?>">Salir</a>
        <?php elseif ($customer): ?>
            <a href="<?= e(url('card.php')) ?>">Mi tarjeta</a>
            <a class="nav-out" href="<?= e(url('logout.php')) ?>">Salir</a>
        <?php else: ?>
            <a href="<?= e(url('index.php?tab=login')) ?>">Ingresar</a>
            <a class="nav-cta" href="<?= e(url('index.php?tab=registro')) ?>">Registrarme</a>
        <?php endif; ?>
    </nav>
</header>
<main class="wrap">
<?php foreach (take_flashes() as $f): ?>
    <div class="alert alert-<?= e($f['type']) ?>"><?= e($f['msg']) ?></div>
<?php endforeach; ?>
<?php
}

function page_end(): void
{
    ?>
</main>
<footer class="footer">
    <p>Hecho con cariño para nuestra clientela 🍲</p>
</footer>
<script>window.APP_SW = <?= json_encode(url('assets/sw.js')) ?: "''" ?>;</script>
<script src="<?= e(url('assets/app.js')) ?>" defer></script>
</body>
</html>
<?php
}

/* ------------------------------------------------------------------ *
 *  Instalación pendiente
 * ------------------------------------------------------------------ */

if (!defined('INSTALL_MODE') && !is_file(dirname(__DIR__) . '/data/.installed')) {
    header('Location: ' . url('install.php'));
    exit;
}
