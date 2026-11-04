<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';
require_once __DIR__ . '/../core/guard.php';
require_once __DIR__ . '/../core/audit.php';
require_once __DIR__ . '/../core/notifications.php';
require_once __DIR__ . '/../core/price-confirmation.php';
require_once __DIR__ . '/../products/pricing-rules.php';
require_once __DIR__ . '/network-rules.php';
require_once __DIR__ . '/supply-arrangement-rules.php';

const RELATIONSHIP_NOTICE_DAYS = 14;
const MANUFACTURING_PARTY_TYPES = ['MANUFACTURER', 'MANUFACTURER_SUPPLIER'];
const SUPPLYING_PARTY_TYPES = ['SUPPLIER', 'MANUFACTURER_SUPPLIER'];

function relationship_appointments(PDO $pdo, int $manufacturerId, int $supplierId, string $today): array
{
    $statement = $pdo->prepare(
        "SELECT ps.product_supplier_id, ps.product_id, ps.area_id, p.product_name, area.area_name
         FROM product_suppliers ps
         INNER JOIN products p ON p.product_id = ps.product_id
         LEFT JOIN distribution_areas area ON area.area_id = ps.area_id
         WHERE p.manufacturer_id = ?
           AND ps.supplier_id = ?
           AND ps.status = 'ACTIVE'
           AND (ps.end_date IS NULL OR ps.end_date >= ?)
         ORDER BY p.product_name, ps.product_supplier_id"
    );
    $statement->execute([$manufacturerId, $supplierId, $today]);

    return $statement->fetchAll(PDO::FETCH_ASSOC);
}

function appointment_has_replacement(PDO $pdo, int $manufacturerId, array $appointment, int $supplierId, string $noticeEnd): bool
{
    $statement = $pdo->prepare(
        "SELECT 1
         FROM product_suppliers ps
         INNER JOIN organizations replacement
            ON replacement.organization_id = ps.supplier_id AND replacement.status = 'ACTIVE'
         INNER JOIN organization_relationships r
            ON r.source_organization_id = ?
           AND r.target_organization_id = ps.supplier_id
           AND r.relationship_type = 'SUPPLIES'
           AND r.status = 'ACTIVE'
           AND r.start_date <= ?
           AND (r.end_date IS NULL OR r.end_date >= ?)
         WHERE ps.product_id = ?
           AND ps.area_id <=> ?
           AND ps.supplier_id <> ?
           AND ps.status = 'ACTIVE'
           AND ps.start_date <= ?
           AND (ps.end_date IS NULL OR ps.end_date >= ?)
         LIMIT 1"
    );
    $statement->execute([
        $manufacturerId,
        $noticeEnd,
        $noticeEnd,
        (int)$appointment['product_id'],
        $appointment['area_id'] === null ? null : (int)$appointment['area_id'],
        $supplierId,
        $noticeEnd,
        $noticeEnd
    ]);

    return $statement->fetchColumn() !== false;
}

function appointment_label(array $appointment): string
{
    return $appointment['product_name'] . ' (' . ($appointment['area_name'] ?? 'no county') . ')';
}

function set_notice_end_dates(PDO $pdo, int $manufacturerId, int $supplierId, ?string $newEnd, ?string $matchEnd): void
{
    if ($newEnd !== null) {
        $appointments = $pdo->prepare(
            "UPDATE product_suppliers ps
             INNER JOIN products p ON p.product_id = ps.product_id
             SET ps.end_date = ?
             WHERE p.manufacturer_id = ? AND ps.supplier_id = ? AND ps.status = 'ACTIVE'
               AND (ps.end_date IS NULL OR ps.end_date > ?)"
        );
        $appointments->execute([$newEnd, $manufacturerId, $supplierId, $newEnd]);

        $offers = $pdo->prepare(
            "UPDATE supplier_product_offers o
             INNER JOIN products p ON p.product_id = o.product_id
             SET o.effective_to = ?
             WHERE p.manufacturer_id = ? AND o.supplier_id = ? AND o.status = 'ACTIVE'
               AND o.effective_from <= ? AND (o.effective_to IS NULL OR o.effective_to > ?)"
        );
        $offers->execute([$newEnd, $manufacturerId, $supplierId, $newEnd, $newEnd]);

        return;
    }

    $appointments = $pdo->prepare(
        "UPDATE product_suppliers ps
         INNER JOIN products p ON p.product_id = ps.product_id
         SET ps.end_date = NULL
         WHERE p.manufacturer_id = ? AND ps.supplier_id = ? AND ps.status = 'ACTIVE' AND ps.end_date = ?"
    );
    $appointments->execute([$manufacturerId, $supplierId, $matchEnd]);

    $offers = $pdo->prepare(
        "UPDATE supplier_product_offers o
         INNER JOIN products p ON p.product_id = o.product_id
         SET o.effective_to = NULL
         WHERE p.manufacturer_id = ? AND o.supplier_id = ? AND o.status = 'ACTIVE' AND o.effective_to = ?"
    );
    $offers->execute([$manufacturerId, $supplierId, $matchEnd]);
}

require_post();

try {
    $actor = require_admin($pdo);
    $body = read_json_body();
    $input = clean_network_input($body);
    $withdraw = ($body['action'] ?? 'end') === 'withdraw';
    $organizationId = (int)$actor['organization_id'];

    $statement = $pdo->prepare(
        "SELECT r.relationship_id, r.status, r.requested_by_organization_id,
                r.source_organization_id, r.target_organization_id,
                source.organization_type AS source_type, source.organization_name AS source_name,
                target.organization_type AS target_type, target.organization_name AS target_name
         FROM organization_relationships r
         INNER JOIN organizations source ON source.organization_id = r.source_organization_id
         INNER JOIN organizations target ON target.organization_id = r.target_organization_id
         WHERE r.relationship_id = ?
           AND (r.source_organization_id = ? OR r.target_organization_id = ?)
         LIMIT 1"
    );
    $statement->execute([$input['relationshipId'], $organizationId, $organizationId]);
    $relationship = $statement->fetch(PDO::FETCH_ASSOC);

    if ($relationship === false) {
        respond_error(404, 'Relationship not found.');
    }

    $noticeFlow = $relationship['status'] === 'ACTIVE'
        && in_array($relationship['source_type'], MANUFACTURING_PARTY_TYPES, true)
        && in_array($relationship['target_type'], SUPPLYING_PARTY_TYPES, true);

    if (!$noticeFlow) {
        if ($withdraw) {
            respond_error(409, 'There is no ending notice to withdraw.');
        }

        $blocked = end_block($relationship, $actor);

        if ($blocked !== null) {
            respond_error(409, $blocked);
        }

        $pdo->beginTransaction();
        set_relationship_status($pdo, $input['relationshipId'], 'INACTIVE');
        record_audit($pdo, $actor, 'RELATIONSHIP_ENDED', 'RELATIONSHIP', $input['relationshipId'], 'Ended a supply relationship');
        $pdo->commit();

        respond(200, ['success' => true, 'message' => 'Relationship ended.']);
    }

    require_price_confirmation_password(
        $pdo,
        $actor,
        (string)($body['adminPassword'] ?? ''),
        $withdraw ? 'notice withdrawal' : 'relationship notice'
    );

    $today = nairobi_today();
    $noticeEnd = (new DateTimeImmutable($today))->modify('+' . RELATIONSHIP_NOTICE_DAYS . ' days')->format('Y-m-d');
    $relationshipId = (int)$relationship['relationship_id'];
    $manufacturerId = (int)$relationship['source_organization_id'];
    $supplierId = (int)$relationship['target_organization_id'];
    $manufacturerName = (string)$relationship['source_name'];
    $supplierName = (string)$relationship['target_name'];
    $initiatedBySupplier = $organizationId === $supplierId;
    $initiator = $initiatedBySupplier ? $supplierName : $manufacturerName;

    $pdo->beginTransaction();

    $statement = $pdo->prepare('SELECT status, end_date FROM organization_relationships WHERE relationship_id = ? FOR UPDATE');
    $statement->execute([$relationshipId]);
    $current = $statement->fetch(PDO::FETCH_ASSOC);
    $currentEnd = $current['end_date'];

    if ($current['status'] !== 'ACTIVE') {
        $pdo->rollBack();
        respond_error(409, 'This relationship has already ended.');
    }

    $appointments = relationship_appointments($pdo, $manufacturerId, $supplierId, $today);
    $retailerProducts = [];

    foreach ($appointments as $appointment) {
        foreach (supply_arrangements($pdo, $today, null, (int)$appointment['product_supplier_id']) as $arrangement) {
            $retailerProducts[(int)$arrangement['retailer_id']][appointment_label($appointment)] = true;
        }
    }

    if ($withdraw) {
        if ($currentEnd === null || $currentEnd < $today) {
            $pdo->rollBack();
            respond_error(409, 'There is no ending notice to withdraw.');
        }

        set_notice_end_dates($pdo, $manufacturerId, $supplierId, null, (string)$currentEnd);
        $statement = $pdo->prepare('UPDATE organization_relationships SET end_date = NULL WHERE relationship_id = ?');
        $statement->execute([$relationshipId]);

        $auditAction = 'RELATIONSHIP_NOTICE_WITHDRAWN';
        $title = 'Supply relationship ending withdrawn';
        $partyMessage = "The notice to end the supply relationship between {$manufacturerName} and {$supplierName} was withdrawn by {$initiator}. Supply continues.";
        $result = 'Notice withdrawn. The relationship continues.';
        $endsOn = null;
    } else {
        if ($currentEnd !== null && $currentEnd >= $today) {
            $pdo->rollBack();
            respond_error(409, "This relationship already ends on {$currentEnd}. Withdraw that notice first.");
        }

        $unreplaced = [];

        foreach ($appointments as $appointment) {
            if (!appointment_has_replacement($pdo, $manufacturerId, $appointment, $supplierId, $noticeEnd)) {
                $unreplaced[] = appointment_label($appointment);
            }
        }

        if (!$initiatedBySupplier && $unreplaced !== []) {
            $pdo->rollBack();
            $message = 'Appoint other suppliers before ending this relationship: ' . implode(', ', $unreplaced) . '.';
            respond(409, [
                'success' => false,
                'code' => 'REPLACEMENT_REQUIRED',
                'message' => $message,
                'error' => $message
            ]);
        }

        set_notice_end_dates($pdo, $manufacturerId, $supplierId, $noticeEnd, null);
        $statement = $pdo->prepare('UPDATE organization_relationships SET end_date = ? WHERE relationship_id = ?');
        $statement->execute([$noticeEnd, $relationshipId]);

        $gap = $unreplaced === []
            ? 'Replacement suppliers are in place for every affected product.'
            : 'Replacements are still needed for: ' . implode(', ', $unreplaced) . '.';
        $auditAction = 'RELATIONSHIP_NOTICE_GIVEN';
        $title = 'Supply relationship ending';
        $partyMessage = "{$initiator} gave notice to end the supply relationship between {$manufacturerName} and {$supplierName}. It ends on {$noticeEnd}, and the appointments and offers between them end on the same date. {$gap}";
        $result = "Notice given. The relationship ends on {$noticeEnd}.";
        $endsOn = $noticeEnd;
    }

    record_audit($pdo, $actor, $auditAction, 'RELATIONSHIP', $relationshipId, $partyMessage);
    notify_organizations($pdo, [$manufacturerId, $supplierId], $relationshipId, $title, $partyMessage);

    foreach ($retailerProducts as $retailerId => $labels) {
        $listText = implode(', ', array_keys($labels));
        $retailerMessage = $withdraw
            ? "{$supplierName} will continue supplying: {$listText}. The notice to stop supplying has been withdrawn."
            : "{$supplierName} will stop supplying {$listText} on {$noticeEnd}. Connect with another appointed supplier to keep receiving these products.";
        notify_organizations($pdo, [(int)$retailerId], $relationshipId, $title, $retailerMessage);
    }

    $pdo->commit();

    respond(200, ['success' => true, 'message' => $result, 'ends_on' => $endsOn]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    log_error('network end', $e);
    respond_error(500, 'Unable to update the relationship.');
}
