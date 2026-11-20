<?php
namespace Config;
class Paths
{
    public string $systemDirectory;
    public string $appDirectory = __DIR__ . '/../';
    public string $writableDirectory = __DIR__ . '/../../writable/';
    public string $testsDirectory = __DIR__ . '/../../tests/';
    public string $viewDirectory = __DIR__ . '/../Views/';
    public function __construct()
    {
        $this->systemDirectory = dirname(__DIR__, 2) . '/vendor/codeigniter4/framework/system';
    }
}
