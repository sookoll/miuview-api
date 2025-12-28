<?php
/*
 * Miuview gallery API
 * constants and configuration
 *
 * Creator: Mihkel Oviir
 * 08.2011
 *
 */

$path = __DIR__;

$app = [
    // status, change something else when managing page
    'STATUS' => 1,
    'URL' => '//' . $_SERVER['SERVER_NAME'] . '/miuview-api/src',
    'PATH' => $path,
    'PATH_INC' => $path . '/includes',
    'PATH_TMPL' => $path . '/templates',
    // original images
    'PATH_ALBUMS' => $_SERVER['DOCUMENT_ROOT'] . '/pictures',
    // thumbnails
    'PATH_CACHE' => $_SERVER['DOCUMENT_ROOT'] . '/cache',
    // application template (must correspond with folder name in template directory)
    'TEMPLATE' => 'admin',
    'USER' => 'user',
    'PSWD' => 'passwd',
    'FORMATS' => [
        'picture' => ['jpeg', 'jpg', 'gif', 'png']
    ],
    'ITEM_SIZE' => 600,
    'TH_SIZE' => 100,
    // security key for request private albums
    'SECURITY_KEY' => 'security_key'
];

$db = [
    'DB_HOST' => 'localhost',
    'DB_NAME' => '',
    'DB_USER' => '',
    'DB_PWD' => '',
    'TBL_ALBUMS' => 'miuview_albums',
    'TBL_ITEMS' => 'miuview_items'
];

// libs for put into html head section
$html = [
    'HTML_TMPL' => 'templates/' . $app['TEMPLATE'],
    'HTML_LIBS' => '../libs',
    'HTML_ALBUMS' => 'data/albums'
];

return array_merge($app, $db, $html);
