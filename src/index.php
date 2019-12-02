<?php
/*
 * Miuview API
 *
 *
 * Creator: Mihkel Oviir
 * 08.2011
 *
 */

// include configuration
include 'config.php';

// include functions class
include PATH_INC.'functions.php';

// if we manage with page
if(STATUS != 1) {
	die('Not allowed!');
}
else {

	// start connection
	$func->connection();

	if(isset($_GET['request'])&& !empty($_GET['request'])){
		$class=$_GET['request'];
		foreach($_GET as $k=>$v){
			$$k=$v;
		}
	} else {
		echo 'Query string empty';
		exit;
	}

	if(!empty($class) && @file_exists(PATH_INC.$class.'.php')){
		include PATH_INC.$class.'.php';
		$m = new $class();
		//$m->getResult();
	} else {
		echo 'Query string empty';
		exit;
	}

	// close connection
	$func->connection_close();
}
?>
