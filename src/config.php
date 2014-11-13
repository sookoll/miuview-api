<?php
/*
 * Miuview gallery API
 * constants and configuration
 * 
 * Creator: Mihkel Oviir
 * 08.2011
 * 
 */

//urls and paths
define('URL','http://'.$_SERVER['SERVER_NAME'].'/miuview-api/');// url
define('PATH',$_SERVER['DOCUMENT_ROOT'].'/miuview-api/');// path
define('PATH_INC',PATH.'includes/');// server-side scripts
define('PATH_TMPL',PATH.'templates/');// templates
define('PATH_ALBUMS',$_SERVER['DOCUMENT_ROOT'].'/galerii-pildid/');// original images
define('PATH_CACHE',$_SERVER['DOCUMENT_ROOT'].'/tmp/galerii-cache/');// thumbnails

// app
define('STATUS',1);// status, change something else when managing page
define('TEMPLATE','admin');// application template (must correspond with folder name in template directory)
define('USER','user');// user
define('PSWD','passwd');// user passwd
define('FORMATS',serialize(array('picture'=>array('jpeg','jpg','gif','png')))); // allowed formats
define('ITEM_SIZE',600);// item size
define('TH_SIZE',100);// thumb size
define('SECURITY_KEY','security_key');// security key for request private albums

// db parameters
define('DB_HOST','server');// database server
define('DB_NAME','dbase');// database
define('DB_USER','user');// database user
define('DB_PWD','passwd');// database user password

// tables
define('TBL_ALBUMS','miuview_albums');// users
define('TBL_ITEMS','miuview_items');// items

// libs for put into html head section
define('HTML_TMPL','templates/'.TEMPLATE.'/');
define('HTML_LIBS','../libs/');
define('HTML_ALBUMS','data/albums/');

?>