<?php
use CodeIgniter\Boot;
use Config\Paths;
define('FCPATH', __DIR__ . DIRECTORY_SEPARATOR);
chdir(FCPATH);
require FCPATH . '../vendor/autoload.php';
$paths = new Paths();
require $paths->systemDirectory . '/Boot.php';
exit(Boot::bootWeb($paths));
