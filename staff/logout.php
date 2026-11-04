<?php
declare(strict_types=1);
require __DIR__ . '/../includes/app.php';

unset($_SESSION['staff_id'], $_SESSION['staff_role'], $_SESSION['staff_name']);
session_regenerate_id(true);
redirect('staff/index.php');
