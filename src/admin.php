<?php
/*
 * Miuview API
 * admin.php
 *
 * Creator: Mihkel Oviir
 * 08.2011
 *
 */

require __DIR__ . '/vendor/autoload.php';

use App\Session;
use App\Functions;
use App\Main;

// include configuration
$conf = require __DIR__ . '/config.php';

// set session
$sess = Session::getInstance();

// start connection
$func = new Functions();
$connOk = $func->connection($conf);

// if we manage with page
if (!$connOk || $conf['STATUS'] !== 1) {
    $html = $conf['PATH_TMPL'] . $conf['TEMPLATE'] . '/html/outoforder.html';
} else {
    $app = new StdClass;
    $app->sess = $sess;
    $app->conf = $conf;
    $app->func = $func;

    $main = new Main($app);
    $data = $main->getResult();
    $html = $conf['PATH_TMPL'] . $conf['TEMPLATE'] . '/html/main.html';
    $html = $func->replace_tags($html, $data);
}

// replace all tags
$defines = $func->definesArray();
$html = $func->replace_tags($html, $defines);

// close connection
$func->connection_close();

echo $html;
//print_r($_SESSION);

