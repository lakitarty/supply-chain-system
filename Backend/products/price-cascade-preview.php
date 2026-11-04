<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/price-cascade-rules.php';
require_once __DIR__ . '/pricing-rules.php';

require_post();

try {
    $actor = require_login($pdo);
    $input = read_json_body();

    $versionId = filter_var(
        $input['product_version_id'] ?? null,
        FILTER_VALIDATE_INT
    );

    $priceText = parse_price_amount(trim((string)($input['mfg_new_price'] ?? '')));
    $price = $priceText === null ? false : (float)$priceText;

    if (!$versionId || $versionId < 1) {
        respond_error(400, 'Select a product version.');
    }

    if ($price === false) {
        respond_error(400, 'Enter a manufacturer price above zero with at most two decimal places.');
    }

    $plan = price_cascade_build_plan($pdo, $actor, (int)$versionId, (float)$price);

    respond(200, [
        'success' => true,
        'plan' => price_cascade_public_plan($plan, $actor)
    ]);
} catch (DomainException $e) {
    $status = (int)$e->getCode();
    if ($status < 400 || $status > 599) {
        $status = 400;
    }
    respond_error($status, $e->getMessage());
} catch (Throwable $e) {
    log_error('price cascade preview', $e);
    respond_error(500, 'Unable to build the price cascade preview.');
}