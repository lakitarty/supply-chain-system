<?php

declare(strict_types=1);
ini_set('display_errors', '0');
header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: no-store');

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/responses.php';
require_once __DIR__ . '/session.php';