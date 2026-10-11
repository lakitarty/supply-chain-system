<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

require_post();
start_secure_session();
sign_out();

respond(200, [
    'success' => true,
    'message' => 'Signed out.'
]);