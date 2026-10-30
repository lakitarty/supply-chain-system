<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/validation.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/notifications.php';

function retailer_promotion_actor(PDO $pdo, bool $canManage = false): array
{
    $actor = require_login($pdo);

    if (($actor['organization_type'] ?? '') !== 'RETAILER') {
        respond_error(403, 'Retailer promotions are available only to retailer organisations.');
    }

    $allowedRoles = $canManage ? ['ADMIN', 'BRANCH_ADMIN'] : ['ADMIN', 'BRANCH_ADMIN', 'STAFF'];
    if (!in_array((string)($actor['role'] ?? ''), $allowedRoles, true)) {
        respond_error(403, 'Your account does not have access to this promotions action.');
    }

    if (($actor['role'] ?? '') === 'BRANCH_ADMIN') {
        if (empty($actor['branch_id'])) {
            respond_error(403, 'Your account must be assigned to an active branch.');
        }
        $statement = $pdo->prepare(
            "SELECT 1 FROM branches
             WHERE branch_id = ? AND organization_id = ? AND status = 'ACTIVE' LIMIT 1"
        );
        $statement->execute([(int)$actor['branch_id'], (int)$actor['organization_id']]);
        if (!$statement->fetchColumn()) {
            respond_error(403, 'Your assigned branch is not active.');
        }
    }

    return $actor;
}

function retailer_promotion_allowed_branches(PDO $pdo, array $actor): array
{
    if (($actor['role'] ?? '') === 'BRANCH_ADMIN' || ($actor['role'] ?? '') === 'STAFF') {
        $statement = $pdo->prepare(
            "SELECT branch_id, branch_name, location
             FROM branches
             WHERE organization_id = ? AND branch_id = ? AND status = 'ACTIVE'
             ORDER BY branch_name"
        );
        $statement->execute([(int)$actor['organization_id'], (int)($actor['branch_id'] ?? 0)]);
    } else {
        $statement = $pdo->prepare(
            "SELECT branch_id, branch_name, location
             FROM branches WHERE organization_id = ? AND status = 'ACTIVE'
             ORDER BY is_main_branch DESC, branch_name"
        );
        $statement->execute([(int)$actor['organization_id']]);
    }

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function retailer_promotion_today(): string
{
    return (new DateTimeImmutable('now', new DateTimeZone('Africa/Nairobi')))->format('Y-m-d');
}

function retailer_promotion_display_status(array $promotion, string $today): string
{
    $status = (string)($promotion['status'] ?? 'DRAFT');
    if (in_array($status, ['DRAFT', 'CANCELLED', 'COMPLETED'], true)) {
        return $status;
    }
    if ($today < (string)$promotion['start_date']) {
        return 'SCHEDULED';
    }
    if ($today > (string)$promotion['end_date']) {
        return 'COMPLETED';
    }
    return 'ACTIVE';
}

function retailer_promotion_money(string $value): ?string
{
    if (!preg_match('/^(\d{1,10})(?:\.(\d{1,2}))?$/', trim($value), $m)) {
        return null;
    }
    $fraction = str_pad($m[2] ?? '', 2, '0');
    return $m[1] . '.' . $fraction;
}

function retailer_promotion_price_for_date(
    PDO $pdo,
    int $retailerId,
    int $branchId,
    int $productVersionId,
    string $date
): ?array {
    $statement = $pdo->prepare(
        "SELECT rbp.product_id, rbp.selling_price, rbp.price_basis,
                p.product_name, p.status AS product_status,
                pv.pack_size, pv.effective_from AS version_effective_from,
                pv.effective_to AS version_effective_to, pv.is_current
         FROM retailer_branch_prices rbp
         INNER JOIN branches b ON b.branch_id = rbp.branch_id
             AND b.organization_id = rbp.retailer_id AND b.status = 'ACTIVE'
         INNER JOIN products p ON p.product_id = rbp.product_id AND p.status = 'ACTIVE'
         INNER JOIN product_versions pv ON pv.product_id = rbp.product_id
             AND pv.product_version_id = rbp.product_version_id
         WHERE rbp.retailer_id = ? AND rbp.branch_id = ? AND rbp.product_version_id = ?
           AND rbp.status = 'ACTIVE'
           AND rbp.effective_from <= ?
           AND (rbp.effective_to IS NULL OR rbp.effective_to >= ?)
           AND pv.effective_from <= ?
           AND (pv.effective_to IS NULL OR pv.effective_to >= ?)
         ORDER BY rbp.effective_from DESC, rbp.retailer_branch_price_id DESC
         LIMIT 1"
    );
    $statement->execute([
        $retailerId, $branchId, $productVersionId,
        $date, $date, $date, $date
    ]);
    $row = $statement->fetch(PDO::FETCH_ASSOC);
    return $row ?: null;
}

function retailer_promotion_scope_allowed(PDO $pdo, array $actor, int $promotionId): bool
{
    if (($actor['role'] ?? '') === 'ADMIN') {
        return true;
    }
    if (($actor['role'] ?? '') !== 'BRANCH_ADMIN') {
        return false;
    }
    if (empty($actor['branch_id'])) {
        return false;
    }

    $statement = $pdo->prepare(
        "SELECT COUNT(*) AS total_items,
                SUM(CASE WHEN branch_id = ? THEN 1 ELSE 0 END) AS own_items
         FROM retailer_promotion_items
         WHERE retailer_promotion_id = ? AND retailer_id = ?"
    );
    $statement->execute([
        (int)$actor['branch_id'], $promotionId, (int)$actor['organization_id']
    ]);
    $row = $statement->fetch(PDO::FETCH_ASSOC) ?: ['total_items' => 0, 'own_items' => 0];

    if ((int)$row['total_items'] === 0) {
        $owner = $pdo->prepare(
            'SELECT created_by FROM retailer_promotions WHERE retailer_promotion_id = ? AND retailer_id = ?'
        );
        $owner->execute([$promotionId, (int)$actor['organization_id']]);
        return (int)$owner->fetchColumn() === (int)$actor['user_id'];
    }
    return (int)$row['total_items'] === (int)$row['own_items'];
}
