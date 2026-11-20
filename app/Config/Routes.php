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
$routes->get('forgot-password', 'PasswordReset::requestForm');
$routes->post('forgot-password', 'PasswordReset::sendLink');
$routes->get('reset-password/(:segment)', 'PasswordReset::resetForm/$1');
$routes->post('reset-password/(:segment)', 'PasswordReset::updatePassword/$1');
$routes->get('logout', 'Auth::logout', ['filter' => 'auth']);

$routes->get('/', 'Dashboard::index', ['filter' => 'auth']);
$routes->get('dashboard', 'Dashboard::index', ['filter' => 'auth']);
$routes->get('files', 'Files::index', ['filter' => 'auth']);
$routes->get('search', 'Files::search', ['filter' => 'auth']);
$routes->get('files/preview/(:num)', 'Files::preview/$1', ['filter' => 'auth']);
$routes->get('files/preview-content/(:num)', 'Files::previewContent/$1', ['filter' => 'auth']);
$routes->get('trash', 'Trash::index', ['filter' => 'auth']);
$routes->post('trash/file/restore/(:num)', 'Trash::restoreFile/$1', ['filter' => 'auth']);
$routes->post('trash/folder/restore/(:num)', 'Trash::restoreFolder/$1', ['filter' => 'auth']);
$routes->post('trash/file/purge/(:num)', 'Trash::deleteFilePermanently/$1', ['filter' => 'auth']);
$routes->post('trash/folder/purge/(:num)', 'Trash::deleteFolderPermanently/$1', ['filter' => 'auth']);
$routes->post('trash/bulk-purge', 'Trash::bulkDeletePermanently', ['filter' => 'auth']);
$routes->post('trash/bulk-restore', 'Trash::bulkRestore', ['filter' => 'auth']);
$routes->get('activity', 'Activity::index', ['filter' => 'auth']);
$routes->get('shares', 'Share::index', ['filter' => 'auth']);
$routes->post('shares/update/(:num)', 'Share::update/$1', ['filter' => 'auth']);
$routes->post('shares/revoke/(:num)', 'Share::revokeOne/$1', ['filter' => 'auth']);
$routes->post('files/share/(:num)', 'Share::create/$1', ['filter' => 'auth']);
$routes->post('files/share/revoke/(:num)', 'Share::revoke/$1', ['filter' => 'auth']);
$routes->get('s/(:segment)', 'Share::open/$1');
$routes->get('s/(:segment)/content', 'Share::content/$1');
$routes->get('s/(:segment)/download', 'Share::download/$1');
$routes->post('files/upload', 'Files::upload', ['filter' => 'auth']);
$routes->post('files/folders/create', 'Files::createFolder', ['filter' => 'auth']);
$routes->post('files/folders/rename/(:num)', 'Files::renameFolder/$1', ['filter' => 'auth']);
$routes->post('files/folders/delete/(:num)', 'Files::deleteFolder/$1', ['filter' => 'auth']);
$routes->post('files/rename/(:num)', 'Files::rename/$1', ['filter' => 'auth']);
$routes->post('files/move/(:num)', 'Files::move/$1', ['filter' => 'auth']);
$routes->post('files/star/(:num)', 'Files::toggleStar/$1', ['filter' => 'auth']);
$routes->get('files/download/(:num)', 'Files::download/$1', ['filter' => 'auth']);
$routes->post('files/delete/(:num)', 'Files::delete/$1', ['filter' => 'auth']);
$routes->post('files/bulk-trash', 'Files::bulkTrash', ['filter' => 'auth']);
$routes->post('files/bulk-move', 'Files::bulkMove', ['filter' => 'auth']);
$routes->post('files/bulk-share', 'Files::bulkShare', ['filter' => 'auth']);
$routes->post('files/bulk-download', 'Files::bulkDownload', ['filter' => 'auth']);

$routes->get('admin/users', 'Admin\\Users::index', ['filter' => 'admin']);
$routes->post('admin/users/create', 'Admin\\Users::create', ['filter' => 'admin']);
$routes->post('admin/users/update/(:num)', 'Admin\\Users::update/$1', ['filter' => 'admin']);
$routes->post('admin/users/reset-password/(:num)', 'Admin\\Users::resetPassword/$1', ['filter' => 'admin']);

$routes->get('account/password', 'Account::password', ['filter' => 'auth']);
$routes->post('account/password', 'Account::updatePassword', ['filter' => 'auth']);
