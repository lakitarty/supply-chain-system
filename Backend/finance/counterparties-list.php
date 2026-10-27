<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/helpers.php';

try {
    $actor = require_admin($pdo);
    $organizationId = (int)$actor['organization_id'];
    $today = finance_today();

    $statement = $pdo->prepare(
        "SELECT DISTINCT o.organization_id, o.organization_name, o.organization_type
         FROM organizations o
         WHERE o.organization_id <> ?
           AND o.status = 'ACTIVE'
           AND EXISTS (
               SELECT 1
               FROM organization_relationships r
               WHERE r.status = 'ACTIVE'
                 AND r.relationship_type = 'SUPPLIES'
                 AND r.start_date <= ?
                 AND (r.end_date IS NULL OR r.end_date >= ?)
                 AND (
                     (r.source_organization_id = ? AND r.target_organization_id = o.organization_id)
                     OR
                     (r.target_organization_id = ? AND r.source_organization_id = o.organization_id)
                 )
           )
         ORDER BY o.organization_name"
    );
    $statement->execute([$organizationId, $today, $today, $organizationId, $organizationId]);

    respond(200, [
        'success' => true,
        'organization_id' => $organizationId,
        'counterparties' => $statement->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (Throwable $e) {
    log_error('finance counterparties list', $e);
    respond_error(500, 'Unable to load connected organisations.');
}