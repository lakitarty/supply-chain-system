<?php
declare(strict_types=1);

function receiving_resolve_branch(
    PDO $pdo,
    array $actor,
    ?int $requestedBranchId = null
): array {
    $role = $actor['role'] ?? '';

    if (!in_array($role, ['ADMIN', 'BRANCH_ADMIN', 'STAFF'], true)) {
        respond_error(403, 'Your account does not have access to receiving.');
    }

    $organizationId = (int)($actor['organization_id'] ?? 0);

    if ($organizationId < 1) {
        respond_error(403, 'Your organisation could not be identified.');
    }

    if ($role === 'ADMIN') {
        if ($requestedBranchId !== null) {
            $branchId = $requestedBranchId;
        } else {
            $statement = $pdo->prepare(
                "SELECT branch_id
                 FROM branches
                 WHERE organization_id = ?
                   AND status = 'ACTIVE'
                   AND is_main_branch = 1
                 LIMIT 1"
            );

            $statement->execute([$organizationId]);
            $branchId = $statement->fetchColumn();

            if ($branchId === false) {
                respond_error(404, 'No active main branch was found.');
            }

            $branchId = (int)$branchId;
        }
    } else {
        $assignedBranchId = (int)($actor['branch_id'] ?? 0);

        if ($assignedBranchId < 1) {
            respond_error(403, 'Your account is not assigned to a branch.');
        }

        if (
            $requestedBranchId !== null
            && $requestedBranchId !== $assignedBranchId
        ) {
            respond_error(403, 'You cannot access the selected branch.');
        }

        $branchId = $assignedBranchId;
    }

    $statement = $pdo->prepare(
        "SELECT
            branch_id,
            organization_id,
            branch_name,
            is_main_branch
         FROM branches
         WHERE branch_id = ?
           AND organization_id = ?
           AND status = 'ACTIVE'
         LIMIT 1"
    );

    $statement->execute([$branchId, $organizationId]);
    $branch = $statement->fetch(PDO::FETCH_ASSOC);

    if (!$branch) {
        respond_error(404, 'The selected active branch was not found.');
    }

    $branch['branch_id'] = (int)$branch['branch_id'];
    $branch['organization_id'] = (int)$branch['organization_id'];
    $branch['is_main_branch'] = (bool)$branch['is_main_branch'];

    return $branch;
}