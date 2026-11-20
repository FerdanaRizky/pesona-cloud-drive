<?php
namespace App\Libraries;

/**
 * Optional Office-to-PDF preview adapter.
 * Requires LibreOffice (soffice) on the server. Original uploads are never modified.
 */
class OfficePreview
{
    public const MIME_TYPES = [
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'application/vnd.oasis.opendocument.text',
        'application/vnd.oasis.opendocument.spreadsheet',
        'application/vnd.oasis.opendocument.presentation',
    ];

    public static function supports(array $file): bool
    {
        $extensions = ['doc','docx','xls','xlsx','ppt','pptx','odt','ods','odp'];
        return in_array(strtolower((string)($file['extension'] ?? '')), $extensions, true)
            || in_array((string)($file['mime_type'] ?? ''), self::MIME_TYPES, true);
    }

    public static function convert(array $file): ?string
    {
        $source = (string)($file['storage_path'] ?? '');
        if ($source === '' || !is_file($source) || filesize($source) > 52428800 || !function_exists('proc_open')) return null;
        $binary = trim((string) env('office.sofficePath', 'soffice'));
        if ($binary === '') return null;
        $cache = WRITEPATH . 'cache/office-preview';
        if (!is_dir($cache) && !mkdir($cache, 0750, true) && !is_dir($cache)) return null;
        $key = hash('sha256', $source . '|' . filemtime($source) . '|' . filesize($source));
        $pdf = $cache . '/' . $key . '.pdf';
        if (is_file($pdf) && filesize($pdf) > 0) return $pdf;
        $profile = $cache . '/profile-' . bin2hex(random_bytes(6));
        if (!mkdir($profile, 0700, true) && !is_dir($profile)) return null;
        $command = [$binary, '-env:UserInstallation=file://' . $profile, '--headless', '--convert-to', 'pdf', '--outdir', $cache, $source];
        $pipes = [];
        $process = @proc_open($command, [0=>['pipe','r'],1=>['pipe','w'],2=>['pipe','w']], $pipes, null, ['HOME'=>$cache]);
        if (!is_resource($process)) { @rmdir($profile); return null; }
        fclose($pipes[0]);
        stream_set_timeout($pipes[1], 60);
        stream_set_timeout($pipes[2], 60);
        $stdout = stream_get_contents($pipes[1]);
        $stderr = stream_get_contents($pipes[2]);
        fclose($pipes[1]); fclose($pipes[2]);
        $status = proc_close($process);
        @rmdir($profile);
        $generated = $cache . '/' . pathinfo($source, PATHINFO_FILENAME) . '.pdf';
        if ($status !== 0 || !is_file($generated) || filesize($generated) < 1) {
            log_message('warning', 'Office preview conversion failed: {error} {output}', ['error'=>$stderr, 'output'=>$stdout]);
            return null;
        }
        if ($generated !== $pdf && !@rename($generated, $pdf)) {
            if (!@copy($generated, $pdf)) return null;
            @unlink($generated);
        }
        return is_file($pdf) ? $pdf : null;
    }
}