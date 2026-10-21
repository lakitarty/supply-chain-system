<?php

declare(strict_types=1);

function organization_logo_directory(): string
{
    return dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'organization-logos';
}

function valid_organization_logo_path(?string $path): bool
{
    return is_string($path) &&
        preg_match('~\Auploads/organization-logos/org-[0-9]+-[a-f0-9]{32}\.(?:jpg|png|webp)\z~D', $path) === 1;
}

function delete_organization_logo_file(?string $path): void
{
    if (!valid_organization_logo_path($path)) {
        return;
    }

    $file = organization_logo_directory() . DIRECTORY_SEPARATOR . basename($path);

    if (is_file($file)) {
        @unlink($file);
    }
}