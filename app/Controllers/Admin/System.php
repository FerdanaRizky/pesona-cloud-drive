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
        $diskPath = WRITEPATH;
        $diskTotal = $this->safeDiskSpace($diskPath, true);
        $diskFree = $this->safeDiskSpace($diskPath, false);

        $dbVersion = 'Tidak tersedia';
        $dbConnected = false;
        try {
            $db = db_connect();
            $dbVersion = (string) $db->getVersion();
            $dbConnected = $db->connID !== false;
            $db->close();
        } catch (\Throwable $e) {
            // Informasi sistem tidak boleh mengganggu aplikasi jika DB gagal dibaca.
        }

        $appVersion = 'Tidak ditentukan';
        $versionFile = ROOTPATH . 'VERSION';
        if ($this->safeIsReadable($versionFile)) {
            $value = trim((string) $this->safeReadFile($versionFile));
            if ($value !== '') $appVersion = $value;
        }

        $data = [
            'title' => 'Informasi Sistem',
            'server' => [
                'hostname' => gethostname() ?: 'Tidak tersedia',
                'os' => $os['PRETTY_NAME'] ?? 'Tidak dapat dibaca oleh PHP (open_basedir)',
                'kernel' => php_uname('r'),
                'architecture' => php_uname('m'),
                'cpu_model' => $this->readCpuModel(),
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
                'database_connected' => $dbConnected,
                'codeigniter' => CodeIgniter::CI_VERSION,
                'openssl' => defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : 'Tidak tersedia',
                'timezone' => date_default_timezone_get(),
                'environment' => ENVIRONMENT,
                'server_time' => date('Y-m-d H:i:s T'),
                'docker' => $this->detectContainer(),
            ],
            'panel' => [
                'installed' => $this->safeIsDirectory('/www/server/panel'),
                'version' => $this->readAaPanelVersion(),
            ],
            'app_version' => $appVersion,
        ];

        return view('admin/system', $data);
    }

    /**
     * Check the configured open_basedir before touching a path.
     * This avoids PHP warnings that CodeIgniter converts into exceptions.
     */
    private function isPathAllowed(string $path): bool
    {
        $base = trim((string) ini_get('open_basedir'));
        if ($base === '') return true;

        $path = rtrim($path, DIRECTORY_SEPARATOR);
        foreach (explode(PATH_SEPARATOR, $base) as $allowed) {
            $allowed = rtrim(trim($allowed), DIRECTORY_SEPARATOR);
            if ($allowed === '') continue;
            if ($path === $allowed || strpos($path, $allowed . DIRECTORY_SEPARATOR) === 0) {
                return true;
            }
        }
        return false;
    }

    private function safeIsReadable(string $path): bool
    {
        return $this->isPathAllowed($path) && @is_readable($path);
    }

    private function safeIsDirectory(string $path): bool
    {
        return $this->isPathAllowed($path) && @is_dir($path);
    }

    private function safeReadFile(string $path): string|false
    {
        if (!$this->safeIsReadable($path)) return false;
        return @file_get_contents($path);
    }

    private function safeDiskSpace(string $path, bool $total): float|false
    {
        if (!$this->isPathAllowed($path) || !$this->safeIsDirectory($path)) return false;
        return $total ? @disk_total_space($path) : @disk_free_space($path);
    }

    private function readOsRelease(): array
    {
        $file = '/etc/os-release';
        $content = $this->safeReadFile($file);
        if ($content === false) return [];

        $values = [];
        foreach (preg_split('/\r?\n/', $content) as $line) {
            if (strpos($line, '=') === false) continue;
            [$key, $value] = explode('=', $line, 2);
            $values[$key] = trim($value, " \t\n\r\0\x0B\"'");
        }
        return $values;
    }

    private function readMemory(): array
    {
        $content = $this->safeReadFile('/proc/meminfo');
        if ($content === false) {
            return ['total' => 'Tidak tersedia (open_basedir)', 'used' => 'Tidak tersedia', 'used_percent' => null];
        }

        $values = [];
        foreach (preg_split('/\r?\n/', $content) as $line) {
            if (preg_match('/^(MemTotal|MemAvailable):\s+(\d+)/', $line, $match)) {
                $values[$match[1]] = (int) $match[2] * 1024;
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

    private function readCpuModel(): string
    {
        $content = $this->safeReadFile('/proc/cpuinfo');
        if ($content === false) return 'Tidak tersedia (open_basedir)';
        if (preg_match('/^(?:model name|Hardware|Processor)\s*:\s*(.+)$/mi', $content, $match)) {
            return trim($match[1]);
        }
        return 'Tidak tersedia';
    }

    private function countCpuCores(): string
    {
        $content = $this->safeReadFile('/proc/cpuinfo');
        if ($content === false) return 'Tidak tersedia (open_basedir)';
        $count = preg_match_all('/^processor\s*:/m', $content);
        return $count > 0 ? (string) $count . ' core' : 'Tidak tersedia';
    }

    private function readUptime(): string
    {
        $content = $this->safeReadFile('/proc/uptime');
        if ($content === false) return 'Tidak tersedia (open_basedir)';
        $seconds = (int) floor((float) strtok($content, ' '));
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

    private function detectContainer(): string
    {
        if ($this->safeIsReadable('/.dockerenv') || $this->safeIsDirectory('/run/.containerenv')) {
            return 'Berjalan di container';
        }
        if (!$this->isPathAllowed('/.dockerenv') && !$this->isPathAllowed('/run/.containerenv')) {
            return 'Tidak dapat diperiksa (open_basedir)';
        }
        return 'Tidak terdeteksi';
    }

    private function readAaPanelVersion(): string
    {
        $panelDir = '/www/server/panel';
        if (!$this->safeIsDirectory($panelDir)) {
            return $this->isPathAllowed($panelDir) ? 'Tidak terpasang / tidak terdeteksi' : 'Tidak dapat diperiksa (open_basedir)';
        }

        foreach ([
            '/www/server/panel/data/version.pl',
            '/www/server/panel/data/version.txt',
            '/www/server/panel/class/panelVersion.py',
        ] as $file) {
            $version = $this->safeReadFile($file);
            if ($version === false || trim($version) === '') continue;
            if (preg_match('/(?:version|VERSION)\s*[=:\(]\s*["\']?([0-9]+(?:\.[0-9]+){1,3})/i', $version, $match)) {
                return $match[1];
            }
            if (preg_match('/^[0-9]+(?:\.[0-9]+){1,3}$/', trim($version))) return trim($version);
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
