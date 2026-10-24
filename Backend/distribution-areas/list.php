<?php

declare(strict_types=1);

require_once __DIR__ . '/../core/bootstrap.php';

try {
    $statement = $pdo->query(
        "SELECT area_id, area_name
         FROM distribution_areas
         WHERE status = 'ACTIVE'
         ORDER BY area_name"
    );

    respond(200, [
        'success' => true,
        'areas' => $statement->fetchAll(PDO::FETCH_ASSOC)
    ]);
} catch (PDOException $e) {
    log_error('distribution areas list', $e);
    respond_error(500, 'Unable to load the county list.');
}
