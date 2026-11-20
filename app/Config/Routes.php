<?php
namespace Config;

$routes = Services::routes();
$routes->setDefaultNamespace('App\\Controllers');
$routes->setDefaultController('Auth');
$routes->setDefaultMethod('login');
$routes->setTranslateURIDashes(false);
$routes->set404Override();
$routes->setAutoRoute(false);

$routes->get('/setup', 'Setup::index');
$routes->post('/setup', 'Setup::create');
$routes->get('login', 'Auth::login');
$routes->post('login', 'Auth::attempt');
$routes->get('logout', 'Auth::logout', ['filter' => 'auth']);

$routes->get('/', 'Dashboard::index', ['filter' => 'auth']);
$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);
$routes->get('files', 'Files::index', ['filter' => 'auth']);
$routes->post('files/upload', 'Files::upload', ['filter' => 'auth']);
$routes->post('files/folders/create', 'Files::createFolder', ['filter' => 'auth']);
$routes->post('files/folders/rename/(:num)', 'Files::renameFolder/$1', ['filter' => 'auth']);
$routes->post('files/folders/delete/(:num)', 'Files::deleteFolder/$1', ['filter' => 'auth']);
$routes->post('files/rename/(:num)', 'Files::rename/$1', ['filter' => 'auth']);
$routes->post('files/move/(:num)', 'Files::move/$1', ['filter' => 'auth']);
$routes->post('files/star/(:num)', 'Files::toggleStar/$1', ['filter' => 'auth']);
$routes->get('files/download/(:num)', 'Files::download/$1', ['filter' => 'auth']);
$routes->post('files/delete/(:num)', 'Files::delete/$1', ['filter' => 'auth']);

$routes->get('admin/users', 'Admin\\Users::index', ['filter' => 'admin']);
$routes->post('admin/users/create', 'Admin\\Users::create', ['filter' => 'admin']);
$routes->post('admin/users/update/(:num)', 'Admin\\Users::update/$1', ['filter' => 'admin']);
$routes->post('admin/users/reset-password/(:num)', 'Admin\\Users::resetPassword/$1', ['filter' => 'admin']);

$routes->get('account/password', 'Account::password', ['filter' => 'auth']);
$routes->post('account/password', 'Account::updatePassword', ['filter' => 'auth']);
