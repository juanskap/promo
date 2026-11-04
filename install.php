<?php
/**
 * Instalador. Abre esta página una sola vez en el navegador:
 *   http://localhost/promo/install.php
 */
declare(strict_types=1);

define('INSTALL_MODE', true);
require __DIR__ . '/includes/app.php';

$driver = $CONFIG['db']['driver'];
$errors = [];
$done    = false;

function schema_statements(string $driver): array
{
    if ($driver === 'sqlite') {
        return [
            "CREATE TABLE IF NOT EXISTS customers (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT NOT NULL UNIQUE,
                name TEXT NOT NULL,
                phone TEXT NOT NULL UNIQUE,
                pin_hash TEXT NOT NULL,
                document TEXT NULL,
                birthday TEXT NULL,
                stamps INTEGER NOT NULL DEFAULT 0,
                total_stamps INTEGER NOT NULL DEFAULT 0,
                rewards_earned INTEGER NOT NULL DEFAULT 0,
                referred_by INTEGER NULL,
                opt_in INTEGER NOT NULL DEFAULT 1,
                last_visit_at TEXT NULL,
                last_seen_notice_at TEXT NULL,
                birthday_notified_year INTEGER NULL,
                created_at TEXT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS staff (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                name TEXT NOT NULL,
                phone TEXT NULL,
                pin_hash TEXT NOT NULL,
                role TEXT NOT NULL DEFAULT 'staff',
                active INTEGER NOT NULL DEFAULT 1
            )",
            "CREATE TABLE IF NOT EXISTS visits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id INTEGER NOT NULL,
                staff_id INTEGER NULL,
                amount NUMERIC NOT NULL DEFAULT 0,
                stamps INTEGER NOT NULL DEFAULT 1,
                note TEXT NULL,
                created_at TEXT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS coupons (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id INTEGER NOT NULL,
                code TEXT NOT NULL UNIQUE,
                type TEXT NOT NULL DEFAULT 'free_caldo',
                description TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'active',
                expires_at TEXT NOT NULL,
                used_at TEXT NULL,
                used_by_staff INTEGER NULL,
                expiry_notified_at TEXT NULL,
                created_at TEXT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS notifications (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                customer_id INTEGER NULL,
                template_key TEXT NOT NULL,
                message TEXT NOT NULL,
                status TEXT NOT NULL DEFAULT 'queued',
                error TEXT NULL,
                scheduled_at TEXT NULL,
                sent_at TEXT NULL,
                created_at TEXT NOT NULL
            )",
            "CREATE TABLE IF NOT EXISTS settings (
                k TEXT PRIMARY KEY,
                v TEXT NOT NULL
            )",
        ];
    }

    return [
        "CREATE TABLE IF NOT EXISTS customers (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            code VARCHAR(12) NOT NULL UNIQUE,
            name VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL UNIQUE,
            pin_hash VARCHAR(255) NOT NULL,
            document VARCHAR(20) NULL,
            birthday DATE NULL,
            stamps TINYINT UNSIGNED NOT NULL DEFAULT 0,
            total_stamps INT UNSIGNED NOT NULL DEFAULT 0,
            rewards_earned INT UNSIGNED NOT NULL DEFAULT 0,
            referred_by INT UNSIGNED NULL,
            opt_in TINYINT(1) NOT NULL DEFAULT 1,
            last_visit_at DATETIME NULL,
            last_seen_notice_at DATETIME NULL,
            birthday_notified_year SMALLINT NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS staff (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NULL,
            pin_hash VARCHAR(255) NOT NULL,
            role VARCHAR(20) NOT NULL DEFAULT 'staff',
            active TINYINT(1) NOT NULL DEFAULT 1
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS visits (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id INT UNSIGNED NOT NULL,
            staff_id INT UNSIGNED NULL,
            amount DECIMAL(10,2) NOT NULL DEFAULT 0,
            stamps TINYINT UNSIGNED NOT NULL DEFAULT 1,
            note VARCHAR(160) NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS coupons (
            id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id INT UNSIGNED NOT NULL,
            code VARCHAR(12) NOT NULL UNIQUE,
            type VARCHAR(30) NOT NULL DEFAULT 'free_caldo',
            description VARCHAR(160) NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'active',
            expires_at DATETIME NOT NULL,
            used_at DATETIME NULL,
            used_by_staff INT UNSIGNED NULL,
            expiry_notified_at DATETIME NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS notifications (
            id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
            customer_id INT UNSIGNED NULL,
            template_key VARCHAR(40) NOT NULL,
            message TEXT NOT NULL,
            status VARCHAR(20) NOT NULL DEFAULT 'queued',
            error VARCHAR(255) NULL,
            scheduled_at DATETIME NULL,
            sent_at DATETIME NULL,
            created_at DATETIME NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS settings (
            k VARCHAR(50) PRIMARY KEY,
            v TEXT NOT NULL
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
    ];
}

function schema_indexes(): array
{
    return [
        'CREATE INDEX idx_visits_customer ON visits (customer_id)',
        'CREATE INDEX idx_visits_created ON visits (created_at)',
        'CREATE INDEX idx_coupons_customer ON coupons (customer_id)',
        'CREATE INDEX idx_coupons_status ON coupons (status)',
        'CREATE INDEX idx_notifications_status ON notifications (status)',
        'CREATE INDEX idx_customers_last_visit ON customers (last_visit_at)',
    ];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    try {
        if ($driver === 'mysql') {
            $db = $CONFIG['db'];
            $root = new PDO(
                sprintf('mysql:host=%s;port=%d;charset=utf8mb4', $db['host'], (int)$db['port']),
                $db['user'],
                $db['pass'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $root->exec(
                'CREATE DATABASE IF NOT EXISTS `' . str_replace('`', '', $db['name']) .
                '` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci'
            );
        }

        $pdo = db();

        foreach (schema_statements($driver) as $sql) {
            $pdo->exec($sql);
        }
        foreach (schema_indexes() as $sql) {
            try {
                $pdo->exec($sql);
            } catch (Throwable $e) {
                // El índice ya existe: no es un problema.
            }
        }

        // Migración suave para instalaciones anteriores.
        try {
            $pdo->exec('ALTER TABLE customers ADD COLUMN document ' . ($driver === 'sqlite' ? 'TEXT NULL' : 'VARCHAR(20) NULL'));
        } catch (Throwable $e) {
            // La columna ya existe.
        }

        $defaults = [
            'business_name'      => 'Cabaña Delfina',
            'tagline'            => 'Calentito, nutritivo y hecho con amor para ti',
            'hero_text'          => 'Caldo de salchicha tradicional, casero y con ingredientes seleccionados. Ideal para días fríos, almuerzos y para compartir en familia.',
            'price_caldo'        => '$3.50',
            'address'            => 'Guaranda, Bolívar',
            'hours'              => 'Sábados y domingos',
            'delivery_info'      => 'Llevamos tu caldo calientito a domicilio en Guaranda.',
            'delivery_cost'      => '+$1,50',
            'address_note'       => 'Estamos arreglando el local físico. Confirma el punto de entrega por WhatsApp.',
            'reservations'       => 'Entre semana puedes reservar para eventos: cumpleaños, reuniones y pedidos grandes.',
            'notice'             => 'Abrimos sábados y domingos · Entre semana, reservas para eventos',
            'whatsapp_orders'    => '',
            'instagram'          => '',
            'reward_text'        => 'caldo de salchicha gratis',
            'stamps_required'    => '10',
            'milestones'         => '3,6,9',
            'notify_every_stamp' => '0',
            'coupon_valid_days'  => '30',
            'inactive_days'      => '21',
            'country_code'       => '593',
            'currency'           => '$',
            'whatsapp_mode'      => 'link',
            'wa_cloud_token'     => '',
            'wa_cloud_phone_id'  => '',
            'cron_key'           => bin2hex(random_bytes(12)),
        ];
        foreach ($defaults as $key => $value) {
            if (!q('SELECT 1 FROM settings WHERE k = ?', [$key])->fetchColumn()) {
                q('INSERT INTO settings (k, v) VALUES (?, ?)', [$key, $value]);
            }
        }

        if (!(int)q('SELECT COUNT(*) FROM staff')->fetchColumn()) {
            q(
                'INSERT INTO staff (name, pin_hash, role, active) VALUES (?, ?, ?, 1)',
                ['Administrador', password_hash('1234', PASSWORD_DEFAULT), 'admin']
            );
            q(
                'INSERT INTO staff (name, pin_hash, role, active) VALUES (?, ?, ?, 1)',
                ['Mostrador', password_hash('1111', PASSWORD_DEFAULT), 'staff']
            );
        }

        $dataDir = dirname($CONFIG['db']['sqlite_path']);
        if (!is_dir($dataDir)) {
            mkdir($dataDir, 0775, true);
        }
        file_put_contents($dataDir . '/.installed', $driver . ' ' . date('c'));

        $done = true;
    } catch (Throwable $e) {
        $errors[] = $e->getMessage();
    }
}

$dataDir = dirname($CONFIG['db']['sqlite_path']);
$checks  = [
    'PHP 8.0 o superior'          => version_compare(PHP_VERSION, '8.0.0', '>='),
    'Extensión PDO'               => extension_loaded('pdo'),
    'Controlador de base de datos' => $driver === 'sqlite'
        ? extension_loaded('pdo_sqlite')
        : extension_loaded('pdo_mysql'),
    'Carpeta /data escribible'    => is_dir($dataDir) ? is_writable($dataDir) : is_writable(dirname($dataDir)),
];
$installed = is_file($dataDir . '/.installed');

page_start('Instalación', 'public');
?>
<section class="hero">
    <h1>Instalar <?= e(setting('business_name', 'la app')) ?></h1>
    <p class="lead">Un solo paso para dejar lista la base de datos y los usuarios iniciales.</p>
</section>

<?php foreach ($errors as $error): ?>
    <div class="alert alert-error"><?= e($error) ?></div>
<?php endforeach; ?>

<section class="card">
    <h2>Revisión del entorno</h2>
    <ul class="checklist">
        <?php foreach ($checks as $label => $ok): ?>
            <li class="<?= $ok ? 'ok' : 'bad' ?>">
                <span><?= $ok ? '✔' : '✖' ?></span> <?= e($label) ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <p class="muted">Motor de base de datos: <strong><?= e($driver) ?></strong></p>
</section>

<?php if ($done): ?>
    <section class="card">
        <h2>¡Listo! 🎉</h2>
        <p>La aplicación quedó instalada.</p>
        <table class="table">
            <tr><th>Panel de clientes</th><td><a href="<?= e(url('index.php')) ?>"><?= e(url('index.php')) ?></a></td></tr>
            <tr><th>Mostrador (empleados)</th><td><a href="<?= e(url('staff/index.php')) ?>"><?= e(url('staff/index.php')) ?></a></td></tr>
            <tr><th>Administración</th><td><a href="<?= e(url('admin/index.php')) ?>"><?= e(url('admin/index.php')) ?></a></td></tr>
        </table>
        <h3>Usuarios iniciales</h3>
        <table class="table">
            <tr><th>Administrador</th><td>PIN <code>1234</code></td></tr>
            <tr><th>Mostrador</th><td>PIN <code>1111</code></td></tr>
        </table>
        <p class="alert alert-warn">Cambia estos PIN en <strong>Admin → Ajustes y equipo</strong> antes de usar la app con clientes reales.</p>
        <a class="btn btn-primary" href="<?= e(url('index.php')) ?>">Ir a la aplicación</a>
    </section>
<?php else: ?>
    <section class="card">
        <?php if ($installed): ?>
            <p class="alert alert-info">Ya existe una instalación. Puedes volver a ejecutar el instalador sin perder datos.</p>
        <?php endif; ?>
        <form method="post">
            <?= csrf_field() ?>
            <button type="submit" class="btn btn-primary btn-block">Instalar ahora</button>
        </form>
    </section>
<?php endif; ?>
<?php page_end(); ?>
