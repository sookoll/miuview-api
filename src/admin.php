<?php
/*
 * Miuview API
 * admin.php
 *
 * Creator: Mihkel Oviir
 * 08.2011
 *
 */

error_reporting(E_ALL | E_STRICT);

// include configuration
include 'config.php';

// set session
include PATH_INC.'session.php';
$sess = Session::getInstance();

// include functions class
include PATH_INC.'functions.php';

// start connection
$func->connection();

// if we manage with page
if(STATUS != 1) {
	$html=PATH_TMPL.TEMPLATE.'/html/outoforder.html';
}
else {

	$class='main';

	// call a main object
	if($class && @file_exists(PATH_INC.$class.'.php')){
		include PATH_INC.$class.'.php';
		$main = new $class();
		$data = $main->getResult();
		$html=PATH_TMPL.TEMPLATE.'/html/main.html';
		$html = $func->replace_tags($html,$data);
	}
}

// replace all tags
$defines = $func->definesArray();
$html = $func->replace_tags($html,$defines);

// close connection
$func->connection_close();

echo $html;
//print_r($_SESSION);
?>
