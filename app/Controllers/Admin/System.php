<?php
namespace App\Controllers\Admin;

use App\Controllers\BaseController;
use CodeIgniter\CodeIgniter;

class System extends BaseController
{
    public function index()
    {
        $os = $this->readOsRelease();
        $memory = $this->readMemory();
        $diskPath = is_dir(WRITEPATH) ? WRITEPATH : ROOTPATH;
        $diskTotal = @disk_total_space($diskPath);
        $diskFree = @disk_free_space($diskPath);
        $dbVersion = 'Tidak tersedia';
        try {
            $dbVersion = (string) db_connect()->getVersion();
        } catch (\Throwable $e) {
            // Informasi sistem tetap dapat dibuka jika versi DB tidak dapat dibaca.
        }

        $data = [
            'title' => 'Informasi Sistem',
            'server' => [
                'hostname' => gethostname() ?: 'Tidak tersedia',
                'os' => $os['PRETTY_NAME'] ?? php_uname('s'),
                'kernel' => php_uname('r'),
                'architecture' => php_uname('m'),
                'cpu' => $this->countCpuCores(),
                'memory' => $memory,
                'uptime' => $this->readUptime(),
                'disk_total' => is_numeric($diskTotal) ? $this->formatBytes((int) $diskTotal) : 'Tidak tersedia',
                'disk_free' => is_numeric($diskFree) ? $this->formatBytes((int) $diskFree) : 'Tidak tersedia',
                'disk_used_percent' => (is_numeric($diskTotal) && $diskTotal > 0 && is_numeric($diskFree))
                    ? round((($diskTotal - $diskFree) / $diskTotal) * 100, 1) : null,
            ],
            'runtime' => [
                'webserver' => $this->detectWebServer(),
                'php' => PHP_VERSION,
                'php_sapi' => PHP_SAPI,
                'database' => $dbVersion,
                'codeigniter' => CodeIgniter::CI_VERSION,
                'openssl' => defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'Tidak tersedia',
                'timezone' => date_default_timezone_get(),
                'environment' => ENVIRONMENT,
                'server_time' => date('Y-m-d H:i:s T'),
                'docker' => is_file('/.dockerenv') || is_dir('/run/.containerenv') ? 'Berjalan di container' : 'Tidak terdeteksi',
            ],
            'panel' => [
                'installed' => is_dir('/www/server/panel'),
                'version' => $this->readAaPanelVersion(),
            ],
        ];

        return view('admin/system', $data);
    }

    private function readOsRelease(): array
    {
        $file = '/etc/os-release';
        if (!is_readable($file)) return [];
        $values = [];
        foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
            if (strpos($line, '=') === false) continue;
            [$key, $value] = explode('=', $line, 2);
            $values[$key] = trim($value, " \t\n\r\0\x0B\"'");
        }
        return $values;
    }

    private function readMemory(): array
    {
        $values = [];
        if (is_readable('/proc/meminfo')) {
            foreach (file('/proc/meminfo', FILE_IGNORE_NEW_LINES) ?: [] as $line) {
                if (preg_match('/^(MemTotal|MemAvailable):\s+(\d+)/', $line, $match)) {
                    $values[$match[1]] = (int) $match[2] * 1024;
                }
            }
        }
        $total = $values['MemTotal'] ?? 0;
        $available = $values['MemAvailable'] ?? null;
        return [
            'total' => $total > 0 ? $this->formatBytes($total) : 'Tidak tersedia',
            'used' => ($total > 0 && $available !== null) ? $this->formatBytes(max(0, $total - $available)) : 'Tidak tersedia',
            'used_percent' => ($total > 0 && $available !== null) ? round((($total - $available) / $total) * 100, 1) : null,
        ];
    }

    private function countCpuCores(): string
    {
        if (is_readable('/proc/cpuinfo')) {
            $count = preg_match_all('/^processor\s*:/m', (string) file_get_contents('/proc/cpuinfo'));
            if ($count > 0) return (string) $count . ' core';
        }
        return 'Tidak tersedia';
    }

    private function readUptime(): string
    {
        if (!is_readable('/proc/uptime')) return 'Tidak tersedia';
        $raw = (string) file_get_contents('/proc/uptime');
        $seconds = (int) floor((float) strtok($raw, ' '));
        $days = intdiv($seconds, 86400);
        $hours = intdiv($seconds % 86400, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        return ($days ? $days . ' hari ' : '') . $hours . ' jam ' . $minutes . ' menit';
    }

    private function detectWebServer(): string
    {
        $software = trim((string) ($_SERVER['SERVER_SOFTWARE'] ?? ''));
        if ($software !== '') return $software;
        if (function_exists('apache_get_version')) {
            $version = @apache_get_version();
            if ($version) return $version;
        }
        return 'Tidak dapat dideteksi dari PHP';
    }

    private function readAaPanelVersion(): string
    {
        if (!is_dir('/www/server/panel')) return 'Tidak terpasang / tidak terdeteksi';
        foreach (['/www/server/panel/data/version.pl', '/www/server/panel/data/version.txt'] as $file) {
            if (is_readable($file)) {
                $version = trim((string) file_get_contents($file));
                if ($version !== '') return $version;
            }
        }
        return 'Terpasang, versi tidak dapat dibaca';
    }

    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        $size = (float) $bytes;
        $unit = 0;
        while ($size >= 1024 && $unit < count($units) - 1) {
            $size /= 1024;
            $unit++;
        }
        return number_format($size, 2) . ' ' . $units[$unit];
    }
}
