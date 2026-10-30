<?php

declare(strict_types=1);

function allocate_lpo_document(PDO $pdo, int $organizationId): array
{
    if (!$pdo->inTransaction()) {
        throw new LogicException('LPO numbering must run inside a database transaction.');
    }

    $organizationLock = $pdo->prepare(
        "SELECT organization_id
         FROM organizations
         WHERE organization_id = ?
         FOR UPDATE"
    );
    $organizationLock->execute([$organizationId]);

    if (!$organizationLock->fetchColumn()) {
        throw new RuntimeException('The LPO issuing organisation could not be found.');
    }

    $lastNumberStatement = $pdo->prepare(
        "SELECT lpo_number
         FROM supply_orders
         WHERE retailer_id = ?
           AND lpo_number REGEXP '^LPO-[0-9]+$'
         ORDER BY CAST(SUBSTRING(lpo_number, 5) AS UNSIGNED) DESC,
                  supply_order_id DESC
         LIMIT 1
         FOR UPDATE"
    );
    $lastNumberStatement->execute([$organizationId]);
    $lastNumber = $lastNumberStatement->fetchColumn();

    $lastSequence = 0;

    if (is_string($lastNumber)
        && preg_match('/^LPO-([0-9]+)$/', $lastNumber, $matches) === 1) {
        $lastSequence = (int)$matches[1];
    }

    $validityStatement = $pdo->query(
        "SELECT DATE_SUB(
            DATE_ADD(CURDATE(), INTERVAL 1 MONTH),
            INTERVAL 1 DAY
         )"
    );

    $validUntil = $validityStatement->fetchColumn();

    if (!$validUntil) {
        throw new RuntimeException('Could not calculate the LPO validity date.');
    }

    return [
        'lpo_number' => sprintf('LPO-%06d', $lastSequence + 1),
        'lpo_valid_until' => (string)$validUntil
    ];
}