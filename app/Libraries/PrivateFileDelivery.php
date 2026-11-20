<?php
namespace App\Libraries;

/**
 * Delegates private-file delivery to Nginx when X-Accel-Redirect is enabled.
 * PHP still performs all authentication, ownership, token and expiry checks.
 */
class PrivateFileDelivery
{
    public static function response($response, string $path, string $mime, string $filename, string $disposition = 'inline')
    {
        if (!filter_var(env('storage.xAccelEnabled', false), FILTER_VALIDATE_BOOLEAN)) {
            return null;
        }

        $realFile = realpath($path);
        if ($realFile === false || !is_file($realFile) || !is_readable($realFile)) {
            return null;
        }

        $privateRoot = realpath((string) env('storage.privatePath', WRITEPATH . 'private'));
        $previewRoot = realpath(WRITEPATH . 'cache/office-preview');
        $prefix = null;
        $relative = null;

        foreach ([
            [$privateRoot, '/_cloud_drive_private/'],
            [$previewRoot, '/_cloud_drive_preview/'],
        ] as [$root, $uriPrefix]) {
            if ($root && str_starts_with($realFile, rtrim($root, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR)) {
                $prefix = $uriPrefix;
                $relative = substr($realFile, strlen(rtrim($root, DIRECTORY_SEPARATOR)) + 1);
                break;
            }
        }

        if ($prefix === null || $relative === null || $relative === '') {
            log_message('warning', 'X-Accel-Redirect skipped: file is outside configured private roots.');
            return null;
        }

        $segments = explode(DIRECTORY_SEPARATOR, $relative);
        $encodedPath = implode('/', array_map('rawurlencode', $segments));
        $safeName = basename(str_replace(["\r", "\n", '"'], '', $filename));
        $fallbackName = preg_replace('/[^A-Za-z0-9._-]/', '_', $safeName) ?: 'download';
        $contentDisposition = $disposition . '; filename="' . $fallbackName . '"; filename*=UTF-8\'\'' . rawurlencode($safeName);

        return $response
            ->setHeader('X-Accel-Redirect', $prefix . $encodedPath)
            ->setHeader('Content-Type', $mime ?: 'application/octet-stream')
            ->setHeader('Content-Disposition', $contentDisposition)
            ->setHeader('X-Content-Type-Options', 'nosniff')
            ->setHeader('Cache-Control', 'private, no-store')
            ->setHeader('Accept-Ranges', 'bytes');
    }
}