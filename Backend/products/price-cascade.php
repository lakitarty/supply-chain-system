<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../core/notifications.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/price-confirmation.php';
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

    $signature = trim((string)($input['plan_signature'] ?? ''));

    if (!$versionId || $versionId < 1) {
        respond_error(400, 'Select a product version.');
    }

    if ($price === false) {
        respond_error(400, 'Enter a manufacturer price above zero with at most two decimal places.');
    }

    if (!preg_match('/^[a-f0-9]{64}$/i', $signature)) {
        respond_error(400, 'Run the price simulation and review the proposed changes first.');
    }

    require_price_confirmation_password(
        $pdo,
        $actor,
        (string)($input['price_confirmation_password'] ?? '')
    );

    $pdo->beginTransaction();

    $plan = price_cascade_build_plan(
        $pdo,
        $actor,
        (int)$versionId,
        (float)$price,
        true
    );

    if (!hash_equals((string)$plan['signature'], $signature)) {
        $pdo->rollBack();
        respond_error(
            409,
            'Prices or supply relationships changed after the preview. Simulate the change again before applying it.'
        );
    }

    $counts = price_cascade_apply_plan($pdo, $actor, $plan);
    $pdo->commit();

    respond(200, [
        'success' => true,
        'message' => 'The price change was processed. This response contains only your organisation’s pricing; participating organisations receive private notifications about their own adjustments.',
        'counts' => $counts,
        'plan' => price_cascade_public_plan($plan, $actor)
    ]);
} catch (DomainException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    $status = (int)$e->getCode();
    if ($status < 400 || $status > 599) {
        $status = 400;
    }

    respond_error($status, $e->getMessage());
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('price cascade apply', $e);
    respond_error(500, 'Unable to apply the price cascade. No partial price change was committed.');
}