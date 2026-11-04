<?php
declare(strict_types=1);
require __DIR__ . '/includes/app.php';

unset($_SESSION['customer_id']);
session_regenerate_id(true);
flash('ok', 'Cerraste sesión. ¡Vuelve pronto!');
redirect('index.php');
