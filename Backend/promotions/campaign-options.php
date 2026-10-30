<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/promotion-rules.php';

try {
    $actor = promotion_require_manufacturer($pdo);
    $organizationId = (int)$actor['organization_id'];
    $effectiveDate = trim((string)($_GET['effective_date'] ?? finance_today()));
    if (!valid_date($effectiveDate)) {
        respond_error(400, 'A valid campaign effective date is required.');
    }

    respond(200, [
        'success' => true,
        'effective_date' => $effectiveDate,
        'products' => promotion_own_products($pdo, $organizationId, $effectiveDate),
        'retailers' => promotion_eligible_retailers($pdo, $organizationId)
    ]);
} catch (Throwable $e) {
    log_error('promotion options', $e);
    respond_error(500, 'Unable to load promotion options.');
}
